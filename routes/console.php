<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Update auction statuses every minute
Schedule::command('auctions:update-statuses')->everyMinute();

// Clean up old images daily at 2 AM
Schedule::command('images:cleanup')->dailyAt('02:00');

// Archive old auctions daily at 3 AM (after image cleanup)
Schedule::command('auctions:archive-old')->dailyAt('03:00');

// Send winner summary emails every 5 minutes
Schedule::command('emails:send-auction-summaries')->everyFiveMinutes();

// Expire abandoned pending transactions after 1 hour
Schedule::command('transactions:expire-stale')->hourly();

// Reset free relist eligibility daily at 4 AM
Schedule::command('relists:reset')->dailyAt('04:00');

// Process queued jobs (broadcast emails, etc.) - runs via cron, no persistent worker needed
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')->everyMinute()->withoutOverlapping();

// Community auctions
// Runs daily — covers both weekly schedules and monthly "Nth weekday" schedules.
// Idempotent: skips schedules whose next auction already exists.
Schedule::command('community:create-next-auction')->dailyAt('00:05');
Schedule::command('community:lock-lineup')->everyFiveMinutes();
Schedule::command('community:finalize-confirmations')->everyMinute();
Schedule::command('community:purge-old')->dailyAt('03:30');
// Monthly platform-fee invoice nudge — soft reminder, no hard enforcement.
Schedule::command('community:invoice-fees')->monthlyOn(1, '08:00');
