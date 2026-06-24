<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;

class GoLiveAuction extends Command
{
    protected $signature = 'auction:go-live {slug}';
    protected $description = 'Force an auction to go live immediately (dev helper)';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $auction = Auction::where('slug', $slug)->first();

        if (!$auction) {
            $this->error("Auction not found: {$slug}");
            return self::FAILURE;
        }

        if ($auction->status === 'live') {
            $this->warn("Auction '{$auction->title}' is already live.");
            return self::SUCCESS;
        }

        $auction->goLive();
        $this->info("Auction '{$auction->title}' ({$auction->auction_type}) is now LIVE.");
        return self::SUCCESS;
    }
}
