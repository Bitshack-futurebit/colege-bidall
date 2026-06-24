<?php

namespace App\Jobs;

use App\Models\Auction;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Posts an auction to the Facebook Page in the background so seller-facing
 * actions (publishing / going live) don't block on the Graph API.
 */
class PostAuctionToFacebook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Auction $auction,
        public bool $force = false,
    ) {}

    public function handle(FacebookService $facebook): void
    {
        $facebook->postAuction($this->auction, $this->force);
    }
}
