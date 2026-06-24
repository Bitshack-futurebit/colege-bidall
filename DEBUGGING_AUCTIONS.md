# Auction Debugging Guide

This guide explains how to debug issues with auctions and get comprehensive logs of what happened during a test auction.

## Quick Debug Tools

### 1. Web-Based Debug Page (EASIEST)

Visit this URL in your browser to see a complete auction report:

```
http://localhost:8000/debug-auction
```

Or for a specific auction:

```
http://localhost:8000/debug-auction/123
```

**What it shows:**
- Auction status and timing
- All lots with their current status, bids, and winners
- All bids placed during the auction
- Credit transactions (lot fees, commissions)
- Warning indicators if auction status is incorrect
- Visual timeline of what happened

### 2. Laravel Log Files

Check recent errors:

```bash
tail -100 storage/logs/laravel.log
```

Search for specific errors:

```bash
grep "ERROR" storage/logs/laravel.log | tail -50
```

### 3. Database Queries (via Tinker)

Get auction details via command line:

```bash
php artisan tinker
```

Then run these commands:

```php
// Get most recent auction
$auction = App\Models\Auction::with(['lots.bids.user', 'auctioneer'])->latest()->first();

// Show auction info
echo "Auction: {$auction->title}\n";
echo "Status: {$auction->status}\n";
echo "Start: {$auction->start_time}\n";
echo "End: {$auction->end_time}\n";

// Show lots
$auction->lots->each(function($lot) {
    echo "Lot #{$lot->lot_number}: {$lot->title} - {$lot->status}\n";
    echo "  Bids: {$lot->bids->count()}, Current: R{$lot->current_bid}\n";
});

// Show all bids
App\Models\Bid::whereHas('lot', function($q) use ($auction) {
    $q->where('event_id', $auction->id);
})->with('user')->latest()->get()->each(function($bid) {
    echo "{$bid->created_at}: {$bid->user->name} bid R{$bid->amount}\n";
});

// Show credit transactions
App\Models\CreditTransaction::where('auctioneer_id', $auction->auctioneer_id)
    ->latest()->take(10)->get()->each(function($txn) {
        echo "{$txn->created_at}: {$txn->type} R{$txn->amount} - {$txn->description}\n";
    });
```

## Common Problems & Solutions

### Problem: Auction Stuck in "Draft" Status

**Symptoms:**
- Auction was scheduled to start but still shows "draft"
- Lots are not going live
- No bids can be placed

**Debug:**
1. Visit `/debug-auction` and check "Warnings" section
2. Look for "stuck_in_draft" warning

**Causes:**
- Auctioneer didn't click "Go Live" button
- Insufficient credits to pay for lots
- Scheduler not running

**Solutions:**
1. Check auctioneer credit balance: `/seller/credits`
2. Manually transition: Visit auction, click "Go Live"
3. Run scheduler manually: `php artisan auctions:update-statuses`

### Problem: Auction Stuck in "Upcoming" Status

**Symptoms:**
- Start time has passed but auction not live
- Lots still showing "pending" status

**Debug:**
1. Visit `/debug-auction`
2. Check if "should_be_live" is YES but status is UPCOMING

**Causes:**
- Scheduler command not running (shared hosting issue)
- External cron not configured

**Solutions:**
1. Manual trigger: `php artisan auctions:update-statuses`
2. Check cron-job.org is set up correctly
3. Visit: `http://yourdomain.com/cron/run?token=YOUR_CRON_SECRET`

### Problem: Auction Stuck in "Live" Status After End Time

**Symptoms:**
- End time passed but auction still shows "live"
- Lots not marked as sold/unsold
- Winners not determined

**Debug:**
1. Visit `/debug-auction`
2. Check if "should_be_ended" is YES

**Causes:**
- Scheduler not running
- Lots have end times in the future (soft close extended them)

**Solutions:**
1. Run: `php artisan auctions:update-statuses`
2. Wait for all lots to reach their end times
3. Check individual lot end times in debug page

### Problem: Bids Not Being Accepted

**Symptoms:**
- Users get errors when placing bids
- Bids disappear or don't show up

**Debug:**
1. Check Laravel logs: `tail -50 storage/logs/laravel.log`
2. Look for bid-related errors
3. Check lot status (must be "live" to accept bids)

**Common Errors:**
- "Lot is not live" - Auction hasn't started yet
- "Bid too low" - Amount less than current bid + increment
- "You already have the highest bid" - Can't outbid yourself
- "Auction registration required" - Must register for auction first

### Problem: Credit Deductions Not Happening

**Symptoms:**
- Auction went live but credits not deducted
- Balance unchanged after auction

**Debug:**
1. Visit `/debug-auction`
2. Check "Credit Transactions" section
3. Look for "lot_live" transaction types

**Causes:**
- Free account set by admin (intentional)
- Error in transition code
- Auction never transitioned to "upcoming"

**Solutions:**
1. Check if auctioneer has free account: Admin > Auctioneers
2. Manually run: `php artisan auctions:update-statuses`
3. Check log for errors

### Problem: Commission Not Deducted After Auction

**Symptoms:**
- Auction ended but no 1% commission charged
- Credit balance not decreased

**Debug:**
1. Check credit transactions for "lot_close" type
2. Visit `/debug-auction` and check transactions

**Causes:**
- Auction hasn't fully ended (some lots still open)
- Scheduler hasn't run the commission calculation yet
- No sold lots (commission only on sales)

**Solutions:**
- Wait for scheduler to run
- Manually trigger: `php artisan auctions:update-statuses`

## Monitoring Tools

### Check Scheduler Status

The scheduler should run automatically via cron-job.org. To test manually:

```bash
php artisan auctions:update-statuses
```

Expected output:
```
Transitioned X upcoming auctions to live
Transitioned Y live auctions to ended
```

### Check Cron Job

Verify external cron is working:

```
http://yourdomain.com/cron/run?token=YOUR_CRON_SECRET
```

Should return:
```json
{"status":"ok","time":"2026-02-20 10:30:00"}
```

### View Activity Logs

Check what users have been doing:

```bash
php artisan tinker

App\Models\ActivityLog::latest()->take(20)->get()->each(function($log) {
    echo "{$log->created_at}: {$log->user->name} - {$log->action}: {$log->description}\n";
});
```

## Best Practices for Testing

### Setting Up a Test Auction

1. **Use Short Duration**: 5-minute auctions are perfect for testing
2. **Use Single Lot**: Easier to track
3. **Set Low Starting Bid**: e.g., R10
4. **Use Free Account**: Ask admin to set your account as free (no credit charges)
5. **Use Multiple Test Users**: Create buyer accounts to test bidding

### Test Checklist

Before running test auction:

- [ ] Auctioneer has sufficient credits (or free account)
- [ ] At least 1 lot created with images
- [ ] Start time set (e.g., 2 minutes from now)
- [ ] End time set (e.g., 7 minutes from now)
- [ ] Scheduler is running (check `/cron/run?token=XXX`)
- [ ] Test buyer account(s) ready

During auction:

- [ ] Visit `/debug-auction` in separate tab
- [ ] Refresh debug page every 30 seconds
- [ ] Watch status transitions (draft → upcoming → live → ended)
- [ ] Place test bids from buyer accounts
- [ ] Monitor bid polling updates in real-time

After auction:

- [ ] Check final lot statuses (sold/unsold)
- [ ] Verify credit transactions (lot fees + commission)
- [ ] Check winning bidder notifications
- [ ] Review full auction report: `/debug-auction`

## Production Debugging

On bidall.co.za:

1. Visit: `https://bidall.co.za/debug-auction`
2. Save the full HTML page (Ctrl+S)
3. Send saved page for analysis

Or get JSON data:

```bash
# SSH into server
ssh idalujwfq@www106.xneelo.co.za

cd /usr/home/bidalujwfq

# Run debug script
php artisan tinker <<'EOF'
$auction = App\Models\Auction::latest()->first();
dd([
    'id' => $auction->id,
    'title' => $auction->title,
    'status' => $auction->status,
    'start' => $auction->start_time,
    'end' => $auction->end_time,
    'lots' => $auction->lots->count(),
    'bids' => App\Models\Bid::whereHas('lot', fn($q) => $q->where('event_id', $auction->id))->count()
]);
EOF
```

## Getting Help

When reporting auction issues, provide:

1. Auction ID or link to `/debug-auction` page
2. Screenshot of the debug page
3. Description of what you expected vs what happened
4. Time when the issue occurred
5. Any error messages from browser console (F12)

The debug page contains ALL the information needed to diagnose auction problems.
