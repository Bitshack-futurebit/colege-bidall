<?php

namespace App\Console\Commands;

use App\Models\Auctioneer;
use App\Models\Lot;
use Illuminate\Console\Command;

class ResetFreeRelists extends Command
{
    protected $signature = 'relists:reset';
    protected $description = 'Clear free relist eligibility for auctioneers with a reset schedule';

    public function handle(): int
    {
        $auctioneers = Auctioneer::whereNotNull('free_relist_reset')->get();

        $totalCleared = 0;

        foreach ($auctioneers as $auctioneer) {
            if (!$this->intervalElapsed($auctioneer)) {
                continue;
            }

            $cleared = Lot::where('free_relist_eligible', true)
                ->where('status', 'unsold')
                ->whereHas('auction', fn($q) => $q->where('auctioneer_id', $auctioneer->id))
                ->update(['free_relist_eligible' => false]);

            $auctioneer->update(['free_relist_last_reset_at' => now()]);

            if ($cleared > 0) {
                $totalCleared += $cleared;
                $this->info("Cleared {$cleared} free relists for {$auctioneer->business_name}");
            }
        }

        $this->info("Done. Total cleared: {$totalCleared}");

        return self::SUCCESS;
    }

    private function intervalElapsed(Auctioneer $auctioneer): bool
    {
        $last = $auctioneer->free_relist_last_reset_at;

        // Never run before — run now
        if (!$last) {
            return true;
        }

        return match ($auctioneer->free_relist_reset) {
            'weekly' => $last->diffInDays(now()) >= 7,
            'biweekly' => $last->diffInDays(now()) >= 14,
            'monthly' => $last->diffInDays(now()) >= 30,
            default => false,
        };
    }
}
