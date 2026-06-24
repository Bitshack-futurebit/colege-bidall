<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $statuses = ['pending', 'active', 'suspended', 'terminated'];
        if (!in_array($status, $statuses)) {
            $status = 'pending';
        }

        $agents = Agent::with(['user', 'communities'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(25);

        $counts = [];
        foreach ($statuses as $s) {
            $counts[$s] = Agent::where('status', $s)->count();
        }

        return view('admin.agents.index', compact('agents', 'status', 'counts'));
    }

    public function show(Agent $agent)
    {
        $agent->load(['user', 'approvedBy', 'communities', 'referredUsers', 'payouts' => fn($q) => $q->orderByDesc('requested_at')]);

        $stats = [
            'referred_count' => $agent->referredUsers()->count(),
            'accrued_pending' => (float) $agent->ledgerEntries()
                ->where('status', 'accrued')
                ->sum('agent_share'),
            'total_earned'   => (float) $agent->ledgerEntries()
                ->whereIn('status', ['seller_paid', 'agent_paid'])
                ->sum('agent_share'),
            'available_balance' => $agent->availableBalance(),
        ];

        // Active assignments + regions available to assign (excluding ones this agent already covers actively)
        $activeCommunities = $agent->communities()
            ->wherePivotNull('ended_at')
            ->get();
        $assignedRegionIds = $activeCommunities->pluck('id')->all();

        $availableRegions = \App\Models\CommunityRegion::where('is_active', true)
            ->whereNotIn('id', $assignedRegionIds)
            ->orderBy('name')
            ->get();

        // Testing helper: sellers with outstanding fees on this agent's communities.
        $outstandingBySeller = collect();
        if (!empty($assignedRegionIds)) {
            $outstandingBySeller = \DB::table('community_commission_ledger')
                ->join('users', 'community_commission_ledger.seller_user_id', '=', 'users.id')
                ->whereIn('community_commission_ledger.community_region_id', $assignedRegionIds)
                ->where('community_commission_ledger.status', 'accrued')
                ->select(
                    'users.id as seller_user_id',
                    'users.name as seller_name',
                    \DB::raw('COUNT(*) as line_count'),
                    \DB::raw('SUM(community_commission_ledger.commission_amount) as total_owed')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_owed')
                ->get();
        }

        return view('admin.agents.show', compact('agent', 'stats', 'activeCommunities', 'availableRegions', 'outstandingBySeller'));
    }

    public function approve(Request $request, Agent $agent)
    {
        if ($agent->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be approved.');
        }

        $agent->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', "Agent {$agent->user->name} approved.");
    }

    public function reject(Request $request, Agent $agent)
    {
        if ($agent->status !== 'pending') {
            return back()->with('error', 'Only pending applications can be rejected.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $agent->update([
            'status' => 'terminated',
            'suspension_reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.agents.index')
            ->with('success', "Application rejected.");
    }

    public function suspend(Request $request, Agent $agent)
    {
        if ($agent->status !== 'active') {
            return back()->with('error', 'Only active agents can be suspended.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $agent->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Agent suspended.');
    }

    public function reinstate(Agent $agent)
    {
        if ($agent->status !== 'suspended') {
            return back()->with('error', 'Only suspended agents can be reinstated.');
        }

        $agent->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', 'Agent reinstated.');
    }

    /**
     * Assign an active agent as primary on a community region.
     * If the community already has a primary agent, that prior assignment is ended.
     */
    public function assignCommunity(Request $request, Agent $agent)
    {
        if ($agent->status !== 'active') {
            return back()->with('error', 'Only active agents can be assigned to communities.');
        }

        $validated = $request->validate([
            'community_region_id' => ['required', 'integer', 'exists:community_regions,id'],
        ]);

        $regionId = $validated['community_region_id'];

        // Already assigned (and not ended) to this region?
        $existing = $agent->communities()
            ->wherePivot('community_region_id', $regionId)
            ->wherePivotNull('ended_at')
            ->exists();
        if ($existing) {
            return back()->with('error', 'Agent is already assigned to that community.');
        }

        $message = "Agent assigned.";

        \DB::transaction(function () use ($agent, $regionId, &$message) {
            // End any current primary assignment on this region (only one primary at a time).
            $endedRows = \DB::table('agent_community')
                ->where('community_region_id', $regionId)
                ->where('is_primary', true)
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'updated_at' => now()]);

            $agent->communities()->attach($regionId, [
                'is_primary' => true,
                'started_at' => now(),
                'ended_at' => null,
            ]);

            if ($endedRows > 0) {
                $message = "Agent assigned. Previous primary agent's assignment was ended.";
            }
        });

        return back()->with('success', $message);
    }

    /**
     * Mark a payout as paid (admin transferred the money out-of-band).
     * Flips the locked ledger rows from seller_paid → agent_paid.
     */
    public function payPayout(Request $request, \App\Models\AgentPayout $payout)
    {
        if (!in_array($payout->status, ['requested', 'approved'])) {
            return back()->with('error', 'Only requested or approved payouts can be marked as paid.');
        }

        $validated = $request->validate([
            'paid_via' => ['nullable', 'string', 'max:120'],
        ]);

        \DB::transaction(function () use ($payout, $request, $validated) {
            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
                'paid_via' => $validated['paid_via'] ?? 'Manual transfer',
                'approved_by_user_id' => $payout->approved_by_user_id ?? $request->user()->id,
                'approved_at' => $payout->approved_at ?? now(),
            ]);

            \App\Models\CommunityCommissionLedger::where('agent_payout_id', $payout->id)
                ->update([
                    'status' => 'agent_paid',
                    'agent_paid_at' => now(),
                ]);
        });

        return back()->with('success', "Payout R" . number_format($payout->amount, 2) . " marked as paid.");
    }

    /**
     * Reject a payout request — releases the locked ledger rows so the agent
     * can re-claim them on a future request.
     */
    public function rejectPayout(Request $request, \App\Models\AgentPayout $payout)
    {
        if ($payout->status !== 'requested') {
            return back()->with('error', 'Only requested payouts can be rejected.');
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        \DB::transaction(function () use ($payout, $validated, $request) {
            $payout->update([
                'status' => 'rejected',
                'notes' => trim(($payout->notes ? $payout->notes . "\n\n" : '') . 'Rejection: ' . $validated['notes']),
                'approved_by_user_id' => $request->user()->id,
            ]);

            \App\Models\CommunityCommissionLedger::where('agent_payout_id', $payout->id)
                ->update(['agent_payout_id' => null]);
        });

        return back()->with('success', 'Payout rejected and ledger rows released.');
    }

    /**
     * Testing helper: mark all of a specific seller's accrued ledger rows as
     * seller_paid, simulating that they've settled their fee invoice. Used to
     * test agent earning + payout flow before the PayFast integration ships.
     */
    public function markSellerPaid(Request $request)
    {
        $validated = $request->validate([
            'seller_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $result = app(\App\Services\CommunityCommissionService::class)
            ->markSellerPaid((int) $validated['seller_user_id']);

        if ($result['count'] === 0) {
            return back()->with('error', 'That seller has no outstanding fees.');
        }

        return back()->with('success',
            "Marked {$result['count']} ledger rows as seller_paid (R" . number_format($result['amount'], 2) . " total).");
    }

    /**
     * End an agent's current assignment to a community.
     */
    public function unassignCommunity(Agent $agent, $communityRegionId)
    {
        $updated = \DB::table('agent_community')
            ->where('agent_id', $agent->id)
            ->where('community_region_id', $communityRegionId)
            ->whereNull('ended_at')
            ->update(['ended_at' => now(), 'updated_at' => now()]);

        if ($updated === 0) {
            return back()->with('error', 'Assignment not found.');
        }

        return back()->with('success', 'Assignment ended.');
    }
}
