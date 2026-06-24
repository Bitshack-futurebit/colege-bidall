<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionRegistration;
use App\Models\CreditTransaction;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\WhiteLabelContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuctionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Show all public auctions.
     */
    public function index(Request $request)
    {
        $query = Auction::query()
            ->where(function ($q) {
                $q->whereIn('status', ['live', 'upcoming', 'ended'])
                  ->orWhere(fn ($q2) => $q2->where('status', 'draft')->where('is_community', true));
            })
            ->with('auctioneer.user')
            ->withCount('lots');

        // White-label: scope to auctioneer's auctions only
        $whiteLabel = app(WhiteLabelContext::class);
        if ($whiteLabel->isActive()) {
            $query->where('auctioneer_id', $whiteLabel->auctioneer()->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'upcoming') {
                // "Upcoming" from a bidder's POV includes community drafts (lineup still open)
                $query->where(function ($q) {
                    $q->where('status', 'upcoming')
                      ->orWhere(fn ($q2) => $q2->where('status', 'draft')->where('is_community', true));
                });
            } else {
                $query->where('status', $request->status);
            }
        } else {
            // Default: show live, upcoming, recently ended (last 24h), and community drafts
            $query->where(function($q) {
                $q->whereIn('status', ['live', 'upcoming'])
                  ->orWhere(function($q2) {
                      $q2->where('status', 'ended')
                         ->where('end_time', '>=', now()->subDay());
                  })
                  ->orWhere(function($q3) {
                      $q3->where('status', 'draft')->where('is_community', true);
                  });
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort — default puts live first, then upcoming + community drafts (soonest first), then ended last
        if ($request->filled('sort')) {
            $query->orderBy($request->get('sort'), $request->get('order', 'asc'));
        } else {
            $query->orderByRaw("FIELD(status, 'live', 'upcoming', 'draft', 'ended')")
                  ->orderBy('start_time', 'asc');
        }

        $auctions = $query->paginate(12);

        return view('auctions.index', compact('auctions'));
    }

    /**
     * Show seller's auctions list.
     */
    public function sellerIndex()
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        // Get auction counts by status in a single query
        $statusCounts = Auction::where('auctioneer_id', $auctioneer->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'draft' => $statusCounts['draft'] ?? 0,
            'upcoming' => $statusCounts['upcoming'] ?? 0,
            'live' => $statusCounts['live'] ?? 0,
            'ended' => $statusCounts['ended'] ?? 0,
        ];

        // Filter by tab if requested
        $query = Auction::where('auctioneer_id', $auctioneer->id)->withCount('lots');

        if (request('tab') && request('tab') !== 'all') {
            $query->where('status', request('tab'));
        }

        $auctions = $query->orderBy('created_at', 'desc')->paginate(20);

        $isStaff = $user->isStaff();
        return view('seller.auctions.index', compact('auctions', 'stats', 'isStaff'));
    }

    /**
     * Show single auction (seller view).
     */
    public function sellerShow(Auction $auction)
    {
        $this->authorize('view', $auction);

        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();
        $isStaff = $user->isStaff();
        $isAdmin = $user->isAdmin();

        $auction->load([
            'auctioneer.user',
            'lots' => function ($query) {
                $query->orderBy('lot_number')
                      ->with('images');
            }
        ]);

        // Staff and admins without an auctioneer profile get a simplified view
        if ($isStaff || ($isAdmin && !$auctioneer)) {
            return view('seller.auctions.show', compact('auction', 'isStaff'));
        }

        $auctionCost = $auctioneer->calculateAuctionCost($auction);
        $creditBalance = $auctioneer->credit_balance;
        $lotsByTier = $auction->lots->whereNull('withdrawn_at')->groupBy(fn($lot) => $lot->image_tier ?? 'basic');
        $tierCosts = [
            'basic'   => $auctioneer->calculateLotCost('basic'),
            'pro'     => $auctioneer->calculateLotCost('pro'),
            'premium' => $auctioneer->calculateLotCost('premium'),
        ];
        $canAfford = $creditBalance >= $auctionCost;

        return view('seller.auctions.show', compact('auction', 'auctionCost', 'creditBalance', 'lotsByTier', 'tierCosts', 'canAfford', 'isStaff'));
    }

    /**
     * Show single auction (public view).
     */
    public function show(Auction $auction)
    {
        // Check if user is the auctioneer who owns this auction (or staff/admin)
        $isOwner = false;
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->isAdmin()) {
                $isOwner = true;
            } elseif ($user->isAuctioneer() && $user->auctioneer && $user->auctioneer->id === $auction->auctioneer_id) {
                $isOwner = true;
            } elseif ($user->isStaff() && $user->staffMembership && $user->staffMembership->auctioneer_id === $auction->auctioneer_id) {
                $isOwner = true;
            }
        }

        // Community auctions: redirect to region page when ended (or when draft/upcoming — region page handles those)
        if ($auction->is_community && $auction->communityRegion) {
            if ($auction->status === 'ended') {
                return redirect()->route('community.region', $auction->communityRegion);
            }
        }

        // Only show live, upcoming, and ended auctions publicly (unless owner/admin viewing)
        // Community drafts are also public (no lots required) so the lineup is visible before go-live
        // Owners and admins can view any draft auction
        $publiclyViewable = in_array($auction->status, ['live', 'upcoming', 'ended'])
            || ($auction->status === 'draft' && $auction->is_community);
        if (!$isOwner && !$publiclyViewable) {
            abort(404);
        }

        // Load auction with lots — only images needed for public view
        // (current_bid, total_bids, winning_bidder_id are on the lots table)
        $auction->load([
            'auctioneer.user',
            'lots' => function ($query) {
                $query->orderBy('lot_number')
                      ->with('images');
            }
        ]);

        // If owner/staff (not admin) is viewing their own auction, redirect to seller view
        if ($isOwner && !auth()->user()->isAdmin()) {
            return redirect()->route('seller.auctions.show', $auction);
        }

        // Check if user is registered (for public view)
        $isRegistered = false;
        $registration = null;

        if (auth()->check()) {
            $registration = AuctionRegistration::where([
                'event_id' => $auction->id,
                'user_id' => auth()->id(),
            ])->first();

            $isRegistered = (bool) $registration;
        }

        // Activity log skipped for auction_viewed — high-frequency page during live auctions

        $watchlistedLotIds = [];
        $showWatchlistButton = false;
        if (auth()->check() && auth()->user()->isBidder() && !$auction->isLiveFormat()) {
            $showWatchlistButton = true;
            $watchlistedLotIds = auth()->user()->watchlist()
                ->whereIn('lot_id', $auction->lots->pluck('id'))
                ->pluck('lot_id')
                ->toArray();
        }

        // For live auctions: has the current user bid on the currently-active lot?
        $activeLiveLotUserHasBid = false;
        if (auth()->check() && $auction->isLiveFormat()) {
            $activeLiveLot = $auction->lots->firstWhere('status', 'live');
            if ($activeLiveLot) {
                $activeLiveLotUserHasBid = $activeLiveLot->bids()
                    ->where('user_id', auth()->id())
                    ->exists();
            }
        }

        return view('auctions.show', compact('auction', 'isRegistered', 'registration', 'watchlistedLotIds', 'showWatchlistButton', 'activeLiveLotUserHasBid'));
    }

    /**
     * Show create auction form.
     */
    public function create()
    {
        return view('seller.auctions.create');
    }

    /**
     * Store new auction.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        $auctionType = $request->input('auction_type', 'english');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_time' => ['required', 'date', 'after:now'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_type' => ['required', 'in:none,refundable,non_refundable'],
            'buyers_premium_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auction_type' => ['required', 'in:english,dutch,sealed,live'],
        ];

        if ($auctionType === 'sealed') {
            $rules['sealed_mode'] = ['required', 'in:highest,lowest'];
            $rules['end_time'] = ['required', 'date', 'after:start_time'];
            $rules['payment_deadline'] = ['nullable', 'date', 'after:end_time'];
        } elseif ($auctionType === 'dutch') {
            $rules['dutch_lot_gap'] = ['required', 'integer', 'min:0'];
            $rules['payment_deadline'] = ['nullable', 'date'];
        } elseif ($auctionType === 'live') {
            // Live: end_time is calculated dynamically from actual lot durations.
            // Optional payment deadline, no upper 12-hour cap (sequential cadence controls it).
            $rules['payment_deadline'] = ['nullable', 'date'];
        } else {
            $rules['end_time'] = ['required', 'date', 'after:start_time'];
            $rules['payment_deadline'] = ['nullable', 'date', 'after:end_time'];
        }

        $validated = $request->validate($rules);

        // Validate maximum 12-hour duration (English only — sealed + live + dutch have no fixed cap)
        if (isset($validated['end_time']) && !in_array($auctionType, ['sealed', 'live', 'dutch'], true)) {
            $startTime = \Carbon\Carbon::parse($request->start_time);
            $endTime = \Carbon\Carbon::parse($request->end_time);
            $durationHours = $startTime->diffInHours($endTime);

            if ($durationHours > 12) {
                return back()->withErrors([
                    'end_time' => 'Auction duration cannot exceed 12 hours. Current duration: ' . $durationHours . ' hours.'
                ])->withInput();
            }
        }

        // Handle checkbox for proxy bidding (English + Live only)
        $validated['allow_proxy_bidding'] = in_array($auctionType, ['english', 'live'], true)
            && $request->has('allow_proxy_bidding');

        // Generate slug
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $count = 1;
        while (Auction::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        $validated['slug'] = $slug;
        $validated['auctioneer_id'] = $auctioneer->id;
        $validated['status'] = 'draft';

        // Set defaults for nullable fields that have non-null database columns
        $validated['buyers_premium_percentage'] = $validated['buyers_premium_percentage'] ?? 0;
        $validated['deposit_amount'] = $validated['deposit_amount'] ?? 0;
        $validated['enable_online_payment'] = $request->has('enable_online_payment') && $auctioneer->hasPayfastConfigured();

        // Dutch auctions: always sequential, end_time auto-calculated at go-live
        if ($auctionType === 'dutch') {
            $validated['dutch_lot_mode'] = 'sequential';
            if (empty($validated['end_time'])) {
                $validated['end_time'] = $validated['start_time'];
            }
        }

        // Live: end_time is a placeholder until the auction plays out. Seed with start_time.
        if ($auctionType === 'live' && empty($validated['end_time'])) {
            $validated['end_time'] = $validated['start_time'];
        }

        $auction = Auction::create($validated);

        return redirect()->route('seller.auctions.show', $auction)
            ->with('success', 'Auction created! Add lots to get started.');
    }

    /**
     * Show edit auction form.
     */
    public function edit(Auction $auction)
    {
        $this->authorize('update', $auction);

        // Dutch auctions can only be edited in draft (schedule is locked once upcoming)
        if ($auction->isDutch() && $auction->status !== 'draft') {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Cannot edit a Dutch auction once it is scheduled.');
        }

        return view('seller.auctions.edit', compact('auction'));
    }

    /**
     * Update auction.
     */
    public function update(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        // Can't edit if live or ended; Dutch auctions also locked once upcoming (schedule is calculated)
        if (in_array($auction->status, ['live', 'ended'])) {
            return back()->with('error', 'Cannot edit live or ended auctions.');
        }
        if ($auction->isDutch() && $auction->status === 'upcoming') {
            return back()->with('error', 'Cannot edit a Dutch auction once it is scheduled. Cancel and recreate if changes are needed.');
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_time' => ['required', 'date'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_type' => ['required', 'in:none,refundable,non_refundable'],
            'buyers_premium_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        if ($auction->isDutch()) {
            $rules['dutch_lot_gap'] = ['required', 'integer', 'min:0'];
            $rules['payment_deadline'] = ['nullable', 'date'];
        } elseif ($auction->isLiveFormat()) {
            $rules['payment_deadline'] = ['nullable', 'date'];
        } else {
            // English and Sealed both have end_time
            $rules['end_time'] = ['required', 'date', 'after:start_time'];
            $rules['payment_deadline'] = ['nullable', 'date', 'after:end_time'];
        }

        $validated = $request->validate($rules);

        // Validate maximum 12-hour duration (English only — sealed/live/dutch have no fixed cap)
        if (isset($validated['end_time']) && !$auction->isSealed() && !$auction->isLiveFormat() && !$auction->isDutch()) {
            $startTime = \Carbon\Carbon::parse($request->start_time);
            $endTime = \Carbon\Carbon::parse($request->end_time);
            $durationHours = $startTime->diffInHours($endTime);

            if ($durationHours > 12) {
                return back()->withErrors([
                    'end_time' => 'Auction duration cannot exceed 12 hours. Current duration: ' . $durationHours . ' hours.'
                ])->withInput();
            }
        }

        // Handle checkbox for proxy bidding (English + Live only)
        $validated['allow_proxy_bidding'] = ($auction->isEnglish() || $auction->isLiveFormat())
            && $request->has('allow_proxy_bidding');

        // Live: keep end_time matching start_time until the auction plays out
        if ($auction->isLiveFormat()) {
            $validated['end_time'] = $validated['start_time'];
        }

        // Set defaults for nullable fields that have non-null database columns
        $validated['buyers_premium_percentage'] = $validated['buyers_premium_percentage'] ?? 0;
        $validated['deposit_amount'] = $validated['deposit_amount'] ?? 0;
        $validated['enable_online_payment'] = $request->has('enable_online_payment') && $auction->auctioneer->hasPayfastConfigured();

        $auction->update($validated);

        // Recalculate Dutch schedule when start time or strategy changes
        if ($auction->isDutch()) {
            $auction->refresh();
            $auction->calculateSequentialSchedule();
        }

        return back()->with('success', 'Auction updated successfully!');
    }

    /**
     * Delete auction (draft only) — hard deletes auction, all lots, and all images.
     */
    public function destroy(Auction $auction)
    {
        $this->authorize('delete', $auction);

        if ($auction->status !== 'draft') {
            return back()->with('error', 'Only draft auctions can be deleted.');
        }

        // Load lots with images for file cleanup
        $auction->load('lots.images');
        $lotCount = $auction->lots->count();
        $title = $auction->title;

        // Delete all image files from storage and their DB records
        foreach ($auction->lots as $lot) {
            foreach ($lot->images as $image) {
                $image->delete(); // Handles physical file deletion + DB record
            }
            $lot->delete();
        }

        // Hard delete (bypasses soft delete)
        $auction->forceDelete();

        $lotLabel = $lotCount === 1 ? 'lot' : 'lots';
        return redirect()->route('seller.auctions.index')
            ->with('success', "\"$title\" and {$lotCount} {$lotLabel} have been permanently deleted.");
    }

    /**
     * Make auction go live.
     */
    public function goLive(Auction $auction)
    {
        $this->authorize('update', $auction);

        if (!$auction->canGoLive()) {
            return back()->with('error', 'Auction cannot go live. Ensure it has lots and is scheduled to start.');
        }

        // Check if auctioneer has enough credits
        $auctioneer = $auction->auctioneer;
        $auctionCost = $auctioneer->calculateAuctionCost($auction);

        if (!$auctioneer->canGoLiveWithAuction($auction)) {
            return back()->with('error', sprintf(
                'Insufficient credits. This auction requires %s in credits. Your balance: %s. Please purchase credits to go live.',
                formatCurrency($auctionCost),
                formatCurrency($auctioneer->credit_balance)
            ));
        }

        try {
            // Deduct credits for all lots (free relists log R0 for audit, no deduction)
            foreach ($auction->lots as $lot) {
                if ($lot->is_free_relist) {
                    \App\Models\CreditTransaction::create([
                        'auctioneer_id' => $auctioneer->id,
                        'lot_id'        => $lot->id,
                        'type'          => 'lot_live',
                        'amount'        => 0,
                        'balance_after' => $auctioneer->credit_balance,
                        'description'   => "Free relist - Lot #{$lot->lot_number} - {$lot->title}",
                    ]);
                    continue;
                }

                $cost = $auctioneer->calculateLotCost($lot->image_tier ?? 'basic');
                $auctioneer->deductCredits(
                    $cost,
                    'lot_live',
                    $lot->id,
                    "Lot #{$lot->lot_number} - {$lot->title}"
                );
            }

            $auction->goLive();

            $auction->refresh(); // Reload to get updated status

            // Post to Facebook in the background (for both live and upcoming) so the
            // seller gets an instant confirmation instead of waiting on the Graph API.
            \App\Jobs\PostAuctionToFacebook::dispatch($auction);

            if ($auction->status === 'live') {
                $message = sprintf(
                    'Auction is now live! %s deducted from your credits. Current balance: %s',
                    formatCurrency($auctionCost),
                    formatCurrency($auctioneer->fresh()->credit_balance)
                );
            } else {
                $message = sprintf(
                    'Auction scheduled! It will go live on %s. %s deducted from your credits. Current balance: %s',
                    $auction->start_time->format('M d, Y \a\t H:i'),
                    formatCurrency($auctionCost),
                    formatCurrency($auctioneer->fresh()->credit_balance)
                );
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start auction: ' . $e->getMessage());
        }
    }

    /**
     * End auction manually.
     */
    public function end(Auction $auction)
    {
        $this->authorize('update', $auction);

        if (!$auction->isLive()) {
            return back()->with('error', 'Only live auctions can be ended.');
        }

        $auction->end();

        return back()->with('success', 'Auction has been ended. Winners will be notified.');
    }

    /**
     * Register for auction (with optional deposit).
     */
    public function register(Request $request, Auction $auction)
    {
        $request->validate([
            'payment_method' => ['nullable', 'in:payfast,blink'],
        ]);

        $user = auth()->user();

        // Check if already registered
        if ($user->isRegisteredForEvent($auction->id)) {
            return back()->with('info', 'You are already registered for this auction.');
        }

        // Check if deposit required
        if ($auction->requiresDeposit()) {
            // Resolve the buyer-selected gateway (Card vs Bitcoin); defaults to PayFast.
            $gateway = app(PaymentGatewayFactory::class)->make($request->input('payment_method') ?: 'payfast');

            // Create deposit payment
            $payment = $gateway->createPayment(
                amount: $auction->deposit_amount,
                user: $user,
                type: 'deposit',
                metadata: [
                    'event_id' => $auction->id,
                    'event_name' => $auction->title,
                    'deposit_type' => $auction->deposit_type,
                ]
            );

            // Store for return handling
            session(['pending_deposit_payment' => $payment['payment_id']]);

            // PayFast needs a POST form submission; other gateways just redirect.
            if (($payment['method'] ?? null) === 'POST') {
                return view('payment.redirect', ['payment' => $payment]);
            }

            return redirect($payment['redirect_url']);
        }

        // No deposit required, register immediately
        AuctionRegistration::create([
            'event_id' => $auction->id,
            'user_id' => $user->id,
            'deposit_paid' => 0,
            'registered_at' => now(),
        ]);

        return back()->with('success', 'Successfully registered for auction!');
    }

    /**
     * Show auction analytics (seller only).
     */
    public function analytics(Auction $auction)
    {
        $this->authorize('view', $auction);

        $auction->load(['lots.bids.user', 'registrations.user']);

        $stats = [
            'total_lots' => $auction->lots()->count(),
            'total_bids' => $auction->total_bids,
            'unique_bidders' => $auction->lots()->distinct('winning_bidder_id')->count('winning_bidder_id'),
            'total_registrations' => $auction->registrations()->count(),
            'lots_sold' => $auction->lots()->where('status', 'sold')->count(),
            'lots_unsold' => $auction->lots()->where('status', 'unsold')->count(),
            'total_revenue' => $auction->total_value,
        ];

        // Top bidders
        $topBidders = $auction->lots()
            ->whereNotNull('winning_bidder_id')
            ->with('winningBidder')
            ->get()
            ->groupBy('winning_bidder_id')
            ->map(function ($lots) {
                $user = $lots->first()->winningBidder;
                return [
                    'user' => $user,
                    'lots_won' => $lots->count(),
                    'total_spent' => $lots->sum('current_bid'),
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10);

        // Bidding activity over time
        $biddingActivity = $auction->lots()
            ->with('bids')
            ->get()
            ->flatMap(fn($lot) => $lot->bids)
            ->groupBy(fn($bid) => $bid->placed_at->format('Y-m-d H:00'))
            ->map(fn($bids) => $bids->count())
            ->sortKeys();

        return view('seller.auctions.analytics', compact('auction', 'stats', 'topBidders', 'biddingActivity'));
    }

    /**
     * Show the live report page for a live auction.
     */
    public function sellerLiveReport(Auction $auction)
    {
        $this->authorize('view', $auction);

        if (!in_array($auction->status, ['live', 'ended'])) {
            abort(404);
        }

        // Build initial data server-side so page loads with data immediately
        $initialData = $this->buildLiveReportData($auction);

        return view('seller.auctions.live-report', compact('auction', 'initialData'));
    }

    /**
     * Return live report data as JSON (for polling).
     */
    public function liveReportData(Auction $auction)
    {
        $this->authorize('view', $auction);

        return response()->json($this->buildLiveReportData($auction));
    }

    private function buildLiveReportData(Auction $auction): array
    {
        $lots = $auction->lots()
            ->orderBy('lot_number')
            ->get();

        $now = now();

        $lotsData = $lots->map(function ($lot) use ($now) {
            $timeRemaining = null;
            if ($lot->end_time && $lot->end_time > $now) {
                $timeRemaining = $lot->end_time->diffForHumans($now, ['parts' => 2, 'short' => true]);
            } elseif ($lot->end_time) {
                $timeRemaining = 'Ended';
            }

            $data = [
                'id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'title' => $lot->title,
                'starting_bid' => (float) $lot->starting_bid,
                'reserve_price' => $lot->reserve_price ? (float) $lot->reserve_price : null,
                'current_bid' => (float) $lot->current_bid,
                'formatted_current_bid' => formatCurrency($lot->current_bid),
                'total_bids' => (int) $lot->total_bids,
                'status' => $lot->status,
                'time_remaining' => $timeRemaining,
            ];

            if ($lot->dutch_start_price) {
                $data['dutch_current_price'] = $lot->getCurrentDutchPrice();
                $data['formatted_current_bid'] = formatCurrency($lot->getCurrentDutchPrice());
                $data['quantity'] = $lot->quantity;
                $data['quantity_sold'] = $lot->quantity_sold;
            }

            return $data;
        });

        $withBids = $lotsData->where('total_bids', '>', 0)->count();
        $noBids = $lotsData->where('total_bids', 0)->count();

        return [
            'lots' => $lotsData->values()->all(),
            'summary' => [
                'total_lots' => $lotsData->count(),
                'with_bids' => $withBids,
                'no_bids' => $noBids,
                'total_current_bids' => formatCurrency($lotsData->where('total_bids', '>', 0)->sum('current_bid')),
                'lots_sold' => $lotsData->where('status', 'sold')->count(),
            ],
        ];
    }

    /**
     * Per-auction financial report for the auctioneer.
     */
    public function sellerAuctionReport(Auction $auction)
    {
        $this->authorize('view', $auction);

        // Auto-fix: close any lots still in non-terminal status for ended auctions
        if ($auction->status === 'ended') {
            $orphaned = $auction->lots()->whereNotIn('status', ['sold', 'unsold', 'pending_confirmation'])->get();
            foreach ($orphaned as $lot) {
                $auction->isDutch() ? $lot->closeDutch() : $lot->close();
            }
        }

        $lots = $auction->lots()
            ->with('winningBidder')
            ->orderBy('lot_number')
            ->get();

        $auctioneer = $auction->auctioneer;

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
            if ($lot->isSold() && $lot->dutch_start_price && $lot->quantity > 1) {
                // Multi-quantity Dutch: total revenue from all buy bids
                $hammer = (float) ($lot->bids()->where('is_dutch_buy', true)->selectRaw('SUM(amount * quantity_bought) as total')->value('total') ?? 0);
            } else {
                $hammer = $lot->isSold() ? (float) $lot->current_bid : 0;
            }
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
            'pending_confirmation' => $lots->where('status', 'pending_confirmation')->count(),
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

        return view('seller.auctions.report', compact(
            'auction', 'lots', 'lotFinancials', 'summary'
        ));
    }
}
