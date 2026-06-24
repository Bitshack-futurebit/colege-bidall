<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CommunityCommissionLedger;
use App\Models\CommunityRegion;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class CommunityCommissionService
{
    /**
     * Compute the platform/agent split for a single sale, given the community's
     * already-accrued commission for the period (so a sale that crosses the
     * tier-1 cap is split correctly).
     *
     * Returns an associative array with the breakdown. Pure function.
     */
    public function splitForSale(float $runningPeriodCommission, float $newCommission): array
    {
        $tier1Cap          = (float) config('community.commission_tier1_cap', 1000);
        $tier1PlatformPct  = (float) config('community.commission_tier1_platform_pct', 50);
        $tier2PlatformPct  = (float) config('community.commission_tier2_platform_pct', 30);

        $tier1Room = max(0.0, $tier1Cap - $runningPeriodCommission);
        $tier1Portion = min($newCommission, $tier1Room);
        $tier2Portion = $newCommission - $tier1Portion;

        $platformShare = $this->round(
            $tier1Portion * ($tier1PlatformPct / 100)
            + $tier2Portion * ($tier2PlatformPct / 100)
        );
        $agentShare = $this->round($newCommission - $platformShare);

        return [
            'tier1_portion'  => $this->round($tier1Portion),
            'tier2_portion'  => $this->round($tier2Portion),
            'platform_share' => $platformShare,
            'agent_share'    => $agentShare,
        ];
    }

    /**
     * Accrue commission for a confirmed community sale. Idempotent — repeated
     * calls for the same lot are no-ops (unique constraint on lot_id).
     *
     * Call this when a community lot's status transitions to 'sold' (i.e. on
     * seller-confirm or auto-confirm of a community lot in pending_confirmation).
     */
    public function accrue(Lot $lot): ?CommunityCommissionLedger
    {
        if (!$lot->isCommunityLot()) {
            return null;
        }
        if (CommunityCommissionLedger::where('lot_id', $lot->id)->exists()) {
            return null;
        }

        $auction = $lot->auction;
        if (!$auction || !$auction->is_community || !$auction->community_region_id) {
            return null;
        }

        $hammer = (float) ($lot->current_bid ?? 0);
        if ($hammer <= 0) {
            return null;
        }

        $commissionPct = (float) config('community.commission_percent', 5);
        $commission = $this->round($hammer * ($commissionPct / 100));

        if ($commission <= 0) {
            return null;
        }

        $regionId = $auction->community_region_id;
        $period = now()->format('Y-m');

        return DB::transaction(function () use ($lot, $regionId, $hammer, $commission, $period) {
            // Sum of non-voided commission already accrued for this region in this period.
            $running = (float) CommunityCommissionLedger::where('community_region_id', $regionId)
                ->where('period_key', $period)
                ->notVoided()
                ->sum('commission_amount');

            // Currently-assigned primary agent for this community (frozen to this row).
            $agentId = DB::table('agent_community')
                ->where('community_region_id', $regionId)
                ->where('is_primary', true)
                ->whereNull('ended_at')
                ->value('agent_id');

            $split = $this->splitForSale($running, $commission);

            // No agent → entire commission goes to platform; agent_share zeroed.
            if (!$agentId) {
                $split['platform_share'] = $commission;
                $split['agent_share']    = 0.0;
            }

            return CommunityCommissionLedger::create([
                'lot_id' => $lot->id,
                'community_region_id' => $regionId,
                'seller_user_id' => $lot->seller_user_id,
                'buyer_user_id' => $lot->winning_bidder_id,
                'hammer_amount' => $hammer,
                'commission_amount' => $commission,
                'period_key' => $period,
                'tier1_portion' => $split['tier1_portion'],
                'tier2_portion' => $split['tier2_portion'],
                'platform_share' => $split['platform_share'],
                'agent_share' => $split['agent_share'],
                'agent_id_at_accrual' => $agentId,
                'status' => 'accrued',
                'accrued_at' => now(),
            ]);
        });
    }

    /**
     * Void a previously-accrued commission row (e.g. seller marked
     * "buyer never paid"). Removes both platform and agent claims.
     */
    public function void(CommunityCommissionLedger $row, string $reason): void
    {
        if ($row->status === 'voided') return;
        $row->update([
            'status' => 'voided',
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }

    /**
     * Mark all of a seller's currently-accrued ledger rows as paid.
     * Called when the seller settles their outstanding fee debt — either via
     * PayFast webhook (Phase 4b) or via admin "mark paid" testing helper.
     *
     * Returns the count of rows updated and the total amount.
     */
    public function markSellerPaid(int $sellerUserId, ?int $transactionId = null): array
    {
        $rows = CommunityCommissionLedger::where('seller_user_id', $sellerUserId)
            ->where('status', 'accrued')
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            return ['count' => 0, 'amount' => 0.0];
        }

        $total = 0.0;
        DB::transaction(function () use ($rows, $transactionId, &$total) {
            foreach ($rows as $row) {
                $row->update([
                    'status' => 'seller_paid',
                    'seller_paid_at' => now(),
                    'seller_payment_transaction_id' => $transactionId,
                ]);
                $total += (float) $row->commission_amount;
            }
        });

        return ['count' => $rows->count(), 'amount' => round($total, 2)];
    }

    /**
     * Total platform fee currently owed by a seller (accrued, not yet paid).
     */
    public function sellerOutstandingDebt(int $sellerUserId): float
    {
        return (float) CommunityCommissionLedger::where('seller_user_id', $sellerUserId)
            ->where('status', 'accrued')
            ->sum('commission_amount');
    }

    /**
     * Returns [allowed, reason] tuple — whether the seller is allowed to create
     * a new community listing under the fee-debt gates.
     *
     * The gate is now COUPLED: a seller is only blocked when debt > threshold
     * AND at least one unpaid line is older than the age cutoff. A high-value
     * seller can carry a big balance for the grace period; a small-time seller
     * never trips the threshold even after months. Both conditions are needed.
     */
    public function canSellerList(int $sellerUserId): array
    {
        $threshold = (float) config('community.fee_debt_block_threshold', 100);
        $ageDays = (int) config('community.fee_debt_age_block_days', 30);

        $debt = $this->sellerOutstandingDebt($sellerUserId);
        if ($debt <= $threshold) {
            return [true, null];
        }

        $cutoff = now()->subDays($ageDays);
        $hasStaleLine = CommunityCommissionLedger::where('seller_user_id', $sellerUserId)
            ->where('status', 'accrued')
            ->where('accrued_at', '<=', $cutoff)
            ->exists();

        if (!$hasStaleLine) {
            // Big debt but everything is still inside the grace window — allow.
            return [true, null];
        }

        return [false, "Outstanding platform fees of R" . number_format($debt, 2)
            . " include unpaid lines older than {$ageDays} days. Settle to continue listing."];
    }

    private function round(float $v): float
    {
        return round($v, 2);
    }
}
