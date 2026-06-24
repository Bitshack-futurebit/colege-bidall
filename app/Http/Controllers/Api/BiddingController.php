<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Lot;
use App\Services\ProxyBiddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiddingController extends Controller
{
    /**
     * Returns a 403 JSON response if the user has been auto-disabled (e.g.
     * accumulated buyer-non-payment strikes), or null if they may proceed.
     */
    private function blockIfBiddingDisabled(?\App\Models\User $user): ?\Illuminate\Http\JsonResponse
    {
        if ($user && $user->bidding_disabled) {
            return response()->json([
                'success' => false,
                'message' => $user->bidding_disabled_reason ?: 'Your bidding has been disabled.',
            ], 403);
        }
        return null;
    }

    /**
     * Place bid via API.
     */
    public function place(Request $request, Lot $lot)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $user = auth()->user();

        if ($block = $this->blockIfBiddingDisabled($user)) return $block;

        $amount = $request->amount;

        try {
            $bid = $lot->placeBid($user, $amount);

            $freshLot = $lot->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Bid placed successfully!',
                'newBid' => $freshLot->current_bid,
                'newEndTime' => $freshLot->end_time->toIso8601String(),
                'totalBids' => $freshLot->total_bids,
                'hasTopBid' => $freshLot->winning_bidder_id === $user->id,
                'reserveMet' => (bool) $freshLot->reserve_met,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get bid history for lot.
     */
    public function history(Lot $lot)
    {
        $bids = $lot->bids()
            ->with('user:id,paddle_number')
            ->orderBy('amount', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($bid) {
                return [
                    'id' => $bid->id,
                    'amount' => $bid->amount,
                    'formatted_amount' => formatCurrency($bid->amount),
                    'bidder' => 'Paddle #' . $bid->user->paddle_number,
                    'placed_at' => $bid->placed_at->toIso8601String(),
                    'is_winning' => $bid->is_winning,
                    'is_proxy' => (bool) $bid->is_proxy,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bids,
        ]);
    }

    /**
     * Set proxy bid via API.
     */
    public function setProxy(Request $request, Lot $lot)
    {
        $request->validate([
            'max_amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($block = $this->blockIfBiddingDisabled(auth()->user())) return $block;

        try {
            $service = app(ProxyBiddingService::class);
            $proxy = $service->setProxy(auth()->user(), $lot, $request->max_amount);

            $freshLot = $lot->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Proxy bid set successfully!',
                'proxyMax' => $proxy->max_amount,
                'currentBid' => $freshLot->current_bid,
                'totalBids' => $freshLot->total_bids,
                'hasTopBid' => $freshLot->winning_bidder_id === auth()->id(),
                'newEndTime' => $freshLot->end_time->toIso8601String(),
                'reserveMet' => (bool) $freshLot->reserve_met,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel proxy bid via API.
     */
    public function cancelProxy(Lot $lot)
    {
        try {
            $service = app(ProxyBiddingService::class);
            $service->cancelProxy(auth()->user(), $lot);

            return response()->json([
                'success' => true,
                'message' => 'Proxy bid cancelled.',
                'proxyMax' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Buy at current Dutch auction price.
     */
    public function dutchBuy(Request $request, Lot $lot)
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = auth()->user();

        if ($block = $this->blockIfBiddingDisabled($user)) return $block;

        $quantityToBuy = $request->input('quantity', 1);

        try {
            $result = DB::transaction(function () use ($lot, $user, $quantityToBuy) {
                // Lock the lot row to prevent race conditions
                $lot = Lot::where('id', $lot->id)->lockForUpdate()->first();

                if (!$lot || !$lot->isLive()) {
                    throw new \Exception('This lot is no longer available.');
                }

                if ($lot->isInDutchCountdown()) {
                    throw new \Exception('This lot is not yet open for buying. Please wait for the countdown to finish.');
                }

                if ($lot->isWithdrawn()) {
                    throw new \Exception('This lot has been withdrawn.');
                }

                if ($lot->isDutchSoldOut()) {
                    throw new \Exception('This lot is sold out.');
                }

                $remaining = $lot->quantityRemaining();
                if ($quantityToBuy > $remaining) {
                    throw new \Exception("Only {$remaining} item(s) remaining.");
                }

                // Check auction registration
                if ($lot->auction->requiresRegistration() && !$user->isRegisteredForAuction($lot->event_id)) {
                    throw new \Exception('You must register for this auction to buy.');
                }

                if ($lot->isOwnedBy($user)) {
                    throw new \Exception('You cannot buy your own lot.');
                }

                $currentPrice = $lot->getCurrentDutchPrice();

                // Create the buy record as a bid
                $bid = $lot->bids()->create([
                    'user_id' => $user->id,
                    'amount' => $currentPrice,
                    'is_winning' => true,
                    'is_dutch_buy' => true,
                    'quantity_bought' => $quantityToBuy,
                    'ip_address' => request()->ip(),
                    'placed_at' => now(),
                ]);

                // Update lot quantities
                $lot->quantity_sold += $quantityToBuy;
                $lot->current_bid = $currentPrice;
                $lot->winning_bidder_id = $user->id;
                $lot->increment('total_bids');

                // If fully sold out, close the lot and advance sequential schedule
                if ($lot->isDutchSoldOut()) {
                    // Set current_bid to total sales value for reports
                    $totalSalesValue = (float) ($lot->bids()
                        ->where('is_dutch_buy', true)
                        ->selectRaw('SUM(amount * quantity_bought) as total')
                        ->value('total') ?? 0);
                    $lot->current_bid = $totalSalesValue;
                    $lot->status = 'sold';
                    $lot->actual_end_time = now();
                    if (!$lot->auction->enable_online_payment) {
                        $lot->payment_status = 'awaiting_collection';
                        $lot->payment_method_selected_at = now();
                    }

                    // Deduct 1% platform fee on total Dutch sales
                    $auction = $lot->auction;
                    $buyersPremium = $totalSalesValue * ($auction->buyers_premium_percentage / 100);
                    $total = $totalSalesValue + $buyersPremium;
                    $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

                    if ($platformFee > 0) {
                        $auction->auctioneer->deductCredits(
                            $platformFee,
                            'lot_close',
                            $lot->id,
                            "1% fee for Dutch Lot #{$lot->lot_number} - {$lot->title}"
                        );
                    }

                    // Schedule image deletion
                    $deletionDate = now()->addDays(config('auction.image_auto_delete_days', 30));
                    $lot->images()->update(['scheduled_deletion_at' => $deletionDate]);

                    // Sequential Dutch: activate the next lot (recalculates timing)
                    if ($auction->isDutchSequential()) {
                        $lot->activateNextDutchLot();
                    }
                }

                $lot->save();

                return [
                    'price' => $currentPrice,
                    'quantity' => $quantityToBuy,
                    'total' => $currentPrice * $quantityToBuy,
                    'remaining' => $lot->quantityRemaining(),
                    'soldOut' => $lot->isDutchSoldOut(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Bought {$result['quantity']} item(s) at " . formatCurrency($result['price']) . " each!",
                'price' => $result['price'],
                'quantity' => $result['quantity'],
                'total' => $result['total'],
                'quantityRemaining' => $result['remaining'],
                'soldOut' => $result['soldOut'],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Place or update a sealed bid.
     */
    public function sealedBid(Request $request, Lot $lot)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = auth()->user();

        if ($block = $this->blockIfBiddingDisabled($user)) return $block;

        try {
            $existingBid = $lot->getUserSealedBid($user->id);
            $bid = $lot->placeSealed($user, $request->amount);

            return response()->json([
                'success' => true,
                'message' => $existingBid ? 'Bid updated successfully!' : 'Sealed bid placed successfully!',
                'amount' => (float) $bid->amount,
                'isUpdate' => (bool) $existingBid,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle watchlist via API.
     */
    public function toggleWatchlist(Lot $lot)
    {
        $user = auth()->user();

        if ($user->hasWatchlisted($lot->id)) {
            $user->watchlist()->where('lot_id', $lot->id)->delete();
            $watchlisted = false;
            $message = 'Removed from watchlist';
        } else {
            $user->watchlist()->create(['lot_id' => $lot->id]);
            $watchlisted = true;
            $message = 'Added to watchlist';
        }

        return response()->json([
            'success' => true,
            'watchlisted' => $watchlisted,
            'message' => $message,
        ]);
    }
}
