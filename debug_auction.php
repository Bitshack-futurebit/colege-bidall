<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Auction;
use App\Models\Lot;
use App\Models\Bid;
use App\Models\CreditTransaction;
use App\Models\ActivityLog;
use Carbon\Carbon;

echo "=== AUCTION DEBUG REPORT ===\n\n";

// Get most recent auction
$auction = Auction::with(['auctioneer.user', 'lots.bids.user'])
    ->orderBy('created_at', 'desc')
    ->first();

if (!$auction) {
    echo "No auctions found.\n";
    exit;
}

echo "AUCTION: {$auction->title} (ID: {$auction->id})\n";
echo "Auctioneer: {$auction->auctioneer->business_name}\n";
echo "Status: {$auction->status}\n";
echo "Created: {$auction->created_at}\n";
echo "Start: {$auction->start_time}\n";
echo "End: {$auction->end_time}\n";
echo "Total Lots: {$auction->total_lots}\n";
echo "\n";

// Lots breakdown
echo "=== LOTS ===\n";
$lots = $auction->lots;
foreach ($lots as $lot) {
    echo "\nLot #{$lot->lot_number}: {$lot->title}\n";
    echo "  Status: {$lot->status}\n";
    echo "  Starting Bid: R" . number_format($lot->starting_bid, 2) . "\n";
    echo "  Current Bid: R" . number_format($lot->current_bid, 2) . "\n";
    echo "  End Time: {$lot->end_time}\n";
    echo "  Total Bids: {$lot->bids->count()}\n";
    echo "  Tier: {$lot->image_tier}\n";
    echo "  Withdrawn: " . ($lot->withdrawn_at ? "YES ({$lot->withdrawn_at})" : "NO") . "\n";

    if ($lot->bids->count() > 0) {
        echo "  Bids:\n";
        foreach ($lot->bids->sortByDesc('amount') as $bid) {
            echo "    - R" . number_format($bid->amount, 2) . " by {$bid->user->name} at {$bid->created_at}\n";
        }
    }
}

// Credit transactions for this auction
echo "\n\n=== CREDIT TRANSACTIONS ===\n";
$creditTxns = CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)
    ->where('created_at', '>=', $auction->created_at->subHours(1))
    ->orderBy('created_at')
    ->get();

foreach ($creditTxns as $txn) {
    echo "{$txn->created_at}: {$txn->type} - R" . number_format($txn->amount, 2) . " (Balance: R" . number_format($txn->balance_after, 2) . ")\n";
    echo "  {$txn->description}\n";
}

// Activity logs
echo "\n\n=== ACTIVITY LOG ===\n";
$activities = ActivityLog::where(function($q) use ($auction) {
    $q->where('user_id', $auction->auctioneer->user_id)
      ->orWhereHas('user.bids', function($q) use ($auction) {
          $q->whereHas('lot', function($q) use ($auction) {
              $q->where('event_id', $auction->id);
          });
      });
})
->where('created_at', '>=', $auction->created_at->subMinutes(10))
->orderBy('created_at')
->get();

foreach ($activities as $activity) {
    $user = $activity->user ? $activity->user->name : 'Unknown';
    echo "{$activity->created_at}: [{$user}] {$activity->action} - {$activity->description}\n";
}

// Scheduler status (check if auction transitions happened)
echo "\n\n=== AUCTION STATUS TRANSITIONS ===\n";
$now = Carbon::now();
echo "Current Time: {$now}\n";
echo "Auction Start: {$auction->start_time} (" . $now->diffForHumans($auction->start_time) . ")\n";
echo "Auction End: {$auction->end_time} (" . $now->diffForHumans($auction->end_time) . ")\n";

if ($auction->start_time <= $now && $auction->status === 'draft') {
    echo "⚠️ WARNING: Auction should be 'upcoming' or 'live' but is still 'draft'\n";
}
if ($auction->start_time <= $now && $auction->status === 'upcoming') {
    echo "⚠️ WARNING: Auction should be 'live' but is still 'upcoming' - Scheduler may not be running\n";
}
if ($auction->end_time <= $now && $auction->status === 'live') {
    echo "⚠️ WARNING: Auction should be 'ended' but is still 'live' - Scheduler may not be running\n";
}

// Check lots that should be live
echo "\n=== LOT STATUS CHECK ===\n";
foreach ($lots as $lot) {
    if ($auction->status === 'live' && $lot->status !== 'live' && !$lot->withdrawn_at) {
        echo "⚠️ Lot #{$lot->lot_number} should be 'live' but is '{$lot->status}'\n";
    }
}

echo "\n=== END REPORT ===\n";
