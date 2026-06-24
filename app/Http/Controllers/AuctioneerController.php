<?php

namespace App\Http\Controllers;

use App\Models\Auctioneer;
use App\Models\AuctioneerFollower;
use App\Models\Auction;
use App\Models\Lot;
use App\Models\User;
use App\Models\Transaction;
use App\Models\SalesRecord;
use App\Models\ActivityLog;
use App\Models\Payout;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AuctioneerController extends Controller
{
    /**
     * Serve a dynamic PWA manifest branded for a white-label auctioneer.
     * Returned as application/manifest+json. Cached aggressively; bump when branding changes.
     */
    public function manifest(Auctioneer $auctioneer)
    {
        abort_unless($auctioneer->isWhiteLabel(), 404);

        $icon192 = route('auctioneer.icon', ['auctioneer' => $auctioneer->slug, 'size' => 192]);
        $icon512 = route('auctioneer.icon', ['auctioneer' => $auctioneer->slug, 'size' => 512]);

        $manifest = [
            // Unique id distinguishes this PWA from BidAll (and other white-label PWAs) on the same origin
            'id' => '/auctioneer/' . $auctioneer->slug,
            'name' => $auctioneer->business_name,
            'short_name' => \Illuminate\Support\Str::limit($auctioneer->business_name, 12, ''),
            'start_url' => '/auctioneer/' . $auctioneer->slug,
            // Scope is origin-wide so navigating to /auctions, /lots, /dashboard stays within the installed PWA
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $auctioneer->brand_primary_color,
            'background_color' => '#ffffff',
            'icons' => [
                ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Serve a dynamically-resized PNG icon for a white-label auctioneer.
     * Cached to disk on first request; the cache file is invalidated when the
     * auctioneer's logo changes (cache key includes logo path + mtime).
     */
    public function icon(Auctioneer $auctioneer, int $size)
    {
        abort_unless($auctioneer->isWhiteLabel(), 404);
        abort_unless(in_array($size, [192, 512, 180]), 404);

        $sourcePath = $auctioneer->logo
            ? Storage::disk('public')->path($auctioneer->logo)
            : public_path('icons/icon-' . ($size === 180 ? 192 : $size) . 'x' . ($size === 180 ? 192 : $size) . '.png');

        if (!file_exists($sourcePath)) {
            $sourcePath = public_path('icons/icon-192x192.png');
        }

        // Cache key includes source mtime so logo changes bust the cache automatically
        $cacheKey = 'pwa-icon-' . $auctioneer->id . '-' . $size . '-' . filemtime($sourcePath) . '.png';
        $cachePath = storage_path('app/pwa-icons/' . $cacheKey);

        if (!file_exists($cachePath)) {
            @mkdir(dirname($cachePath), 0755, true);
            $manager = new ImageManager(new Driver());
            $img = $manager->read($sourcePath);
            $img->contain($size, $size, 'ffffff');
            $img->save($cachePath);
        }

        return response()
            ->file($cachePath, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=604800, immutable',
            ]);
    }

    /**
     * Show public auctioneers directory (regular auctioneers only).
     */
    public function index()
    {
        $auctioneers = Auctioneer::activated()
            ->notCommunity()
            ->whereHas('user')
            ->with('user:id,city,province')
            ->withCount(['auctions' => function ($q) {
                $q->whereIn('status', ['live', 'upcoming']);
            }])
            ->orderByDesc('created_at')
            ->get();

        return view('auctioneer.index', compact('auctioneers'));
    }

    /**
     * Show public communities directory.
     */
    public function communities()
    {
        $regions = \App\Models\CommunityRegion::active()
            ->withCount(['users as member_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        // Attach the latest/current auction for each region
        $regionIds = $regions->pluck('id');
        $latestAuctions = \App\Models\Auction::whereIn('community_region_id', $regionIds)
            ->whereIn('status', ['draft', 'upcoming', 'live'])
            ->orderByRaw("FIELD(status, 'live', 'upcoming', 'draft')")
            ->get()
            ->groupBy('community_region_id')
            ->map(fn ($group) => $group->first());

        return view('community.listing', compact('regions', 'latestAuctions'));
    }

    /**
     * Show public auctioneer profile.
     */
    public function show(Auctioneer $auctioneer)
    {
        if (!$auctioneer->is_activated) {
            abort(404, 'Auctioneer not found.');
        }

        // Community system auctioneers (slug: community-{region-slug}) redirect to the region page.
        if (str_starts_with($auctioneer->slug, 'community-')) {
            $regionSlug = substr($auctioneer->slug, strlen('community-'));
            $region = \App\Models\CommunityRegion::where('slug', $regionSlug)->where('is_active', true)->first();
            if ($region) {
                return redirect()->route('community.region', $region);
            }
        }

        $auctioneer->load('user');

        // Get live auctions
        $liveAuctions = $auctioneer->auctions()
            ->where('status', 'live')
            ->withCount('lots')
            ->orderBy('start_time')
            ->get();

        // Get upcoming auctions. For community auctioneers, drafts are the
        // listing-window phase and ARE upcoming from a bidder's POV — match
        // the convention used on the public auctions index.
        $upcomingAuctions = $auctioneer->auctions()
            ->where(function ($q) use ($auctioneer) {
                $q->where('status', 'upcoming');
                if ($auctioneer->isCommunity()) {
                    $q->orWhere(fn ($q2) => $q2->where('status', 'draft')->where('is_community', true));
                }
            })
            ->withCount('lots')
            ->orderBy('start_time')
            ->get();

        // Get past auctions (limit to recent 20 — auctioneers with hundreds of past auctions)
        $pastAuctions = $auctioneer->auctions()
            ->where('status', 'ended')
            ->withCount('lots')
            ->orderBy('end_time', 'desc')
            ->limit(20)
            ->get();

        // Calculate stats
        $auctionIds = $auctioneer->auctions()->pluck('id');

        $stats = [
            'total_auctions' => $auctionIds->count(),
            'live_auctions' => $liveAuctions->count(),
            'total_lots' => Lot::whereIn('event_id', $auctionIds)->count(),
            'lots_sold' => Lot::whereIn('event_id', $auctionIds)->where('status', 'sold')->count(),
        ];

        // Rating data
        $averageRating = round($auctioneer->ratings()->avg('rating'), 1);
        $totalRatings = $auctioneer->ratings()->count();
        $canRate = false;
        $userRating = null;

        if (auth()->check() && auth()->user()->isBidder()) {
            $canRate = auth()->user()->hasBidOnAuctioneerAuctions($auctioneer->id);
            $userRating = \App\Models\AuctioneerRating::where('user_id', auth()->id())
                ->where('auctioneer_id', $auctioneer->id)
                ->value('rating');
        }

        // Activity log skipped for auctioneer_viewed — high-frequency page

        return view('auctioneer.show', compact('auctioneer', 'liveAuctions', 'upcomingAuctions', 'pastAuctions', 'stats', 'averageRating', 'totalRatings', 'canRate', 'userRating'));
    }

    /**
     * Seller dashboard.
     */
    public function dashboard()
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        if (!$auctioneer) {
            if ($user->isStaff()) {
                abort(403, 'Your staff account is not linked to an active auctioneer.');
            }
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        $isStaff = $user->isStaff();

        // Get stats (exclude already-relisted lots from unsold counts)
        $unsoldBase = \App\Models\Lot::whereHas('auction', function ($q) use ($auctioneer) {
            $q->where('auctioneer_id', $auctioneer->id)->where('status', 'ended');
        })->where('status', 'unsold')->whereNull('withdrawn_at')
          ->whereDoesntHave('relistedTo');

        $stats = [
            'draft_auctions'  => $auctioneer->auctions()->draft()->count(),
            'live_auctions'   => $auctioneer->auctions()->live()->count(),
            'total_lots'      => \App\Models\Lot::whereHas('auction', function ($q) use ($auctioneer) {
                $q->where('auctioneer_id', $auctioneer->id);
            })->count(),
            'lots_sold'       => \App\Models\Lot::whereHas('auction', function ($q) use ($auctioneer) {
                $q->where('auctioneer_id', $auctioneer->id);
            })->where('status', 'sold')->count(),
            'followers'       => $auctioneer->followers()->count(),
            'unsold_lots'     => (clone $unsoldBase)->count(),
            'free_relist_lots' => (clone $unsoldBase)->where('free_relist_eligible', true)->count(),
            'awaiting_collection' => \App\Models\Lot::whereHas('auction', function ($q) use ($auctioneer) {
                $q->where('auctioneer_id', $auctioneer->id);
            })->where('status', 'sold')
              ->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))
              ->count(),
        ];

        // Get recent auctions
        $recentAuctions = $auctioneer->auctions()
            ->with('lots')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent activity (transactions)
        $recentActivity = \App\Models\ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent followers (limited for dashboard)
        $recentFollowers = $auctioneer->followers()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $staffPermissions = [
            'lots' => $user->hasStaffPermission('lots'),
            'auctions' => $user->hasStaffPermission('auctions'),
            'collections' => $user->hasStaffPermission('collections'),
        ];

        return view('seller.dashboard', compact(
            'auctioneer',
            'stats',
            'recentAuctions',
            'recentActivity',
            'recentFollowers',
            'isStaff',
            'staffPermissions'
        ));
    }

    /**
     * Show activation page.
     */
    public function showActivation()
    {
        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        if (!$auctioneer) {
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        if ($auctioneer->is_activated) {
            return redirect()->route('seller.dashboard')
                ->with('info', 'Your account is already activated.');
        }

        $activationFee = config('platform.pricing.activation_fee');

        return view('seller.activate', compact('auctioneer', 'activationFee'));
    }

    /**
     * Process activation payment.
     */
    public function processActivation(Request $request, PaymentGatewayFactory $gateways)
    {
        $request->validate([
            'payment_method' => ['nullable', 'in:payfast,blink'],
        ]);

        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        if (!$auctioneer) {
            return back()->with('error', 'Auctioneer profile not found.');
        }

        if ($auctioneer->is_activated) {
            return redirect()->route('seller.dashboard')
                ->with('info', 'Your account is already activated.');
        }

        $activationFee = config('platform.pricing.activation_fee');

        // Buyer-selected gateway (Card vs Bitcoin); defaults to PayFast.
        $gateway = $gateways->make($request->input('payment_method') ?: 'payfast');

        // Create payment
        $payment = $gateway->createPayment(
            amount: $activationFee,
            user: $user,
            type: 'activation_fee',
            metadata: [
                'auctioneer_id' => $auctioneer->id,
            ]
        );

        // Store payment ID in session for return handling
        session(['pending_activation_payment' => $payment['payment_id']]);

        // PayFast needs a POST form submission; Lightning/others just redirect.
        if (($payment['method'] ?? null) === 'POST') {
            return view('payment.redirect', ['payment' => $payment]);
        }

        return redirect($payment['redirect_url']);
    }

    /**
     * Show credits page.
     */
    public function credits()
    {
        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        // Get recent credit transactions
        $transactions = $auctioneer->creditTransactions()
            ->with('lot')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Pricing
        $pricing = [
            'basic' => config('platform.pricing.tier_basic.price'),
            'pro' => config('platform.pricing.tier_pro.price'),
            'premium' => config('platform.pricing.tier_premium.price'),
        ];

        return view('seller.credits', compact('auctioneer', 'transactions', 'pricing'));
    }

    /**
     * Purchase credits.
     */
    public function purchaseCredits(Request $request, PaymentGatewayFactory $gateways)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
            'payment_method' => ['nullable', 'in:payfast,blink'],
        ]);

        $user = auth()->user();
        $amount = $request->amount;

        // Buyer-selected gateway (Card vs Bitcoin); defaults to PayFast.
        $gateway = $gateways->make($request->input('payment_method') ?: 'payfast');

        // Create payment
        $payment = $gateway->createPayment(
            amount: $amount,
            user: $user,
            type: 'credit_purchase',
            metadata: [
                'credits' => $amount,
                'auctioneer_id' => $user->auctioneer->id,
            ]
        );

        // Store payment ID in session
        session(['pending_credit_payment' => $payment['payment_id']]);

        // PayFast needs a POST form submission; Lightning/others just redirect.
        if (($payment['method'] ?? null) === 'POST') {
            return view('payment.redirect', ['payment' => $payment]);
        }

        return redirect($payment['redirect_url']);
    }

    /**
     * Show profile page.
     */
    public function profile()
    {
        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        return view('seller.profile', compact('user', 'auctioneer'));
    }

    /**
     * Update auctioneer profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        // Handle white-label branding section separately
        if ($request->input('_section') === 'white_label') {
            return $this->updateWhiteLabelBranding($request, $auctioneer);
        }

        // Convert empty strings to null for URL fields
        $request->merge([
            'whatsapp_group_link' => $request->whatsapp_group_link ?: null,
            'facebook' => $request->facebook ?: null,
            'instagram' => $request->instagram ?: null,
            'tiktok' => $request->tiktok ?: null,
            'twitter' => $request->twitter ?: null,
            'linkedin' => $request->linkedin ?: null,
            'website' => $request->website ?: null,
        ]);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'rules' => ['nullable', 'string', 'max:10000'],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'whatsapp_group_link' => ['nullable', 'url', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:8192'],
            'banner_image' => ['nullable', 'image', 'max:8192'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Handle logo upload — only update if a new file was provided
        if ($request->hasFile('logo')) {
            if ($auctioneer->logo) {
                Storage::disk('public')->delete($auctioneer->logo);
            }
            $validated['logo'] = $request->file('logo')->store('auctioneers/logos', 'public');
        } else {
            unset($validated['logo']);
        }

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            if ($auctioneer->banner_image) {
                Storage::disk('public')->delete($auctioneer->banner_image);
            }
            $validated['banner_image'] = $request->file('banner_image')->store('auctioneers/banners', 'public');
        } else {
            unset($validated['banner_image']);
        }

        // Update auctioneer
        $auctioneer->update($validated);

        // Update user fields (phone, address, city, province)
        $user->update([
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
        ]);

        // Handle coordinates - use manual if provided, otherwise try to geocode
        if (!empty($validated['lat']) && !empty($validated['lng'])) {
            // Use manually provided coordinates
            $user->update([
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ]);
        } elseif (!empty($validated['city'])) {
            // Try to auto-geocode if no manual coordinates and city is present
            try {
                $coordinates = $this->geocodeAddress(
                    $validated['address'] ?? '',
                    $validated['city'] ?? '',
                    $validated['province'] ?? ''
                );
                $user->update([
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng'],
                ]);
            } catch (\Exception $e) {
                // Geocoding failed - keep existing coordinates
                \Log::warning('Geocoding failed for auctioneer profile update', [
                    'user_id' => $user->id,
                    'city' => $validated['city'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update white-label branding settings.
     */
    private function updateWhiteLabelBranding(Request $request, \App\Models\Auctioneer $auctioneer)
    {
        $validated = $request->validate([
            'white_label_enabled' => ['required', 'boolean'],
            'brand_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_favicon' => ['nullable', 'image', 'max:1024', 'dimensions:max_width=512,max_height=512'],
            'brand_hero_text' => ['nullable', 'string', 'max:500'],
        ]);

        // Handle favicon upload
        if ($request->hasFile('brand_favicon')) {
            if ($auctioneer->brand_favicon) {
                \Storage::disk('public')->delete($auctioneer->brand_favicon);
            }
            $validated['brand_favicon'] = $request->file('brand_favicon')->store('auctioneers/favicons', 'public');
        } else {
            unset($validated['brand_favicon']);
        }

        // Convert empty color strings to null
        if (empty($validated['brand_primary_color'])) {
            $validated['brand_primary_color'] = null;
        }
        if (empty($validated['brand_secondary_color'])) {
            $validated['brand_secondary_color'] = null;
        }

        $auctioneer->update($validated);

        return back()->with('success', 'White-label branding updated successfully!');
    }

    /**
     * Update PayFast integration settings.
     */
    public function updatePayfast(Request $request)
    {
        $auctioneer = auth()->user()->auctioneer;

        $validated = $request->validate([
            'payfast_merchant_id' => ['nullable', 'string', 'max:50'],
            'payfast_merchant_key' => ['nullable', 'string', 'max:50'],
            'payfast_passphrase' => ['nullable', 'string', 'max:100'],
            'payfast_sandbox' => ['nullable'],
        ]);

        $auctioneer->payfast_merchant_id = $validated['payfast_merchant_id'] ?? null;
        $auctioneer->payfast_merchant_key = $validated['payfast_merchant_key'] ?? null;
        $auctioneer->payfast_sandbox = $request->has('payfast_sandbox');

        // Only update passphrase if a new one was entered
        if (!empty($validated['payfast_passphrase'])) {
            $auctioneer->payfast_passphrase = $validated['payfast_passphrase'];
        }

        $auctioneer->save();

        return back()->with('success', 'PayFast settings updated successfully!');
    }

    /**
     * Update account settings (name, email, password).
     */
    public function updateAccount(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ];

        if ($request->filled('current_password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->filled('current_password')) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Account settings updated successfully!');
    }

    /**
     * Show analytics.
     */
    public function analytics()
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        // Get statistics
        $auctionIds = $auctioneer->auctions()->pluck('id');
        $soldLots = \App\Models\Lot::whereIn('event_id', $auctionIds)
            ->where('status', 'sold')
            ->get();

        $lotsSoldCount = $soldLots->count();
        $totalSales = $soldLots->sum('current_bid');

        $stats = [
            'total_auctions' => $auctioneer->auctions()->count(),
            'total_lots' => $auctioneer->auctions()->withCount('lots')->get()->sum('lots_count'),
            'lots_sold' => $lotsSoldCount,
            'total_sales' => $totalSales,
            'avg_sale_price' => $lotsSoldCount > 0 ? $totalSales / $lotsSoldCount : 0,
            'total_revenue' => $auctioneer->auctions()->sum('total_value'),
            'total_bids' => $auctioneer->auctions()->sum('total_bids'),
        ];

        // Recent auctions performance
        $recentAuctions = $auctioneer->auctions()
            ->with('lots')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();

        // Top performing lots (highest selling prices)
        $topLots = \App\Models\Lot::whereIn('event_id', $auctionIds)
            ->where('status', 'sold')
            ->with(['auction', 'images'])
            ->orderBy('current_bid', 'desc')
            ->limit(10)
            ->get();

        return view('seller.analytics', compact('auctioneer', 'stats', 'recentAuctions', 'topLots'));
    }

    /**
     * Show transactions history.
     */
    public function transactions()
    {
        $user = auth()->user();
        $auctioneer = $user->auctioneer;

        $transactions = Transaction::where('auctioneer_id', $auctioneer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('seller.transactions', compact('transactions', 'auctioneer'));
    }

    /**
     * Get auctioneers for map (API).
     */
    public function mapData()
    {
        $data = \Cache::remember('auctioneers_map_data_v2', 1800, function () {
            // All activated auctioneers (including community system auctioneers whose
            // lat/lng is synced to their user record by syncSystemAuctioneerCoords).
            $all = Auctioneer::activated()
                ->with('user:id,lat,lng,city,province')
                ->whereHas('user', function ($query) {
                    $query->whereNotNull('lat')->whereNotNull('lng');
                })
                ->get()
                ->map(function ($a) {
                    $isCommunity = str_starts_with($a->slug, 'community-');
                    return [
                        'id'       => $a->id,
                        'name'     => $a->business_name,
                        'slug'     => $a->slug,
                        'location' => trim($a->user->city . ', ' . $a->user->province, ', '),
                        'lat'      => (float) $a->user->lat,
                        'lng'      => (float) $a->user->lng,
                        'type'     => $isCommunity ? 'community' : 'auctioneer',
                    ];
                });

            return $all->values();
        });

        return response()->json($data);
    }

    /**
     * Toggle follow/unfollow for an auctioneer.
     */
    public function toggleFollow(Auctioneer $auctioneer)
    {
        $user = auth()->user();

        $existing = \App\Models\AuctioneerFollower::where('user_id', $user->id)
            ->where('auctioneer_id', $auctioneer->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Unfollowed ' . $auctioneer->business_name;
            $isFollowing = false;
        } else {
            \App\Models\AuctioneerFollower::create([
                'user_id' => $user->id,
                'auctioneer_id' => $auctioneer->id,
            ]);
            $message = 'Now following ' . $auctioneer->business_name;
            $isFollowing = true;
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_following' => $isFollowing,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Rate an auctioneer (1-5 stars). Bidder must have bid on their auctions.
     */
    public function rateAuctioneer(Request $request, Auctioneer $auctioneer)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
        ]);

        $user = auth()->user();

        if (!$user->isBidder()) {
            return back()->with('error', 'Only bidders can rate auctioneers.');
        }

        if (!$user->hasBidOnAuctioneerAuctions($auctioneer->id)) {
            return back()->with('error', 'You must bid on an auction before you can rate this auctioneer.');
        }

        \App\Models\AuctioneerRating::updateOrCreate(
            ['user_id' => $user->id, 'auctioneer_id' => $auctioneer->id],
            ['rating' => $request->input('rating')]
        );

        return back()->with('success', 'Thank you for your rating!');
    }

    /**
     * Show followers list for the auctioneer.
     */
    public function followers(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if (!$auctioneer) {
            abort(403, 'You must be an auctioneer to view followers.');
        }

        $query = $auctioneer->followers()->with('user');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by location
        if ($request->filled('location')) {
            $location = $request->location;
            $query->whereHas('user', function($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                  ->orWhere('province', 'like', "%{$location}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->join('users', 'auctioneer_followers.user_id', '=', 'users.id')
                      ->orderBy('users.name', 'asc')
                      ->select('auctioneer_followers.*');
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
        }

        $followers = $query->paginate(20)->withQueryString();

        return view('seller.followers', compact('followers', 'auctioneer'));
    }

    /**
     * Show accounting overview
     */
    public function accounting()
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        // Lot fees and commissions from credit transaction ledger (source of truth)
        $lotFeesPaid    = abs($auctioneer->creditTransactions()->where('type', 'lot_live')->sum('amount'));
        $commissionsPaid = abs($auctioneer->creditTransactions()->where('type', 'lot_close')->sum('amount'));

        $stats = [
            'credit_balance'  => (float) $auctioneer->credit_balance,
            'lot_fees_paid'   => $lotFeesPaid,
            'commissions_paid' => $commissionsPaid,
        ];

        return view('seller.accounting', compact('auctioneer', 'stats'));
    }

    /**
     * Show sales history
     */
    public function salesHistory(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        $query = $auctioneer->salesRecords()
            ->with('lot.auction');

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('paid_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('paid_date', '<=', $request->to);
        }

        // Sort
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('paid_date', 'asc');
                break;
            case 'highest':
                $query->orderBy('sale_price', 'desc');
                break;
            case 'lowest':
                $query->orderBy('sale_price', 'asc');
                break;
            default: // newest
                $query->orderBy('paid_date', 'desc');
        }

        $salesRecords = $query->paginate(20)->withQueryString();

        // Calculate totals for current filter
        $totals = [
            'total_sales' => $salesRecords->sum('sale_price'),
            'total_fees' => $salesRecords->sum('payment_gateway_fee'),
            'total_commission' => $salesRecords->sum('platform_commission'),
            'total_net' => $salesRecords->sum('net_to_auctioneer'),
        ];

        return view('seller.sales', compact('auctioneer', 'salesRecords', 'totals'));
    }

    /**
     * Show payout history
     */
    public function payouts()
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        $payouts = $auctioneer->payouts()
            ->with('processedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'credit_balance' => (float) $auctioneer->credit_balance,
            'available_balance' => $auctioneer->getAvailablePayoutBalance(),
            'pending_clearance' => $auctioneer->getPendingClearance(),
            'can_request_payout' => $auctioneer->canRequestPayout(),
            'minimum_payout' => config('platform.payout.minimum_payout', 500),
            'pending_count' => $auctioneer->pendingPayouts()->count(),
        ];

        return view('seller.payouts', compact('auctioneer', 'payouts', 'stats'));
    }

    /**
     * Show full credit transaction ledger for the auctioneer.
     */
    public function creditLedger(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        $query = $auctioneer->creditTransactions()->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $creditTransactions = $query->paginate(50)->withQueryString();

        return view('seller.credit-ledger', compact('auctioneer', 'creditTransactions'));
    }

    /**
     * Request a payout
     */
    public function requestPayout(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        $availableBalance = $auctioneer->getAvailablePayoutBalance();
        $minimumPayout   = config('platform.payout.minimum_payout', 500);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $minimumPayout, 'max:' . $availableBalance],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'branch_code' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $payout = $auctioneer->requestPayout(
                amount: $request->amount,
                bankDetails: [
                    'bank_name' => $request->bank_name,
                    'account_holder' => $request->account_holder,
                    'account_number' => $request->account_number,
                    'branch_code' => $request->branch_code,
                ]
            );

            return redirect()->route('seller.payouts')
                ->with('success', 'Payout request submitted successfully. Amount: ' . formatCurrency($request->amount));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show unsold lots from ended auctions (for relisting), grouped by source auction.
     * Excludes lots that have already been relisted (copy exists).
     */
    public function unsoldLots(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if (!$auctioneer) {
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        // Base query: unsold, not withdrawn, not already relisted
        $unsoldBase = \App\Models\Lot::whereHas('auction', function ($q) use ($auctioneer) {
            $q->where('auctioneer_id', $auctioneer->id)->where('status', 'ended');
        })->where('status', 'unsold')
          ->whereNull('withdrawn_at')
          ->whereDoesntHave('relistedTo');

        // Filter by relist type
        $query = (clone $unsoldBase)->with(['auction', 'images']);

        if ($request->filled('type')) {
            if ($request->type === 'free') {
                $query->where('free_relist_eligible', true);
            } elseif ($request->type === 'paid') {
                $query->where('free_relist_eligible', false);
            }
        }

        // Load all matching lots grouped by source auction (newest auctions first, lots by lot_number)
        $allLots = $query->orderBy('event_id', 'desc')->orderBy('lot_number')->get();

        // Group by auction and build per-auction metadata
        $auctionGroups = [];
        foreach ($allLots->groupBy('event_id') as $eventId => $lots) {
            $auction = $lots->first()->auction;
            $auctionGroups[] = [
                'auction' => $auction,
                'lots' => $lots,
                'total' => $lots->count(),
                'free' => $lots->where('free_relist_eligible', true)->count(),
                'paid' => $lots->where('free_relist_eligible', false)->count(),
            ];
        }

        // Draft auctions available for relisting
        $draftAuctions = $auctioneer->auctions()
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();

        // Page-level stats
        $stats = [
            'total_unsold'  => (clone $unsoldBase)->count(),
            'free_relist'   => (clone $unsoldBase)->where('free_relist_eligible', true)->count(),
            'paid_relist'   => (clone $unsoldBase)->where('free_relist_eligible', false)->count(),
        ];

        // Tier costs for display
        $tierCosts = [
            'basic'   => $auctioneer->calculateLotCost('basic'),
            'pro'     => $auctioneer->calculateLotCost('pro'),
            'premium' => $auctioneer->calculateLotCost('premium'),
        ];

        return view('seller.unsold-lots', compact(
            'auctionGroups', 'draftAuctions', 'stats', 'tierCosts', 'auctioneer'
        ));
    }

    /**
     * Show collection management page for offline payments.
     */
    public function collections(Request $request)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if (!$auctioneer) {
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        // Base query: lots from this auctioneer's auctions that have collection status
        $baseQuery = Lot::whereHas('auction', function ($q) use ($auctioneer) {
            $q->where('auctioneer_id', $auctioneer->id);
        })->whereIn('status', ['sold', 'pending_confirmation'])
          ->whereNotNull('winning_bidder_id')
          ->where(fn($q) => $q->whereIn('payment_status', ['awaiting_collection', 'paid_offline', 'paid_platform'])->orWhereNull('payment_status'));

        // Stats
        $stats = [
            'awaiting'   => (clone $baseQuery)->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))->count(),
            'confirmed'  => (clone $baseQuery)->whereIn('payment_status', ['paid_offline', 'paid_platform'])->count(),
            'total_outstanding' => (clone $baseQuery)->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))->get()->sum(fn ($lot) => $lot->getTotalAmountDue()),
        ];

        // Filterable query
        $query = (clone $baseQuery)->with(['auction', 'images', 'winningBidder']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        // Filter by auction
        if ($request->filled('auction_id')) {
            $query->where('event_id', $request->auction_id);
        }

        $allLots = $query->orderByRaw("CASE WHEN payment_status = 'awaiting_collection' OR payment_status IS NULL THEN 0 ELSE 1 END")
            ->orderBy('payment_method_selected_at', 'asc')
            ->get();

        // Group lots by bidder + auction
        $grouped = $allLots->groupBy(fn ($lot) => $lot->winning_bidder_id . '-' . $lot->event_id)
            ->map(function ($lots) {
                $first = $lots->first();
                return [
                    'bidder' => $first->winningBidder,
                    'auction' => $first->auction,
                    'lots' => $lots,
                    'total_due' => $lots->sum(fn ($lot) => $lot->getTotalAmountDue()),
                    'status' => $lots->every(fn ($l) => in_array($l->payment_status, ['paid_offline', 'paid_platform'])) ? 'paid' : 'awaiting_collection',
                    'lot_ids' => $lots->pluck('id')->join(','),
                    'earliest_arranged' => $lots->min('payment_method_selected_at'),
                ];
            })->values();

        // Bidder summary: group unpaid lots by bidder
        $bidderSummary = $allLots->filter(fn($l) => ($l->payment_status === 'awaiting_collection' || $l->payment_status === null) && $l->winning_bidder_id)
            ->groupBy('winning_bidder_id')->map(function ($bidderLots) {
                $bidder = $bidderLots->first()->winningBidder;
                return [
                    'bidder' => $bidder,
                    'lot_count' => $bidderLots->count(),
                    'total_owed' => $bidderLots->sum(fn ($lot) => $lot->getTotalAmountDue()),
                ];
            })->sortByDesc('total_owed')->values();

        // Auctions for filter dropdown
        $auctions = $auctioneer->auctions()
            ->whereIn('status', ['ended', 'live'])
            ->orderBy('end_time', 'desc')
            ->get();

        return view('seller.collections', compact(
            'grouped', 'stats', 'bidderSummary', 'auctions', 'auctioneer'
        ));
    }

    /**
     * Confirm offline payment for one or more lots.
     */
    public function confirmCollectionPayment(Request $request)
    {
        $request->validate(['lot_ids' => 'required|string']);

        $auctioneer = auth()->user()->resolveAuctioneer();
        $lotIds = explode(',', $request->lot_ids);

        $lots = Lot::with('auction')
            ->whereIn('id', $lotIds)
            ->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))
            ->get();

        // Verify all lots belong to this auctioneer
        foreach ($lots as $lot) {
            if ($lot->auction->auctioneer_id !== $auctioneer->id) {
                abort(403);
            }
        }

        if ($lots->isEmpty()) {
            return redirect()->back()->with('error', 'No lots awaiting collection payment.');
        }

        $totalAmount = 0;
        foreach ($lots as $lot) {
            $lot->update([
                'is_paid' => true,
                'payment_status' => 'paid_offline',
                'payment_completed_at' => now(),
            ]);
            $totalAmount += $lot->getTotalAmountDue();
        }

        $lotTitles = $lots->count() === 1
            ? '"' . $lots->first()->title . '"'
            : $lots->count() . ' lots';

        ActivityLog::create([
            'user_id' => auth()->id(),
            'type' => 'collection_confirmed',
            'description' => 'Confirmed offline payment for ' . $lotTitles . ' (' . formatCurrency($totalAmount) . ')',
            'subject_type' => 'App\\Models\\Auction',
            'subject_id' => $lots->first()->auction->id,
        ]);

        return redirect()->back()->with('success', 'Payment confirmed for ' . $lotTitles . '.');
    }

    /**
     * Confirm a pending_confirmation lot as sold.
     */
    public function confirmLot(Request $request, Lot $lot)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if (!$lot->auction || $lot->auction->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        if ($lot->status !== 'pending_confirmation') {
            return back()->with('error', 'This lot is not pending confirmation.');
        }

        $lot->confirmSale();

        // Send in-app notification to winner
        if ($lot->winning_bidder_id) {
            \App\Models\PushNotification::create([
                'sender_type' => 'auctioneer',
                'sender_id' => auth()->id(),
                'auctioneer_id' => $auctioneer->id,
                'audience' => 'specific_user',
                'target_user_id' => $lot->winning_bidder_id,
                'title' => 'Sale Confirmed!',
                'body' => "Your winning bid on \"{$lot->title}\" (Lot #{$lot->lot_number}) has been confirmed by the auctioneer. Please arrange payment and collection.",
                'url' => route('lots.show', $lot),
            ]);
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'type' => 'lot_confirmed',
            'description' => "Confirmed sale of Lot #{$lot->lot_number} - {$lot->title}",
            'subject_type' => 'App\\Models\\Lot',
            'subject_id' => $lot->id,
        ]);

        return back()->with('success', "Lot #{$lot->lot_number} confirmed as sold.");
    }

    /**
     * Reject a pending_confirmation lot — mark as unsold.
     */
    public function rejectLot(Request $request, Lot $lot)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if (!$lot->auction || $lot->auction->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        if ($lot->status !== 'pending_confirmation') {
            return back()->with('error', 'This lot is not pending confirmation.');
        }

        $winnerId = $lot->winning_bidder_id;
        $lot->rejectSale();

        // Send in-app notification to bidder
        if ($winnerId) {
            \App\Models\PushNotification::create([
                'sender_type' => 'auctioneer',
                'sender_id' => auth()->id(),
                'auctioneer_id' => $auctioneer->id,
                'audience' => 'specific_user',
                'target_user_id' => $winnerId,
                'title' => 'Sale Not Confirmed',
                'body' => "Unfortunately, the auctioneer has not confirmed the sale of \"{$lot->title}\" (Lot #{$lot->lot_number}). This lot was subject to confirmation.",
                'url' => route('lots.show', $lot),
            ]);
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'type' => 'lot_rejected',
            'description' => "Rejected sale of Lot #{$lot->lot_number} - {$lot->title}",
            'subject_type' => 'App\\Models\\Lot',
            'subject_id' => $lot->id,
        ]);

        return back()->with('success', "Lot #{$lot->lot_number} rejected — marked as unsold.");
    }

    /**
     * Suspend a bidder who has unpaid collection lots with this auctioneer.
     */
    public function suspendBidder(User $user)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        // Verify bidder has unpaid lots with this auctioneer
        $unpaidCount = Lot::whereHas('auction', function ($q) use ($auctioneer) {
            $q->where('auctioneer_id', $auctioneer->id);
        })->where('winning_bidder_id', $user->id)
          ->where(fn($q) => $q->where('payment_status', 'awaiting_collection')->orWhereNull('payment_status'))
          ->count();

        if ($unpaidCount === 0) {
            return redirect()->back()->with('error', 'This bidder has no unpaid collection lots with you.');
        }

        $user->update([
            'is_active' => false,
            'suspended_by_auctioneer_id' => $auctioneer->id,
            'suspension_reason' => 'Suspended by ' . $auctioneer->business_name . ' for non-payment of ' . $unpaidCount . ' lot(s)',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'type' => 'bidder_suspended',
            'description' => 'Suspended bidder "' . $user->name . '" for non-payment of ' . $unpaidCount . ' lot(s)',
            'subject_type' => 'App\\Models\\User',
            'subject_id' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Bidder "' . $user->name . '" has been suspended.');
    }

    /**
     * Unsuspend a bidder that this auctioneer previously suspended.
     */
    public function unsuspendBidder(User $user)
    {
        $auctioneer = auth()->user()->resolveAuctioneer();

        if ($user->suspended_by_auctioneer_id !== $auctioneer->id) {
            return redirect()->back()->with('error', 'You can only unsuspend bidders that you suspended.');
        }

        $user->update([
            'is_active' => true,
            'suspended_by_auctioneer_id' => null,
            'suspension_reason' => null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'type' => 'bidder_unsuspended',
            'description' => 'Unsuspended bidder "' . $user->name . '"',
            'subject_type' => 'App\\Models\\User',
            'subject_id' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Bidder "' . $user->name . '" has been unsuspended.');
    }

    /**
     * Show bidder insights dashboard.
     */
    public function bidderInsights(Request $request)
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        if (!$auctioneer) {
            return redirect()->route('seller.profile')
                ->with('error', 'Please complete your auctioneer profile first.');
        }

        // Period filter
        $period = $request->get('period', '30');
        $dateFrom = match ($period) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            default => null, // 'all'
        };

        // Auction filter
        $auctionIds = $auctioneer->auctions()->pluck('id')->toArray();
        $filteredAuctionId = $request->get('auction_id');

        // Get lot IDs scoped to this auctioneer (and optionally filtered auction)
        $lotQuery = \App\Models\Lot::whereIn('event_id', $auctionIds);
        if ($filteredAuctionId) {
            $lotQuery->where('event_id', $filteredAuctionId);
        }
        $lotIds = $lotQuery->pluck('id')->toArray();

        // Base activity log query for this auctioneer's views
        $viewQuery = ActivityLog::where(function ($q) {
            $q->where('type', 'lot_viewed')
              ->orWhere('type', 'auction_viewed');
        });
        if ($dateFrom) {
            $viewQuery->where('created_at', '>=', $dateFrom);
        }

        // Filter by auctioneer_id in properties JSON
        $viewQuery->where(function ($q) use ($auctioneer) {
            $q->whereRaw("JSON_EXTRACT(properties, '$.auctioneer_id') = ?", [$auctioneer->id]);
        });

        // If filtering by specific auction, narrow lot views to that auction's lots
        if ($filteredAuctionId) {
            $viewQuery->where(function ($q) use ($filteredAuctionId, $lotIds) {
                $q->where(function ($q2) use ($filteredAuctionId) {
                    $q2->where('type', 'auction_viewed')
                        ->where('subject_id', $filteredAuctionId);
                })->orWhere(function ($q2) use ($lotIds) {
                    $q2->where('type', 'lot_viewed')
                        ->whereIn('subject_id', $lotIds);
                });
            });
        }

        $allViews = (clone $viewQuery)->get();

        // Stats
        $lotViews = $allViews->where('type', 'lot_viewed')->count();
        $auctionViews = $allViews->where('type', 'auction_viewed')->count();
        $uniqueVisitors = $allViews->pluck('user_id')->unique()->count();

        // View-to-Bid %: unique bidders / unique lot viewers
        $uniqueLotViewers = $allViews->where('type', 'lot_viewed')->pluck('user_id')->unique()->count();

        $bidQuery = \App\Models\Bid::whereIn('lot_id', $lotIds);
        if ($dateFrom) {
            $bidQuery->where('created_at', '>=', $dateFrom);
        }
        $allBids = (clone $bidQuery)->get();
        $uniqueBidders = $allBids->pluck('user_id')->unique()->count();

        $viewToBidPct = $uniqueLotViewers > 0
            ? round(($uniqueBidders / $uniqueLotViewers) * 100, 1)
            : 0;

        // Avg bids per lot (lots that have bids)
        $lotsWithBids = $allBids->pluck('lot_id')->unique()->count();
        $avgBidsPerLot = $lotsWithBids > 0
            ? round($allBids->count() / $lotsWithBids, 1)
            : 0;

        // Mobile %
        $totalViewCount = $allViews->count();
        $mobileCount = 0;
        $desktopCount = 0;
        $tabletCount = 0;
        foreach ($allViews as $view) {
            $ua = strtolower($view->user_agent ?? '');
            if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
                $tabletCount++;
            } elseif (preg_match('/mobile|android|iphone|ipod|opera mini|iemobile|wp7/i', $ua)) {
                $mobileCount++;
            } else {
                $desktopCount++;
            }
        }
        $mobilePct = $totalViewCount > 0 ? round(($mobileCount / $totalViewCount) * 100, 1) : 0;
        $desktopPct = $totalViewCount > 0 ? round(($desktopCount / $totalViewCount) * 100, 1) : 0;
        $tabletPct = $totalViewCount > 0 ? round(($tabletCount / $totalViewCount) * 100, 1) : 0;

        $stats = [
            'lot_views' => $lotViews,
            'auction_views' => $auctionViews,
            'unique_visitors' => $uniqueVisitors,
            'view_to_bid_pct' => $viewToBidPct,
            'avg_bids_per_lot' => $avgBidsPerLot,
            'mobile_pct' => $mobilePct,
        ];

        $deviceBreakdown = [
            'mobile' => $mobilePct,
            'desktop' => $desktopPct,
            'tablet' => $tabletPct,
        ];

        // Most Viewed Lots (top 15)
        $lotViewLogs = $allViews->where('type', 'lot_viewed');
        $lotViewCounts = $lotViewLogs->groupBy('subject_id')
            ->map(fn($views) => $views->count())
            ->sortDesc()
            ->take(15);

        $mostViewedLots = collect();
        if ($lotViewCounts->isNotEmpty()) {
            $topLotIds = $lotViewCounts->keys()->toArray();
            $topLots = \App\Models\Lot::whereIn('id', $topLotIds)
                ->with(['auction', 'images'])
                ->get()
                ->keyBy('id');

            // Get watchlist counts for these lots
            $watchlistCounts = \App\Models\Watchlist::whereIn('lot_id', $topLotIds)
                ->selectRaw('lot_id, COUNT(*) as count')
                ->groupBy('lot_id')
                ->pluck('count', 'lot_id');

            // Get bid counts for these lots
            $bidCounts = \App\Models\Bid::whereIn('lot_id', $topLotIds)
                ->selectRaw('lot_id, COUNT(*) as count')
                ->groupBy('lot_id')
                ->pluck('count', 'lot_id');

            foreach ($lotViewCounts as $lotId => $viewCount) {
                $lot = $topLots->get($lotId);
                if (!$lot) continue;

                $bids = $bidCounts->get($lotId, 0);
                $uniqueLotViewersForLot = $lotViewLogs->where('subject_id', $lotId)->pluck('user_id')->unique()->count();

                $mostViewedLots->push([
                    'lot' => $lot,
                    'views' => $viewCount,
                    'watchlisters' => $watchlistCounts->get($lotId, 0),
                    'bids' => $bids,
                    'conversion' => $uniqueLotViewersForLot > 0
                        ? round(($bids / $uniqueLotViewersForLot) * 100, 1)
                        : 0,
                ]);
            }
        }

        // Top Bidders (top 15)
        $topBidders = collect();
        if ($allBids->isNotEmpty()) {
            $bidderGroups = $allBids->groupBy('user_id');
            $bidderUserIds = $bidderGroups->keys()->toArray();
            $bidderUsers = \App\Models\User::whereIn('id', $bidderUserIds)->get()->keyBy('id');

            // Get won lots with amounts for these bidders
            $wonLots = \App\Models\Lot::whereIn('event_id', $auctionIds)
                ->where('status', 'sold')
                ->whereIn('winning_bidder_id', $bidderUserIds)
                ->get();
            $wonByBidder = $wonLots->groupBy('winning_bidder_id');

            // Count auctions participated in per bidder
            $auctionsPerBidder = $allBids->groupBy('user_id')->map(function ($bids) use ($lotIds) {
                $bidLotIds = $bids->pluck('lot_id')->unique();
                return \App\Models\Lot::whereIn('id', $bidLotIds)
                    ->pluck('event_id')
                    ->unique()
                    ->count();
            });

            foreach ($bidderGroups->sortByDesc(fn($bids) => $bids->count())->take(15) as $userId => $bids) {
                $bidderUser = $bidderUsers->get($userId);
                if (!$bidderUser) continue;

                $won = $wonByBidder->get($userId, collect());
                $auctionCount = $auctionsPerBidder->get($userId, 0);

                // Mask email
                $email = $bidderUser->email;
                $parts = explode('@', $email);
                $maskedEmail = substr($parts[0], 0, 1) . '***@' . ($parts[1] ?? '');

                $topBidders->push([
                    'user' => $bidderUser,
                    'masked_email' => $maskedEmail,
                    'auctions_count' => $auctionCount,
                    'total_bids' => $bids->count(),
                    'total_spent' => $won->sum('current_bid'),
                    'last_active' => $bids->max('created_at'),
                    'is_repeat' => $auctionCount >= 2,
                ]);
            }
        }

        // Peak Activity
        $peakDay = null;
        $peakHour = null;
        if ($allViews->isNotEmpty()) {
            $dayOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $dayCounts = $allViews->groupBy(fn($v) => $v->created_at->dayOfWeek)
                ->map(fn($v) => $v->count())
                ->sortDesc();
            $peakDay = $dayOfWeek[$dayCounts->keys()->first()] ?? null;

            $hourCounts = $allViews->groupBy(fn($v) => $v->created_at->format('H'))
                ->map(fn($v) => $v->count())
                ->sortDesc();
            $peakHourNum = (int) $hourCounts->keys()->first();
            $peakHourEnd = ($peakHourNum + 2) % 24;
            $peakHour = date('g A', mktime($peakHourNum)) . ' - ' . date('g A', mktime($peakHourEnd));
        }

        // Lot Interest Alerts (high views, 0 bids, from live/upcoming auctions)
        $alertLots = collect();
        if ($lotViewCounts->isNotEmpty()) {
            $activeLotIds = \App\Models\Lot::whereIn('event_id', $auctionIds)
                ->whereHas('auction', fn($q) => $q->whereIn('status', ['live', 'upcoming']))
                ->pluck('id')
                ->toArray();

            foreach ($lotViewCounts as $lotId => $viewCount) {
                if (!in_array($lotId, $activeLotIds)) continue;
                if ($viewCount < 3) continue; // Only alert if meaningful views

                $bidCount = $bidCounts->get($lotId, 0) ?? 0;
                if ($bidCount === 0) {
                    $lot = $topLots->get($lotId);
                    if ($lot) {
                        $alertLots->push([
                            'lot' => $lot,
                            'views' => $viewCount,
                            'watchlisters' => $watchlistCounts->get($lotId, 0) ?? 0,
                        ]);
                    }
                }
            }
        }

        // All lot IDs for this auctioneer (unfiltered) — used to evaluate
        // cross-period bidder history (for new vs returning, follower conversion, etc.)
        $allAuctioneerLotIds = \App\Models\Lot::whereIn('event_id', $auctionIds)->pluck('id')->toArray();

        // 1. New vs Returning bidders
        // - With a date range: "returning" = also bid before the period on this auctioneer's lots
        // - All-time: "returning" = has bid across >=2 distinct auctions with this auctioneer
        $periodBidderIds = $allBids->pluck('user_id')->unique()->filter()->values();
        $newBidders = 0;
        $returningBidders = 0;
        if ($periodBidderIds->isNotEmpty()) {
            if ($dateFrom) {
                $returningBidderIds = \App\Models\Bid::whereIn('lot_id', $allAuctioneerLotIds)
                    ->whereIn('user_id', $periodBidderIds->toArray())
                    ->where('created_at', '<', $dateFrom)
                    ->pluck('user_id')->unique();
                $returningBidders = $returningBidderIds->count();
                $newBidders = $periodBidderIds->count() - $returningBidders;
            } else {
                $auctionsPerBidder = \App\Models\Bid::whereIn('bids.lot_id', $allAuctioneerLotIds)
                    ->whereIn('bids.user_id', $periodBidderIds->toArray())
                    ->join('lots', 'bids.lot_id', '=', 'lots.id')
                    ->select('bids.user_id', 'lots.event_id')
                    ->distinct()
                    ->get()
                    ->groupBy('user_id');
                $returningBidders = $auctionsPerBidder->filter(fn($rows) => $rows->count() >= 2)->count();
                $newBidders = $periodBidderIds->count() - $returningBidders;
            }
        }
        $totalBiddersInPeriod = $periodBidderIds->count();
        $returningBidderPct = $totalBiddersInPeriod > 0
            ? round(($returningBidders / $totalBiddersInPeriod) * 100, 1)
            : 0;

        // 2. Follower conversion (all-time — followers accumulate over time)
        $followerUserIds = \App\Models\AuctioneerFollower::where('auctioneer_id', $auctioneer->id)
            ->pluck('user_id')->unique();
        $followerCount = $followerUserIds->count();
        $followerBidders = 0;
        if ($followerCount > 0 && !empty($allAuctioneerLotIds)) {
            $followerBidders = \App\Models\Bid::whereIn('lot_id', $allAuctioneerLotIds)
                ->whereIn('user_id', $followerUserIds->toArray())
                ->distinct('user_id')
                ->count('user_id');
        }
        $followerConversionPct = $followerCount > 0
            ? round(($followerBidders / $followerCount) * 100, 1)
            : 0;

        // 3. Watchlist -> Bid conversion (all-time, scoped to this auctioneer's lots)
        $totalWatchlistEntries = 0;
        $watchlistConverted = 0;
        if (!empty($allAuctioneerLotIds)) {
            $watchlistEntries = \App\Models\Watchlist::whereIn('lot_id', $allAuctioneerLotIds)
                ->select('user_id', 'lot_id')->get();
            $totalWatchlistEntries = $watchlistEntries->count();
            if ($totalWatchlistEntries > 0) {
                $bidPairs = \App\Models\Bid::whereIn('lot_id', $allAuctioneerLotIds)
                    ->select('user_id', 'lot_id')->distinct()->get()
                    ->mapWithKeys(fn($b) => [$b->user_id . '-' . $b->lot_id => true]);
                foreach ($watchlistEntries as $wl) {
                    if (isset($bidPairs[$wl->user_id . '-' . $wl->lot_id])) {
                        $watchlistConverted++;
                    }
                }
            }
        }
        $watchlistConversionPct = $totalWatchlistEntries > 0
            ? round(($watchlistConverted / $totalWatchlistEntries) * 100, 1)
            : 0;

        // 4. Underbidders ("nearly won") — users who placed the top losing bid on sold lots
        $underbidders = collect();
        $endedLotsQuery = \App\Models\Lot::whereIn('id', $lotIds)
            ->where('status', 'sold')
            ->whereNotNull('winning_bidder_id');
        if ($dateFrom) {
            $endedLotsQuery->where('updated_at', '>=', $dateFrom);
        }
        $endedLots = $endedLotsQuery->with('auction')->get();
        if ($endedLots->isNotEmpty()) {
            $nearMissEntries = collect();
            foreach ($endedLots as $lot) {
                $topLosing = \App\Models\Bid::where('lot_id', $lot->id)
                    ->where('user_id', '!=', $lot->winning_bidder_id)
                    ->orderByDesc('amount')
                    ->first();
                if ($topLosing) {
                    $nearMissEntries->push([
                        'lot' => $lot,
                        'user_id' => $topLosing->user_id,
                        'their_bid' => (float) $topLosing->amount,
                        'winning_bid' => (float) $lot->current_bid,
                        'gap' => (float) $lot->current_bid - (float) $topLosing->amount,
                    ]);
                }
            }
            if ($nearMissEntries->isNotEmpty()) {
                $byUser = $nearMissEntries->groupBy('user_id');
                $userLookup = \App\Models\User::whereIn('id', $byUser->keys()->toArray())->get()->keyBy('id');
                $underbidders = $byUser->map(function ($entries, $userId) use ($userLookup) {
                    $userRow = $userLookup->get($userId);
                    if (!$userRow) return null;
                    $parts = explode('@', $userRow->email);
                    return [
                        'user' => $userRow,
                        'masked_email' => substr($parts[0], 0, 1) . '***@' . ($parts[1] ?? ''),
                        'near_misses' => $entries->count(),
                        'highest_bid' => $entries->max('their_bid'),
                        'avg_gap' => round($entries->avg('gap'), 2),
                        'last_lot' => $entries->last()['lot'],
                    ];
                })->filter()->sortByDesc('near_misses')->take(15)->values();
            }
        }

        // 5. Bid timing distribution — buckets by time-until-lot-close at bid moment
        $bidTiming = [
            'last_5min' => 0,
            'last_hour' => 0,
            'last_day' => 0,
            'earlier' => 0,
        ];
        if ($allBids->isNotEmpty()) {
            $bidLotIds = $allBids->pluck('lot_id')->unique()->toArray();
            $lotEndTimes = \App\Models\Lot::whereIn('id', $bidLotIds)->pluck('end_time', 'id');
            foreach ($allBids as $bid) {
                $endTime = $lotEndTimes->get($bid->lot_id);
                if (!$endTime) continue;
                $secondsBeforeEnd = $endTime->getTimestamp() - $bid->created_at->getTimestamp();
                if ($secondsBeforeEnd < 0) continue;
                $minutesBefore = $secondsBeforeEnd / 60;
                if ($minutesBefore <= 5) {
                    $bidTiming['last_5min']++;
                } elseif ($minutesBefore <= 60) {
                    $bidTiming['last_hour']++;
                } elseif ($minutesBefore <= 1440) {
                    $bidTiming['last_day']++;
                } else {
                    $bidTiming['earlier']++;
                }
            }
        }
        $bidTimingTotal = array_sum($bidTiming);
        $bidTimingPct = [];
        foreach ($bidTiming as $k => $v) {
            $bidTimingPct[$k] = $bidTimingTotal > 0 ? round(($v / $bidTimingTotal) * 100, 1) : 0;
        }

        // 6. Bidder geography (by province)
        $bidderGeography = collect();
        if ($periodBidderIds->isNotEmpty()) {
            $bidderGeography = \App\Models\User::whereIn('id', $periodBidderIds->toArray())
                ->select('province')
                ->get()
                ->groupBy(fn($u) => $u->province ?: 'Unknown')
                ->map(fn($rows) => $rows->count())
                ->sortDesc()
                ->take(10);
        }

        // 7. Follower growth over the period
        $followerGrowth = collect();
        if ($dateFrom) {
            $days = (int) $period;
            $rawCounts = \App\Models\AuctioneerFollower::where('auctioneer_id', $auctioneer->id)
                ->where('created_at', '>=', $dateFrom)
                ->get()
                ->groupBy(fn($f) => $f->created_at->format('Y-m-d'))
                ->map(fn($group) => $group->count());
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $followerGrowth->push([
                    'label' => now()->subDays($i)->format('M d'),
                    'count' => $rawCounts->get($date, 0),
                ]);
            }
        } else {
            $followerGrowth = \App\Models\AuctioneerFollower::where('auctioneer_id', $auctioneer->id)
                ->get()
                ->groupBy(fn($f) => $f->created_at->format('Y-m'))
                ->map(fn($group, $month) => [
                    'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'count' => $group->count(),
                ])
                ->sortKeys()
                ->values();
        }
        $followerGrowthMax = $followerGrowth->max('count') ?: 1;

        $engagement = [
            'new_bidders' => $newBidders,
            'returning_bidders' => $returningBidders,
            'total_period_bidders' => $totalBiddersInPeriod,
            'returning_pct' => $returningBidderPct,
            'follower_count' => $followerCount,
            'follower_bidders' => $followerBidders,
            'follower_conversion_pct' => $followerConversionPct,
            'watchlist_total' => $totalWatchlistEntries,
            'watchlist_converted' => $watchlistConverted,
            'watchlist_conversion_pct' => $watchlistConversionPct,
        ];

        // Auctions for filter dropdown
        $auctions = $auctioneer->auctions()
            ->whereIn('status', ['live', 'upcoming', 'ended'])
            ->orderBy('start_time', 'desc')
            ->get();

        return view('seller.bidder-insights', compact(
            'auctioneer', 'stats', 'deviceBreakdown', 'mostViewedLots',
            'topBidders', 'peakDay', 'peakHour', 'alertLots', 'auctions',
            'period', 'filteredAuctionId',
            'engagement', 'underbidders', 'bidTiming', 'bidTimingPct', 'bidTimingTotal',
            'bidderGeography', 'followerGrowth', 'followerGrowthMax'
        ));
    }

    /**
     * Geocode an address to get latitude and longitude coordinates.
     * Uses OpenStreetMap Nominatim API (free, no API key required).
     */
    private function geocodeAddress(string $address, string $city, string $province): array
    {
        try {
            // Build full address for South Africa
            $fullAddress = urlencode("{$address}, {$city}, {$province}, South Africa");

            // Query Nominatim API
            $url = "https://nominatim.openstreetmap.org/search?q={$fullAddress}&format=json&limit=1&countrycodes=za";

            // Set user agent (required by Nominatim)
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: " . config('branding.name', 'BasicBidall') . " Auction Platform\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                // Geocoding failed, return default coordinates (center of South Africa)
                return ['lat' => -29.0, 'lng' => 24.0];
            }

            $data = json_decode($response, true);

            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }

            // No results found, try city/province only
            $cityAddress = urlencode("{$city}, {$province}, South Africa");
            $url = "https://nominatim.openstreetmap.org/search?q={$cityAddress}&format=json&limit=1&countrycodes=za";
            $response = @file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lng' => (float) $data[0]['lon'],
                    ];
                }
            }

            // Fallback to center of South Africa
            return ['lat' => -29.0, 'lng' => 24.0];

        } catch (\Exception $e) {
            // On any error, return default coordinates
            \Log::warning("Geocoding failed: " . $e->getMessage());
            return ['lat' => -29.0, 'lng' => 24.0];
        }
    }

    /**
     * Show staff management page.
     */
    public function staffIndex()
    {
        $auctioneer = auth()->user()->auctioneer;

        $staffMembers = $auctioneer->staffMembers()->with('user')->get();
        $pendingInvites = $auctioneer->staffInvites()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return view('seller.staff', compact('auctioneer', 'staffMembers', 'pendingInvites'));
    }

    /**
     * Generate a staff invite link.
     */
    public function generateStaffInvite(Request $request)
    {
        $request->validate([
            'staff_role' => ['required', 'in:lot_manager,auction_manager,collections_manager'],
        ]);

        $auctioneer = auth()->user()->auctioneer;

        $invite = \App\Models\StaffInvite::create([
            'auctioneer_id' => $auctioneer->id,
            'staff_role' => $request->staff_role,
            'token' => \App\Models\StaffInvite::generateToken(),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = route('register.staff', $invite->token);

        return back()->with('success', 'Invite link generated! Share this link: ' . $inviteUrl)
            ->with('invite_url', $inviteUrl);
    }

    /**
     * Revoke a pending staff invite.
     */
    public function revokeStaffInvite(\App\Models\StaffInvite $invite)
    {
        $auctioneer = auth()->user()->auctioneer;

        if ($invite->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        $invite->update(['expires_at' => now()]);

        return back()->with('success', 'Invite revoked.');
    }

    /**
     * Toggle staff member active/inactive.
     */
    public function toggleStaffActive(\App\Models\StaffMember $staffMember)
    {
        $auctioneer = auth()->user()->auctioneer;

        if ($staffMember->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        $staffMember->update(['is_active' => !$staffMember->is_active]);

        $status = $staffMember->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Staff member {$status}.");
    }

    /**
     * Remove a staff member.
     */
    public function removeStaff(\App\Models\StaffMember $staffMember)
    {
        $auctioneer = auth()->user()->auctioneer;

        if ($staffMember->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        $user = $staffMember->user;
        $staffMember->delete();

        // Revert user role to bidder
        $user->update(['role' => 'bidder']);

        return back()->with('success', 'Staff member removed.');
    }

    /**
     * Show the auctioneer join/invite landing page.
     */
    public function joinPage(Auctioneer $auctioneer)
    {
        if (!$auctioneer->is_activated) {
            abort(404);
        }

        $auctioneer->load('user');

        // Store ref in session so it persists through login/register flow
        session(['join_ref' => $auctioneer->slug]);

        $isFollowing = false;
        if (auth()->check()) {
            $isFollowing = AuctioneerFollower::where('user_id', auth()->id())
                ->where('auctioneer_id', $auctioneer->id)
                ->exists();
        }

        // Get some stats to show credibility
        $auctionIds = $auctioneer->auctions()->pluck('id');
        $stats = [
            'total_auctions' => $auctionIds->count(),
            'live_auctions' => $auctioneer->auctions()->where('status', 'live')->count(),
            'upcoming_auctions' => $auctioneer->auctions()->where('status', 'upcoming')->count(),
            'followers' => $auctioneer->followers()->count(),
        ];

        $averageRating = round($auctioneer->ratings()->avg('rating'), 1);
        $totalRatings = $auctioneer->ratings()->count();

        // Get live/upcoming auctions to show
        $auctions = $auctioneer->auctions()
            ->whereIn('status', ['live', 'upcoming'])
            ->withCount('lots')
            ->orderByRaw("FIELD(status, 'live', 'upcoming')")
            ->limit(6)
            ->get();

        return view('auctioneer.join', compact(
            'auctioneer', 'isFollowing', 'stats', 'averageRating', 'totalRatings', 'auctions'
        ));
    }

    /**
     * Handle follow from the join page (for logged-in users).
     */
    public function joinFollow(Auctioneer $auctioneer)
    {
        if (!$auctioneer->is_activated) {
            abort(404);
        }

        $user = auth()->user();

        $existing = AuctioneerFollower::where('user_id', $user->id)
            ->where('auctioneer_id', $auctioneer->id)
            ->first();

        if (!$existing) {
            AuctioneerFollower::create([
                'user_id' => $user->id,
                'auctioneer_id' => $auctioneer->id,
            ]);
        }

        return redirect()->route('auctioneer.show', $auctioneer)
            ->with('success', 'You are now following ' . $auctioneer->business_name . '!');
    }

    /**
     * WhatsApp Broadcast Builder page.
     */
    public function whatsappBlast()
    {
        $user = auth()->user();
        $auctioneer = $user->resolveAuctioneer();

        if (!$auctioneer) {
            return redirect()->route('seller.dashboard');
        }

        // Get auctions that have shareable lots (upcoming or live)
        $auctions = $auctioneer->auctions()
            ->whereIn('status', ['upcoming', 'live'])
            ->with(['lots' => function ($q) {
                $q->whereNull('withdrawn_at');
            }, 'lots.images'])
            ->orderBy('start_time', 'asc')
            ->get();

        // Pre-build JSON data for Alpine.js (avoids Blade parsing issues with @json + closures)
        $auctionsJson = $auctions->map(function ($auction) {
            return [
                'id' => $auction->id,
                'title' => $auction->title,
                'status' => $auction->status,
                'slug' => $auction->slug,
                'start_time' => $auction->start_time->format('M d, Y \a\t H:i'),
                'end_time' => $auction->end_time ? $auction->end_time->format('M d, Y \a\t H:i') : null,
                'auction_type' => $auction->auction_type,
                'url' => route('auctions.show', $auction->slug),
                'lots' => $auction->lots->map(function ($lot) use ($auction) {
                    if ($auction->isDutch()) {
                        $priceLabel = formatCurrency($lot->dutch_start_price) . ' start';
                    } elseif ($auction->isSealed()) {
                        $priceLabel = $lot->reserve_price ? formatCurrency($lot->reserve_price) . ' reserve' : 'No reserve';
                    } else {
                        $priceLabel = formatCurrency($lot->current_bid ?? $lot->starting_bid);
                    }
                    return [
                        'id' => $lot->id,
                        'lot_number' => $lot->lot_number,
                        'title' => $lot->title,
                        'starting_bid' => $lot->starting_bid,
                        'current_bid' => $lot->current_bid,
                        'bids' => $lot->total_bids ?? 0,
                        'price_label' => $priceLabel,
                    ];
                })->values(),
            ];
        });

        return view('seller.whatsapp-blast', compact('auctioneer', 'auctions', 'auctionsJson'));
    }
}
