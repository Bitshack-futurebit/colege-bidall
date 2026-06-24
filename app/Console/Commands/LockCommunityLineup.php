<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;

class LockCommunityLineup extends Command
{
    protected $signature = 'community:lock-lineup';
    protected $description = 'Lock community auction lineups; cancel & roll if below viability thresholds';

    public function handle(): int
    {
        $now = now();
        $auctions = Auction::where('is_community', true)
            ->where('status', 'draft')
            ->whereNotNull('lineup_locks_at')
            ->where('lineup_locks_at', '<=', $now)
            ->with(['communityRegion', 'lots'])
            ->get();

        if ($auctions->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($auctions as $auction) {
            $region = $auction->communityRegion;
            if (!$region) {
                continue;
            }

            $lotCount = $auction->lots->count();
            $bidderCount = $region->bidderCount();
            $minLots = $region->min_lots_for_viability ?? config('community.min_lots_for_viability', 5);
            $minBidders = $region->min_bidders_for_viability ?? config('community.min_bidders_for_viability', 20);

            if ($lotCount < $minLots || $bidderCount < $minBidders) {
                $this->warn("- {$region->name}: viability gate failed ({$lotCount} lots / {$bidderCount} bidders). Rolling to next week.");
                $this->rollAuction($auction);
                continue;
            }

            $auction->update(['status' => 'upcoming']);
            $this->info("+ {$region->name}: lineup locked with {$lotCount} lots.");
        }

        return self::SUCCESS;
    }

    /**
     * Reassign auction's lots to next week's auction and soft-delete the
     * current empty auction. Next week's auction is created by the weekly
     * command if not already present.
     */
    private function rollAuction(Auction $auction): void
    {
        $region = $auction->communityRegion;
        $schedule = $auction->communitySchedule;
        $nextGoesLiveAt = $schedule
            ? $schedule->nextGoesLiveAt($auction->goes_live_at)
            : $region->nextGoesLiveAt($auction->goes_live_at);
        $safetyCapHours = (int) config('community.auction_safety_cap_hours', 12);

        $next = Auction::firstOrCreate(
            [
                'community_region_id' => $region->id,
                'community_schedule_id' => $schedule?->id,
                'goes_live_at' => $nextGoesLiveAt,
            ],
            [
                'auctioneer_id' => $auction->auctioneer_id,
                'title' => $region->name . ($schedule ? " — {$schedule->name} " : ' Community Auction — ') . $nextGoesLiveAt->format('d M Y'),
                'slug' => $auction->slug . '-rolled',
                'status' => 'draft',
                'auction_type' => 'live',
                'start_time' => $nextGoesLiveAt,
                'end_time' => $nextGoesLiveAt->copy()->addHours($safetyCapHours),
                'is_community' => true,
                'pilot_mode' => $auction->pilot_mode,
                'lineup_locks_at' => $nextGoesLiveAt->copy()->subHours(
                    (int) config('community.lineup_lock_hours_before_live', 18)
                ),
                'requires_registration' => false,
                'allow_proxy_bidding' => false,
            ]
        );

        foreach ($auction->lots as $lot) {
            $lot->update([
                'event_id' => $next->id,
                'rolled_from_lot_id' => $lot->id,
            ]);
        }

        $auction->delete();
    }
}
