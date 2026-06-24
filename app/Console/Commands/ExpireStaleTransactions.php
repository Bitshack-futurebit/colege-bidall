<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class ExpireStaleTransactions extends Command
{
    protected $signature = 'transactions:expire-stale';

    protected $description = 'Mark pending transactions older than 1 hour as failed';

    public function handle(): int
    {
        $count = Transaction::where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->update(['status' => 'failed']);

        if ($count > 0) {
            $this->info("Marked {$count} stale transaction(s) as failed.");
        } else {
            $this->comment('No stale transactions found.');
        }

        return Command::SUCCESS;
    }
}
