<?php

namespace App\Console\Commands;

use App\Models\Auction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EndAuction extends Command
{
    protected $signature = 'auctions:end {id : The auction ID to end}';

    protected $description = 'Manually end a live or upcoming auction and close all its lots';

    public function handle(): int
    {
        $auction = Auction::find($this->argument('id'));

        if (!$auction) {
            $this->error("Auction #{$this->argument('id')} not found.");
            return Command::FAILURE;
        }

        if (!in_array($auction->status, ['live', 'upcoming'])) {
            $this->error("Auction #{$auction->id} '{$auction->title}' is '{$auction->status}' — can only end live or upcoming auctions.");
            return Command::FAILURE;
        }

        $this->info("Auction: {$auction->title} (#{$auction->id})");
        $this->info("Current status: {$auction->status}");
        $this->newLine();

        if (!$this->confirm('Are you sure you want to end this auction now?')) {
            $this->comment('Cancelled.');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($auction) {
            $auction->update([
                'status'   => 'ended',
                'end_time' => now(),
            ]);

            $lots = $auction->lots()->where('status', 'live')->get();

            foreach ($lots as $lot) {
                $lot->close();
            }

            $soldCount   = $auction->lots()->where('status', 'sold')->count();
            $unsoldCount = $auction->lots()->where('status', 'unsold')->count();

            $this->info("Auction ended successfully.");
            $this->table(
                ['Sold', 'Unsold', 'Withdrawn'],
                [[
                    $soldCount,
                    $unsoldCount,
                    $auction->lots()->whereNotNull('withdrawn_at')->count(),
                ]]
            );
        });

        $this->newLine();
        $this->comment("Winner emails will be sent within 5 minutes by the scheduler.");
        $this->comment("Or run now: php artisan emails:send-auction-summaries");

        return Command::SUCCESS;
    }
}
