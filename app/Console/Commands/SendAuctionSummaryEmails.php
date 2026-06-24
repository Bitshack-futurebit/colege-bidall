<?php

namespace App\Console\Commands;

use App\Mail\AuctionWinnerSummary;
use App\Models\Auction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendAuctionSummaryEmails extends Command
{
    protected $signature = 'emails:send-auction-summaries';

    protected $description = 'Send winner summary emails for auctions that have ended';

    public function handle(): int
    {
        // Prevent duplicate runs (scheduler + /cron/run can overlap)
        $lock = Cache::lock('send-auction-summaries', 120);
        if (!$lock->get()) {
            $this->comment('Another instance is already running, skipping');
            return Command::SUCCESS;
        }

        try {
            return $this->processEmails();
        } finally {
            $lock->release();
        }
    }

    private function processEmails(): int
    {
        // Find ended auctions that haven't had emails sent yet
        $auctions = Auction::where('status', 'ended')
            ->where('winner_emails_sent', 0)
            ->with([
                'auctioneer',
                'lots' => function ($query) {
                    $query->where('status', 'sold')
                          ->whereNotNull('winning_bidder_id')
                          ->with('winningBidder');
                },
            ])
            ->get();

        if ($auctions->isEmpty()) {
            $this->comment('No auctions pending winner emails');
            return Command::SUCCESS;
        }

        $totalEmailsSent = 0;

        foreach ($auctions as $auction) {
            $soldLots = $auction->lots->filter(fn ($lot) => $lot->status === 'sold');

            if ($soldLots->isEmpty()) {
                // No sold lots — mark as done and skip
                $auction->update(['winner_emails_sent' => true]);
                continue;
            }

            // For Dutch multi-quantity auctions, a lot may have multiple buyers.
            // Collect all buyer → lot mappings from bids.
            $isDutch = $auction->isDutch();
            $lotsByWinner = collect();

            if ($isDutch) {
                // Get all Dutch buy bids for sold lots
                $soldLotIds = $soldLots->pluck('id');
                $dutchBids = \App\Models\Bid::whereIn('lot_id', $soldLotIds)
                    ->where('is_dutch_buy', true)
                    ->with('user', 'lot')
                    ->get();

                // Group by buyer: each buyer gets a summary of their purchases
                foreach ($dutchBids->groupBy('user_id') as $userId => $bids) {
                    $winnerLots = $bids->map(function ($bid) {
                        // Attach buy info to the lot for the email template
                        $lot = $bid->lot;
                        $lot->dutch_buy_price = $bid->amount;
                        $lot->dutch_buy_quantity = $bid->quantity_bought;
                        return $lot;
                    });
                    $lotsByWinner[$userId] = $winnerLots;
                }
            } else {
                // English: group by winning_bidder_id as before
                $lotsByWinner = $soldLots->filter(fn ($lot) => $lot->winningBidder)->groupBy('winning_bidder_id');
            }

            foreach ($lotsByWinner as $winnerId => $winnerLots) {
                $winner = $isDutch ? $winnerLots->first()->bids()->where('user_id', $winnerId)->first()?->user ?? \App\Models\User::find($winnerId) : $winnerLots->first()->winningBidder;

                if (!$winner) continue;

                // Respect user's email notification preference
                if (!$winner->email_notifications) {
                    $this->line("  Skipping {$winner->email} (notifications disabled)");
                    continue;
                }

                // Calculate grand total
                $grandTotal = $winnerLots->sum(function ($lot) use ($auction, $isDutch) {
                    if ($isDutch) {
                        $hammer = (float) ($lot->dutch_buy_price ?? $lot->current_bid) * ($lot->dutch_buy_quantity ?? 1);
                    } else {
                        $hammer = (float) $lot->current_bid;
                    }
                    $premium = $hammer * ((float) $auction->buyers_premium_percentage / 100);
                    return $hammer + $premium;
                });

                try {
                    Mail::to($winner->email)->send(
                        new AuctionWinnerSummary($auction, $winner, $winnerLots, $grandTotal)
                    );

                    $this->info("  Sent to {$winner->email} — {$winnerLots->count()} lot(s), total " . formatCurrency($grandTotal));
                    $totalEmailsSent++;
                } catch (\Exception $e) {
                    $this->error("  Failed to send to {$winner->email}: {$e->getMessage()}");
                }
            }

            // Mark auction emails as sent (even if some individual sends failed,
            // to avoid re-sending to winners who already received their email)
            $auction->update(['winner_emails_sent' => true]);
            $this->info("Auction #{$auction->id} '{$auction->title}' — emails processed");
        }

        $this->info("\n✓ Sent {$totalEmailsSent} winner email(s) across {$auctions->count()} auction(s)");

        return Command::SUCCESS;
    }
}
