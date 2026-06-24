<x-app-layout>
    <x-slot name="title">Agent — {{ $agent->user->name }}</x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-3">
            <a href="{{ route('admin.agents.index', ['status' => $agent->status]) }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; All agents</a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Header --}}
        <div class="card p-5 mb-5">
            <div class="flex items-start gap-4">
                @if($agent->photo)
                    <img src="{{ Storage::url($agent->photo) }}" class="w-20 h-20 rounded-full object-cover">
                @else
                    <div class="w-20 h-20 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center text-2xl font-bold text-teal-700 dark:text-teal-300">
                        {{ substr($agent->user->name, 0, 1) }}
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold">{{ $agent->user->name }}</h1>
                        @php
                            $statusColors = ['pending' => 'amber', 'active' => 'green', 'suspended' => 'red', 'terminated' => 'gray'];
                            $color = $statusColors[$agent->status] ?? 'gray';
                        @endphp
                        <span class="px-2 py-0.5 text-xs bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300 rounded uppercase tracking-wide">
                            {{ strtoupper($agent->status) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 mt-0.5">{{ $agent->user->email }}</div>
                    @if($agent->user->phone)
                        <div class="text-sm text-gray-500">{{ $agent->user->phone }}</div>
                    @endif
                    @if($agent->status === 'active')
                        <div class="text-xs text-gray-500 mt-2">
                            Approved {{ $agent->approved_at?->format('d M Y') }}
                            @if($agent->approvedBy) by {{ $agent->approvedBy->name }} @endif
                            · Referral code: <span class="font-mono font-bold">{{ $agent->referral_code }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($agent->bio)
                <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">{{ $agent->bio }}</p>
            @endif
        </div>

        {{-- Qualification --}}
        <div class="card p-5 mb-5">
            <h2 class="text-lg font-bold mb-3">Qualification</h2>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-4">
                <div>
                    <dt class="text-xs text-gray-500 uppercase">WhatsApp group</dt>
                    <dd class="font-semibold">{{ $agent->whatsapp_group_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Member count claimed</dt>
                    <dd class="font-semibold">{{ $agent->whatsapp_group_size_claim }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase">Public WhatsApp</dt>
                    <dd class="font-semibold">{{ $agent->public_whatsapp_number }}</dd>
                </div>
            </dl>
            @if($agent->whatsapp_group_proof_path)
                <div>
                    <div class="text-xs text-gray-500 uppercase mb-1">Proof screenshot</div>
                    <a href="{{ Storage::url($agent->whatsapp_group_proof_path) }}" target="_blank">
                        <img src="{{ Storage::url($agent->whatsapp_group_proof_path) }}" class="max-w-md rounded border border-gray-200 dark:border-gray-700">
                    </a>
                </div>
            @endif
        </div>

        {{-- Stats (only for active agents) --}}
        @if($agent->status === 'active')
            <div class="card p-5 mb-5">
                <h2 class="text-lg font-bold mb-3">Performance</h2>
                <dl class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Communities</dt>
                        <dd class="text-2xl font-bold">{{ $agent->communities->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Referred users</dt>
                        <dd class="text-2xl font-bold">{{ $stats['referred_count'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase" title="Sales confirmed but seller hasn't paid the platform fee yet">Accrued</dt>
                        <dd class="text-2xl font-bold text-blue-600">R{{ number_format($stats['accrued_pending'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase" title="Seller has paid — claimable + already paid out">Earned</dt>
                        <dd class="text-2xl font-bold text-green-600">R{{ number_format($stats['total_earned'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase" title="Earned and ready to be paid out">Available</dt>
                        <dd class="text-2xl font-bold text-amber-600">R{{ number_format($stats['available_balance'], 2) }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-gray-500 mt-3">
                    Pipeline: <strong>Accrued</strong> (sale confirmed) → <strong>Earned</strong> (seller paid platform fee) → <strong>Available</strong> (claimable for payout) → paid out.
                </p>
            </div>
        @endif

        {{-- Community assignments (only meaningful for active agents) --}}
        @if($agent->status === 'active')
            <div class="card p-5 mb-5">
                <h2 class="text-lg font-bold mb-3">Communities</h2>

                @if($activeCommunities->isEmpty())
                    <p class="text-sm text-gray-500 mb-4">Not currently assigned to any community.</p>
                @else
                    <div class="space-y-2 mb-4">
                        @foreach($activeCommunities as $community)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded border border-gray-200 dark:border-gray-700">
                                <div class="min-w-0">
                                    <div class="font-semibold">{{ $community->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        @if($community->metro_area){{ $community->metro_area }} · @endif
                                        Started {{ \Carbon\Carbon::parse($community->pivot->started_at)->format('d M Y') }}
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.agents.unassign-community', [$agent, $community->id]) }}"
                                      onsubmit="return confirm('End this assignment? Future commission won\'t accrue to this agent on this community.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline text-xs text-red-600 border-red-200">End assignment</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($availableRegions->isNotEmpty())
                    <form method="POST" action="{{ route('admin.agents.assign-community', $agent) }}"
                          class="flex flex-col sm:flex-row gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="community_region_id" required class="input flex-1">
                            <option value="">Select a community to assign…</option>
                            @foreach($availableRegions as $region)
                                <option value="{{ $region->id }}">
                                    {{ $region->name }}@if($region->metro_area) — {{ $region->metro_area }}@endif
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary text-sm">Assign as primary</button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">Assigning here ends any current primary agent on the chosen community.</p>
                @else
                    <p class="text-xs text-gray-500">No more active regions available to assign.</p>
                @endif
            </div>
        @endif

        {{-- Payouts --}}
        @if($agent->status === 'active' && $agent->payouts->isNotEmpty())
            <div class="card p-5 mb-5">
                <h2 class="text-lg font-bold mb-3">Payouts</h2>
                <div class="space-y-2">
                    @foreach($agent->payouts as $payout)
                        @php
                            $colors = [
                                'requested' => 'blue',
                                'approved'  => 'amber',
                                'paid'      => 'green',
                                'rejected'  => 'red',
                            ];
                            $c = $colors[$payout->status] ?? 'gray';
                        @endphp
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="font-bold">R{{ number_format($payout->amount, 2) }}</div>
                                    <div class="text-xs text-gray-500">
                                        Requested {{ $payout->requested_at->format('d M Y H:i') }}
                                        @if($payout->paid_at) · Paid {{ $payout->paid_at->format('d M Y') }}@endif
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-xs bg-{{ $c }}-100 text-{{ $c }}-800 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-300 rounded uppercase tracking-wide">{{ $payout->status }}</span>
                            </div>
                            @if($payout->notes)
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $payout->notes }}</div>
                            @endif

                            @if(in_array($payout->status, ['requested', 'approved']))
                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.agents.pay-payout', $payout) }}" x-data="{ open: false }" class="flex-1 min-w-[200px]">
                                        @csrf
                                        <button type="button" @click="open = true" x-show="!open" class="btn btn-primary text-xs w-full">Mark paid</button>
                                        <div x-show="open" x-cloak class="flex gap-2 items-stretch">
                                            <input type="text" name="paid_via" maxlength="120" class="input text-sm flex-1" placeholder="e.g. EFT 25 Apr">
                                            <button type="submit" class="btn btn-primary text-sm whitespace-nowrap"
                                                    onclick="return confirm('Mark this payout of R{{ number_format($payout->amount, 2) }} as paid?');">Confirm</button>
                                            <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                                        </div>
                                    </form>
                                    @if($payout->status === 'requested')
                                        <form method="POST" action="{{ route('admin.agents.reject-payout', $payout) }}" x-data="{ open: false }" class="flex-1 min-w-[200px]">
                                            @csrf
                                            <button type="button" @click="open = true" x-show="!open" class="btn btn-outline text-xs w-full text-red-600 border-red-200">Reject</button>
                                            <div x-show="open" x-cloak class="flex gap-2 items-stretch">
                                                <input type="text" name="notes" required minlength="5" maxlength="500" class="input text-sm flex-1" placeholder="Reason">
                                                <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white text-sm whitespace-nowrap">Reject</button>
                                                <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Testing helper: simulate seller paying their fee invoice --}}
        @if($agent->status === 'active' && $outstandingBySeller->isNotEmpty())
            <div class="card p-5 mb-5 border-dashed border-2 border-amber-300 bg-amber-50/30">
                <h2 class="text-lg font-bold mb-1">Testing helper — sellers with outstanding fees</h2>
                <p class="text-xs text-gray-600 mb-3">Use until PayFast integration ships. One click flips that seller's accrued ledger rows to <code>seller_paid</code> — agent share becomes claimable.</p>
                <div class="space-y-2">
                    @foreach($outstandingBySeller as $row)
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded border border-amber-200">
                            <div class="text-sm">
                                <div class="font-semibold">{{ $row->seller_name }}</div>
                                <div class="text-xs text-gray-500">{{ $row->line_count }} unpaid {{ Str::plural('line', $row->line_count) }} · R{{ number_format($row->total_owed, 2) }} total</div>
                            </div>
                            <form method="POST" action="{{ route('admin.agents.mark-seller-paid') }}"
                                  onsubmit="return confirm('Mark all of {{ $row->seller_name }}\'s outstanding fees as paid?');">
                                @csrf
                                <input type="hidden" name="seller_user_id" value="{{ $row->seller_user_id }}">
                                <button type="submit" class="btn btn-primary text-xs">Mark paid</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Suspension/termination reason --}}
        @if(in_array($agent->status, ['suspended', 'terminated']) && $agent->suspension_reason)
            <div class="card p-5 mb-5 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500">
                <h3 class="font-bold mb-1">Reason</h3>
                <p class="text-sm">{{ $agent->suspension_reason }}</p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="card p-5">
            <h2 class="text-lg font-bold mb-3">Actions</h2>

            @if($agent->status === 'pending')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <form method="POST" action="{{ route('admin.agents.approve', $agent) }}"
                          onsubmit="return confirm('Approve this agent? They\'ll get an active referral code immediately.')">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('admin.agents.reject', $agent) }}" x-data="{ open: false }">
                        @csrf
                        <button type="button" @click="open = true" x-show="!open" class="btn btn-outline w-full text-red-600 border-red-200">Reject</button>
                        <div x-show="open" x-cloak class="space-y-2">
                            <textarea name="reason" rows="2" required minlength="5" maxlength="500" class="input text-sm" placeholder="Reason (required)"></textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white text-sm flex-1">Confirm reject</button>
                                <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            @elseif($agent->status === 'active')
                <form method="POST" action="{{ route('admin.agents.suspend', $agent) }}" x-data="{ open: false }">
                    @csrf
                    <button type="button" @click="open = true" x-show="!open" class="btn btn-outline text-red-600 border-red-200">Suspend agent</button>
                    <div x-show="open" x-cloak class="space-y-2">
                        <textarea name="reason" rows="2" required minlength="5" maxlength="500" class="input text-sm" placeholder="Reason (required)"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white text-sm">Confirm suspend</button>
                            <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                        </div>
                    </div>
                </form>
            @elseif($agent->status === 'suspended')
                <form method="POST" action="{{ route('admin.agents.reinstate', $agent) }}"
                      onsubmit="return confirm('Reinstate this agent?')">
                    @csrf
                    <button type="submit" class="btn btn-primary">Reinstate</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
