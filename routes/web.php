<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuctioneerController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BulkMailController;
use App\Http\Controllers\Admin\TermsController;
use App\Http\Controllers\Admin\AgentsController;
use App\Http\Controllers\Admin\CommunityRegionsController;
use App\Http\Controllers\Admin\CommunitySchedulesController;
use App\Http\Controllers\AgentApplicationController;
use App\Http\Controllers\CommunityAuctionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Debug auction - comprehensive diagnostic tool
Route::get('/debug-auction/{id?}', function($id = null) {
    // Get most recent auction if no ID provided
    if (!$id) {
        $auction = \App\Models\Auction::orderBy('created_at', 'desc')->first();
    } else {
        $auction = \App\Models\Auction::find($id);
    }

    if (!$auction) {
        return view('debug-auction', ['error' => 'Auction not found']);
    }

    $auction->load(['auctioneer.user', 'lots.bids.user', 'lots.images']);

    $now = now();

    // Collect lots data
    $lotsData = $auction->lots->map(function($lot) use ($now) {
        $topBid = $lot->bids()->orderBy('amount', 'desc')->first();
        return [
            'lot_number' => $lot->lot_number,
            'title' => $lot->title,
            'status' => $lot->status,
            'starting_bid' => $lot->starting_bid,
            'current_bid' => $lot->current_bid,
            'tier' => $lot->image_tier,
            'images_count' => $lot->images->count(),
            'end_time' => $lot->end_time?->toDateTimeString(),
            'is_past_end' => $lot->end_time && $lot->end_time < $now,
            'withdrawn' => $lot->withdrawn_at ? true : false,
            'withdrawn_at' => $lot->withdrawn_at?->toDateTimeString(),
            'total_bids' => $lot->bids->count(),
            'top_bidder' => $topBid ? $topBid->user->name : null,
            'winning_bid' => $topBid ? $topBid->amount : null,
        ];
    });

    // Get credit transactions for this auction's auctioneer
    $creditTxns = \App\Models\CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)
        ->where('created_at', '>=', $auction->created_at->subHours(1))
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($txn) {
            return [
                'time' => $txn->created_at->toDateTimeString(),
                'type' => $txn->type,
                'amount' => $txn->amount,
                'balance_after' => $txn->balance_after,
                'description' => $txn->description,
            ];
        });

    // Get all bids for this auction
    $allBids = \App\Models\Bid::whereHas('lot', function($q) use ($auction) {
        $q->where('event_id', $auction->id);
    })->with(['user', 'lot'])->orderBy('created_at', 'desc')->get()->map(function($bid) {
        return [
            'time' => $bid->created_at->toDateTimeString(),
            'lot' => "#{$bid->lot->lot_number} - {$bid->lot->title}",
            'bidder' => $bid->user->name,
            'amount' => $bid->amount,
        ];
    });

    return view('debug-auction', [
        'auction' => [
            'id' => $auction->id,
            'title' => $auction->title,
            'auctioneer' => $auction->auctioneer->business_name,
            'status' => $auction->status,
            'created_at' => $auction->created_at->toDateTimeString(),
            'start_time' => $auction->start_time?->toDateTimeString(),
            'end_time' => $auction->end_time?->toDateTimeString(),
            'total_lots' => $auction->total_lots,
        ],
        'current_time' => $now->toDateTimeString(),
        'time_checks' => [
            'should_be_live' => $auction->start_time && $auction->start_time <= $now && $auction->end_time > $now,
            'should_be_ended' => $auction->end_time && $auction->end_time <= $now,
            'status_correct' => (
                ($auction->status === 'draft' && (!$auction->start_time || $auction->start_time > $now)) ||
                ($auction->status === 'upcoming' && $auction->start_time > $now) ||
                ($auction->status === 'live' && $auction->start_time <= $now && $auction->end_time > $now) ||
                ($auction->status === 'ended' && $auction->end_time <= $now)
            ),
        ],
        'warnings' => [
            'stuck_in_draft' => $auction->status === 'draft' && $auction->start_time && $auction->start_time <= $now,
            'stuck_in_upcoming' => $auction->status === 'upcoming' && $auction->start_time <= $now,
            'stuck_in_live' => $auction->status === 'live' && $auction->end_time <= $now,
        ],
        'lots' => $lotsData,
        'credit_transactions' => $creditTxns,
        'all_bids' => $allBids,
        'auctioneer_credit_balance' => $auction->auctioneer->credit_balance,
    ]);
})->name('debug.auction');

// Check email exists (for login UX)
Route::get('/api/check-email', function(\Illuminate\Http\Request $request) {
    $email = $request->get('email');
    if (!$email) {
        return response()->json(['exists' => false, 'role' => null]);
    }

    $user = \App\Models\User::where('email', $email)->first();

    return response()->json([
        'exists' => $user !== null,
        'role' => $user ? $user->role : null,
    ]);
});

// Check PHP upload limits - diagnostic tool
Route::get('/check-upload-limits', function() {
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    $memoryLimit = ini_get('memory_limit');
    $maxExecutionTime = ini_get('max_execution_time');
    $maxInputTime = ini_get('max_input_time');

    $convertToBytes = function($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    };

    $uploadBytes = $convertToBytes($uploadMaxFilesize);
    $postBytes = $convertToBytes($postMaxSize);
    $memoryBytes = $convertToBytes($memoryLimit);
    $effectiveLimit = min($uploadBytes, $postBytes);

    return view('check-upload-limits', [
        'uploadMaxFilesize' => $uploadMaxFilesize,
        'postMaxSize' => $postMaxSize,
        'memoryLimit' => $memoryLimit,
        'maxExecutionTime' => $maxExecutionTime,
        'maxInputTime' => $maxInputTime,
        'uploadBytes' => $uploadBytes,
        'postBytes' => $postBytes,
        'memoryBytes' => $memoryBytes,
        'effectiveLimit' => $effectiveLimit,
    ]);
})->name('check.upload.limits');

// Public Routes
/*
|--------------------------------------------------------------------------
| External Cron Endpoint
|--------------------------------------------------------------------------
| Called every minute by cron-job.org (workaround for shared hosting
| cron restrictions). Secured by CRON_SECRET token in .env.
*/
Route::get('/cron/run', function (\Illuminate\Http\Request $request) {
    $secret = config('platform.cron_secret');

    if (!$secret || $request->query('token') !== $secret) {
        abort(403);
    }

    \Illuminate\Support\Facades\Artisan::call('auctions:update-statuses');
    \Illuminate\Support\Facades\Artisan::call('emails:send-auction-summaries');
    \Illuminate\Support\Facades\Artisan::call('transactions:expire-stale');
    \Illuminate\Support\Facades\Artisan::call('relists:reset');
    \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 3,
        '--timeout' => 60,
    ]);

    return response()->json([
        'status' => 'ok',
        'time'   => now()->toDateTimeString(),
    ]);
})->name('cron.run');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// OG image proxy — serves storage images with correct Content-Type for social crawlers
Route::get('/og-image/{auctioneer:slug}/banner', function (\App\Models\Auctioneer $auctioneer) {
    abort_unless($auctioneer->banner_image, 404);
    $content = \Illuminate\Support\Facades\Storage::disk('public')->get($auctioneer->banner_image);
    abort_unless($content !== false && $content !== null, 404);
    $mime = match(strtolower(pathinfo($auctioneer->banner_image, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'webp'        => 'image/webp',
        'gif'         => 'image/gif',
        default       => 'image/png',
    };
    return response($content, 200, [
        'Content-Type'            => $mime,
        'Cache-Control'           => 'no-store, no-cache',
        'X-LiteSpeed-Cache-Control' => 'no-cache',
    ]);
})->name('og.banner');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/how-it-works', [HomeController::class, 'howItWorks'])->name('how-it-works');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/register/seller', [AuthController::class, 'showSellerRegister'])->name('register.seller');
    Route::post('/register/seller', [AuthController::class, 'registerSeller']);
    Route::get('/register/staff/{token}', [AuthController::class, 'showStaffRegister'])->name('register.staff');
    Route::post('/register/staff/{token}', [AuthController::class, 'registerStaff'])->name('register.staff.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Terms Acceptance (for existing users when terms are updated)
Route::middleware('auth')->group(function () {
    Route::get('/terms/accept', function () {
        $terms = auth()->user()->unacceptedTerms();
        if (empty($terms)) {
            return redirect('/');
        }
        return view('auth.accept-terms', compact('terms'));
    })->name('terms.accept');

    Route::post('/terms/accept', function (\Illuminate\Http\Request $request) {
        $unaccepted = auth()->user()->unacceptedTerms();

        if (empty($unaccepted)) {
            return redirect('/');
        }

        // Validate that all required terms are checked
        $rules = [];
        foreach ($unaccepted as $version) {
            $rules["accept_terms.{$version->id}"] = ['required', 'accepted'];
        }

        $request->validate($rules, [
            'accept_terms.*.required' => 'You must accept all Terms & Conditions.',
            'accept_terms.*.accepted' => 'You must accept all Terms & Conditions.',
        ]);

        foreach ($unaccepted as $version) {
            \App\Models\TermsAcceptance::firstOrCreate([
                'user_id' => auth()->id(),
                'terms_version_id' => $version->id,
            ], [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted_at' => now(),
            ]);
        }

        return redirect('/')->with('success', 'Thank you for accepting the updated terms.');
    })->name('terms.accept.store');
});

// Email Verification
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Auctioneer Invite/Join Link
Route::get('/join/{auctioneer:slug}', [AuctioneerController::class, 'joinPage'])->name('auctioneer.join');
Route::post('/join/{auctioneer:slug}', [AuctioneerController::class, 'joinFollow'])->name('auctioneer.join.follow')->middleware('auth');

// Public Auctioneers Directory
Route::get('/auctioneers', [AuctioneerController::class, 'index'])->name('auctioneers.index');

// Public Communities Directory
Route::get('/communities', [AuctioneerController::class, 'communities'])->name('communities.index');

// Public Auctioneer Profile
Route::get('/auctioneer/{auctioneer:slug}', [AuctioneerController::class, 'show'])->name('auctioneer.show');

// Public agent recruitment landing page (shareable marketing link)
Route::view('/become-an-agent', 'agents.landing')->name('agents.landing');

// Public community-seller explainer (shareable: "how to sell at a community auction")
Route::view('/sell-in-community', 'community.sell-landing')->name('community.sell-landing');

// White-label dynamic PWA manifest (per-auctioneer branding)
Route::get('/auctioneer/{auctioneer:slug}/manifest.webmanifest', [AuctioneerController::class, 'manifest'])->name('auctioneer.manifest');
Route::get('/auctioneer/{auctioneer:slug}/icon-{size}.png', [AuctioneerController::class, 'icon'])
    ->whereNumber('size')
    ->name('auctioneer.icon');

// Public Auctions
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{auction:slug}', [AuctionController::class, 'show'])->name('auctions.show');

// Public Lots
Route::get('/lots/{lot}', [LotController::class, 'show'])->name('lots.show');

// Community Auctions
Route::get('/community', fn () => redirect()->route('communities.index'))->name('community.index');
Route::get('/community/region/{region:slug}', [CommunityAuctionController::class, 'showRegion'])->name('community.region');
Route::middleware('auth')->group(function () {
    Route::post('/community/region/{region:slug}/join', [CommunityAuctionController::class, 'joinRegion'])->name('community.join');
    Route::get('/community/list-item', [CommunityAuctionController::class, 'createLot'])->name('community.create-lot');
    Route::post('/community/list-item', [CommunityAuctionController::class, 'storeLot'])->name('community.store-lot');
    Route::get('/community/my-lots', [CommunityAuctionController::class, 'myLots'])->name('community.my-lots');
    Route::get('/community/fees', [CommunityAuctionController::class, 'fees'])->name('community.fees');
    Route::post('/community/fees/cancel-pending', [CommunityAuctionController::class, 'cancelPendingFeePayment'])->name('community.fees.cancel-pending');
    Route::delete('/community/lot/{lot}', [CommunityAuctionController::class, 'deleteLot'])->name('community.delete-lot');
    Route::get('/community/confirm/{lot}', [CommunityAuctionController::class, 'showConfirmation'])->name('community.confirm');
    Route::post('/community/confirm/{lot}/accept', [CommunityAuctionController::class, 'confirmLot'])->name('community.confirm-accept');
    Route::post('/community/confirm/{lot}/decline', [CommunityAuctionController::class, 'declineLot'])->name('community.confirm-decline');
    Route::get('/community/lot/{lot}/invoice', [CommunityAuctionController::class, 'invoice'])->name('community.invoice');
    Route::post('/community/lot/{lot}/mark-paid', [CommunityAuctionController::class, 'markPaid'])->name('community.mark-paid');
    Route::post('/community/lot/{lot}/void-buyer-nonpayment', [CommunityAuctionController::class, 'voidBuyerNonPayment'])->name('community.void-buyer-nonpayment');
    Route::post('/community/lot/{lot}/relist', [CommunityAuctionController::class, 'relistLot'])->name('community.relist-lot');

    // Agent application (any logged-in non-staff/non-auctioneer/non-admin)
    Route::get('/agent/apply', [AgentApplicationController::class, 'show'])->name('agent.apply');
    Route::post('/agent/apply', [AgentApplicationController::class, 'store'])->name('agent.apply.store');

    // Agent dashboard (active agents only)
    Route::get('/agent', [\App\Http\Controllers\AgentController::class, 'dashboard'])->name('agent.dashboard');
    Route::get('/agent/whatsapp-blast', [\App\Http\Controllers\AgentController::class, 'whatsappBlast'])->name('agent.whatsapp-blast');
    Route::post('/agent/payout/request', [\App\Http\Controllers\AgentController::class, 'requestPayout'])->name('agent.payout.request');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Bidder Dashboard
    Route::get('/dashboard', [DashboardController::class, 'bidder'])
        ->name('dashboard')
        ->middleware('role:bidder,admin');

    Route::get('/dashboard/bids', [DashboardController::class, 'bids'])
        ->name('dashboard.bids')
        ->middleware('role:bidder,admin');

    Route::get('/dashboard/watchlist', [DashboardController::class, 'watchlist'])
        ->name('dashboard.watchlist')
        ->middleware('role:bidder,admin');

    Route::get('/dashboard/won', [DashboardController::class, 'won'])
        ->name('dashboard.won')
        ->middleware('role:bidder,admin');

    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])
        ->name('dashboard.profile')
        ->middleware('role:bidder,admin');

    Route::patch('/dashboard/profile', [DashboardController::class, 'updateProfile'])
        ->name('dashboard.profile.update')
        ->middleware('role:bidder,admin');

    // Auction Registration
    Route::post('/auctions/{auction}/register', [AuctionController::class, 'register'])->name('auctions.register');

    // Bidding
    Route::post('/lots/{lot}/bid', [BidController::class, 'place'])->name('bids.place');

    // Watchlist
    Route::post('/lots/{lot}/watchlist', [LotController::class, 'toggleWatchlist'])->name('lots.watchlist.toggle');

    // Follow & Rate Auctioneers
    Route::post('/auctioneer/{auctioneer}/follow', [AuctioneerController::class, 'toggleFollow'])->name('auctioneer.follow.toggle');
    Route::post('/auctioneer/{auctioneer}/rate', [AuctioneerController::class, 'rateAuctioneer'])->name('auctioneer.rate');
    Route::get('/dashboard/following', [DashboardController::class, 'following'])
        ->name('dashboard.following')
        ->middleware('role:bidder,admin');

    // Profile
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile.edit');
    Route::patch('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
});

// Seller Routes (accessible to auctioneers, staff, and admins)
Route::middleware(['auth', 'role:auctioneer,staff,admin'])->prefix('seller')->name('seller.')->group(function () {

    // Dashboard (all staff can view)
    Route::get('/', [AuctioneerController::class, 'dashboard'])->name('dashboard');

    // Owner-only routes (no staff access)
    Route::middleware('role:auctioneer,admin')->group(function () {
        // Activation
        Route::get('/activate', [AuctioneerController::class, 'showActivation'])->name('activate');
        Route::post('/activate/payment', [AuctioneerController::class, 'processActivation'])->name('activate.process');

        // Credits
        Route::get('/credits', [AuctioneerController::class, 'credits'])->name('credits');
        Route::post('/credits/purchase', [AuctioneerController::class, 'purchaseCredits'])->name('credits.purchase');

        // Profile
        Route::get('/profile', [AuctioneerController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AuctioneerController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile', [AuctioneerController::class, 'updateAccount'])->name('profile.update.account');
        Route::put('/profile/payfast', [AuctioneerController::class, 'updatePayfast'])->name('profile.update.payfast');

        // Transactions
        Route::get('/transactions', [AuctioneerController::class, 'transactions'])->name('transactions');

        // Accounting & Payouts
        Route::get('/accounting', [AuctioneerController::class, 'accounting'])->name('accounting');
        Route::get('/credit-ledger', [AuctioneerController::class, 'creditLedger'])->name('credit-ledger');
        Route::get('/sales', [AuctioneerController::class, 'salesHistory'])->name('sales');
        Route::get('/payouts', [AuctioneerController::class, 'payouts'])->name('payouts');
        Route::post('/payouts/request', [AuctioneerController::class, 'requestPayout'])->name('payouts.request');

        // Staff management
        Route::get('/staff', [AuctioneerController::class, 'staffIndex'])->name('staff');
        Route::post('/staff/invite', [AuctioneerController::class, 'generateStaffInvite'])->name('staff.invite');
        Route::delete('/staff/invite/{invite}', [AuctioneerController::class, 'revokeStaffInvite'])->name('staff.invite.revoke');
        Route::post('/staff/{staffMember}/toggle', [AuctioneerController::class, 'toggleStaffActive'])->name('staff.toggle');
        Route::delete('/staff/{staffMember}', [AuctioneerController::class, 'removeStaff'])->name('staff.remove');
    });

    // Followers (all staff can view)
    Route::get('/followers', [AuctioneerController::class, 'followers'])->name('followers');

    // Push Notifications (owner only)
    Route::middleware('role:auctioneer,admin')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\PushNotificationController::class, 'sellerIndex'])->name('notifications');
        Route::post('/notifications/send', [\App\Http\Controllers\PushNotificationController::class, 'sellerSend'])->name('notifications.send');
    });

    // WhatsApp Broadcast Builder (owner only)
    Route::get('/whatsapp-blast', [AuctioneerController::class, 'whatsappBlast'])->name('whatsapp-blast')->middleware('role:auctioneer,admin');

    // Auctions (requires activation)
    Route::middleware('auctioneer.active')->group(function () {
        // Auction list (all staff)
        Route::get('/auctions', [AuctionController::class, 'sellerIndex'])->name('auctions.index');

        // Auction CRUD (auction managers + owners) — create must be before {auction} wildcard
        Route::middleware('staff.permission:auctions')->group(function () {
            Route::get('/auctions/create', [AuctionController::class, 'create'])->name('auctions.create');
            Route::post('/auctions', [AuctionController::class, 'store'])->name('auctions.store');
            Route::get('/auctions/{auction}/edit', [AuctionController::class, 'edit'])->name('auctions.edit');
            Route::put('/auctions/{auction}', [AuctionController::class, 'update'])->name('auctions.update');
            Route::patch('/auctions/{auction}', [AuctionController::class, 'update']);
            Route::delete('/auctions/{auction}', [AuctionController::class, 'destroy'])->name('auctions.destroy');
            Route::post('/auctions/{auction}/go-live', [AuctionController::class, 'goLive'])->name('auctions.go-live');
            Route::post('/auctions/{auction}/end', [AuctionController::class, 'end'])->name('auctions.end');
        });

        // Auction detail view (all staff — must come after /auctions/create)
        Route::get('/auctions/{auction}', [AuctionController::class, 'sellerShow'])->name('auctions.show');

        // Lot CRUD (lot managers + auction managers + owners)
        Route::middleware('staff.permission:lots')->group(function () {
            Route::get('/auctions/{auction}/lots/create', [LotController::class, 'create'])->name('auctions.lots.create');
            Route::post('/auctions/{auction}/lots', [LotController::class, 'store'])->name('auctions.lots.store');
            Route::get('/auctions/{auction}/lots/{lot}/edit', [LotController::class, 'edit'])->name('auctions.lots.edit');
            Route::put('/auctions/{auction}/lots/{lot}', [LotController::class, 'update'])->name('auctions.lots.update');
            Route::patch('/auctions/{auction}/lots/{lot}', [LotController::class, 'update']);
            Route::delete('/auctions/{auction}/lots/{lot}', [LotController::class, 'destroy'])->name('auctions.lots.destroy');
            Route::post('/auctions/{auction}/lots/{lot}/withdraw', [LotController::class, 'withdraw'])->name('auctions.lots.withdraw');
            Route::post('/lots/{lot}/images', [LotController::class, 'uploadImages'])->name('lots.images.upload');
            Route::delete('/lots/{lot}/images/{image}', [LotController::class, 'deleteImage'])->name('lots.images.delete');

            // Relisting & unsold lot management
            Route::get('/unsold-lots', [AuctioneerController::class, 'unsoldLots'])->name('unsold-lots');
            Route::post('/lots/bulk-relist', [LotController::class, 'bulkRelist'])->name('lots.bulk-relist');
            Route::post('/lots/{lot}/relist', [LotController::class, 'relist'])->name('lots.relist');
            Route::delete('/lots/{lot}/delete-unsold', [LotController::class, 'destroyUnsold'])->name('lots.destroy-unsold');
            Route::delete('/lots/bulk-delete-unsold', [LotController::class, 'bulkDeleteUnsold'])->name('lots.bulk-delete-unsold');
        });

        // Collections management (collections managers + owners)
        Route::middleware('staff.permission:collections')->group(function () {
            Route::get('/collections', [AuctioneerController::class, 'collections'])->name('collections');
            Route::post('/collections/confirm', [AuctioneerController::class, 'confirmCollectionPayment'])->name('collections.confirm');
            Route::post('/collections/bidder/{user}/suspend', [AuctioneerController::class, 'suspendBidder'])->name('collections.suspend');
            Route::post('/collections/bidder/{user}/unsuspend', [AuctioneerController::class, 'unsuspendBidder'])->name('collections.unsuspend');
        });

        // Lot confirmation (subject to confirmation workflow)
        Route::middleware('staff.permission:auctions')->group(function () {
            Route::post('/lots/{lot}/confirm-sale', [AuctioneerController::class, 'confirmLot'])->name('lots.confirm-sale');
            Route::post('/lots/{lot}/reject-sale', [AuctioneerController::class, 'rejectLot'])->name('lots.reject-sale');
        });
    });

    // Analytics & Reports (all staff can view)
    Route::get('/analytics', [AuctioneerController::class, 'analytics'])->name('analytics');
    Route::get('/bidder-insights', [AuctioneerController::class, 'bidderInsights'])->name('bidder-insights');
    Route::get('/auctions/{auction}/analytics', [AuctionController::class, 'analytics'])->name('auctions.analytics');
    Route::get('/auctions/{auction}/report', [AuctionController::class, 'sellerAuctionReport'])->withTrashed()->name('auctions.report');
    Route::get('/auctions/{auction}/live', [AuctionController::class, 'sellerLiveReport'])->name('auctions.live-report');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Users
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/users/{user}/quick-stats', [AdminController::class, 'userQuickStats'])->name('users.quick-stats');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
    Route::post('/users/{user}/mail', [AdminController::class, 'mailUser'])->name('users.mail');
    Route::get('/users/{user}/id-document', [AdminController::class, 'viewIdDocument'])->name('users.id-document');
    Route::post('/users/{user}/verify-id', [AdminController::class, 'verifyId'])->name('users.verify-id');
    Route::post('/users/{user}/unverify-id', [AdminController::class, 'unverifyId'])->name('users.unverify-id');

    // Auctioneers
    Route::get('/auctioneers', [AdminController::class, 'auctioneers'])->name('auctioneers.index');
    Route::get('/auctioneers/{auctioneer}', [AdminController::class, 'showAuctioneer'])->name('auctioneers.show');
    Route::get('/auctioneers/{auctioneer}/credit-ledger', [AdminController::class, 'auctioneerCreditLedger'])->name('auctioneers.credit-ledger');
    Route::patch('/auctioneers/{auctioneer}/pricing', [AdminController::class, 'updateAuctioneerPricing'])->name('auctioneers.update-pricing');
    Route::post('/auctioneers/{auctioneer}/credits', [AdminController::class, 'addAuctioneerCredits'])->name('auctioneers.add-credits');
    Route::get('/auctioneers/{auctioneer}/settings', [AdminController::class, 'auctioneerSettings'])->name('auctioneers.settings');
    Route::patch('/auctioneers/{auctioneer}/relist-settings', [AdminController::class, 'updateRelistSettings'])->name('auctioneers.update-relist-settings');
    Route::get('/auctioneers/{auctioneer}/auctions/{auction}', [AdminController::class, 'auctioneerAuctionReport'])->withTrashed()->name('auctioneers.auction-report');
    Route::delete('/auctioneers/{auctioneer}/delete', [AdminController::class, 'hardDeleteAuctioneer'])->name('auctioneers.hard-delete');

    // Auctions
    Route::get('/auctions', [AdminController::class, 'auctions'])->name('auctions.index');
    Route::get('/auctions/{auction}/live', [AuctionController::class, 'sellerLiveReport'])->name('auctions.live-report');
    Route::delete('/auctions/{auction}', [AdminController::class, 'deleteAuction'])->name('auctions.delete');
    Route::post('/auctions/{auction}/facebook', [AdminController::class, 'postToFacebook'])->name('auctions.facebook');

    // Transactions
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [AdminController::class, 'showTransaction'])->name('transactions.show');

    // Revenue
    Route::get('/revenue', [AdminController::class, 'revenue'])->name('revenue');

    // Payouts
    Route::get('/payouts', [AdminController::class, 'payouts'])->name('payouts.index');
    Route::get('/payouts/{payout}', [AdminController::class, 'showPayout'])->name('payouts.show');
    Route::post('/payouts/{payout}/process', [AdminController::class, 'processPayout'])->name('payouts.process');
    Route::post('/payouts/{payout}/reject', [AdminController::class, 'rejectPayout'])->name('payouts.reject');

    // Activity Logs
    Route::get('/activity', [AdminController::class, 'activity'])->name('activity');

    // Broadcast Email
    Route::get('/broadcast', [AdminController::class, 'showBroadcast'])->name('broadcast');
    Route::post('/broadcast', [BulkMailController::class, 'send'])->name('broadcast.send');

    // Terms & Conditions
    Route::get('/terms', [TermsController::class, 'index'])->name('terms.index');
    Route::get('/terms/create', [TermsController::class, 'create'])->name('terms.create');
    Route::post('/terms', [TermsController::class, 'store'])->name('terms.store');
    Route::get('/terms/{term}/edit', [TermsController::class, 'edit'])->name('terms.edit');
    Route::put('/terms/{term}', [TermsController::class, 'update'])->name('terms.update');
    Route::delete('/terms/{term}', [TermsController::class, 'destroy'])->name('terms.destroy');

    // Push Notifications
    Route::get('/notifications', [\App\Http\Controllers\Admin\PushNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [\App\Http\Controllers\Admin\PushNotificationController::class, 'send'])->name('notifications.send');

    // Community Regions
    Route::get('/community-regions', [CommunityRegionsController::class, 'index'])->name('community-regions.index');
    Route::get('/community-regions/create', [CommunityRegionsController::class, 'create'])->name('community-regions.create');
    Route::post('/community-regions', [CommunityRegionsController::class, 'store'])->name('community-regions.store');
    Route::get('/community-regions/{region}/edit', [CommunityRegionsController::class, 'edit'])->name('community-regions.edit');
    Route::put('/community-regions/{region}', [CommunityRegionsController::class, 'update'])->name('community-regions.update');
    Route::post('/community-regions/{region}/toggle-active', [CommunityRegionsController::class, 'toggleActive'])->name('community-regions.toggle-active');
    Route::post('/community-regions/{region}/toggle-pilot', [CommunityRegionsController::class, 'togglePilot'])->name('community-regions.toggle-pilot');
    Route::post('/community-regions/{region}/profile', [CommunityRegionsController::class, 'updateProfile'])->name('community-regions.update-profile');
    Route::delete('/community-regions/{region}', [CommunityRegionsController::class, 'destroy'])->name('community-regions.destroy');

    // Community Schedules (nested under region)
    Route::post('/community-regions/{region}/schedules', [CommunitySchedulesController::class, 'store'])->name('community-schedules.store');
    Route::put('/community-regions/{region}/schedules/{schedule}', [CommunitySchedulesController::class, 'update'])->name('community-schedules.update');
    Route::post('/community-regions/{region}/schedules/{schedule}/toggle-active', [CommunitySchedulesController::class, 'toggleActive'])->name('community-schedules.toggle-active');
    Route::delete('/community-regions/{region}/schedules/{schedule}', [CommunitySchedulesController::class, 'destroy'])->name('community-schedules.destroy');
    Route::post('/community-regions/{region}/schedules/{schedule}/create-next', [CommunitySchedulesController::class, 'createNext'])->name('community-schedules.create-next');

    // Agents
    Route::get('/agents', [AgentsController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentsController::class, 'show'])->name('agents.show');
    Route::post('/agents/{agent}/approve', [AgentsController::class, 'approve'])->name('agents.approve');
    Route::post('/agents/{agent}/reject', [AgentsController::class, 'reject'])->name('agents.reject');
    Route::post('/agents/{agent}/suspend', [AgentsController::class, 'suspend'])->name('agents.suspend');
    Route::post('/agents/{agent}/reinstate', [AgentsController::class, 'reinstate'])->name('agents.reinstate');
    Route::post('/agents/{agent}/assign-community', [AgentsController::class, 'assignCommunity'])->name('agents.assign-community');
    Route::post('/agents/{agent}/unassign-community/{communityRegionId}', [AgentsController::class, 'unassignCommunity'])->name('agents.unassign-community');
    Route::post('/agents/mark-seller-paid', [AgentsController::class, 'markSellerPaid'])->name('agents.mark-seller-paid');
    Route::post('/agents/payouts/{payout}/pay', [AgentsController::class, 'payPayout'])->name('agents.pay-payout');
    Route::post('/agents/payouts/{payout}/reject', [AgentsController::class, 'rejectPayout'])->name('agents.reject-payout');

    // Buyer strikes review queue
    Route::get('/buyer-strikes', [\App\Http\Controllers\Admin\BuyerStrikesController::class, 'index'])->name('buyer-strikes.index');
    Route::post('/buyer-strikes/{strike}/reverse', [\App\Http\Controllers\Admin\BuyerStrikesController::class, 'reverse'])->name('buyer-strikes.reverse');

    // Community Auctions - testing helpers
    Route::post('/community-auctions/{auction}/go-live', [CommunitySchedulesController::class, 'goLive'])->name('community-auctions.go-live');
    Route::post('/community-auctions/{auction}/end-now', [CommunitySchedulesController::class, 'endNow'])->name('community-auctions.end-now');
    Route::post('/community-auctions/{auction}/reschedule', [CommunitySchedulesController::class, 'reschedule'])->name('community-auctions.reschedule');
    Route::delete('/community-auctions/{auction}', [CommunitySchedulesController::class, 'destroyAuction'])->name('community-auctions.destroy')->withTrashed();

    // Promo Codes
    Route::get('/promo-codes', [AdminController::class, 'promoCodes'])->name('promo-codes.index');
    Route::get('/promo-codes/create', [AdminController::class, 'createPromoCode'])->name('promo-codes.create');
    Route::post('/promo-codes', [AdminController::class, 'storePromoCode'])->name('promo-codes.store');
    Route::get('/promo-codes/{promoCode}/edit', [AdminController::class, 'editPromoCode'])->name('promo-codes.edit');
    Route::patch('/promo-codes/{promoCode}', [AdminController::class, 'updatePromoCode'])->name('promo-codes.update');
    Route::post('/promo-codes/{promoCode}/toggle', [AdminController::class, 'togglePromoCode'])->name('promo-codes.toggle');
});

// Payment Routes
Route::middleware('auth')->prefix('payment')->name('payment.')->group(function () {
    Route::post('/create', [PaymentController::class, 'create'])->name('create');
    Route::get('/return', [PaymentController::class, 'return'])->name('return');
    Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');

    // Lot payment methods
    Route::post('/lots/pay-now', [PaymentController::class, 'payForLotsNow'])->name('lots.paynow');
    Route::post('/lots/arrange-collection', [PaymentController::class, 'arrangeCollection'])->name('lots.arrange-collection');

    // Lightning (Blink) checkout — QR/invoice page + status poll for the frontend
    Route::get('/lightning/{paymentId}', [PaymentController::class, 'lightningCheckout'])->name('lightning');
    Route::get('/lightning/{paymentId}/status', [PaymentController::class, 'lightningStatus'])->name('lightning.status');
});

// Payment Webhook (no auth - verified by signature)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// Blink (Lightning) webhook — Svix-signed; verified on the RAW body in the controller
Route::post('/payment/blink/webhook', [PaymentController::class, 'blinkWebhook'])->name('payment.blink.webhook');

// Direct Payment to Auctioneer's PayFast
Route::middleware('auth')->prefix('direct-payment')->name('direct-payment.')->group(function () {
    Route::post('/lots', [\App\Http\Controllers\DirectPaymentController::class, 'payNow'])->name('lots');
    Route::get('/return', [\App\Http\Controllers\DirectPaymentController::class, 'return'])->name('return');
    Route::get('/cancel', [\App\Http\Controllers\DirectPaymentController::class, 'cancel'])->name('cancel');
});
Route::post('/direct-payment/webhook', [\App\Http\Controllers\DirectPaymentController::class, 'webhook'])->name('direct-payment.webhook');

// API Routes for Map
Route::get('/api/auctioneers/map', [AuctioneerController::class, 'mapData'])->name('api.auctioneers.map');

// ── Legacy / WordPress 410 Gone ──────────────────────────────────────────────
// These URLs were indexed from a previous WordPress install. Return 410 Gone
// so Google deindexes them faster than a standard 404.
$gone = response('Gone', 410);

// WordPress core patterns
Route::get('/wp-login.php', fn () => $gone);
Route::get('/wp-admin', fn () => $gone);
Route::get('/wp-admin/{any}', fn () => $gone)->where('any', '.*');
Route::get('/wp-content/{any}', fn () => $gone)->where('any', '.*');
Route::get('/wp-includes/{any}', fn () => $gone)->where('any', '.*');
Route::get('/wp-{file}.php', fn () => $gone)->where('file', '[a-z\-]+');
Route::get('/feed', fn () => $gone);
Route::get('/feed/{any}', fn () => $gone)->where('any', '.*');
Route::get('/author/{any}', fn () => $gone)->where('any', '.*');
Route::get('/category/{any}', fn () => $gone)->where('any', '.*');
Route::get('/tag/{any}', fn () => $gone)->where('any', '.*');

// WordPress date archive patterns
Route::get('/{year}/{month}/{day}/{any}', fn () => $gone)
    ->where(['year' => '\d{4}', 'month' => '\d{2}', 'day' => '\d{2}', 'any' => '.*']);
Route::get('/{year}/{month}/{any}', fn () => $gone)
    ->where(['year' => '\d{4}', 'month' => '\d{2}', 'any' => '.*']);

// Old WP auction plugin — redirect to current auctions listing
Route::get('/auction/{any}', fn () => redirect('/auctions', 301))->where('any', '.*');
Route::redirect('/all-auctions-by-category', '/auctions', 301);
Route::redirect('/auctions-for-nov-19', '/auctions', 301);
Route::redirect('/comming-auctions', '/auctions', 301);

// Known WordPress/test pages
Route::get('/hello-world', fn () => $gone);
Route::get('/a-contact-page', fn () => $gone);
Route::get('/shop', fn () => $gone);
Route::get('/shop/{any}', fn () => $gone)->where('any', '.*');
Route::get('/margate', fn () => $gone);

// Debug & test pages (Laravel dev leftovers)
Route::get('/debug-auctions', fn () => $gone);
Route::get('/debug-categories', fn () => $gone);
Route::get('/debug-no-divi', fn () => $gone);
Route::get('/test-lot-1', fn () => $gone);

// Old demo auction slug
Route::get('/auctions/demo-auction', fn () => $gone);
