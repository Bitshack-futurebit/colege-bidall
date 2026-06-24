<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSessions extends Command
{
    protected $signature = 'session:clean {--days=7 : Delete sessions older than X days}';
    protected $description = 'Clean up old session records from database';

    public function handle()
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days)->timestamp;

        $this->info("Cleaning sessions older than {$days} days...");

        $count = DB::table('sessions')
            ->where('last_activity', '<', $cutoff)
            ->count();

        if ($count === 0) {
            $this->info('No old sessions to clean.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$count} old sessions.");

        if ($this->confirm('Delete these sessions?', true)) {
            DB::table('sessions')
                ->where('last_activity', '<', $cutoff)
                ->delete();

            $this->info("✓ Deleted {$count} old sessions.");
        } else {
            $this->info('Cancelled.');
        }

        $remaining = DB::table('sessions')->count();
        $this->info("Remaining sessions: {$remaining}");

        return Command::SUCCESS;
    }
}
