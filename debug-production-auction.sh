#!/bin/bash
# Production Auction Debug Script
# SSH into server and run this script

ssh idalujwfq@www106.xneelo.co.za << 'ENDSSH'
cd /usr/home/bidalujwfq

/usr/bin/phpwrapper artisan tinker << 'EOF'
use App\Models\Auction;
use App\Models\Bid;
use App\Models\CreditTransaction;

echo "============================================\n";
echo "AUCTION DEBUG REPORT (PRODUCTION)\n";
echo "============================================\n\n";

$auction = Auction::with(['auctioneer.user', 'lots.bids.user'])->orderBy('created_at', 'desc')->first();

if (!$auction) {
    echo "No auctions found.\n";
    exit;
}

echo "AUCTION: {$auction->title}\n";
echo "ID: {$auction->id}\n";
echo "Auctioneer: {$auction->auctioneer->business_name}\n";
echo "Status: {$auction->status}\n";
echo "Start: {$auction->start_time}\n";
echo "End: {$auction->end_time}\n";
echo "Lots: {$auction->total_lots}\n";
echo "\n";

$now = now();
echo "Current Time: {$now}\n";

// Status validation
if ($auction->start_time && $auction->start_time <= $now && $auction->status === 'draft') {
    echo "⚠️ WARNING: Should be UPCOMING but is DRAFT\n";
}
if ($auction->start_time && $auction->start_time <= $now && $auction->status === 'upcoming') {
    echo "⚠️ WARNING: Should be LIVE but is UPCOMING (scheduler not running?)\n";
}
if ($auction->end_time && $auction->end_time <= $now && $auction->status === 'live') {
    echo "⚠️ WARNING: Should be ENDED but is LIVE (scheduler not running?)\n";
}
echo "\n";

echo "=== LOTS ===\n";
foreach ($auction->lots as $lot) {
    echo "Lot #{$lot->lot_number}: {$lot->title}\n";
    echo "  Status: {$lot->status}";
    if ($lot->withdrawn_at) echo " (WITHDRAWN)";
    echo "\n";
    echo "  Starting Bid: R" . number_format($lot->starting_bid, 2) . "\n";
    echo "  Current Bid: R" . number_format($lot->current_bid, 2) . "\n";
    echo "  End Time: {$lot->end_time}\n";
    echo "  Total Bids: {$lot->bids->count()}\n";

    if ($lot->bids->count() > 0) {
        $topBid = $lot->bids->sortByDesc('amount')->first();
        echo "  Top Bidder: {$topBid->user->name} - R" . number_format($topBid->amount, 2) . "\n";
    }
    echo "\n";
}

echo "\n=== ALL BIDS (Chronological) ===\n";
$allBids = Bid::whereHas('lot', function($q) use ($auction) {
    $q->where('event_id', $auction->id);
})->with(['user', 'lot'])->orderBy('created_at', 'asc')->get();

if ($allBids->count() > 0) {
    foreach ($allBids as $bid) {
        echo "{$bid->created_at}: Lot #{$bid->lot->lot_number} - {$bid->user->name} bid R" . number_format($bid->amount, 2) . "\n";
    }
} else {
    echo "No bids placed.\n";
}

echo "\n=== CREDIT TRANSACTIONS ===\n";
$txns = CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)
    ->where('created_at', '>=', $auction->created_at->subHours(2))
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

if ($txns->count() > 0) {
    foreach ($txns as $txn) {
        echo "{$txn->created_at}: {$txn->type} - R" . number_format($txn->amount, 2);
        echo " (Balance after: R" . number_format($txn->balance_after, 2) . ")\n";
        echo "  Description: {$txn->description}\n";
    }
} else {
    echo "No credit transactions found.\n";
}

echo "\n=== AUCTIONEER INFO ===\n";
echo "Current Balance: R" . number_format($auction->auctioneer->credit_balance, 2) . "\n";
echo "Is Free Account: " . ($auction->auctioneer->is_free_account ? "YES" : "NO") . "\n";
if ($auction->auctioneer->custom_lot_fee) {
    echo "Custom Lot Fee: R" . number_format($auction->auctioneer->custom_lot_fee, 2) . "\n";
}

echo "\n============================================\n";
echo "END REPORT\n";
echo "============================================\n";

exit;
EOF

ENDSSH
