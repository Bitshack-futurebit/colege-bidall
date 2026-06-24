<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentPayout;
use App\Models\CommunityCommissionLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    /**
     * Agent dashboard — earnings, ladder progress per community, referral link,
     * payout request button.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return redirect()->route('agent.apply');
        }
        if ($agent->status !== 'active') {
            return redirect()->route('agent.apply')
                ->with('error', 'Your agent account is not active.');
        }

        $agent->load(['communities' => fn($q) => $q->wherePivotNull('ended_at')]);

        // Headline pipeline numbers
        $accrued   = (float) $agent->ledgerEntries()->where('status', 'accrued')->sum('agent_share');
        $available = (float) $agent->ledgerEntries()
            ->where('status', 'seller_paid')
            ->whereNull('agent_payout_id')
            ->sum('agent_share');
        $earnedLifetime = (float) $agent->ledgerEntries()
            ->whereIn('status', ['seller_paid', 'agent_paid'])
            ->sum('agent_share');

        // Per-community current-month progress
        $period = now()->format('Y-m');
        $tier1Cap = (float) config('community.commission_tier1_cap', 1000);

        $communityProgress = $agent->communities->map(function ($region) use ($period, $tier1Cap) {
            $commissionThisMonth = (float) CommunityCommissionLedger::where('community_region_id', $region->id)
                ->where('period_key', $period)
                ->where('status', '!=', 'voided')
                ->sum('commission_amount');

            $myShareThisMonth = (float) CommunityCommissionLedger::where('community_region_id', $region->id)
                ->where('period_key', $period)
                ->where('status', '!=', 'voided')
                ->where('agent_id_at_accrual', $this->agentIdForRegion($region->id))
                ->sum('agent_share');

            $tier1Used = min($commissionThisMonth, $tier1Cap);
            $tier1Pct  = $tier1Cap > 0 ? round(($tier1Used / $tier1Cap) * 100) : 0;
            $inTier2   = $commissionThisMonth >= $tier1Cap;

            return (object) [
                'region' => $region,
                'commission_this_month' => $commissionThisMonth,
                'my_share_this_month' => $myShareThisMonth,
                'tier1_used' => $tier1Used,
                'tier1_pct' => $tier1Pct,
                'in_tier2' => $inTier2,
            ];
        });

        // Recent ledger activity
        $recentEntries = $agent->ledgerEntries()
            ->with(['lot', 'region'])
            ->orderByDesc('accrued_at')
            ->limit(15)
            ->get();

        // Payout history
        $payouts = $agent->payouts()
            ->orderByDesc('requested_at')
            ->limit(10)
            ->get();

        $payoutMin = (float) config('community.agent_payout_min', 500);
        $canRequestPayout = $available >= $payoutMin
            && !$agent->payouts()->whereIn('status', ['requested', 'approved'])->exists();

        $primaryCommunity = $agent->activeCommunities()->first();
        $referralUrl = $primaryCommunity
            ? route('community.region', $primaryCommunity->slug)
            : url('/register') . '?ref=' . $agent->referral_code;

        return view('agent.dashboard', [
            'agent' => $agent,
            'accrued' => $accrued,
            'available' => $available,
            'earnedLifetime' => $earnedLifetime,
            'communityProgress' => $communityProgress,
            'recentEntries' => $recentEntries,
            'payouts' => $payouts,
            'payoutMin' => $payoutMin,
            'canRequestPayout' => $canRequestPayout,
            'referralUrl' => $referralUrl,
            'referredCount' => $agent->referredUsers()->count(),
        ]);
    }

    /** Helper — primary agent id currently active for a region (always this agent on their dashboard). */
    private function agentIdForRegion(int $regionId): int
    {
        return (int) auth()->user()->agent->id;
    }

    /**
     * WhatsApp broadcast builder — community-auction equivalent of the seller
     * blast tool. Agent picks one of their assigned regions, then an auction
     * within it, then lots; a pre-formatted, agent-centric WhatsApp message
     * is generated for sharing in groups. Every message includes the agent's
     * referral link as a recruitment CTA.
     */
    public function whatsappBlast(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return redirect()->route('agent.apply');
        }
        if ($agent->status !== 'active') {
            return redirect()->route('agent.apply')
                ->with('error', 'Your agent account is not active.');
        }

        $agent->load(['communities' => fn($q) => $q->wherePivotNull('ended_at')]);
        $regions = $agent->communities;

        $auctionsJson = collect();
        if ($regions->isNotEmpty()) {
            $regionIds = $regions->pluck('id');

            $auctions = \App\Models\Auction::where('is_community', true)
                ->whereIn('community_region_id', $regionIds)
                ->whereIn('status', ['draft', 'upcoming', 'live'])
                ->with([
                    'lots' => fn($q) => $q->whereNull('withdrawn_at')->orderBy('lot_number'),
                    'communityRegion',
                ])
                ->orderBy('goes_live_at', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            $auctionsJson = $auctions->map(function ($auction) {
                return [
                    'id' => $auction->id,
                    'title' => $auction->title,
                    'status' => $auction->status,
                    'slug' => $auction->slug,
                    'region_id' => $auction->community_region_id,
                    'region_name' => $auction->communityRegion?->name ?? 'Community',
                    'start_time' => $auction->goes_live_at?->format('D j M, H:i')
                        ?? $auction->start_time?->format('D j M, H:i'),
                    'end_time' => $auction->end_time?->format('D j M, H:i'),
                    'url' => route('auctions.show', $auction->slug),
                    'lots' => $auction->lots->map(function ($lot) {
                        return [
                            'id' => $lot->id,
                            'lot_number' => $lot->lot_number,
                            'title' => $lot->title,
                            'starting_bid' => (float) $lot->starting_bid,
                            'current_bid' => $lot->current_bid !== null ? (float) $lot->current_bid : null,
                            'bids' => (int) ($lot->total_bids ?? 0),
                            'price_label' => formatCurrency($lot->current_bid ?? $lot->starting_bid),
                        ];
                    })->values(),
                ];
            });
        }

        $regionUrls = $regions->mapWithKeys(fn($r) => [$r->id => route('community.region', $r->slug)])->toArray();

        return view('agent.whatsapp-blast', [
            'agent' => $agent,
            'regions' => $regions,
            'auctionsJson' => $auctionsJson,
            'agentName' => $user->name,
            'referralUrl' => url('/register') . '?ref=' . $agent->referral_code,
            'referralCode' => $agent->referral_code,
            'regionUrls' => $regionUrls,
        ]);
    }

    /**
     * Agent submits a payout request — locks all currently-claimable ledger rows
     * onto the new payout record. Admin reviews and pays from the admin agent panel.
     */
    public function requestPayout(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;
        abort_unless($agent && $agent->isActive(), 403);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Don't allow stacking pending requests.
        if ($agent->payouts()->whereIn('status', ['requested', 'approved'])->exists()) {
            return back()->with('error', 'You already have a payout request in progress.');
        }

        $payoutMin = (float) config('community.agent_payout_min', 500);

        $payout = DB::transaction(function () use ($agent, $payoutMin, $validated) {
            $rows = CommunityCommissionLedger::where('agent_id_at_accrual', $agent->id)
                ->where('status', 'seller_paid')
                ->whereNull('agent_payout_id')
                ->lockForUpdate()
                ->get();

            $total = (float) $rows->sum('agent_share');
            if ($total < $payoutMin) {
                return null;
            }

            $payout = AgentPayout::create([
                'agent_id' => $agent->id,
                'amount' => round($total, 2),
                'status' => 'requested',
                'requested_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            CommunityCommissionLedger::whereIn('id', $rows->pluck('id'))
                ->update(['agent_payout_id' => $payout->id]);

            return $payout;
        });

        if (!$payout) {
            return back()->with('error', "Available balance is below the R" . number_format($payoutMin, 2) . " minimum.");
        }

        return back()->with('success', "Payout requested: R" . number_format($payout->amount, 2) . ". You'll be notified when it's processed.");
    }
}
