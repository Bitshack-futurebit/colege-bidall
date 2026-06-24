<?php

namespace App\Console\Commands;

use App\Models\LotImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldImages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'images:cleanup';

    /**
     * The console command description.
     */
    protected $description = 'Delete lot images older than 30 days after lot close';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletionDate = now()->subDays(config('platform.images.auto_delete_days', 30));
        $deletedCount = 0;

        // Find images from lots that closed more than 30 days ago
        $imagesToDelete = LotImage::whereHas('lot', function ($query) use ($deletionDate) {
            $query->whereIn('status', ['sold', 'unsold'])
                ->whereHas('auction', function ($auctionQuery) use ($deletionDate) {
                    $auctionQuery->where('status', 'ended')
                        ->where('end_time', '<=', $deletionDate);
                });
        })->get();

        foreach ($imagesToDelete as $image) {
            try {
                // Delete database record (model's delete() method handles file deletion)
                $image->delete();
                $deletedCount++;

                $this->info("Deleted image #{$image->id} from lot #{$image->lot_id}");
            } catch (\Exception $e) {
                $this->error("Failed to delete image #{$image->id}: {$e->getMessage()}");
            }
        }

        if ($deletedCount > 0) {
            $this->info("\n✓ Deleted {$deletedCount} old images");
        } else {
            $this->comment('No old images to delete');
        }

        return Command::SUCCESS;
    }
}
