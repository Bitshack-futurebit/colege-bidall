# Laravel Scheduler Setup Guide

## What Was Implemented

✅ **Automatic Auction Status Transitions** (`auctions:update-statuses` — every minute)
- Auctions automatically go from `upcoming` → `live` when start_time passes
- Auctions automatically go from `live` → `ended` when end_time passes
- Lots automatically marked as `sold` or `unsold` based on bids and reserve prices
- Withdrawn lots are never transitioned to live

✅ **Winner Summary Emails** (`emails:send-auction-summaries` — every 5 minutes)
- After an auction ends, one email per winning bidder listing all lots won
- Includes hammer price, buyers premium, grand total, and payment CTA
- Sends once only — `winner_emails_sent` flag prevents duplicate sends

✅ **Automatic Image Cleanup** (`images:cleanup` — daily at 2 AM)
- Lot images deleted 30 days after auction ends
- Saves storage space automatically
- Auctioneer profile/logo/banner images are never deleted

✅ **Automatic Auction Archival** (`auctions:archive-old` — daily at 3 AM)
- Soft-deletes auctions 30+ days after ending
- Data preserved for accounting and audit trail

## Testing Locally

### Option 1: Manual Testing (Immediate)

Run the commands once to test them:

```bash
php artisan auctions:update-statuses
php artisan emails:send-auction-summaries
php artisan images:cleanup
```

### Option 2: Scheduler Worker (Continuous)

Run this to have the scheduler check every minute automatically:

```bash
php artisan schedule:work
```

Leave this running in a terminal. It will execute scheduled tasks at their intervals:
- `auctions:update-statuses` — Every minute
- `emails:send-auction-summaries` — Every 5 minutes
- `images:cleanup` — Daily at 2 AM
- `auctions:archive-old` — Daily at 3 AM

Press `Ctrl+C` to stop.

## Production Setup

### For Xneelo Shared Hosting

> **Important**: Xneelo shared hosting cron jobs have a minimum interval of 2 hours. This is not acceptable for live auction timing. The solution is a two-part approach.

#### Part A: cron-job.org (Primary — every minute)

1. Create a free account at [cron-job.org](https://cron-job.org)
2. Create a new cron job:
   - **URL**: `https://www.bidall.co.za/cron/run?token=YOUR_CRON_SECRET`
   - **Schedule**: Every minute
3. Replace `YOUR_CRON_SECRET` with the `CRON_SECRET` value from `.env`
4. The endpoint runs `auctions:update-statuses` and `emails:send-auction-summaries`
5. Returns `{"status":"ok","time":"..."}` on success, 403 on bad token

#### Part B: Xneelo cPanel Cron (Fallback — every 2 hours)

1. **Access cPanel → Cron Jobs**
2. **Add new cron job:**
   - **Minute**: `0`
   - **Hour**: `*/2`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**:
     ```bash
     /usr/bin/php8.3 /usr/home/bidalujwfq/artisan schedule:run >> /dev/null 2>&1
     ```
3. **Save and activate**

The fallback runs the full scheduler (all commands including image cleanup and archival) in case cron-job.org is unavailable.

## How It Works

### Auction Status Transitions

```php
// Every minute, the scheduler checks:

// 1. Upcoming → Live
Auctions with status='upcoming' and start_time <= NOW()
  → Set status to 'live'
  → Set all non-withdrawn lots to 'live'
  → Deduct lot fees from auctioneer credit balance

// 2. Live → Ended
Auctions with status='live' and end_time <= NOW()
  → Set status to 'ended'
  → For each lot:
      - Has bids AND meets reserve? → 'sold'
      - Otherwise → 'unsold'
  → Deduct 1% commission from auctioneer credit balance
```

### Winner Summary Emails

```php
// Every 5 minutes, the scheduler checks:

Find auctions where status='ended' AND winner_emails_sent=false
  → Group sold lots by winning_bidder_id
  → Send one AuctionWinnerSummary email per winner
  → Mark auction winner_emails_sent=true (prevents re-sends)
```

### Image Cleanup

```php
// Daily at 2 AM:

Find all lot images where:
  - Lot status is 'sold' or 'unsold'
  - Auction status is 'ended'
  - Auction ended more than 30 days ago

Delete:
  - Storage files (optimized + thumbnail)
  - Database records

Note: Auctioneer profile/logo/banner images are never deleted.
```

### Auction Archival

```php
// Daily at 3 AM:

Find auctions where:
  - status = 'ended'
  - ended more than 30 days ago

Soft-delete the auction (data preserved for accounting/auditing).
```

## Manual Commands

```bash
# Update auction statuses immediately
php artisan auctions:update-statuses

# Send winner summary emails immediately
php artisan emails:send-auction-summaries

# Clean up old images immediately
php artisan images:cleanup

# Archive old ended auctions immediately
php artisan auctions:archive-old

# End a specific live auction manually
php artisan auctions:end {id}

# View all scheduled tasks
php artisan schedule:list

# Test scheduler without waiting
php artisan schedule:test
```

## Verifying It's Working

### Check Scheduler Status

```bash
php artisan schedule:list
```

Should show:
```
  * * * * *    auctions:update-statuses ........ Next Due: [time]
  */5 * * * *  emails:send-auction-summaries ... Next Due: [time]
  0 2 * * *    images:cleanup .................. Next Due: [time]
  0 3 * * *    auctions:archive-old ............ Next Due: [time]
```

### Check Auction Transitions

1. Create a test auction with start_time = now + 1 minute
2. Run `php artisan auctions:update-statuses` after 1 minute
3. Auction should transition from `upcoming` to `live`

### Check the cron-job.org Endpoint

Visit in a browser (or check cron-job.org execution logs):
```
https://www.bidall.co.za/cron/run?token=YOUR_CRON_SECRET
```
Should return `{"status":"ok","time":"..."}`. A 403 means the token is wrong.

## Troubleshooting

### "No events needed status updates"

This is normal if:
- No events are ready to transition
- All events are already in correct status

### Auctions Not Transitioning

Check:
1. **Time zone** in `.env` → `APP_TIMEZONE=Africa/Johannesburg`
2. **Start/end times** are set correctly in database
3. **cron-job.org job is active** and the URL token is correct
4. **Scheduler is running locally** → `php artisan schedule:work`
5. Test the endpoint: `https://www.bidall.co.za/cron/run?token=YOUR_CRON_SECRET`

### Images Not Deleting

Check:
1. **Images older than 30 days?** (configurable in `config/platform.php`)
2. **Event status is 'ended'?**
3. **Storage permissions** are correct

## Configuration

### Change Auto-Delete Period

Edit `config/platform.php`:

```php
'images' => [
    'auto_delete_days' => env('IMAGE_AUTO_DELETE_DAYS', 30),
],
```

Then in `.env`:
```env
IMAGE_AUTO_DELETE_DAYS=45  # Change to 45 days
```

### Change Cleanup Time

Edit `routes/console.php`:

```php
// Run at 3 AM instead
Schedule::command('images:cleanup')->dailyAt('03:00');
```

## Important Notes

- Auctions in `draft` status are never auto-transitioned
- Auctioneer must manually move `draft` → `upcoming` via the Go Live button
- `upcoming` → `live` is automatic when start_time passes
- `live` → `ended` is automatic when end_time passes
- Withdrawn lots are never set to live — they stay withdrawn
- Lot images are deleted permanently after 30 days (lot/auction records remain)
- Winner emails send once only — the `winner_emails_sent` flag prevents duplicates
- Xneelo cron alone (every 2 hours) is not sufficient for live auction timing

## Next Steps

1. Test manually: `php artisan auctions:update-statuses`
2. Create a test auction to verify transitions work
3. Set up cron-job.org with the secure endpoint (see Production Setup above)
4. Add the Xneelo cPanel fallback cron (every 2 hours)
5. Monitor the first few automatic transitions to ensure they work correctly

---

**Need help?** Check the Laravel documentation on [Task Scheduling](https://laravel.com/docs/11.x/scheduling)
