<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;

class ArchiveOldAuctions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'auctions:archive-old';

    /**
     * The console command description.
     */
    protected $description = 'Archive (soft delete) auctions older than configured days after ending (default: 7 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $archivalDate = now()->subDays(config('platform.auctions.auto_archive_days', 7));
        $archivedCount = 0;

        // Find ended auctions older than configured days (default: 7)
        $auctionsToArchive = Auction::where('status', 'ended')
            ->where('end_time', '<=', $archivalDate)
            ->whereNull('deleted_at') // Only non-archived auctions
            ->get();

        foreach ($auctionsToArchive as $auction) {
            try {
                // Soft delete (archive) the auction
                // This preserves all data but removes from public display
                $auction->delete();
                $archivedCount++;

                $this->info("Archived auction #{$auction->id}: {$auction->title}");
            } catch (\Exception $e) {
                $this->error("Failed to archive auction #{$auction->id}: {$e->getMessage()}");
            }
        }

        if ($archivedCount > 0) {
            $this->info("\n✓ Archived {$archivedCount} old auctions");
            $this->comment('Note: Auctions are soft-deleted and can be restored if needed');
        } else {
            $this->comment('No old auctions to archive');
        }

        return Command::SUCCESS;
    }
}
