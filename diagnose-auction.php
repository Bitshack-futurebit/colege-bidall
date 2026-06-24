<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Now (server time): ".now()." (tz: ".config('app.timezone').")\n\n";

echo "=== Active proxy bids ===\n";
$proxies = \App\Models\ProxyBid::with(['lot.auction', 'user'])->where('is_active', true)->get();
foreach ($proxies as $p) {
    echo "  lot #{$p->lot_id} '".($p->lot->title ?? '?')."' user={$p->user->name} max=R{$p->max_amount} "
       . "| lot current_bid=R".($p->lot->current_bid ?? 0)
       . " winner=".($p->lot->winning_bidder_id ?? 'none')
       . " allow_proxy=".($p->lot->auction->allow_proxy_bidding ? 'Y' : 'N')
       . " increment=R".$p->lot->increment."\n";
}
if ($proxies->isEmpty()) echo "  (none active)\n";
echo "\n";

echo "=== ENDED auctions with future end_time (bug candidates) ===\n";
$bad = \App\Models\Auction::where('status','ended')->where('end_time','>',now())->get();
foreach ($bad as $a) {
    echo "  #{$a->id} '{$a->title}' end={$a->end_time} (".now()->diffForHumans($a->end_time).")\n";
}
if ($bad->isEmpty()) echo "  (none)\n";

echo "\n=== Last 10 auctions ===\n";
foreach (\App\Models\Auction::latest()->take(10)->get() as $a) {
    echo "  #{$a->id} status={$a->status} type={$a->auction_type} start={$a->start_time} end={$a->end_time} '{$a->title}'\n";
}
