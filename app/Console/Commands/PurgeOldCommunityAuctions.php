<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\LotImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeOldCommunityAuctions extends Command
{
    protected $signature = 'community:purge-old {--days=30 : Days after end_time to purge} {--dry-run}';

    protected $description = 'Hard-delete community auctions (and their lots/images/bids) N days after ending. Community data is disposable.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $auctions = Auction::withTrashed()
            ->where('is_community', true)
            ->where('status', 'ended')
            ->where('end_time', '<=', $cutoff)
            ->with('lots.images')
            ->get();

        if ($auctions->isEmpty()) {
            $this->comment("No community auctions older than {$days} days to purge.");
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$auctions->count()} community auction(s) older than {$days} days.");

        $totalImages = 0;
        $totalLots = 0;

        foreach ($auctions as $auction) {
            $lotCount = $auction->lots->count();
            $imageCount = $auction->lots->sum(fn($lot) => $lot->images->count());
            $totalLots += $lotCount;
            $totalImages += $imageCount;

            $this->line("- #{$auction->id} \"{$auction->title}\" (ended {$auction->end_time?->format('Y-m-d')}): {$lotCount} lot(s), {$imageCount} image(s)");

            if ($dryRun) {
                continue;
            }

            try {
                DB::transaction(function () use ($auction) {
                    foreach ($auction->lots as $lot) {
                        foreach ($lot->images as $image) {
                            $image->delete();
                        }
                    }
                    $auction->forceDelete();
                });
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $verb = $dryRun ? 'would purge' : 'Purged';
        $this->info("\n{$verb} {$auctions->count()} auction(s), {$totalLots} lot(s), {$totalImages} image(s).");

        return self::SUCCESS;
    }
}
