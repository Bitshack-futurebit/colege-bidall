<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Lot;
use App\Services\FacebookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateAuctionStatuses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'auctions:update-statuses';

    /**
     * The console command description.
     */
    protected $description = 'Update auction and lot statuses based on current time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $updatedAuctions = 0;
        $updatedLots = 0;

        // Transition upcoming auctions to live
        $auctionsToGoLive = Auction::where('status', 'upcoming')
            ->where('start_time', '<=', $now)
            ->get();

        foreach ($auctionsToGoLive as $auction) {
            DB::transaction(function () use ($auction, &$updatedLots) {
                // Update auction status
                $auction->update(['status' => 'live']);

                if ($auction->isDutch()) {
                    // Dutch: only activate the first lot (sequential)
                    $firstLot = $auction->lots()->whereNull('withdrawn_at')->orderBy('lot_number')->first();
                    if ($firstLot) {
                        $firstLot->update(['status' => 'live']);
                        $updatedLots++;
                    }
                    $this->info("Dutch auction #{$auction->id} '{$auction->title}' is now LIVE (first lot activated)");
                } elseif ($auction->isLiveFormat()) {
                    // Live: activate first lot in presenting phase
                    $auction->scheduleLiveLots();
                    $updatedLots++;
                    $this->info("Live auction #{$auction->id} '{$auction->title}' is now LIVE (first lot presenting)");
                } else {
                    // English & Sealed: all lots go live
                    $count = $auction->lots()->whereNull('withdrawn_at')->update(['status' => 'live']);
                    $updatedLots += $count;
                    $type = $auction->isSealed() ? 'Sealed' : 'English';
                    $this->info("{$type} auction #{$auction->id} '{$auction->title}' is now LIVE with {$count} lots");
                }
            });
            $updatedAuctions++;

            // Post to Facebook after auction goes live
            app(FacebookService::class)->postAuction($auction);
        }

        // Activate sequential Dutch lots whose countdown should begin
        // (lot goes live DUTCH_COUNTDOWN_BUFFER seconds before dutch_start_time)
        $dutchLotsToActivate = Lot::where('status', 'draft')
            ->whereNotNull('dutch_start_time')
            ->where('dutch_start_time', '<=', $now->copy()->addSeconds(Lot::DUTCH_COUNTDOWN_BUFFER))
            ->whereHas('auction', fn ($q) => $q->where('status', 'live')->where('auction_type', 'dutch'))
            ->get();

        foreach ($dutchLotsToActivate as $lot) {
            $lot->update(['status' => 'live']);
            $updatedLots++;
            $this->info("Dutch sequential lot #{$lot->lot_number} '{$lot->title}' activated (auction #{$lot->event_id})");
        }

        // Close Dutch lots whose end time has passed
        $dutchLotsToClose = Lot::where('status', 'live')
            ->whereNotNull('dutch_end_time')
            ->where('dutch_end_time', '<=', $now)
            ->whereHas('auction', fn ($q) => $q->where('auction_type', 'dutch'))
            ->get();

        foreach ($dutchLotsToClose as $lot) {
            DB::transaction(function () use ($lot) {
                $lot->closeDutch();
            });
            $updatedLots++;
            $this->info("Dutch lot #{$lot->lot_number} '{$lot->title}' closed (auction #{$lot->event_id})");
        }

        // Close individual English lots whose end_time has passed (staggered closing)
        // Dutch lots use dutch_end_time instead (handled above)
        $lotsToClose = Lot::where('status', 'live')
            ->where('end_time', '<=', $now)
            ->whereHas('auction', fn ($q) => $q->where('auction_type', 'english'))
            ->get();

        foreach ($lotsToClose as $lot) {
            DB::transaction(function () use ($lot) {
                $lot->close();
            });
            $updatedLots++;
            $this->info("Lot #{$lot->lot_number} '{$lot->title}' closed (auction #{$lot->event_id})");
        }

        // Live format: advance any lot whose phase timer has expired (backstop when no pollers).
        // Includes CLOSED-phase lots (status sold/unsold) so the 5s result window can tick over
        // to startNextLiveLot().
        $liveLotsToTick = Lot::whereNotNull('live_phase_ends_at')
            ->where('live_phase_ends_at', '<=', $now)
            ->where(function ($q) {
                $q->where('status', 'live')
                  ->orWhere('live_phase', 'closed');
            })
            ->whereHas('auction', fn ($q) => $q->where('auction_type', 'live'))
            ->get();

        foreach ($liveLotsToTick as $lot) {
            DB::transaction(function () use ($lot) {
                $lot->advanceLivePhase();
            });
            $updatedLots++;
            $this->info("Live lot #{$lot->lot_number} phase advanced (auction #{$lot->event_id})");
        }

        // Close sealed lots whose end_time has passed (all lots close simultaneously)
        $sealedLotsToClose = Lot::where('status', 'live')
            ->where('end_time', '<=', $now)
            ->whereHas('auction', fn ($q) => $q->where('auction_type', 'sealed'))
            ->get();

        foreach ($sealedLotsToClose as $lot) {
            DB::transaction(function () use ($lot) {
                $lot->closeSealed();
            });
            $updatedLots++;
            $this->info("Sealed lot #{$lot->lot_number} '{$lot->title}' closed (auction #{$lot->event_id})");
        }

        // End auctions where ALL lots have closed (no more live lots)
        $liveAuctions = Auction::where('status', 'live')->get();

        foreach ($liveAuctions as $auction) {
            $activeLots = $auction->lots()->whereNotIn('status', ['sold', 'unsold', 'pending_confirmation'])->count();

            // Live format: don't end while any lot is still queued (pending/draft/live)
            if ($activeLots > 0 && $auction->isLiveFormat()) {
                continue;
            }

            // If there are still lots not in a terminal state, don't end yet
            if ($activeLots > 0 && $auction->isDutchSequential()) {
                // Sequential Dutch: activate any draft lots whose countdown should begin
                // (handles edge case where closing + activation happen in same scheduler run)
                $auction->lots()
                    ->where('status', 'draft')
                    ->whereNotNull('dutch_start_time')
                    ->where('dutch_start_time', '<=', $now->copy()->addSeconds(Lot::DUTCH_COUNTDOWN_BUFFER))
                    ->each(function ($lot) use (&$updatedLots) {
                        $lot->update(['status' => 'live']);
                        $updatedLots++;
                        $this->info("Dutch sequential lot #{$lot->lot_number} '{$lot->title}' activated (auction #{$lot->event_id})");
                    });

                continue; // Don't end — lots still pending
            }

            $hasLiveLots = $auction->lots()->where('status', 'live')->exists();

            if (!$hasLiveLots) {
                // Force-close any lots still in non-terminal status (e.g. pending, active, draft)
                $orphaned = $auction->lots()->whereNotIn('status', ['sold', 'unsold', 'pending_confirmation'])->get();
                foreach ($orphaned as $lot) {
                    DB::transaction(function () use ($lot, $auction) {
                        if ($auction->isDutch()) {
                            $lot->closeDutch();
                        } elseif ($auction->isSealed()) {
                            $lot->closeSealed();
                        } elseif ($auction->isLiveFormat()) {
                            $lot->closeLive(false);
                        } else {
                            $lot->close();
                        }
                    });
                    $updatedLots++;
                    $this->info("Force-closed orphaned lot #{$lot->lot_number} (was '{$lot->status}')");
                }

                $auction->update(['status' => 'ended']);
                $this->info("Auction #{$auction->id} '{$auction->title}' has ENDED (all lots closed)");
                $updatedAuctions++;
            }
        }

        // Summary
        if ($updatedAuctions > 0 || $updatedLots > 0) {
            $this->info("\n✓ Updated {$updatedAuctions} auctions and {$updatedLots} lots");
        } else {
            $this->comment('No auctions needed status updates');
        }

        return Command::SUCCESS;
    }
}
