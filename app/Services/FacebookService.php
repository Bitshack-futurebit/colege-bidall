<?php

namespace App\Services;

use App\Models\Auction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacebookService
{
    /**
     * Post an auction to the Facebook Page.
     *
     * @param  bool  $force  If true, bypasses duplicate check (admin manual post)
     * @return string|null  The Facebook post ID, or null on failure
     */
    public function postAuction(Auction $auction, bool $force = false): ?string
    {
        if (!config('facebook.enabled') || !config('facebook.page_token')) {
            Log::info('Facebook posting skipped: disabled or no token configured.');
            return null;
        }

        // Skip if already posted for this status (unless forced by admin)
        // Allow re-posting when status changes (e.g. upcoming → live)
        if (!$force && $auction->facebook_post_id && $auction->facebook_posted_status === $auction->status) {
            Log::info("Facebook posting skipped: auction #{$auction->id} already posted for status '{$auction->status}'.");
            return $auction->facebook_post_id;
        }

        try {
            $auction->loadMissing(['auctioneer', 'lots.images']);

            $message = $this->buildMessage($auction);
            $pageId = config('facebook.page_id');
            $token = config('facebook.page_token');

            // Always use /feed endpoint (link post with OG preview)
            $postId = $this->postLink($pageId, $token, $message, route('auctions.show', $auction));

            if ($postId) {
                $auction->update([
                    'facebook_post_id' => $postId,
                    'facebook_posted_status' => $auction->status,
                ]);
                Log::info("Facebook post created for auction #{$auction->id} (status: {$auction->status}): {$postId}");
            }

            return $postId;
        } catch (\Exception $e) {
            Log::error("Facebook posting failed for auction #{$auction->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build the post message for an auction.
     */
    private function buildMessage(Auction $auction): string
    {
        $lotCount = $auction->lots->count();
        $auctioneerName = $auction->auctioneer->business_name ?? 'Unknown';
        $startTime = $auction->start_time?->format('d M Y, H:i') ?? 'TBA';
        $endTime = $auction->end_time?->format('d M Y, H:i') ?? 'TBA';
        $url = route('auctions.show', $auction);

        $cta = $auction->status === 'upcoming' ? 'Coming soon' : 'Bid now';

        $lines = [
            $auction->title,
            '',
            "{$lotCount} lots by {$auctioneerName}",
            "Starts: {$startTime}",
            "Ends: {$endTime}",
            '',
            "{$cta}: {$url}",
            '',
            '#BidAll #OnlineAuction #SouthAfrica',
        ];

        return implode("\n", $lines);
    }

    /**
     * Get the public URL of the first lot's first image, if available.
     */
    private function getFirstLotImageUrl(Auction $auction): ?string
    {
        $firstLot = $auction->lots->first();
        if (!$firstLot) {
            return null;
        }

        $firstImage = $firstLot->images->first();
        if (!$firstImage || !$firstImage->optimized_path) {
            return null;
        }

        // Build full public URL
        return asset('storage/' . $firstImage->optimized_path);
    }

    /**
     * Post a photo with message to the Facebook Page.
     */
    private function postWithImage(string $pageId, string $token, string $message, string $imageUrl): ?string
    {
        $response = Http::connectTimeout(5)->timeout(15)
            ->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                'url' => $imageUrl,
                'message' => $message,
                'access_token' => $token,
            ]);

        if ($response->successful()) {
            return $response->json('post_id') ?? $response->json('id');
        }

        Log::error('Facebook photo post failed: ' . $response->body());
        return null;
    }

    /**
     * Post a link with message to the Facebook Page feed.
     */
    private function postLink(string $pageId, string $token, string $message, string $link): ?string
    {
        $response = Http::connectTimeout(5)->timeout(15)
            ->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
                'message' => $message,
                'link' => $link,
                'access_token' => $token,
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        Log::error('Facebook link post failed: ' . $response->body());
        return null;
    }
}
