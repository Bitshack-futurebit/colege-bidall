<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerStrike;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerStrikesController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'active');
        $filter = in_array($filter, ['active', 'reversed', 'all']) ? $filter : 'active';

        $query = BuyerStrike::with(['user:id,name,email,bidding_disabled,bidding_disabled_reason', 'seller:id,name', 'lot:id,title']);

        if ($filter === 'active') {
            $query->whereNull('reversed_at');
        } elseif ($filter === 'reversed') {
            $query->whereNotNull('reversed_at');
        }

        $strikes = $query->orderByDesc('reported_at')->paginate(25);

        $counts = [
            'active'   => BuyerStrike::whereNull('reversed_at')->count(),
            'reversed' => BuyerStrike::whereNotNull('reversed_at')->count(),
        ];

        // Buyers currently auto-disabled from non-payment strikes (top of page)
        $disabledBuyers = User::where('bidding_disabled', true)
            ->where('bidding_disabled_reason', 'like', '%non-payment strikes%')
            ->select(['id', 'name', 'email', 'bidding_disabled_reason'])
            ->limit(10)
            ->get();

        return view('admin.buyer-strikes.index', compact('strikes', 'filter', 'counts', 'disabledBuyers'));
    }

    /**
     * Reverse a strike. If the buyer's active strike count drops below the
     * block threshold, automatically restore their bidding privileges.
     */
    public function reverse(Request $request, BuyerStrike $strike)
    {
        if ($strike->reversed_at) {
            return back()->with('error', 'Strike already reversed.');
        }

        $validated = $request->validate([
            'reversal_note' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        \DB::transaction(function () use ($strike, $validated, $request) {
            $strike->update([
                'reversed_at' => now(),
                'reversed_by_user_id' => $request->user()->id,
                'reversal_note' => $validated['reversal_note'],
            ]);

            // Re-evaluate the buyer's active strike count.
            $buyer = User::find($strike->user_id);
            if (!$buyer) return;

            $window = (int) config('community.buyer_strike_window_months', 6);
            $threshold = (int) config('community.buyer_strike_block_threshold', 2);

            $activeStrikes = BuyerStrike::where('user_id', $buyer->id)
                ->whereNull('reversed_at')
                ->where('reported_at', '>=', now()->subMonths($window))
                ->count();

            // Restore bidding if they're now below threshold AND the disable
            // reason looks like an auto-strike disable (don't accidentally undo
            // an admin-entered manual disable).
            if ($activeStrikes < $threshold
                && $buyer->bidding_disabled
                && str_contains($buyer->bidding_disabled_reason ?? '', 'non-payment strikes')
            ) {
                $buyer->update([
                    'bidding_disabled' => false,
                    'bidding_disabled_reason' => null,
                ]);
            }
        });

        return back()->with('success', 'Strike reversed.');
    }
}
