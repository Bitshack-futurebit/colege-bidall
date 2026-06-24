<?php

namespace App\Services;

use App\Mail\ProxyBidExceeded;
use App\Models\Lot;
use App\Models\ProxyBid;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProxyBiddingService
{
    /**
     * Set or update a proxy bid for a user on a lot.
     */
    public function setProxy(User $user, Lot $lot, float $maxAmount): ProxyBid
    {
        if (!$lot->auction->allow_proxy_bidding) {
            throw new \Exception('Proxy bidding is not enabled for this auction.');
        }

        if (!$lot->isLive()) {
            throw new \Exception('This lot is not currently live.');
        }

        if ($lot->isOwnedBy($user)) {
            throw new \Exception('You cannot bid on your own lot.');
        }

        $minimumBid = $lot->current_bid
            ? $lot->current_bid + $lot->increment
            : $lot->starting_bid;

        // Max must be at least the minimum bid (unless user is already winning)
        if ($lot->winning_bidder_id !== $user->id && $maxAmount < $minimumBid) {
            throw new \Exception("Maximum bid must be at least " . formatCurrency($minimumBid));
        }

        if ($maxAmount <= $lot->current_bid && $lot->winning_bidder_id !== $user->id) {
            throw new \Exception("Maximum bid must be higher than the current bid of " . formatCurrency($lot->current_bid));
        }

        // Deactivate any existing active proxy for this user on this lot
        ProxyBid::where('lot_id', $lot->id)
            ->where('user_id', $user->id)
            ->active()
            ->update(['is_active' => false]);

        // Create new proxy bid
        $proxy = ProxyBid::create([
            'lot_id' => $lot->id,
            'user_id' => $user->id,
            'max_amount' => $maxAmount,
            'is_active' => true,
        ]);

        // Immediately resolve proxies to place initial bid
        $this->resolveProxies($lot->fresh());

        return $proxy->fresh();
    }

    /**
     * Cancel a user's active proxy bid on a lot.
     */
    public function cancelProxy(User $user, Lot $lot): void
    {
        ProxyBid::where('lot_id', $lot->id)
            ->where('user_id', $user->id)
            ->active()
            ->update(['is_active' => false]);
    }

    /**
     * Called after a manual bid — resolve proxies from other users.
     */
    public function resolveAfterBid(Lot $lot, User $manualBidder): void
    {
        $proxies = ProxyBid::where('lot_id', $lot->id)
            ->where('user_id', '!=', $manualBidder->id)
            ->active()
            ->orderByDesc('max_amount')
            ->get();

        if ($proxies->isEmpty()) {
            return;
        }

        foreach ($proxies as $proxy) {
            $lot->refresh();

            $minimumBid = $lot->current_bid + $lot->increment;

            if ($proxy->max_amount >= $minimumBid && $lot->winning_bidder_id !== $proxy->user_id) {
                // Proxy can still beat the current bid — bid at minimum increment
                $bidAmount = $minimumBid;
                try {
                    $lot->placeBid($proxy->user, $bidAmount, isProxy: true);
                } catch (\Exception $e) {
                    // If bid fails (e.g., already winning), skip
                    continue;
                }
            } elseif ($proxy->max_amount < $minimumBid) {
                // Proxy exceeded — deactivate and notify
                $proxy->update(['is_active' => false]);
                $this->notifyIfExceeded($proxy, $lot);
            }
        }
    }

    /**
     * Resolve all active proxies on a lot (e.g., when a new proxy is set).
     */
    public function resolveProxies(Lot $lot): void
    {
        $proxies = ProxyBid::where('lot_id', $lot->id)
            ->active()
            ->orderByDesc('max_amount')
            ->get();

        if ($proxies->isEmpty()) {
            return;
        }

        if ($proxies->count() === 1) {
            $proxy = $proxies->first();
            $lot->refresh();

            $minimumBid = $lot->current_bid
                ? $lot->current_bid + $lot->increment
                : $lot->starting_bid;

            // Only bid if not already winning
            if ($lot->winning_bidder_id !== $proxy->user_id && $proxy->max_amount >= $minimumBid) {
                try {
                    $lot->placeBid($proxy->user, $minimumBid, isProxy: true);
                } catch (\Exception $e) {
                    // Silently handle — user might already be winning
                }
            }
            return;
        }

        // Multiple proxies — resolve competition
        $highest = $proxies->first();
        $secondHighest = $proxies->skip(1)->first();

        $lot->refresh();

        $minimumBid = $lot->current_bid
            ? $lot->current_bid + $lot->increment
            : $lot->starting_bid;

        // The winning bid is the lower of: highest max, or second_max + increment
        $winningBid = min(
            (float) $highest->max_amount,
            (float) $secondHighest->max_amount + (float) $lot->increment
        );

        // Ensure winning bid is at least the minimum
        $winningBid = max($winningBid, $minimumBid);

        // Don't exceed the highest proxy's max
        if ($winningBid > $highest->max_amount) {
            $winningBid = (float) $highest->max_amount;
        }

        // Place the winning bid if not already winning at that amount
        if ($lot->winning_bidder_id !== $highest->user_id || $lot->current_bid < $winningBid) {
            try {
                $lot->placeBid($highest->user, $winningBid, isProxy: true);
            } catch (\Exception $e) {
                // Handle gracefully
            }
        }

        // Deactivate losing proxies and notify
        foreach ($proxies->skip(1) as $loser) {
            $lot->refresh();
            $minimumAfter = $lot->current_bid + $lot->increment;

            if ($loser->max_amount < $minimumAfter) {
                $loser->update(['is_active' => false]);
                $this->notifyIfExceeded($loser, $lot);
            }
        }
    }

    /**
     * Send notification email if proxy was exceeded and conditions are met.
     */
    protected function notifyIfExceeded(ProxyBid $proxy, Lot $lot): void
    {
        // Skip if already notified
        if ($proxy->notified_at) {
            return;
        }

        // Skip if lot has 30 minutes or less remaining
        if ($lot->timeRemaining() <= 1800) {
            return;
        }

        $user = $proxy->user;

        // Skip if user has email notifications disabled
        if (!($user->email_notifications ?? true)) {
            return;
        }

        $proxy->update(['notified_at' => now()]);

        Mail::to($user->email)->send(new ProxyBidExceeded(
            user: $user,
            lot: $lot,
            proxyMax: $proxy->max_amount,
            currentBid: $lot->current_bid,
        ));
    }
}
