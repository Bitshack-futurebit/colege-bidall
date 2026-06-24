<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Auction;
use App\Models\ProxyBid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LotStatusController extends Controller
{
    /**
     * Get real-time status of a lot (for polling).
     * This endpoint is PUBLIC (no auth/session) for maximum performance.
     * Auth-dependent data (hasTopBid, proxyMax) is fetched via a separate
     * authenticated endpoint only when the user is logged in.
     */
    public function status(Lot $lot)
    {
        $auction = $lot->auction;
        $isLiveFormat = $auction && $auction->isLiveFormat();

        // Only advance live-format phases when the timer has actually elapsed,
        // and use a short cache lock so only one poll per second writes — the
        // rest become pure reads. Prevents poll transactions from competing
        // with bid writes on the same lot row during pulse phases.
        if ($isLiveFormat
            && ($lot->status === 'live' || $lot->live_phase === 'closed')
            && $lot->live_phase_ends_at
            && $lot->live_phase_ends_at->lessThanOrEqualTo(now())
        ) {
            $advanceLock = Cache::lock("advance-phase-{$lot->id}", 2);
            if ($advanceLock->get()) {
                try {
                    DB::transaction(function () use ($lot) {
                        $lot->refresh();
                        if ($lot->live_phase_ends_at && $lot->live_phase_ends_at->lessThanOrEqualTo(now())) {
                            $lot->advanceLivePhase();
                        }
                    });
                    $lot->refresh();
                    Cache::forget($this->statusCacheKey($lot));
                } finally {
                    $advanceLock->release();
                }
            } else {
                // Another poll is advancing — brief wait, then read the result
                $lot->refresh();
            }
        }

        return response()->json(
            Cache::remember(
                $this->statusCacheKey($lot),
                1,
                fn () => $this->buildStatusPayload($lot)
            )
        );
    }

    /**
     * Build the status response payload (cached).
     */
    protected function buildStatusPayload(Lot $lot): array
    {
        $auction = $lot->auction;
        $isSealed = $auction && $auction->isSealed();
        $isLiveFormat = $auction && $auction->isLiveFormat();
        $auctionEnded = $auction && $auction->hasEnded();

        // Sealed auctions: hide bid data until auction ends
        if ($isSealed && !$auctionEnded) {
            return [
                'isSealed' => true,
                'sealedMode' => $auction->sealed_mode,
                'endTime' => $lot->end_time->toIso8601String(),
                'status' => $lot->status,
            ];
        }

        $data = [
            'currentBid' => $lot->current_bid ?? $lot->starting_bid ?? 0,
            'totalBids' => $lot->total_bids,
            'endTime' => $lot->end_time?->toIso8601String(),
            'winningBidderId' => $lot->winning_bidder_id,
            'reserveMet' => (bool) $lot->reserve_met,
            'status' => $lot->status,
            'declinedAt' => $lot->declined_at?->toIso8601String(),
        ];

        if ($isSealed) {
            $data['isSealed'] = true;
            $data['sealedMode'] = $auction->sealed_mode;
        }

        if ($isLiveFormat) {
            $data['isLive'] = true;
            $data['livePhase'] = $lot->live_phase;
            $data['livePhaseEndsAt'] = $lot->live_phase_ends_at?->toIso8601String();
            $data['liveOpensAt'] = $lot->live_opens_at?->toIso8601String();
            $data['distinctBidders'] = $lot->distinctLiveBidderCount();
            $data['acceptsBids'] = $lot->acceptsLiveBids();
        }

        if ($lot->dutch_start_price) {
            $data['isDutch'] = true;
            $data['dutchCurrentPrice'] = $lot->getCurrentDutchPrice();
            $data['dutchFloorPrice'] = (float) $lot->dutch_floor_price;
            $data['dutchNextDropIn'] = $lot->getDutchNextDropIn();
            $data['dutchAtFloor'] = $lot->isAtDutchFloor();
            $data['dutchDropStrategy'] = $lot->dutch_drop_strategy ?: 'constant';
            $data['inCountdown'] = $lot->isInDutchCountdown();
            $data['dutchStartTime'] = $lot->dutch_start_time ? $lot->dutch_start_time->toIso8601String() : null;
            $data['quantity'] = $lot->quantity;
            $data['quantitySold'] = $lot->quantity_sold;
            $data['quantityRemaining'] = $lot->quantityRemaining();
        }

        return $data;
    }

    protected function statusCacheKey(Lot $lot): string
    {
        return "lot-status:{$lot->id}";
    }

    /**
     * Get user-specific lot data (proxy bid, top bid status).
     * Only called once on page load + after placing a bid, not every poll.
     */
    public function userStatus(Request $request, Lot $lot)
    {
        $user = $request->user();

        // Sealed auction: return user's own sealed bid
        if ($lot->auction && $lot->auction->isSealed()) {
            $sealedBid = $lot->getUserSealedBid($user->id);
            return response()->json([
                'sealedBid' => $sealedBid ? (float) $sealedBid->amount : null,
            ]);
        }

        $proxyMax = ProxyBid::where('lot_id', $lot->id)
            ->where('user_id', $user->id)
            ->active()
            ->value('max_amount');

        return response()->json([
            'hasTopBid' => $lot->winning_bidder_id === $user->id,
            'proxyMax' => $proxyMax ? (float) $proxyMax : null,
        ]);
    }

    /**
     * Batch status check for multiple lots with change detection.
     * Public endpoint (no auth/session) for watchlist polling.
     *
     * Query params:
     *   ids: comma-separated lot IDs (e.g. "42,43,44")
     *   v:   comma-separated id:total_bids pairs (e.g. "42:5,43:3,44:0")
     *
     * If all lots match their version, returns { changed: false }.
     * Otherwise returns full status for changed lots only.
     */
    public function batchStatus(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->query('ids', ''))));

        if (empty($ids) || count($ids) > 50) {
            return response()->json(['changed' => false]);
        }

        // Parse version map: "42:5,43:3" → [42 => 5, 43 => 3]
        $versions = [];
        foreach (explode(',', $request->query('v', '')) as $pair) {
            $parts = explode(':', $pair);
            if (count($parts) === 2) {
                $versions[(int) $parts[0]] = (int) $parts[1];
            }
        }

        $lots = Lot::whereIn('id', $ids)
            ->select(['id', 'current_bid', 'starting_bid', 'total_bids', 'end_time', 'winning_bidder_id', 'status'])
            ->get();

        // If versions provided, check for changes
        if (!empty($versions)) {
            $changed = $lots->filter(fn ($lot) =>
                !isset($versions[$lot->id]) || $versions[$lot->id] !== $lot->total_bids
            );

            if ($changed->isEmpty()) {
                return response()->json(['changed' => false]);
            }

            // Return only changed lots
            $lots = $changed;
        }

        $data = $lots->map(fn ($lot) => [
            'id' => $lot->id,
            'currentBid' => $lot->current_bid ?? $lot->starting_bid ?? 0,
            'totalBids' => $lot->total_bids,
            'endTime' => $lot->end_time->toIso8601String(),
            'winningBidderId' => $lot->winning_bidder_id,
            'status' => $lot->status,
        ])->keyBy('id');

        return response()->json([
            'changed' => true,
            'lots' => $data,
        ]);
    }

    /**
     * Get status of all lots in an auction (for auction page polling).
     */
    public function auctionStatus(Auction $auction)
    {
        $isDutch = $auction->isDutch();
        $isLiveFormat = $auction->isLiveFormat();

        // Auto-close expired Dutch lots — gated by a cache lock so only one poll
        // per second performs the work regardless of viewer count.
        if ($isDutch) {
            $this->maybeAdvanceDutch($auction);
        }

        // Advance live-format phase only if timer elapsed; single poll per
        // second does the work via cache lock.
        if ($isLiveFormat) {
            $this->maybeAdvanceLive($auction);
        }

        return response()->json(
            Cache::remember(
                "auction-status:{$auction->id}",
                1,
                fn () => $this->buildAuctionStatusPayload($auction)
            )
        );
    }

    protected function maybeAdvanceDutch(Auction $auction): void
    {
        $hasExpired = $auction->lots()
            ->where('status', 'live')
            ->whereNotNull('dutch_end_time')
            ->where('dutch_end_time', '<=', now())
            ->exists();

        if (!$hasExpired) return;

        $lock = Cache::lock("advance-auction:{$auction->id}", 2);
        if (!$lock->get()) return;

        try {
            $expiredLots = $auction->lots()
                ->where('status', 'live')
                ->whereNotNull('dutch_end_time')
                ->where('dutch_end_time', '<=', now())
                ->get();

            foreach ($expiredLots as $expiredLot) {
                DB::transaction(fn () => $expiredLot->closeDutch());
            }

            Cache::forget("auction-status:{$auction->id}");
        } finally {
            $lock->release();
        }
    }

    protected function maybeAdvanceLive(Auction $auction): void
    {
        $activeLot = $auction->lots()
            ->where(fn ($q) => $q->where('status', 'live')->orWhere('live_phase', 'closed'))
            ->whereNotNull('live_phase_ends_at')
            ->where('live_phase_ends_at', '<=', now())
            ->orderBy('lot_number')
            ->first();

        if (!$activeLot) return;

        $lock = Cache::lock("advance-phase-{$activeLot->id}", 2);
        if (!$lock->get()) return;

        try {
            DB::transaction(function () use ($activeLot) {
                $activeLot->refresh();
                if ($activeLot->live_phase_ends_at && $activeLot->live_phase_ends_at->lessThanOrEqualTo(now())) {
                    $activeLot->advanceLivePhase();
                }
            });
            Cache::forget("auction-status:{$auction->id}");
        } finally {
            $lock->release();
        }
    }

    protected function buildAuctionStatusPayload(Auction $auction): array
    {
        $isDutch = $auction->isDutch();
        $isSealed = $auction->isSealed();
        $isLiveFormat = $auction->isLiveFormat();
        $auctionEnded = $auction->hasEnded();

        $lots = $auction->lots()
            ->select(['id', 'event_id', 'lot_number', 'status', 'current_bid', 'starting_bid', 'total_bids', 'end_time', 'winning_bidder_id', 'dutch_start_price', 'dutch_floor_price', 'dutch_drop_amount', 'dutch_drop_interval', 'dutch_drop_strategy', 'quantity', 'quantity_sold', 'dutch_start_time', 'dutch_end_time', 'live_phase', 'live_phase_ends_at', 'live_opens_at'])
            ->orderBy('lot_number')
            ->get()
            ->map(function ($lot) use ($isDutch, $isSealed, $isLiveFormat, $auctionEnded) {
                $data = [
                    'id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'status' => $lot->status,
                    'end_time' => $lot->end_time ? $lot->end_time->toIso8601String() : null,
                    'time_remaining' => $lot->timeRemaining(),
                ];

                if ($isLiveFormat) {
                    $data['live_phase'] = $lot->live_phase;
                    $data['live_phase_ends_at'] = $lot->live_phase_ends_at?->toIso8601String();
                    $data['live_opens_at'] = $lot->live_opens_at?->toIso8601String();
                    $data['accepts_bids'] = $lot->acceptsLiveBids();
                }

                if ($isSealed && !$auctionEnded) {
                    $data['current_bid'] = 0;
                    $data['total_bids'] = 0;
                    $data['formatted_current_bid'] = '';
                    $data['is_in_soft_close'] = false;
                    return $data;
                }

                $data['current_bid'] = $lot->current_bid ?? $lot->starting_bid ?? 0;
                $data['total_bids'] = $lot->total_bids;
                $data['is_in_soft_close'] = $isDutch ? false : $lot->isInSoftClose();

                if ($isDutch && $lot->dutch_start_price) {
                    $data['formatted_current_bid'] = formatCurrency($lot->getCurrentDutchPrice());
                    $data['dutch_current_price'] = $lot->getCurrentDutchPrice();
                    $data['dutch_floor_price'] = (float) $lot->dutch_floor_price;
                    $data['dutch_next_drop_in'] = $lot->getDutchNextDropIn();
                    $data['dutch_at_floor'] = $lot->isAtDutchFloor();
                    $data['in_countdown'] = $lot->isInDutchCountdown();
                    $data['dutch_start_time'] = $lot->dutch_start_time ? $lot->dutch_start_time->toIso8601String() : null;
                    $data['quantity'] = $lot->quantity;
                    $data['quantity_sold'] = $lot->quantity_sold;
                    $data['quantity_remaining'] = $lot->quantityRemaining();
                } else {
                    $data['formatted_current_bid'] = formatCurrency($lot->current_bid ?? $lot->starting_bid ?? 0);
                }

                return $data;
            });

        return [
            'success' => true,
            'data' => [
                'auction' => [
                    'id' => $auction->id,
                    'status' => $auction->status,
                    'total_bids' => $auction->total_bids,
                    'auction_type' => $auction->auction_type ?? 'english',
                    'sealed_mode' => $auction->sealed_mode,
                    'dutch_lot_mode' => $auction->dutch_lot_mode,
                ],
                'lots' => $lots,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
