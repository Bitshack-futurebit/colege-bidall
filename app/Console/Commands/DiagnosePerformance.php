<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DiagnosePerformance extends Command
{
    protected $signature = 'diagnose:performance';
    protected $description = 'Diagnose performance issues';

    public function handle()
    {
        $this->info('=== PERFORMANCE DIAGNOSTIC ===');
        $this->newLine();

        // 1. Session table size
        $this->info('1. Session Storage:');
        $totalSessions = DB::table('sessions')->count();
        $oldSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(7)->timestamp)
            ->count();
        $veryOldSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(30)->timestamp)
            ->count();

        $this->line("   Total sessions: {$totalSessions}");
        $this->line("   Old (7+ days): {$oldSessions}");
        $this->line("   Very old (30+ days): {$veryOldSessions}");

        if ($totalSessions > 10000) {
            $this->error("   ⚠️  TOO MANY SESSIONS! This will slow down authentication.");
            $this->warn("   Run: php artisan session:clean");
        } elseif ($totalSessions > 5000) {
            $this->warn("   ⚠️  High session count. Consider cleanup.");
        } else {
            $this->info("   ✓ Session count looks good");
        }
        $this->newLine();

        // 2. Database table sizes
        $this->info('2. Database Table Sizes:');
        $tables = ['users', 'auctioneers', 'events', 'lots', 'bids', 'transactions', 'credit_transactions'];
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $this->line("   {$table}: {$count} rows");
        }
        $this->newLine();

        // 3. Cache status
        $this->info('3. Cache Status:');
        try {
            Cache::put('test_key', 'test_value', 60);
            $value = Cache::get('test_key');
            if ($value === 'test_value') {
                $this->info("   ✓ Cache is working");
            } else {
                $this->error("   ✗ Cache read/write failed");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Cache error: " . $e->getMessage());
        }
        $this->newLine();

        // 4. Config cached?
        $this->info('4. Laravel Optimization:');
        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $routesCached = file_exists(base_path('bootstrap/cache/routes-v7.php'));
        $viewsCached = is_dir(storage_path('framework/views')) && count(glob(storage_path('framework/views/*.php'))) > 0;

        $this->line("   Config cached: " . ($configCached ? '✓ Yes' : '✗ No - run php artisan config:cache'));
        $this->line("   Routes cached: " . ($routesCached ? '✓ Yes' : '✗ No - run php artisan route:cache'));
        $this->line("   Views cached: " . ($viewsCached ? '✓ Yes' : '✗ No - run php artisan view:cache'));
        $this->newLine();

        // 5. Recent errors
        $this->info('5. Recent Errors:');
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logSize = filesize($logFile);
            $this->line("   Log file size: " . round($logSize / 1024 / 1024, 2) . " MB");

            if ($logSize > 50 * 1024 * 1024) {
                $this->warn("   ⚠️  Log file is large (>50MB). Consider rotation.");
            }

            // Check last 100 lines for errors
            $handle = popen("tail -100 " . escapeshellarg($logFile) . " | grep -i 'error\\|exception' | wc -l", 'r');
            $errorCount = trim(fread($handle, 1024));
            pclose($handle);

            $this->line("   Recent errors (last 100 lines): {$errorCount}");
            if ($errorCount > 10) {
                $this->error("   ⚠️  Many recent errors! Check: tail -50 storage/logs/laravel.log");
            }
        }
        $this->newLine();

        // 6. Database indexes
        $this->info('6. Database Indexes:');
        $this->line("   Checking if performance indexes exist...");

        try {
            $indexes = DB::select("SHOW INDEX FROM events WHERE Key_name LIKE 'idx_%'");
            if (count($indexes) > 0) {
                $this->info("   ✓ Performance indexes found");
            } else {
                $this->warn("   ⚠️  No custom indexes found. Run migrations.");
            }
        } catch (\Exception $e) {
            $this->error("   Could not check indexes: " . $e->getMessage());
        }
        $this->newLine();

        // 7. Recommendations
        $this->info('=== RECOMMENDATIONS ===');
        if ($oldSessions > 1000) {
            $this->warn("→ Clean old sessions: php artisan session:clean");
        }
        if (!$configCached) {
            $this->warn("→ Cache config: php artisan config:cache");
        }
        if (!$routesCached) {
            $this->warn("→ Cache routes: php artisan route:cache");
        }
        $this->newLine();

        return Command::SUCCESS;
    }
}
