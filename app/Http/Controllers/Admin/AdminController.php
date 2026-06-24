<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Auctioneer;
use App\Models\Auction;
use App\Models\Transaction;
use App\Models\CreditTransaction;
use App\Models\ActivityLog;
use App\Models\Payout;
use App\Models\PromoCode;
use App\Models\SalesRecord;
use App\Models\CommunityRegion;
use App\Services\FacebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Admin dashboard.
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'bidders' => User::where('role', 'bidder')->count(),
            'auctioneers' => User::where('role', 'auctioneer')->count(),
            'activated_auctioneers' => Auctioneer::activated()->count(),
            'white_label_auctioneers' => Auctioneer::where('white_label_enabled', true)->whereNotNull('brand_primary_color')->count(),
            'total_auctions' => Auction::count(),
            'live_auctions' => Auction::live()->count(),
            'upcoming_auctions' => Auction::upcoming()->count(),
            'platform_revenue' => Transaction::where('status', 'completed')->sum('platform_fee'),
            'total_bids' => \App\Models\Bid::count(),
            'pending_payments' => Transaction::where('status', 'pending')->count(),
            'pending_payouts' => Payout::where('status', 'pending')->count(),
        ];

        // Recent activity
        $recentActivity = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentActivity', 'recentTransactions'));
    }

    /**
     * List all users.
     */
    public function users(Request $request)
    {
        $query = User::query()
            ->with('auctioneer')
            ->withCount(['bids', 'wonLots'])
            ->addSelect(['total_spend' => \App\Models\Lot::selectRaw('COALESCE(SUM(current_bid), 0)')
                ->whereColumn('winning_bidder_id', 'users.id')
                ->where('status', 'sold'),
            ])
            ->addSelect(['last_bid_at' => \App\Models\Bid::select('created_at')
                ->whereColumn('user_id', 'users.id')
                ->orderByDesc('created_at')
                ->limit(1),
            ]);

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'suspended') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->appends(request()->query());

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show single user.
     */
    public function showUser(User $user)
    {
        $user->load([
            'auctioneer.auctions',
            'bids.lot.auction',
            'wonLots.auction',
            'transactions',
            'followedAuctioneers.auctioneer',
        ]);

        // Bidder stats
        $bidderStats = null;
        if ($user->role === 'bidder') {
            $bidderStats = [
                'total_spend' => $user->wonLots->where('status', 'sold')->sum('current_bid'),
                'following_count' => $user->followedAuctioneers->count(),
                'auctions_participated' => $user->bids->pluck('lot.event_id')->unique()->count(),
            ];
        }

        return view('admin.users.show', compact('user', 'bidderStats'));
    }

    /**
     * Quick stats JSON for bidder details modal.
     */
    public function userQuickStats(User $user)
    {
        $user->load([
            'bids.lot.auction',
            'wonLots.auction',
            'followedAuctioneers',
        ]);

        $wonLots = $user->wonLots->sortByDesc('updated_at')->map(function ($lot) {
            if ($lot->payment_status === 'paid_platform') {
                $paymentStatus = 'paid';
                $paymentLabel = 'Paid';
            } elseif ($lot->payment_status === 'awaiting_collection') {
                $paymentStatus = 'collection';
                $paymentLabel = 'Collection';
            } elseif ($lot->payment_status === 'paid_offline') {
                $paymentStatus = 'offline';
                $paymentLabel = 'Offline';
            } else {
                $paymentStatus = 'unpaid';
                $paymentLabel = 'Unpaid';
            }

            return [
                'id' => $lot->id,
                'title' => $lot->title,
                'auction' => $lot->auction->title ?? 'N/A',
                'price' => formatCurrency($lot->current_bid),
                'date' => $lot->updated_at->format('M d, Y'),
                'payment_status' => $paymentStatus,
                'payment_label' => $paymentLabel,
            ];
        })->values();

        $recentBids = $user->bids->sortByDesc('created_at')->take(10)->map(function ($bid) use ($user) {
            return [
                'id' => $bid->id,
                'lot_title' => $bid->lot->title,
                'auction' => $bid->lot->auction->title ?? 'N/A',
                'amount' => formatCurrency($bid->amount),
                'date' => $bid->created_at->format('M d, Y H:i'),
                'is_winning' => $bid->lot->winning_bidder_id === $user->id,
            ];
        })->values();

        return response()->json([
            'auctions_participated' => $user->bids->pluck('lot.event_id')->unique()->count(),
            'following_count' => $user->followedAuctioneers->count(),
            'won_lots' => $wonLots,
            'recent_bids' => $recentBids,
        ]);
    }

    /**
     * Update user.
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,auctioneer,bidder'],
            'is_active' => ['boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', 'User updated successfully!');
    }

    /**
     * Delete user.
     */
    public function deleteUser(User $user)
    {
        // Can't delete yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Suspend user.
     */
    public function suspendUser(User $user)
    {
        $user->update(['is_active' => false]);

        ActivityLog::log(
            'user_suspended',
            "User {$user->name} suspended by admin",
            $user
        );

        return back()->with('success', 'User suspended successfully.');
    }

    /**
     * Activate user.
     */
    public function activateUser(User $user)
    {
        $user->update(['is_active' => true]);

        ActivityLog::log(
            'user_activated',
            "User {$user->name} activated by admin",
            $user
        );

        return back()->with('success', 'User activated successfully.');
    }

    /**
     * Stream a user's ID document to the admin browser (private storage).
     */
    public function viewIdDocument(User $user)
    {
        if (!$user->id_document || !Storage::disk('local')->exists($user->id_document)) {
            abort(404, 'No ID document on file.');
        }

        $mime = Storage::disk('local')->mimeType($user->id_document);
        return response()->stream(
            fn () => fpassthru(Storage::disk('local')->readStream($user->id_document)),
            200,
            ['Content-Type' => $mime, 'Content-Disposition' => 'inline']
        );
    }

    /**
     * Mark a user's ID document as verified.
     */
    public function verifyId(User $user)
    {
        if (!$user->id_document) {
            return back()->with('error', 'No ID document uploaded.');
        }

        $user->forceFill(['id_verified_at' => now()])->save();

        ActivityLog::log('id_verified', "ID document verified for {$user->name}", $user);

        return back()->with('success', "{$user->name}'s ID has been verified.");
    }

    /**
     * Remove ID verification (e.g. document expired or replaced).
     */
    public function unverifyId(User $user)
    {
        $user->forceFill(['id_verified_at' => null])->save();

        ActivityLog::log('id_unverified', "ID verification removed for {$user->name}", $user);

        return back()->with('success', 'Verification removed.');
    }

    /**
     * List all auctioneers.
     */
    public function auctioneers(Request $request)
    {
        $query = Auctioneer::query()->with('user');

        // Filter by activation status
        if ($request->filled('status')) {
            if ($request->status === 'activated') {
                $query->where('is_activated', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_activated', false);
            }
        }

        // Filter by white-label status
        if ($request->filled('white_label')) {
            if ($request->white_label === 'enabled') {
                $query->where('white_label_enabled', true)
                      ->whereNotNull('brand_primary_color');
            } elseif ($request->white_label === 'configured_not_active') {
                $query->where(function ($q) {
                    $q->where('white_label_enabled', false)
                      ->whereNotNull('brand_primary_color');
                });
            } elseif ($request->white_label === 'none') {
                $query->where(function ($q) {
                    $q->where('white_label_enabled', false)
                      ->orWhereNull('brand_primary_color');
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $auctioneers = $query->orderBy('created_at', 'desc')->paginate(20)->appends(request()->query());

        return view('admin.auctioneers.index', compact('auctioneers'));
    }

    /**
     * Auctioneer financial report — auction-level breakdown.
     */
    public function showAuctioneer(Auctioneer $auctioneer)
    {
        $auctioneer->load('user');

        // Get all auctions with lot stats (include archived auctions and soft-deleted lots for accurate financial history)
        $auctions = $auctioneer->auctions()->withTrashed()
            ->withCount([
                'lots' => fn($q) => $q->withTrashed(),
                'lots as lots_sold_count' => fn($q) => $q->withTrashed()->where('status', 'sold'),
                'lots as lots_unsold_no_bids_count' => fn($q) => $q->withTrashed()->where('status', 'unsold')->where('total_bids', 0),
                'lots as lots_unsold_reserve_count' => fn($q) => $q->withTrashed()->where('status', 'unsold')->where('total_bids', '>', 0),
            ])
            ->withSum(['lots as total_sales' => fn($q) => $q->withTrashed()->where('status', 'sold')], 'current_bid')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get lot fees and commissions grouped by lot from CreditTransactions
        $auctionLotIds = [];
        foreach ($auctions as $auction) {
            $lotIds = $auction->lots()->pluck('id')->toArray();
            $auctionLotIds[$auction->id] = $lotIds;
        }
        $allLotIds = collect($auctionLotIds)->flatten()->all();

        $creditsByLot = [];
        if (!empty($allLotIds)) {
            $rows = CreditTransaction::where('auctioneer_id', $auctioneer->id)
                ->whereIn('type', ['lot_live', 'lot_close'])
                ->whereNotNull('lot_id')
                ->whereIn('lot_id', $allLotIds)
                ->selectRaw('lot_id, type, SUM(ABS(amount)) as total')
                ->groupBy('lot_id', 'type')
                ->get();

            foreach ($rows as $row) {
                $creditsByLot[$row->lot_id][$row->type] = $row->total;
            }
        }

        // Map credits to per-auction totals
        $auctionFinancials = [];
        foreach ($auctions as $auction) {
            $lotFees = 0;
            $commission = 0;
            foreach ($auctionLotIds[$auction->id] ?? [] as $lotId) {
                $lotFees += $creditsByLot[$lotId]['lot_live'] ?? 0;
                $commission += $creditsByLot[$lotId]['lot_close'] ?? 0;
            }
            $totalSales = $auction->total_sales ?? 0;
            $auctionFinancials[$auction->id] = [
                'lot_fees' => $lotFees,
                'commission' => $commission,
                'net' => $totalSales - $commission,
                'sell_through' => $auction->lots_count > 0
                    ? round(($auction->lots_sold_count / $auction->lots_count) * 100, 1)
                    : 0,
                'avg_sale' => $auction->lots_sold_count > 0
                    ? $totalSales / $auction->lots_sold_count
                    : 0,
            ];
        }

        // Summary stats
        $stats = [
            'total_auctions' => $auctions->count(),
            'total_lots' => $auctions->sum('lots_count'),
            'total_sold' => $auctions->sum('lots_sold_count'),
            'total_sales' => $auctions->sum('total_sales'),
            'total_lot_fees' => abs($auctioneer->creditTransactions()->where('type', 'lot_live')->sum('amount')),
            'total_commissions' => abs($auctioneer->creditTransactions()->where('type', 'lot_close')->sum('amount')),
            'total_payouts' => abs($auctioneer->creditTransactions()->where('type', 'payout')->sum('amount')),
            'total_sale_income' => $auctioneer->creditTransactions()->where('type', 'sale_income')->sum('amount'),
            'credit_balance' => $auctioneer->credit_balance,
        ];

        return view('admin.auctioneers.show', compact('auctioneer', 'auctions', 'stats', 'auctionFinancials'));
    }

    /**
     * Per-auction lot-level financial report.
     */
    public function auctioneerAuctionReport(Auctioneer $auctioneer, Auction $auction)
    {
        abort_if($auction->auctioneer_id !== $auctioneer->id, 404);

        $auctioneer->load('user');

        // Auto-fix: close any lots still in non-terminal status for ended auctions
        if ($auction->status === 'ended') {
            $orphaned = $auction->lots()->whereNotIn('status', ['sold', 'unsold', 'pending_confirmation'])->get();
            foreach ($orphaned as $lot) {
                $lot->close();
            }
        }

        $lots = $auction->lots()
            ->with('winningBidder')
            ->orderBy('lot_number')
            ->get();

        // Credit transactions for this auction's lots
        $lotIds = $lots->pluck('id')->all();
        $creditsByLot = [];
        if (!empty($lotIds)) {
            $rows = CreditTransaction::where('auctioneer_id', $auctioneer->id)
                ->whereIn('type', ['lot_live', 'lot_close'])
                ->whereNotNull('lot_id')
                ->whereIn('lot_id', $lotIds)
                ->selectRaw('lot_id, type, SUM(ABS(amount)) as total')
                ->groupBy('lot_id', 'type')
                ->get();

            foreach ($rows as $row) {
                $creditsByLot[$row->lot_id][$row->type] = $row->total;
            }
        }

        $bp = $auction->buyers_premium_percentage;

        // Build per-lot financials
        $lotFinancials = [];
        foreach ($lots as $lot) {
            $hammer = $lot->isSold() ? (float) $lot->current_bid : 0;
            $buyersPremium = $hammer * ($bp / 100);
            $totalDue = $hammer + $buyersPremium;
            $lotFee = $creditsByLot[$lot->id]['lot_live'] ?? 0;
            $commission = $creditsByLot[$lot->id]['lot_close'] ?? 0;

            $lotFinancials[$lot->id] = [
                'hammer' => $hammer,
                'buyers_premium' => $buyersPremium,
                'total_due' => $totalDue,
                'lot_fee' => $lotFee,
                'commission' => $commission,
            ];
        }

        // Summary
        $soldLots = $lots->where('status', 'sold');
        $summary = [
            'total_lots' => $lots->count(),
            'sold' => $soldLots->count(),
            'unsold' => $lots->where('status', 'unsold')->count(),
            'active' => $lots->where('status', 'active')->count(),
            'pending' => $lots->where('status', 'pending')->count(),
            'total_sales' => $soldLots->sum('current_bid'),
            'total_buyers_premium' => $soldLots->sum(fn($l) => (float) $l->current_bid * ($bp / 100)),
            'total_lot_fees' => collect($lotFinancials)->sum('lot_fee'),
            'total_commission' => collect($lotFinancials)->sum('commission'),
        ];
        $summary['total_due'] = $summary['total_sales'] + $summary['total_buyers_premium'];
        $summary['net'] = $summary['total_sales'] - $summary['total_commission'];

        return view('admin.auctioneers.auction-report', compact(
            'auctioneer', 'auction', 'lots', 'lotFinancials', 'summary'
        ));
    }

    /**
     * Auctioneer settings — pricing, credits, danger zone.
     */
    public function auctioneerSettings(Auctioneer $auctioneer)
    {
        $auctioneer->load([
            'user',
            'auctions' => fn($q) => $q->withCount('lots'),
            'payouts',
        ]);

        return view('admin.auctioneers.settings', compact('auctioneer'));
    }

    /**
     * Full credit ledger for a specific auctioneer (admin view).
     */
    public function auctioneerCreditLedger(Request $request, Auctioneer $auctioneer)
    {
        $auctioneer->load('user');

        $query = $auctioneer->creditTransactions()->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $creditTransactions = $query->paginate(50)->withQueryString();

        return view('admin.auctioneers.credit-ledger', compact('auctioneer', 'creditTransactions'));
    }

    /**
     * Update auctioneer pricing controls (admin only).
     */
    public function updateAuctioneerPricing(Request $request, Auctioneer $auctioneer)
    {
        $validated = $request->validate([
            'is_free_account' => ['nullable', 'boolean'],
            'custom_lot_fee' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_basic' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_pro' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_premium' => ['nullable', 'numeric', 'min:0'],
            'pricing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Convert checkbox to boolean (null if not checked)
        $validated['is_free_account'] = $request->has('is_free_account');

        // If free account is enabled, clear all custom pricing
        if ($validated['is_free_account']) {
            $validated['custom_lot_fee'] = null;
            $validated['custom_tier_basic'] = null;
            $validated['custom_tier_pro'] = null;
            $validated['custom_tier_premium'] = null;
        }

        // If custom flat fee is set, clear per-tier prices (flat fee takes priority)
        if (!empty($validated['custom_lot_fee'])) {
            $validated['custom_tier_basic'] = null;
            $validated['custom_tier_pro'] = null;
            $validated['custom_tier_premium'] = null;
        }

        $auctioneer->update($validated);

        // Log the change
        ActivityLog::log(
            'update_auctioneer_pricing',
            "Updated pricing for {$auctioneer->business_name}: " .
                ($validated['is_free_account'] ? 'Free Account' :
                ($validated['custom_lot_fee'] ? 'Custom Fee: R' . $validated['custom_lot_fee'] :
                ($validated['custom_tier_basic'] !== null || $validated['custom_tier_pro'] !== null || $validated['custom_tier_premium'] !== null
                    ? 'Custom Tiers: R' . ($validated['custom_tier_basic'] ?? 'std') . '/R' . ($validated['custom_tier_pro'] ?? 'std') . '/R' . ($validated['custom_tier_premium'] ?? 'std')
                    : 'Standard Pricing'))),
            $auctioneer
        );

        return back()->with('success', 'Pricing settings updated successfully.');
    }

    /**
     * Add credits to auctioneer account (admin only).
     */
    public function addAuctioneerCredits(Request $request, Auctioneer $auctioneer)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        // Add credits using the model method
        $auctioneer->addCredits(
            (float) $validated['amount'],
            'adjustment',
            "Admin: {$validated['description']} (by " . auth()->user()->name . ")"
        );

        // Log the action
        ActivityLog::log(
            'add_auctioneer_credits',
            "Added " . formatCurrency($validated['amount']) . " credits to {$auctioneer->business_name}: {$validated['description']}",
            $auctioneer
        );

        return back()->with('success', "Successfully added " . formatCurrency($validated['amount']) . " credits.");
    }

    /**
     * Update free relist reset settings for an auctioneer.
     */
    public function updateRelistSettings(Request $request, Auctioneer $auctioneer)
    {
        $validated = $request->validate([
            'free_relist_reset' => ['nullable', 'in:weekly,biweekly,monthly'],
        ]);

        $auctioneer->update([
            'free_relist_reset' => $validated['free_relist_reset'],
        ]);

        ActivityLog::log(
            'update_relist_settings',
            "Updated free relist reset for {$auctioneer->business_name}: " . ($validated['free_relist_reset'] ?? 'disabled'),
            $auctioneer
        );

        return back()->with('success', 'Relist reset settings updated.');
    }

    /**
     * List all auctions.
     */
    public function auctions(Request $request)
    {
        $query = Auction::query()->with(['auctioneer.user', 'lots', 'communityRegion']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by auction type
        if ($request->filled('type')) {
            $query->where('auction_type', $request->type);
        }

        // Filter by community flag
        if ($request->filled('community')) {
            $query->where('is_community', (bool) $request->community);
        }

        // Filter by community region
        if ($request->filled('region_id')) {
            $query->where('community_region_id', $request->region_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $auctions = $query->orderBy('start_time', 'desc')->paginate(20)->withQueryString();

        $regions = \Schema::hasTable('community_regions')
            ? \App\Models\CommunityRegion::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.auctions.index', compact('auctions', 'regions'));
    }

    /**
     * Delete auction (admin override).
     */
    public function deleteAuction(Auction $auction)
    {
        $auction->delete();

        return redirect()->route('admin.auctions.index')
            ->with('success', 'Auction deleted successfully.');
    }

    /**
     * List all transactions.
     */
    public function transactions(Request $request)
    {
        $query = Transaction::query()->with('user');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20)->appends(request()->query());

        // Calculate summary
        $summary = [
            'completed_value' => Transaction::where('status', 'completed')->sum('amount'),
            'pending_value' => Transaction::where('status', 'pending')->sum('amount'),
        ];

        return view('admin.transactions.index', compact('transactions', 'summary'));
    }

    /**
     * Show single transaction.
     */
    public function showTransaction(Transaction $transaction)
    {
        $transaction->load('user');

        return view('admin.transactions.show', compact('transaction'));
    }

    /**
     * Revenue dashboard.
     */
    public function revenue(Request $request)
    {
        $period = $request->get('period', 'all');

        // Calculate date range
        $query = Transaction::where('status', 'completed');

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->startOfYear());
                break;
            // 'all' - no filter
        }

        // Calculate revenue stats
        // Lot fees are tracked in CreditTransaction with type='lot_live'
        $lotFeeQuery = CreditTransaction::query();

        // Apply same date filter to credit transactions
        switch ($period) {
            case 'today':
                $lotFeeQuery->whereDate('created_at', today());
                break;
            case 'week':
                $lotFeeQuery->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $lotFeeQuery->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'year':
                $lotFeeQuery->where('created_at', '>=', now()->startOfYear());
                break;
        }

        $lotFeeTransactions = (clone $lotFeeQuery)->where('type', 'lot_live');
        $platformCommissions = (clone $lotFeeQuery)->where('type', 'lot_close');

        $revenue = [
            'lot_fees' => abs($lotFeeTransactions->sum('amount')),
            'lots_created' => $lotFeeTransactions->count(),
            'platform_fees' => abs($platformCommissions->sum('amount')),
            'lots_sold' => \App\Models\Lot::where('status', 'sold')->count(),
        ];

        // Calculate total revenue
        $revenue['total'] = $revenue['lot_fees'] + $revenue['platform_fees'];

        // Revenue by auctioneer — single query grouped by auctioneer + type
        $creditByAuctioneer = CreditTransaction::whereIn('type', ['lot_live', 'lot_close'])
            ->when($period === 'today',  fn($q) => $q->whereDate('created_at', today()))
            ->when($period === 'week',   fn($q) => $q->where('created_at', '>=', now()->startOfWeek()))
            ->when($period === 'month',  fn($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->when($period === 'year',   fn($q) => $q->where('created_at', '>=', now()->startOfYear()))
            ->selectRaw('auctioneer_id, type, SUM(ABS(amount)) as total')
            ->groupBy('auctioneer_id', 'type')
            ->get()
            ->groupBy('auctioneer_id');

        $auctioneerRevenue = Auctioneer::with('user')
            ->get()
            ->map(function ($auctioneer) use ($creditByAuctioneer) {
                $txs = $creditByAuctioneer->get($auctioneer->id, collect());
                $auctioneer->lot_fees_revenue    = $txs->where('type', 'lot_live')->sum('total');
                $auctioneer->commissions_revenue = $txs->where('type', 'lot_close')->sum('total');
                $auctioneer->total_revenue       = $auctioneer->lot_fees_revenue + $auctioneer->commissions_revenue;
                return $auctioneer;
            })
            ->sortByDesc('total_revenue')
            ->values();

        $topAuctioneers = $auctioneerRevenue->filter(fn($a) => $a->total_revenue > 0)->take(5);

        // Recent high-value transactions
        $highValueTransactions = Transaction::with('user')
            ->where('status', 'completed')
            ->where('amount', '>', 100)
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        return view('admin.revenue', compact('revenue', 'topAuctioneers', 'auctioneerRevenue', 'highValueTransactions'));
    }

    /**
     * Activity logs.
     */
    public function activity(Request $request)
    {
        $query = ActivityLog::query()->with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by period
        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(50)->appends(request()->query());

        // Get all users for filter dropdown
        $users = User::orderBy('name')->get();

        return view('admin.activity', compact('activities', 'users'));
    }

    /**
     * Show broadcast email form.
     */
    public function showBroadcast()
    {
        $stats = [
            'total_users'           => User::where('is_active', true)->count(),
            'bidders'               => User::where('role', 'bidder')->where('is_active', true)->count(),
            'auctioneers'           => User::where('role', 'auctioneer')->where('is_active', true)->count(),
            'active_bidders'        => User::where('role', 'bidder')->where('is_active', true)->whereHas('bids')->count(),
            'activated_auctioneers' => User::where('role', 'auctioneer')->where('is_active', true)
                ->whereHas('auctioneer', fn ($q) => $q->where('is_activated', true))->count(),
        ];

        $auctioneers = Auctioneer::withCount('followers')
            ->orderBy('business_name')
            ->get(['id', 'business_name', 'followers_count']);

        $communities = CommunityRegion::withCount('users')
            ->having('users_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $previousBroadcasts = collect([]);

        return view('admin.broadcast', compact('stats', 'auctioneers', 'communities', 'previousBroadcasts'));
    }

    /**
     * Send an email via configured SMTP mailer.
     */
    private function sendViaResend(string $to, string $subject, string $htmlBody): bool
    {
        Mail::html($htmlBody, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });

        return true;
    }

    /**
     * Render broadcast email to HTML.
     */
    private function renderBroadcast(User $user, string $subject, string $message): string
    {
        $personalised = str_replace(
            ['{name}', '{email}', '{platform_name}'],
            [$user->name, $user->email, config('app.name')],
            $message
        );

        return view('emails.broadcast', [
            'user'             => $user,
            'broadcastSubject' => $subject,
            'broadcastMessage' => $personalised,
        ])->render();
    }

    /**
     * Send broadcast email.
     */
    public function sendBroadcast(Request $request)
    {
        // Single-user quick mail (from user list page)
        if ($request->filled('user_id')) {
            $request->validate([
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ]);

            $user = User::findOrFail($request->input('user_id'));

            try {
                $html = $this->renderBroadcast($user, $request->input('subject'), $request->input('message'));

                if ($this->sendViaResend($user->email, $request->input('subject'), $html)) {
                    return redirect()->back()->with('success', "Email sent to {$user->name} ({$user->email}).");
                }

                return redirect()->back()->with('error', "Failed to send email to {$user->email}. Check Resend API.");
            } catch (\Exception $e) {
                return redirect()->back()->with('error', "Send failed: " . $e->getMessage());
            }
        }

        $request->validate([
            'subject'      => ['required', 'string', 'max:255'],
            'message'      => ['required', 'string'],
            'recipients'   => ['required', 'array'],
            'recipients.*' => ['required', 'in:all_users,bidders,auctioneers,active_bidders,activated_auctioneers'],
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');
        $groups  = $request->input('recipients');

        // Collect unique user IDs across all selected groups
        $userIds = collect();

        foreach ($groups as $group) {
            switch ($group) {
                case 'all_users':
                    $userIds = $userIds->merge(User::where('is_active', true)->pluck('id'));
                    break;
                case 'bidders':
                    $userIds = $userIds->merge(User::where('role', 'bidder')->where('is_active', true)->pluck('id'));
                    break;
                case 'auctioneers':
                    $userIds = $userIds->merge(User::where('role', 'auctioneer')->where('is_active', true)->pluck('id'));
                    break;
                case 'active_bidders':
                    $userIds = $userIds->merge(
                        User::where('role', 'bidder')->where('is_active', true)->whereHas('bids')->pluck('id')
                    );
                    break;
                case 'activated_auctioneers':
                    $userIds = $userIds->merge(
                        User::where('role', 'auctioneer')->where('is_active', true)
                            ->whereHas('auctioneer', fn ($q) => $q->where('is_activated', true))
                            ->pluck('id')
                    );
                    break;
            }
        }

        $users = User::whereIn('id', $userIds->unique()->values())->get();

        // Give enough time for bulk send (SMTP per email)
        set_time_limit(120);

        // Optional test send to admin first
        if ($request->boolean('send_test')) {
            try {
                $html = $this->renderBroadcast(auth()->user(), "[TEST] {$subject}", $message);
                if (!$this->sendViaResend(auth()->user()->email, "[TEST] {$subject}", $html)) {
                    return back()->withInput()->with('error', 'Test email failed. Check your Resend API key.');
                }
            } catch (\Exception $e) {
                return back()->withInput()->with('error', 'Test email failed: ' . $e->getMessage());
            }
        }

        $sent   = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $html = $this->renderBroadcast($user, $subject, $message);
                if ($this->sendViaResend($user->email, $subject, $html)) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        ActivityLog::log(
            'broadcast_sent',
            'Broadcast email sent to: ' . implode(', ', $groups),
            null,
            ['subject' => $subject, 'sent' => $sent, 'failed' => $failed]
        );

        $msg = "Broadcast sent to {$sent} user(s).";
        if ($failed > 0) {
            $msg .= " {$failed} could not be delivered.";
        }

        return back()->with('success', $msg);
    }

    /**
     * List all payout requests
     */
    public function payouts(Request $request)
    {
        $query = Payout::with('auctioneer.user');

        // Filter by status
        $status = $request->get('status', 'pending');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Sort
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'highest':
                $query->orderBy('amount', 'desc');
                break;
            case 'lowest':
                $query->orderBy('amount', 'asc');
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
        }

        $payouts = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'pending_count' => Payout::where('status', 'pending')->count(),
            'pending_amount' => Payout::where('status', 'pending')->sum('amount'),
            'processing_count' => Payout::where('status', 'processing')->count(),
            'completed_count' => Payout::where('status', 'completed')->count(),
            'completed_amount' => Payout::where('status', 'completed')->sum('amount'),
            'total_liability' => Auctioneer::sum('credit_balance'),
        ];

        return view('admin.payouts.index', compact('payouts', 'stats', 'status'));
    }

    /**
     * Show payout details
     */
    public function showPayout(Payout $payout)
    {
        $payout->load('auctioneer.user', 'processedBy');

        // Get auctioneer's sales history
        $salesRecords = $payout->auctioneer->salesRecords()
            ->with('lot.auction')
            ->orderBy('paid_date', 'desc')
            ->limit(10)
            ->get();

        return view('admin.payouts.show', compact('payout', 'salesRecords'));
    }

    /**
     * Process (approve) a payout
     */
    public function processPayout(Request $request, Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Only pending payouts can be processed.');
        }

        $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check auctioneer has sufficient cleared balance (respects R100 minimum balance reserve)
        $availableBalance = $payout->auctioneer->getAvailablePayoutBalance();
        if ($payout->amount > $availableBalance) {
            $minimumBalance   = config('platform.payout.minimum_balance', 100);
            $pendingClearance = $payout->auctioneer->getPendingClearance();
            return back()->with('error',
                'Auctioneer does not have sufficient funds. '
                . 'Available (after ' . formatCurrency($minimumBalance) . ' reserve): ' . formatCurrency($availableBalance)
                . ($pendingClearance > 0 ? ', Pending clearance: ' . formatCurrency($pendingClearance) . ' (48hr hold).' : '.')
            );
        }

        // Mark as completed
        $payout->markAsCompleted(
            admin: auth()->user(),
            reference: $request->reference,
            notes: $request->notes
        );

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout processed successfully! ' . formatCurrency($payout->amount) . ' will be transferred to ' . $payout->auctioneer->business_name);
    }

    /**
     * Reject a payout request
     */
    public function rejectPayout(Request $request, Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Only pending payouts can be rejected.');
        }

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $payout->update([
            'status' => 'failed',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'notes' => $request->notes,
        ]);

        ActivityLog::log(
            'payout_rejected',
            'Payout request of ' . formatCurrency($payout->amount) . ' was rejected',
            $payout->auctioneer
        );

        return redirect()->route('admin.payouts.index')
            ->with('warning', 'Payout request rejected.');
    }

    /**
     * Post an auction to Facebook (admin manual trigger).
     */
    public function postToFacebook(Auction $auction)
    {
        $postId = app(FacebookService::class)->postAuction($auction, force: true);

        if ($postId) {
            return back()->with('success', "Posted to Facebook successfully (Post ID: {$postId}).");
        }

        return back()->with('error', 'Failed to post to Facebook. Check logs for details.');
    }

    /**
     * Hard delete an auctioneer and all associated data.
     * Demotes the user back to bidder role.
     */
    public function hardDeleteAuctioneer(Request $request, Auctioneer $auctioneer)
    {
        $auctioneer->load('user');

        // Guard: no live or upcoming auctions
        $blockedAuctions = $auctioneer->auctions()
            ->whereIn('status', ['live', 'upcoming', 'pending'])
            ->count();
        if ($blockedAuctions > 0) {
            return back()->with('error', 'Cannot delete: this auctioneer has live or upcoming auctions. End or delete them first.');
        }

        // Guard: no pending payouts
        $pendingPayouts = $auctioneer->payouts()->where('status', 'pending')->count();
        if ($pendingPayouts > 0) {
            return back()->with('error', 'Cannot delete: this auctioneer has pending payout requests. Process or reject them first.');
        }

        // Confirm business name matches
        if ($request->input('confirm_name') !== $auctioneer->business_name) {
            return back()->with('error', 'Business name confirmation does not match.');
        }

        $user = $auctioneer->user;
        $businessName = $auctioneer->business_name;

        // Load all lot images for physical file cleanup
        $auctioneer->load('auctions.lots.images');

        // Delete lot images (physical files)
        $auctioneer->auctions->each(function ($auction) {
            $auction->lots->each(function ($lot) {
                $lot->images->each(function ($image) {
                    $image->delete(); // LotImage::delete() handles physical file removal
                });
            });
        });

        // Delete auctioneer profile images
        foreach (['logo', 'profile_image', 'banner_image'] as $imageField) {
            if ($auctioneer->$imageField) {
                Storage::disk('public')->delete($auctioneer->$imageField);
            }
        }

        // Force delete the auctioneer (bypasses soft delete)
        // Cascade will handle: auctions, lots, bids, credit_transactions, etc.
        $auctioneer->forceDelete();

        // Demote user to bidder
        $user->update(['role' => 'bidder']);

        // Log the action
        ActivityLog::log(
            'auctioneer_hard_deleted',
            "Auctioneer '{$businessName}' permanently deleted by admin " . auth()->user()->name . ". User {$user->name} demoted to bidder.",
            $user
        );

        return redirect()->route('admin.auctioneers.index')
            ->with('success', "Auctioneer '{$businessName}' has been permanently deleted. User {$user->name} has been demoted to bidder.");
    }

    /**
     * List all promo codes.
     */
    public function promoCodes()
    {
        $promoCodes = PromoCode::withCount('auctioneers')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    /**
     * Show create promo code form.
     */
    public function createPromoCode()
    {
        return view('admin.promo-codes.create');
    }

    /**
     * Store a new promo code.
     */
    public function storePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:promo_codes,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_free_account' => ['nullable', 'boolean'],
            'custom_lot_fee' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_basic' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_pro' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_premium' => ['nullable', 'numeric', 'min:0'],
            'free_relist_reset' => ['nullable', 'in:weekly,biweekly,monthly'],
            'bonus_credits' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_free_account'] = $request->boolean('is_free_account');
        $validated['bonus_credits'] = $validated['bonus_credits'] ?? 0;

        PromoCode::create($validated);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code "' . $validated['code'] . '" created successfully.');
    }

    /**
     * Show edit promo code form.
     */
    public function editPromoCode(PromoCode $promoCode)
    {
        $promoCode->load(['auctioneers.user']);

        return view('admin.promo-codes.edit', compact('promoCode'));
    }

    /**
     * Update a promo code.
     */
    public function updatePromoCode(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:promo_codes,code,' . $promoCode->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_free_account' => ['nullable', 'boolean'],
            'custom_lot_fee' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_basic' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_pro' => ['nullable', 'numeric', 'min:0'],
            'custom_tier_premium' => ['nullable', 'numeric', 'min:0'],
            'free_relist_reset' => ['nullable', 'in:weekly,biweekly,monthly'],
            'bonus_credits' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_free_account'] = $request->boolean('is_free_account');
        $validated['bonus_credits'] = $validated['bonus_credits'] ?? 0;

        $promoCode->update($validated);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code "' . $validated['code'] . '" updated successfully.');
    }

    /**
     * Toggle promo code active/inactive.
     */
    public function togglePromoCode(PromoCode $promoCode)
    {
        $promoCode->update(['is_active' => !$promoCode->is_active]);

        $status = $promoCode->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Promo code \"{$promoCode->code}\" {$status}.");
    }
}
