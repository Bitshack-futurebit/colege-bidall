<?php

use App\Http\Controllers\Api\BiddingController;
use App\Http\Controllers\Api\LotStatusController;
use App\Http\Controllers\Api\CreditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Real-time endpoints for polling and AJAX requests.
|
*/

// CSRF token refresh (for PWA / backgrounded tabs) — no auth required
Route::middleware(['web'])->get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

Route::middleware(['web', 'auth'])->group(function () {

    // Push Notification Subscriptions
    Route::post('/push/subscribe', [\App\Http\Controllers\PushNotificationController::class, 'subscribe'])->name('api.push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushNotificationController::class, 'unsubscribe'])->name('api.push.unsubscribe');

    // In-app Notification Bell
    Route::get('/notifications/unread', [\App\Http\Controllers\Api\NotificationController::class, 'unread'])->name('api.notifications.unread');
    Route::get('/notifications/history', [\App\Http\Controllers\Api\NotificationController::class, 'history'])->name('api.notifications.history');
    Route::post('/notifications/{push_notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead'])->name('api.notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('api.notifications.read-all');

    // Real-time Bidding
    Route::prefix('lots')->group(function () {
        Route::post('/{lot}/bid', [BiddingController::class, 'place'])->middleware('throttle:30,1')->name('api.lots.bid');
        Route::get('/{lot}/user-status', [LotStatusController::class, 'userStatus'])->name('api.lots.user-status');
        Route::get('/{lot}/bids', [BiddingController::class, 'history'])->name('api.lots.bids');
        Route::post('/{lot}/proxy', [BiddingController::class, 'setProxy'])->middleware('throttle:10,1')->name('api.lots.proxy.set');
        Route::delete('/{lot}/proxy', [BiddingController::class, 'cancelProxy'])->name('api.lots.proxy.cancel');
        Route::post('/{lot}/buy', [BiddingController::class, 'dutchBuy'])->middleware('throttle:20,1')->name('api.lots.dutch-buy');
        Route::post('/{lot}/sealed-bid', [BiddingController::class, 'sealedBid'])->middleware('throttle:10,1')->name('api.lots.sealed-bid');
    });

    // Seller Live Report Data
    Route::get('/auctions/{auction}/seller-report', [\App\Http\Controllers\AuctionController::class, 'liveReportData'])->name('api.auctions.seller-report');

    // Credit Balance (for monitoring)
    Route::get('/credits/balance', [CreditController::class, 'balance'])->name('api.credits.balance');

    // Watchlist Toggle
    Route::post('/lots/{lot}/watchlist', [BiddingController::class, 'toggleWatchlist'])->name('api.lots.watchlist');

    // Supplier search (auctioneer-scoped, for lot create/edit)
    Route::get('/suppliers/search', [\App\Http\Controllers\Api\SupplierController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('api.suppliers.search');
});

// Public API endpoints (no auth/session — maximum performance for polling)
Route::get('/auctioneers/map', [\App\Http\Controllers\AuctioneerController::class, 'mapData'])->name('api.auctioneers.map');
Route::get('/lots/{lot}/status', [LotStatusController::class, 'status'])->name('api.lots.status');
Route::get('/lots/batch-status', [LotStatusController::class, 'batchStatus'])->name('api.lots.batch-status');
Route::get('/auctions/{auction}/status', [LotStatusController::class, 'auctionStatus'])->name('api.auctions.status');
