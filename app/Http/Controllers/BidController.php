<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class BidController extends Controller
{
    /**
     * Place a bid on a lot.
     */
    public function place(Request $request, Lot $lot)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $user = auth()->user();
        $amount = $request->amount;

        try {
            // Place bid (model handles all validation and logic)
            $bid = $lot->placeBid($user, $amount);

            // Log activity
            ActivityLog::log(
                'bid_placed',
                "Placed bid of " . formatCurrency($amount) . " on lot #{$lot->lot_number}",
                $lot,
                [
                    'lot_id' => $lot->id,
                    'amount' => $amount,
                    'event_id' => $lot->event_id,
                ]
            );

            // Return response
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bid placed successfully!',
                    'bid' => [
                        'id' => $bid->id,
                        'amount' => $amount,
                        'formatted_amount' => formatCurrency($amount),
                    ],
                    'lot' => [
                        'current_bid' => $lot->fresh()->current_bid,
                        'formatted_current_bid' => formatCurrency($lot->fresh()->current_bid),
                        'total_bids' => $lot->fresh()->total_bids,
                        'end_time' => $lot->fresh()->end_time->toIso8601String(),
                        'time_remaining' => $lot->fresh()->timeRemaining(),
                    ],
                ]);
            }

            return back()->with('success', 'Bid placed successfully!');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
