<?php

namespace App\Console\Commands;

use App\Models\CreditTransaction;
use App\Models\Lot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixReserveNotMetLots extends Command
{
    protected $signature = 'lots:fix-reserve-not-met {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix lots incorrectly marked as sold where reserve was not met';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made');
            $this->newLine();
        }

        // Find lots marked as "sold" that have a reserve price higher than the current bid
        $lots = Lot::withTrashed()
            ->where('status', 'sold')
            ->whereNotNull('reserve_price')
            ->where('reserve_price', '>', 0)
            ->whereColumn('current_bid', '<', 'reserve_price')
            ->with(['auction.auctioneer', 'bids'])
            ->get();

        if ($lots->isEmpty()) {
            $this->info('No incorrectly sold lots found. All good.');
            return Command::SUCCESS;
        }

        $this->info("Found {$lots->count()} lot(s) marked as sold with reserve not met:");
        $this->newLine();

        $fixed = 0;
        $commissionsRefunded = 0;
        $totalRefunded = 0;

        foreach ($lots as $lot) {
            $auction = $lot->auction;
            $auctioneer = $auction?->auctioneer;
            $hadBids = $lot->bids()->exists();

            $this->line("  Lot #{$lot->lot_number} \"{$lot->title}\" (ID: {$lot->id})");
            $this->line("    Auction: #{$auction?->id} {$auction?->title}");
            $this->line("    Current bid: " . formatCurrency($lot->current_bid) . " | Reserve: " . formatCurrency($lot->reserve_price));
            $this->line("    Winning bidder ID: {$lot->winning_bidder_id} | Total bids: {$lot->total_bids}");

            // Check if a lot_close commission was charged for this lot
            $commissionTx = CreditTransaction::where('lot_id', $lot->id)
                ->where('type', 'lot_close')
                ->first();

            if ($commissionTx) {
                $refundAmount = abs($commissionTx->amount);
                $this->line("    Commission charged: " . formatCurrency($refundAmount) . " — will be refunded");
                $commissionsRefunded++;
                $totalRefunded += $refundAmount;
            }

            if (!$dryRun) {
                DB::transaction(function () use ($lot, $hadBids, $commissionTx, $auctioneer) {
                    // Fix the lot status
                    $lot->update([
                        'status' => 'unsold',
                        'winning_bidder_id' => null,
                        'free_relist_eligible' => !$hadBids,
                    ]);

                    // Refund the incorrectly charged commission
                    if ($commissionTx && $auctioneer) {
                        $refundAmount = abs($commissionTx->amount);

                        $auctioneer->addCredits(
                            $refundAmount,
                            'adjustment',
                            "Refund: commission incorrectly charged on Lot #{$lot->lot_number} (reserve not met)"
                        );
                    }
                });
            }

            $fixed++;
            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn("DRY RUN complete. Would fix {$fixed} lot(s), refund {$commissionsRefunded} commission(s) totalling " . formatCurrency($totalRefunded));
        } else {
            $this->info("Fixed {$fixed} lot(s). Refunded {$commissionsRefunded} commission(s) totalling " . formatCurrency($totalRefunded));
        }

        return Command::SUCCESS;
    }
}
