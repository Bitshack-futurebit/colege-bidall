<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Auctioneer;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * SPIKE: prove a NON-community (is_community=false) Live auction can be
 * created by a seller and driven through its full phase lifecycle + close,
 * using ONLY the standalone engine. Timers are forced (not slept) so the
 * state-machine transitions are tested deterministically.
 */
class SpikeLiveTest extends Command
{
    protected $signature = 'spike:live-test';
    protected $description = 'Standalone (non-community) Live auction engine spike';

    private array $fails = [];
    private array $log = [];

    public function handle(): int
    {
        $this->cleanup();

        // --- Actors (non-community) ---
        $seller = $this->user('spike.seller@college.test', 'Spike Seller', 'auctioneer');
        $auctioneer = Auctioneer::create([
            'user_id' => $seller->id,
            'business_name' => 'Spike Auction House',
            'slug' => 'spike-auction-house-' . uniqid(),
            'whatsapp_number' => '0820000000',
            'is_activated' => true,
            'credit_balance' => 1000,
            'is_free_account' => true,
        ]);
        $bidderA = $this->user('spike.bidderA@college.test', 'Bidder A', 'bidder');
        $bidderB = $this->user('spike.bidderB@college.test', 'Bidder B', 'bidder');

        // --- Live auction, NON-community ---
        $auction = Auction::create([
            'auctioneer_id' => $auctioneer->id,
            'title' => 'Spike Live Auction',
            'slug' => 'spike-live-' . uniqid(),
            'description' => 'standalone live spike',
            'status' => 'draft',
            'auction_type' => 'live',
            'is_community' => false,
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHours(2),
            'buyers_premium_percentage' => 10,
            'requires_registration' => false,
            'allow_proxy_bidding' => false,
            'enable_online_payment' => false,
        ]);

        // Lot 1: has reserve, will SELL. Lot 2: no bids, will close no-interest.
        $lot1 = Lot::create([
            'event_id' => $auction->id, 'lot_number' => 1, 'title' => 'Lot 1 (sells)',
            'starting_bid' => 100, 'increment' => 10, 'reserve_price' => 100,
            'image_tier' => 'basic', 'status' => 'pending',
        ]);
        $lot2 = Lot::create([
            'event_id' => $auction->id, 'lot_number' => 2, 'title' => 'Lot 2 (no interest)',
            'starting_bid' => 50, 'increment' => 5, 'reserve_price' => 0,
            'image_tier' => 'basic', 'status' => 'pending',
        ]);

        $this->line("Created NON-community live auction #{$auction->id} with 2 lots.");

        // === GO LIVE ===
        $auction->goLive();
        $auction->refresh();
        $lot1->refresh();
        $this->assert($auction->status === 'live', "auction goes live (status={$auction->status})");
        $this->assert($lot1->live_phase === Lot::LIVE_PHASE_OPENING, "lot1 starts in OPENING (phase={$lot1->live_phase})");

        // === LOT 1: drive phases to a SALE ===
        $this->forceAdvance($lot1, Lot::LIVE_PHASE_PRESENTING, 'OPENING→PRESENTING');
        $this->forceAdvance($lot1, Lot::LIVE_PHASE_OPEN_CALL, 'PRESENTING→OPEN_CALL');

        // Bid rejected outside open phases? test: bid is allowed in OPEN_CALL
        $this->placeBidExpectOk($lot1, $bidderA, 100, 'bidderA opens at 100 in OPEN_CALL');
        $lot1->refresh();
        $this->assert($lot1->live_phase === Lot::LIVE_PHASE_ACTIVE, "first bid moves OPEN_CALL→ACTIVE (phase={$lot1->live_phase})");

        $this->placeBidExpectOk($lot1, $bidderB, 110, 'bidderB raises to 110 in ACTIVE');
        $lot1->refresh();
        $this->assert((float)$lot1->current_bid === 110.0 && $lot1->winning_bidder_id === $bidderB->id, "current bid 110 to bidderB");

        // ACTIVE silence → GOING_ONCE
        $this->forceAdvance($lot1, Lot::LIVE_PHASE_GOING_ONCE, 'ACTIVE→GOING_ONCE');
        // Reclaim: bid in GOING_ONCE reverts to ACTIVE
        $this->placeBidExpectOk($lot1, $bidderA, 120, 'bidderA reclaims at 120 in GOING_ONCE');
        $lot1->refresh();
        $this->assert($lot1->live_phase === Lot::LIVE_PHASE_ACTIVE, "going-phase bid reverts to ACTIVE (phase={$lot1->live_phase})");

        // Run the pulse out to close
        $this->forceAdvance($lot1, Lot::LIVE_PHASE_GOING_ONCE, 'ACTIVE→GOING_ONCE (2)');
        $this->forceAdvance($lot1, Lot::LIVE_PHASE_GOING_TWICE, 'GOING_ONCE→GOING_TWICE');
        // GOING_TWICE elapses → closeLive()
        $lot1->update(['live_phase_ends_at' => now()->subSecond()]);
        $lot1->advanceLivePhase();
        $lot1->refresh();
        $this->assert($lot1->status === 'sold', "lot1 SOLD (status={$lot1->status})");
        $this->assert($lot1->winning_bidder_id === $bidderA->id && (float)$lot1->current_bid === 120.0, "winner bidderA @ 120");
        $this->assert($lot1->live_phase === Lot::LIVE_PHASE_CLOSED, "lot1 phase CLOSED (result window)");

        // CLOSED result window elapses → next lot starts
        $lot1->update(['live_phase_ends_at' => now()->subSecond()]);
        $lot1->advanceLivePhase();
        $lot2->refresh();
        $this->assert($lot2->status === 'live' && $lot2->live_phase === Lot::LIVE_PHASE_INTERMISSION,
            "lot2 advanced to INTERMISSION (status={$lot2->status}, phase={$lot2->live_phase})");

        // === LOT 2: no bids → no-interest close ===
        $this->forceAdvance($lot2, Lot::LIVE_PHASE_PRESENTING, 'lot2 INTERMISSION→PRESENTING');
        $this->forceAdvance($lot2, Lot::LIVE_PHASE_OPEN_CALL, 'lot2 PRESENTING→OPEN_CALL');
        // No bid; open-call elapses → close as no-interest
        $lot2->update(['live_phase_ends_at' => now()->subSecond()]);
        $lot2->advanceLivePhase();
        $lot2->refresh();
        $this->assert($lot2->status === 'unsold', "lot2 UNSOLD via no-interest (status={$lot2->status})");

        // Close result window → no more lots → auction ends
        $lot2->update(['live_phase_ends_at' => now()->subSecond()]);
        $lot2->advanceLivePhase();
        $auction->refresh();
        $this->assert($auction->status === 'ended', "auction ENDED after last lot (status={$auction->status})");

        // Bonus: bid rejected in a non-open phase (sanity on gating)
        $lot2->refresh();
        $this->placeBidExpectFail($lot2, $bidderA, 50, 'bid rejected on closed lot2');

        $this->printReport();
        return empty($this->fails) ? Command::SUCCESS : Command::FAILURE;
    }

    private function forceAdvance(Lot $lot, string $expectedPhase, string $label): void
    {
        $lot->update(['live_phase_ends_at' => now()->subSecond()]);
        $lot->advanceLivePhase();
        $lot->refresh();
        $this->assert($lot->live_phase === $expectedPhase, "{$label} (got {$lot->live_phase})");
    }

    private function placeBidExpectOk(Lot $lot, User $u, float $amt, string $label): void
    {
        try {
            $lot->placeBid($u, $amt);
            $this->log[] = "  OK  {$label}";
        } catch (\Throwable $e) {
            $this->fails[] = "{$label} — threw: {$e->getMessage()}";
        }
    }

    private function placeBidExpectFail(Lot $lot, User $u, float $amt, string $label): void
    {
        try {
            $lot->placeBid($u, $amt);
            $this->fails[] = "{$label} — expected rejection but bid succeeded";
        } catch (\Throwable $e) {
            $this->log[] = "  OK  {$label} (rejected: {$e->getMessage()})";
        }
    }

    private function assert(bool $cond, string $label): void
    {
        if ($cond) {
            $this->log[] = "  ✓  {$label}";
        } else {
            $this->fails[] = $label;
            $this->log[] = "  ✗  {$label}";
        }
    }

    private function user(string $email, string $name, string $role): User
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'phone' => '0820000000',
            'password' => Hash::make('password'),
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    private function cleanup(): void
    {
        $ids = User::where('email', 'like', 'spike.%@college.test')->pluck('id');
        if ($ids->isEmpty()) return;
        $aids = Auctioneer::whereIn('user_id', $ids)->pluck('id');
        $eids = Auction::whereIn('auctioneer_id', $aids)->pluck('id');
        $lids = Lot::whereIn('event_id', $eids)->pluck('id');
        DB::table('bids')->whereIn('lot_id', $lids)->delete();
        DB::table('bids')->whereIn('user_id', $ids)->delete();
        Lot::whereIn('id', $lids)->forceDelete();
        Auction::whereIn('id', $eids)->forceDelete();
        Auctioneer::whereIn('id', $aids)->forceDelete();
        User::whereIn('id', $ids)->forceDelete();
    }

    private function printReport(): void
    {
        $this->newLine();
        foreach ($this->log as $l) $this->line($l);
        $this->newLine();
        if (empty($this->fails)) {
            $this->info('SPIKE RESULT: 🟢 GREEN — standalone (non-community) Live engine works end-to-end.');
        } else {
            $this->error('SPIKE RESULT: issues found (' . count($this->fails) . '):');
            foreach ($this->fails as $f) $this->error("  - {$f}");
        }
    }
}
