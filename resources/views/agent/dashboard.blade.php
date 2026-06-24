<x-app-layout>
    <x-slot name="title">Agent Dashboard</x-slot>

    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-2xl font-bold">Agent Dashboard</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Welcome, {{ $agent->user->name }}.</p>
            </div>
            <span class="px-3 py-1 text-xs font-bold uppercase tracking-wide bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300 rounded">Active</span>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded">{{ session('error') }}</div>
        @endif

        {{-- Pipeline stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            <div class="card p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">Accrued</div>
                <div class="text-2xl font-bold text-blue-600 mt-1">R{{ number_format($accrued, 2) }}</div>
                <div class="text-[10px] text-gray-500 mt-1">Awaiting seller payment</div>
            </div>
            <div class="card p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">Available</div>
                <div class="text-2xl font-bold text-amber-600 mt-1">R{{ number_format($available, 2) }}</div>
                <div class="text-[10px] text-gray-500 mt-1">Ready to claim</div>
            </div>
            <div class="card p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">Lifetime earned</div>
                <div class="text-2xl font-bold text-green-600 mt-1">R{{ number_format($earnedLifetime, 2) }}</div>
            </div>
            <div class="card p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">Referred users</div>
                <div class="text-2xl font-bold mt-1">{{ $referredCount }}</div>
            </div>
        </div>

        {{-- Payout request --}}
        <div class="card p-5 mb-5 {{ $canRequestPayout ? 'bg-amber-50/50 dark:bg-amber-900/10 border-amber-200' : '' }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-bold">Request payout</h2>
                    @if($canRequestPayout)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Claim R{{ number_format($available, 2) }} now. Admin will review and pay out.
                        </p>
                    @elseif($agent->payouts()->whereIn('status', ['requested', 'approved'])->exists())
                        <p class="text-sm text-gray-600 dark:text-gray-400">You have a payout in progress — see history below.</p>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Available balance must reach <strong>R{{ number_format($payoutMin, 2) }}</strong> before you can request a payout.
                        </p>
                    @endif
                </div>

                @if($canRequestPayout)
                    <form method="POST" action="{{ route('agent.payout.request') }}" x-data="{ open: false }" class="shrink-0">
                        @csrf
                        <button type="button" @click="open = true" x-show="!open" class="btn btn-primary whitespace-nowrap">
                            Request R{{ number_format($available, 2) }}
                        </button>
                        <div x-show="open" x-cloak class="flex flex-col sm:flex-row gap-2 items-stretch">
                            <input type="text" name="notes" maxlength="500" class="input text-sm" placeholder="Bank details / notes (optional)">
                            <button type="submit" class="btn btn-primary text-sm whitespace-nowrap"
                                    onclick="return confirm('Request payout of R{{ number_format($available, 2) }}?');">Confirm request</button>
                            <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Communities — ladder progress this month --}}
        <h2 class="text-lg font-bold mb-3">Your communities — {{ now()->format('M Y') }}</h2>
        @if($communityProgress->isEmpty())
            <div class="card p-5 mb-5 text-sm text-gray-500">
                Not currently assigned to any community. Check with admin.
            </div>
        @else
            <div class="space-y-3 mb-5">
                @foreach($communityProgress as $cp)
                    <div class="card p-4">
                        <div class="flex flex-wrap justify-between gap-2 mb-2">
                            <div>
                                <div class="font-bold">{{ $cp->region->name }}</div>
                                <div class="text-xs text-gray-500">
                                    @if($cp->region->metro_area){{ $cp->region->metro_area }} · @endif
                                    Commission collected this month: R{{ number_format($cp->commission_this_month, 2) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] uppercase tracking-wide text-gray-500">Your share</div>
                                <div class="font-bold text-teal-600">R{{ number_format($cp->my_share_this_month, 2) }}</div>
                            </div>
                        </div>

                        {{-- Tier ladder progress bar --}}
                        <div class="mt-2">
                            <div class="flex justify-between text-[10px] uppercase tracking-wide text-gray-500 mb-1">
                                <span>Tier 1: 50/50</span>
                                <span class="{{ $cp->in_tier2 ? 'text-teal-600 font-semibold' : '' }}">
                                    {{ $cp->in_tier2 ? '→ Tier 2: 70% to you' : 'Tier 2 unlocks at R' . number_format(config('community.commission_tier1_cap'), 0) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-teal-500 h-2 rounded-full transition-all" style="width: {{ $cp->tier1_pct }}%"></div>
                            </div>
                            <div class="text-[10px] text-gray-500 mt-1">
                                R{{ number_format($cp->tier1_used, 2) }} / R{{ number_format(config('community.commission_tier1_cap'), 0) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Referral link --}}
        <div class="card p-5 mb-5" x-data="{ copied: false }">
            <h2 class="text-lg font-bold mb-1">Your referral link</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Share this link in your WhatsApp group. Anyone who signs up via it is attributed to you.
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" readonly value="{{ $referralUrl }}"
                       class="input text-sm font-mono flex-1"
                       onclick="this.select()">
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $referralUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="btn btn-primary whitespace-nowrap">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </button>
            </div>
            <div class="mt-3 text-xs text-gray-500">
                Code: <span class="font-mono font-bold tracking-wider">{{ $agent->referral_code }}</span>
            </div>
        </div>

        {{-- Marketing tools --}}
        <a href="{{ route('agent.whatsapp-blast') }}" class="card card-hover p-5 mb-5 flex items-center gap-4 group">
            <div class="w-12 h-12 rounded-lg bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition">
                    WhatsApp Broadcast Builder
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Build a share-ready message for upcoming community auctions — your referral link is included automatically.
                </p>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>

        {{-- Recent earnings --}}
        @if($recentEntries->isNotEmpty())
            <h2 class="text-lg font-bold mb-3">Recent earnings</h2>
            <div class="card divide-y divide-gray-200 dark:divide-gray-700 mb-5">
                @foreach($recentEntries as $row)
                    @php
                        $statusColors = [
                            'accrued' => ['blue', 'Pending'],
                            'seller_paid' => ['amber', 'Claimable'],
                            'agent_paid' => ['green', 'Paid out'],
                            'voided' => ['gray', 'Voided'],
                        ];
                        [$rowColor, $rowLabel] = $statusColors[$row->status] ?? ['gray', $row->status];
                    @endphp
                    <div class="flex items-center justify-between gap-3 p-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold truncate">{{ $row->lot?->title ?? 'Lot #' . $row->lot_id }}</div>
                            <div class="text-xs text-gray-500">
                                R{{ number_format($row->hammer_amount, 0) }} hammer · {{ $row->region?->name ?? '—' }}
                                · {{ $row->accrued_at->format('d M') }}
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] bg-{{ $rowColor }}-100 text-{{ $rowColor }}-800 dark:bg-{{ $rowColor }}-900/30 dark:text-{{ $rowColor }}-300 rounded uppercase tracking-wide whitespace-nowrap">{{ $rowLabel }}</span>
                        <div class="text-right shrink-0">
                            <div class="font-bold {{ $row->status === 'voided' ? 'text-gray-400 line-through' : 'text-teal-600' }}">
                                R{{ number_format($row->agent_share, 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Payout history --}}
        @if($payouts->isNotEmpty())
            <h2 class="text-lg font-bold mb-3">Payout history</h2>
            <div class="card divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($payouts as $payout)
                    @php
                        $payoutColors = [
                            'requested' => ['blue', 'Requested'],
                            'approved' => ['amber', 'Approved'],
                            'paid' => ['green', 'Paid'],
                            'rejected' => ['red', 'Rejected'],
                        ];
                        [$pColor, $pLabel] = $payoutColors[$payout->status] ?? ['gray', $payout->status];
                    @endphp
                    <div class="flex items-center justify-between gap-3 p-3 text-sm">
                        <div>
                            <div class="font-semibold">R{{ number_format($payout->amount, 2) }}</div>
                            <div class="text-xs text-gray-500">
                                Requested {{ $payout->requested_at->format('d M Y') }}
                                @if($payout->paid_at) · Paid {{ $payout->paid_at->format('d M Y') }}@endif
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] bg-{{ $pColor }}-100 text-{{ $pColor }}-800 dark:bg-{{ $pColor }}-900/30 dark:text-{{ $pColor }}-300 rounded uppercase tracking-wide">{{ $pLabel }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
