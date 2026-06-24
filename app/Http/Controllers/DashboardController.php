<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class DashboardController extends Controller
{
    /**
     * Show bidder dashboard.
     */
    public function bidder()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Active bids (on live lots)
        $activeBids = Bid::where('user_id', $user->id)
            ->whereHas('lot', function ($query) {
                $query->where('status', 'live');
            })
            ->with(['lot.auction', 'lot.primaryImage'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('lot_id')
            ->map(fn($bids) => $bids->first()); // Get latest bid per lot

        // Winning lots
        $winningLots = Lot::where('winning_bidder_id', $user->id)
            ->where('status', 'live')
            ->with(['auction', 'primaryImage'])
            ->get();

        // Watchlist
        $watchlist = $user->watchlist()
            ->with(['lot.auction', 'lot.primaryImage'])
            ->whereHas('lot', function ($query) {
                $query->whereIn('status', ['live', 'pending']);
            })
            ->latest()
            ->limit(6)
            ->get();

        // Won lots (awaiting collection or pending online payment)
        $wonLots = Lot::where('winning_bidder_id', $user->id)
            ->where('status', 'sold')
            ->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))
            ->with(['auction', 'primaryImage'])
            ->get();

        // Followed auctioneers (limited for dashboard)
        $followedAuctioneers = $user->followedAuctioneers()
            ->with(['auctioneer.user', 'auctioneer.auctions' => function ($q) {
                $q->whereIn('status', ['live', 'upcoming'])
                  ->orderBy('start_time')
                  ->limit(2);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $stats = [
            'active_bids' => $activeBids->count(),
            'winning' => $winningLots->count(),
            'watchlist' => $user->watchlist()->count(),
            'won_unpaid' => $wonLots->count(),
            'following' => $user->followedAuctioneers()->count(),
        ];

        return view('dashboard.bidder', compact(
            'user',
            'activeBids',
            'winningLots',
            'watchlist',
            'wonLots',
            'followedAuctioneers',
            'stats'
        ));
    }

    /**
     * Show all bids.
     */
    public function bids()
    {
        $user = auth()->user();

        $bids = Bid::where('user_id', $user->id)
            ->with(['lot.auction', 'lot.primaryImage'])
            ->orderBy('placed_at', 'desc')
            ->paginate(20);

        return view('dashboard.bids', compact('bids'));
    }

    /**
     * Show watchlist.
     */
    public function watchlist()
    {
        $user = auth()->user();

        $watchlist = $user->watchlist()
            ->with(['lot.auction', 'lot.primaryImage'])
            ->latest()
            ->paginate(20);

        return view('dashboard.watchlist', compact('watchlist'));
    }

    /**
     * Show won lots.
     */
    public function won(Request $request)
    {
        $user = auth()->user();

        $query = Lot::where('winning_bidder_id', $user->id)
            ->whereIn('status', ['sold', 'pending_confirmation'])
            ->whereHas('auction')
            ->with(['auction.auctioneer.user', 'images'])
            ->orderBy('actual_end_time', 'desc');

        // Filter: payment status
        if ($request->filled('payment')) {
            match($request->payment) {
                'awaiting' => $query->where('payment_status', 'awaiting_collection'),
                'paid'     => $query->whereIn('payment_status', ['paid_platform', 'paid_offline']),
                default    => null,
            };
        }

        // Filter: auctioneer
        if ($request->filled('auctioneer')) {
            $query->whereHas('auction', fn($q) => $q->where('auctioneer_id', $request->auctioneer));
        }

        // Filter: date range (based on auction end time)
        if ($request->filled('from')) {
            $query->where('actual_end_time', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('actual_end_time', '<=', $request->to . ' 23:59:59');
        }

        $wonLots = $query->paginate(25)->withQueryString();

        // Auctioneers the user has won lots from (for filter dropdown)
        $auctioneers = \App\Models\Auctioneer::whereHas('auctions.lots', fn($q) =>
            $q->where('winning_bidder_id', $user->id)->whereIn('status', ['sold', 'pending_confirmation'])
        )->orderBy('business_name')->get();

        // Summary counts (unfiltered)
        $totalWon   = Lot::where('winning_bidder_id', $user->id)->whereIn('status', ['sold', 'pending_confirmation'])->whereHas('auction')->count();
        $totalUnpaid = Lot::where('winning_bidder_id', $user->id)->whereIn('status', ['sold', 'pending_confirmation'])->whereHas('auction')
            ->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))
            ->count();
        $totalValue  = Lot::where('winning_bidder_id', $user->id)->whereIn('status', ['sold', 'pending_confirmation'])->whereHas('auction')->sum('current_bid');

        return view('dashboard.won', compact('wonLots', 'auctioneers', 'totalWon', 'totalUnpaid', 'totalValue'));
    }

    /**
     * Show profile edit page.
     */
    public function profile()
    {
        $user = auth()->user();

        $stats = [
            'total_bids' => \App\Models\Bid::where('user_id', $user->id)->count(),
            'active_bids' => \App\Models\Bid::where('user_id', $user->id)
                ->whereHas('lot', function($q) {
                    $q->where('status', 'live');
                })->count(),
            'lots_won' => \App\Models\Lot::where('winning_bidder_id', $user->id)
                ->where('status', 'sold')->count(),
            'watchlist_count' => \App\Models\Watchlist::where('user_id', $user->id)->count(),
        ];

        return view('dashboard.profile', compact('user', 'stats'));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'email_notifications' => ['sometimes', 'boolean'],
            'event_reminders' => ['sometimes', 'boolean'],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // Handle ID document upload
        if ($request->hasFile('id_document')) {
            if ($user->id_document && Storage::disk('local')->exists($user->id_document)) {
                Storage::disk('local')->delete($user->id_document);
            }
            $path = $request->file('id_document')->store('id-documents/' . $user->id, 'local');
            $user->forceFill(['id_document' => $path, 'id_verified_at' => null])->save();
        }
        unset($validated['id_document']);

        // Verify current password if changing password
        if ($request->filled('password')) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Current password is required to set a new password.']);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $validated['password'] = $request->password;
        } else {
            unset($validated['password'], $validated['current_password']);
        }

        unset($validated['current_password']);
        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Show followed auctioneers.
     */
    public function following()
    {
        $user = auth()->user();

        $followedAuctioneers = $user->followedAuctioneers()
            ->with(['auctioneer.user', 'auctioneer.auctions' => function ($q) {
                $q->whereIn('status', ['live', 'upcoming'])
                  ->orderBy('start_time')
                  ->limit(3);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('dashboard.following', compact('followedAuctioneers'));
    }
}
