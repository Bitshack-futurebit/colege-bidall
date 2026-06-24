#!/bin/bash
cd F:/basic_bidall

# Get the most recent auction with all related data
php artisan tinker <<'TINKER'
use App\Models\Auction;
use App\Models\Lot;
use App\Models\Bid;
use App\Models\CreditTransaction;
use Carbon\Carbon;

$auction = Auction::with(['auctioneer.user', 'lots.bids.user'])->orderBy('created_at', 'desc')->first();

if (!$auction) {
    echo "No auctions found\n";
    exit;
}

echo "=== AUCTION DEBUG ===\n";
echo "ID: {$auction->id}\n";
echo "Title: {$auction->title}\n";
echo "Auctioneer: {$auction->auctioneer->business_name}\n";
echo "Status: {$auction->status}\n";
echo "Start: {$auction->start_time}\n";
echo "End: {$auction->end_time}\n";
echo "Total Lots: {$auction->total_lots}\n\n";

echo "=== LOTS ===\n";
foreach ($auction->lots as $lot) {
    echo "Lot #{$lot->lot_number}: {$lot->title}\n";
    echo "  Status: {$lot->status}\n";
    echo "  Current Bid: R{$lot->current_bid}\n";
    echo "  Total Bids: {$lot->bids->count()}\n";
    echo "  Withdrawn: " . ($lot->withdrawn_at ? "YES" : "NO") . "\n";
    if ($lot->bids->count() > 0) {
        foreach ($lot->bids->sortByDesc('amount')->take(3) as $bid) {
            echo "    Bid: R{$bid->amount} by {$bid->user->name} at {$bid->created_at}\n";
        }
    }
    echo "\n";
}

echo "\n=== CREDIT TRANSACTIONS (last 10) ===\n";
$txns = CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)->orderBy('created_at', 'desc')->limit(10)->get();
foreach ($txns as $txn) {
    echo "{$txn->created_at}: {$txn->type} R{$txn->amount} (Balance: R{$txn->balance_after}) - {$txn->description}\n";
}

echo "\n=== TIME CHECKS ===\n";
$now = Carbon::now();
echo "Current Time: {$now}\n";
echo "Should be live? " . ($auction->start_time <= $now ? "YES" : "NO") . "\n";
echo "Should be ended? " . ($auction->end_time <= $now ? "YES" : "NO") . "\n";

TINKER
