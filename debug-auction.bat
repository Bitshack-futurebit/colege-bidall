@echo off
echo ============================================
echo AUCTION DEBUG REPORT
echo ============================================
echo.

cd /d F:\basic_bidall

"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan tinker << 'EOF'
use App\Models\Auction;
use App\Models\Bid;
use App\Models\CreditTransaction;

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
if ($auction->start_time && $auction->start_time <= $now && $auction->status === 'draft') {
    echo "⚠️ WARNING: Should be UPCOMING but is DRAFT\n";
}
if ($auction->start_time && $auction->start_time <= $now && $auction->status === 'upcoming') {
    echo "⚠️ WARNING: Should be LIVE but is UPCOMING\n";
}
if ($auction->end_time && $auction->end_time <= $now && $auction->status === 'live') {
    echo "⚠️ WARNING: Should be ENDED but is LIVE\n";
}
echo "\n";

echo "=== LOTS ===\n";
foreach ($auction->lots as $lot) {
    echo "#{$lot->lot_number}: {$lot->title}\n";
    echo "  Status: {$lot->status}";
    if ($lot->withdrawn_at) echo " (WITHDRAWN)";
    echo "\n";
    echo "  Current Bid: R" . number_format($lot->current_bid, 2) . "\n";
    echo "  Total Bids: {$lot->bids->count()}\n";
    if ($lot->bids->count() > 0) {
        $topBid = $lot->bids->sortByDesc('amount')->first();
        echo "  Top Bidder: {$topBid->user->name} - R" . number_format($topBid->amount, 2) . "\n";
    }
    echo "\n";
}

echo "\n=== ALL BIDS ===\n";
$allBids = Bid::whereHas('lot', function($q) use ($auction) {
    $q->where('event_id', $auction->id);
})->with(['user', 'lot'])->orderBy('created_at', 'desc')->get();

if ($allBids->count() > 0) {
    foreach ($allBids as $bid) {
        echo "{$bid->created_at}: Lot #{$bid->lot->lot_number} - {$bid->user->name} bid R" . number_format($bid->amount, 2) . "\n";
    }
} else {
    echo "No bids placed.\n";
}

echo "\n=== CREDIT TRANSACTIONS ===\n";
$txns = CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)
    ->where('created_at', '>=', $auction->created_at->subHours(1))
    ->orderBy('created_at', 'desc')
    ->get();

if ($txns->count() > 0) {
    foreach ($txns as $txn) {
        echo "{$txn->created_at}: {$txn->type} - R" . number_format($txn->amount, 2);
        echo " (Balance: R" . number_format($txn->balance_after, 2) . ")\n";
        echo "  {$txn->description}\n";
    }
} else {
    echo "No credit transactions found.\n";
}

echo "\n=== AUCTIONEER BALANCE ===\n";
echo "Current Balance: R" . number_format($auction->auctioneer->credit_balance, 2) . "\n";

exit;
EOF

echo.
echo ============================================
echo Report complete. Press any key to exit.
pause > nul
