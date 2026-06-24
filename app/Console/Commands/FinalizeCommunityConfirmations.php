<?php

namespace App\Console\Commands;

use App\Models\Lot;
use Illuminate\Console\Command;

class FinalizeCommunityConfirmations extends Command
{
    protected $signature = 'community:finalize-confirmations';
    protected $description = 'Auto-confirm community lots whose 24h confirmation window has expired';

    public function handle(): int
    {
        $now = now();
        $lots = Lot::where('status', 'pending_confirmation')
            ->whereNotNull('seller_user_id')
            ->whereNotNull('confirmation_expires_at')
            ->where('confirmation_expires_at', '<=', $now)
            ->get();

        foreach ($lots as $lot) {
            try {
                $lot->communityConfirm(auto: true, reason: 'Auto-confirmed (24h window expired)');
                $this->info("+ Lot #{$lot->id}: auto-confirmed at {$lot->current_bid}.");
            } catch (\Throwable $e) {
                $this->error("- Lot #{$lot->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
