<x-app-layout>
    <x-slot name="title">{{ $region->name }} Community Auction</x-slot>
    <x-slot name="description">Join the {{ $region->name }} community auction — list items, bid on local finds, and connect with {{ number_format($memberCount) }} neighbours in your area.</x-slot>
    @if($auctioneer?->banner_image)
    <x-slot name="ogImage">{{ Storage::url($auctioneer->banner_image) }}</x-slot>
    @endif

    @php
        $phase = 'none';
        if ($auction) {
            $phase = match($auction->status) {
                'draft'    => 'draft',
                'upcoming' => 'upcoming',
                'live'     => 'live',
                'ended'    => 'ended',
                default    => 'none',
            };
        }
        $lotsPercent   = $minLots > 0 ? min(100, (int) round(($lotCount / $minLots) * 100)) : 100;
        $membersPercent = $minBidders > 0 ? min(100, (int) round(($memberCount / $minBidders) * 100)) : 100;
        $viable = $lotCount >= $minLots && $memberCount >= $minBidders;
        $goesLiveIso = $auction?->goes_live_at?->toIso8601String();
    @endphp

    {{-- ── Banner ────────────────────────────────────────────────────── --}}
    @if($auctioneer?->banner_image)
        <div class="w-full h-36 sm:h-52 overflow-hidden">
            <img src="{{ Storage::url($auctioneer->banner_image) }}" alt="" class="w-full h-full object-cover">
        </div>
    @else
        <div class="w-full h-36 sm:h-52 bg-gradient-to-br from-teal-600 via-teal-700 to-teal-900"></div>
    @endif

    {{-- ── Community header ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-4xl mx-auto px-4 pb-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">

            <div class="flex items-end gap-4">
                {{-- Logo overlapping banner --}}
                <div class="-mt-10 shrink-0">
                    @if($auctioneer?->logo)
                        <img src="{{ Storage::url($auctioneer->logo) }}"
                             alt="{{ $region->name }}"
                             class="w-20 h-20 rounded-xl object-cover border-4 border-white dark:border-gray-900 shadow-lg">
                    @else
                        <div class="w-20 h-20 rounded-xl bg-teal-600 flex items-center justify-center border-4 border-white dark:border-gray-900 shadow-lg">
                            <span class="text-3xl font-bold text-white">{{ substr($region->name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>

                <div class="pb-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('community.index') }}" class="text-xs text-teal-600 dark:text-teal-400 hover:underline">&larr; Communities</a>
                        @if($region->pilot_mode)
                            <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded">Pilot</span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $region->name }}</h1>
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        @if($region->metro_area)
                            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/></svg>
                                {{ $region->metro_area }}
                            </p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($memberCount) }}</span> members
                        </p>
                    </div>
                </div>
            </div>

            <div class="shrink-0 pb-1">
                @auth
                    @if(!$isMemberOfThisRegion)
                        <form method="POST" action="{{ route('community.join', $region) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Join this community</button>
                        </form>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 rounded-lg border border-teal-200 dark:border-teal-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            You're in
                        </span>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Log in to join</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════
             PHASE: NONE — no auction yet
        ══════════════════════════════════════════════════════════════ --}}
        @if($phase === 'none')
            <div class="card p-8 text-center">
                <div class="mx-auto w-14 h-14 rounded-full bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                @php $nextLive = $region->nextGoesLiveAt(); @endphp
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Next auction coming</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                    The next {{ $region->name }} community auction is forming.
                </p>
                <p class="text-teal-600 dark:text-teal-400 font-semibold mb-6">
                    {{ $nextLive->format('l, d M Y \a\t H:i') }}
                </p>
                @auth
                    @if(!$isMemberOfThisRegion)
                        <form method="POST" action="{{ route('community.join', $region) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Join to be notified</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">You'll be notified when listing opens.</p>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Join to be notified</a>
                @endauth
            </div>

        {{-- ══════════════════════════════════════════════════════════════
             PHASE: DRAFT — listing open, building anticipation
        ══════════════════════════════════════════════════════════════ --}}
        @elseif($phase === 'draft')

            {{-- Countdown to go-live --}}
            @if($goesLiveIso)
            <div class="card p-6 text-center" x-data="communityCountdown('{{ $goesLiveIso }}')" x-init="start()">
                <p class="text-xs uppercase tracking-widest text-teal-600 dark:text-teal-400 font-semibold mb-3">Auction goes live in</p>
                <div class="flex items-end justify-center gap-3 sm:gap-6">
                    <div class="text-center">
                        <div x-text="pad(days)" class="text-5xl sm:text-6xl font-extrabold tabular-nums text-gray-900 dark:text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">days</div>
                    </div>
                    <div class="text-4xl font-bold text-gray-300 dark:text-gray-600 pb-5">:</div>
                    <div class="text-center">
                        <div x-text="pad(hours)" class="text-5xl sm:text-6xl font-extrabold tabular-nums text-gray-900 dark:text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">hours</div>
                    </div>
                    <div class="text-4xl font-bold text-gray-300 dark:text-gray-600 pb-5">:</div>
                    <div class="text-center">
                        <div x-text="pad(minutes)" class="text-5xl sm:text-6xl font-extrabold tabular-nums text-gray-900 dark:text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">mins</div>
                    </div>
                    <div class="text-4xl font-bold text-gray-300 dark:text-gray-600 pb-5">:</div>
                    <div class="text-center">
                        <div x-text="pad(seconds)" class="text-5xl sm:text-6xl font-extrabold tabular-nums text-teal-600 dark:text-teal-400 leading-none"></div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 mt-1">secs</div>
                    </div>
                </div>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-4">
                    {{ $auction->goes_live_at->format('l, d M Y \a\t H:i') }}
                    &nbsp;&middot;&nbsp; Lineup locks {{ $auction->lineup_locks_at?->format('D d M \a\t H:i') }}
                </p>
            </div>
            @endif

            {{-- Primary CTA — join or list --}}
            @auth
                @if($isMemberOfThisRegion)
                    <div class="card p-5 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white">Got something to sell?</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">List it for your community. Collect directly from your neighbour.</p>
                        </div>
                        <a href="{{ route('community.create-lot') }}" class="btn btn-primary shrink-0">+ List your item</a>
                    </div>
                @else
                    <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white">Join your local community</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Buy and sell with your neighbours every Sunday night.</p>
                        </div>
                        <form method="POST" action="{{ route('community.join', $region) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full sm:w-auto">Join this community</button>
                        </form>
                    </div>
                @endif
            @else
                <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white">Your neighbourhood auction</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Buy and sell with your neighbours every Sunday night.</p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('register', ['community' => $region->slug, 'redirect' => route('community.region', $region)]) }}" class="btn btn-primary">Create account</a>
                        <a href="{{ route('login', ['redirect' => route('community.region', $region)]) }}" class="btn btn-outline">Log in</a>
                    </div>
                </div>
            @endauth

            {{-- Viability — urgency card --}}
            @if(!$viable)
            @php
                $combinedPct = (int) round(($lotsPercent + $membersPercent) / 2);
                if ($combinedPct < 30) {
                    $urgency = 'critical';
                    $urgencyBg    = 'bg-red-50 dark:bg-red-950/40';
                    $urgencyBorder= 'border-red-300 dark:border-red-700';
                    $urgencyIcon  = 'text-red-500';
                    $urgencyTitle = 'text-red-700 dark:text-red-300';
                    $urgencyBar   = 'bg-red-500';
                    $urgencyLabel = 'text-red-600 dark:text-red-400';
                    $headline     = 'This auction may not happen.';
                    $subline      = 'We need more lots and members before it can be confirmed. Spread the word — don\'t let your community miss out.';
                } elseif ($combinedPct < 65) {
                    $urgency = 'warning';
                    $urgencyBg    = 'bg-white dark:bg-gray-800';
                    $urgencyBorder= 'border-amber-400 dark:border-amber-500';
                    $urgencyIcon  = 'text-amber-500';
                    $urgencyTitle = 'text-gray-900 dark:text-white';
                    $urgencyBar   = 'bg-amber-500';
                    $urgencyLabel = 'text-gray-500 dark:text-gray-400';
                    $headline     = 'Help push this auction over the line.';
                    $subline      = 'You\'re making progress, but we still need a few more lots and members to confirm the auction is happening.';
                } else {
                    $urgency = 'close';
                    $urgencyBg    = 'bg-white dark:bg-gray-800';
                    $urgencyBorder= 'border-amber-300 dark:border-amber-600';
                    $urgencyIcon  = 'text-amber-400';
                    $urgencyTitle = 'text-gray-900 dark:text-white';
                    $urgencyBar   = 'bg-amber-400';
                    $urgencyLabel = 'text-gray-500 dark:text-gray-400';
                    $headline     = 'So close — just a little more!';
                    $subline      = 'Almost at the threshold. One more listing or a neighbour joining could lock this in.';
                }
            @endphp
            <div class="rounded-xl border-2 {{ $urgencyBorder }} {{ $urgencyBg }} p-5">
                {{-- Header --}}
                <div class="flex items-start gap-3 mb-4">
                    <span class="{{ $urgencyIcon }} mt-0.5 shrink-0">
                        @if($urgency === 'critical')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        @elseif($urgency === 'warning')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </span>
                    <div>
                        <p class="font-extrabold text-base {{ $urgencyTitle }} leading-snug">{{ $headline }}</p>
                        <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">{{ $subline }}</p>
                    </div>
                </div>

                {{-- Progress bars --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex justify-between items-baseline mb-1.5">
                            <span class="text-xs font-bold uppercase tracking-wide {{ $urgencyLabel }}">Lots</span>
                            <span class="text-sm font-extrabold {{ $urgencyTitle }}">{{ $lotCount }} <span class="font-normal text-gray-400 text-xs">/ {{ $minLots }}</span></span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $urgencyBar }} transition-all duration-700 {{ $urgency === 'critical' ? 'animate-pulse' : '' }}"
                                 style="width: {{ $lotsPercent }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $minLots - $lotCount }} more needed</p>
                    </div>
                    <div>
                        <div class="flex justify-between items-baseline mb-1.5">
                            <span class="text-xs font-bold uppercase tracking-wide {{ $urgencyLabel }}">Members</span>
                            <span class="text-sm font-extrabold {{ $urgencyTitle }}">{{ $memberCount }} <span class="font-normal text-gray-400 text-xs">/ {{ $minBidders }}</span></span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $urgencyBar }} transition-all duration-700 {{ $urgency === 'critical' ? 'animate-pulse' : '' }}"
                                 style="width: {{ $membersPercent }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $minBidders - $memberCount }} more needed</p>
                    </div>
                </div>
            </div>
            @else
            <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400 font-semibold px-1">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Auction confirmed &mdash; {{ $lotCount }} {{ Str::plural('lot', $lotCount) }}, {{ $memberCount }} {{ Str::plural('member', $memberCount) }}
            </div>
            @endif

            {{-- Lineup --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white">Lineup so far</h3>
                    <span class="text-xs text-gray-400">{{ $lotCount }} lot{{ $lotCount === 1 ? '' : 's' }}</span>
                </div>
                @if($lotCount > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($auction->lots as $lot)
                            <a href="{{ route('lots.show', $lot) }}" class="card p-2 hover:shadow-md transition block">
                                @if($lot->images->isNotEmpty())
                                    <img src="{{ Storage::url($lot->images->first()->thumbnail_path) }}"
                                         alt="{{ $lot->title }}"
                                         class="w-full aspect-square object-cover rounded mb-2">
                                @else
                                    <div class="w-full aspect-square bg-gray-100 dark:bg-gray-800 rounded mb-2 flex items-center justify-center text-gray-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="text-[10px] uppercase tracking-wide text-gray-400">Lot #{{ $lot->lot_number }}</div>
                                <div class="font-semibold text-sm mt-0.5 line-clamp-2 text-gray-900 dark:text-white">{{ $lot->title }}</div>
                                <div class="text-xs text-gray-400 mt-1 truncate">{{ $lot->seller?->name ?? 'Community member' }}</div>
                            </a>
                        @endforeach

                        {{-- "Be next" placeholder --}}
                        @auth
                            @if($isMemberOfThisRegion)
                                <a href="{{ route('community.create-lot') }}" class="card p-2 border-2 border-dashed border-teal-300 dark:border-teal-700 hover:border-teal-500 transition flex flex-col items-center justify-center aspect-square text-teal-500 dark:text-teal-400">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-xs font-semibold">Add yours</span>
                                </a>
                            @endif
                        @endauth
                    </div>
                @else
                    <div class="card p-10 text-center border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <p class="text-gray-400 text-sm mb-3">No lots yet. Be the first to list.</p>
                        @auth
                            @if($isMemberOfThisRegion)
                                <a href="{{ route('community.create-lot') }}" class="btn btn-primary btn-sm">+ List an item</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>

        {{-- ══════════════════════════════════════════════════════════════
             PHASE: UPCOMING — lineup locked, counting down to the event
        ══════════════════════════════════════════════════════════════ --}}
        @elseif($phase === 'upcoming')

            {{-- Big countdown hero --}}
            @if($goesLiveIso)
            <div class="card p-8 text-center bg-gradient-to-b from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 border-0"
                 x-data="communityCountdown('{{ $goesLiveIso }}')" x-init="start()">
                <p class="text-xs uppercase tracking-widest text-teal-400 font-semibold mb-6">Auction starts in</p>
                <div class="flex items-end justify-center gap-3 sm:gap-8">
                    <div class="text-center">
                        <div x-text="pad(days)" class="text-6xl sm:text-7xl font-extrabold tabular-nums text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-widest text-gray-500 mt-2">days</div>
                    </div>
                    <div class="text-5xl font-bold text-gray-600 pb-7">:</div>
                    <div class="text-center">
                        <div x-text="pad(hours)" class="text-6xl sm:text-7xl font-extrabold tabular-nums text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-widest text-gray-500 mt-2">hours</div>
                    </div>
                    <div class="text-5xl font-bold text-gray-600 pb-7">:</div>
                    <div class="text-center">
                        <div x-text="pad(minutes)" class="text-6xl sm:text-7xl font-extrabold tabular-nums text-white leading-none"></div>
                        <div class="text-xs uppercase tracking-widest text-gray-500 mt-2">mins</div>
                    </div>
                    <div class="text-5xl font-bold text-gray-600 pb-7">:</div>
                    <div class="text-center">
                        <div x-text="pad(seconds)" class="text-6xl sm:text-7xl font-extrabold tabular-nums text-teal-400 leading-none"></div>
                        <div class="text-xs uppercase tracking-widest text-gray-500 mt-2">secs</div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-6">
                    {{ $auction->goes_live_at->format('l, d M Y \a\t H:i') }}
                </p>
                <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-teal-900/40 text-teal-300 text-sm font-semibold rounded-lg border border-teal-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Lineup locked &mdash; {{ $lotCount }} lot{{ $lotCount === 1 ? '' : 's' }} confirmed
                </div>
            </div>
            @endif

            {{-- Lot preview strip --}}
            @if($lotCount > 0)
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-3">Tonight's lineup</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($auction->lots as $lot)
                            <a href="{{ route('lots.show', $lot) }}" class="card p-2 hover:shadow-md transition block">
                                @if($lot->images->isNotEmpty())
                                    <img src="{{ Storage::url($lot->images->first()->thumbnail_path) }}"
                                         alt="{{ $lot->title }}"
                                         class="w-full aspect-square object-cover rounded mb-2">
                                @else
                                    <div class="w-full aspect-square bg-gray-100 dark:bg-gray-800 rounded mb-2 flex items-center justify-center text-gray-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="text-[10px] uppercase tracking-wide text-gray-400">Lot #{{ $lot->lot_number }}</div>
                                <div class="font-semibold text-sm mt-0.5 line-clamp-2 text-gray-900 dark:text-white">{{ $lot->title }}</div>
                                <div class="text-xs text-gray-400 mt-1 truncate">{{ $lot->seller?->name ?? 'Community member' }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        {{-- ══════════════════════════════════════════════════════════════
             PHASE: LIVE — it's happening right now
        ══════════════════════════════════════════════════════════════ --}}
        @elseif($phase === 'live')

            <div class="card p-8 text-center bg-gradient-to-b from-red-950 to-gray-900 border-0">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="text-red-400 text-sm font-bold uppercase tracking-widest">Live now</span>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2">{{ $auction->title }}</h2>
                <p class="text-gray-400 text-sm mb-8">
                    {{ $region->name }} &middot; {{ $lotCount }} lots &middot; one at a time
                </p>
                <a href="{{ route('auctions.show', $auction->slug) }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-red-500 hover:bg-red-400 text-white text-lg font-bold rounded-xl transition shadow-lg shadow-red-900/40">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Enter the auction room
                </a>
            </div>

        {{-- ══════════════════════════════════════════════════════════════
             PHASE: ENDED — show results and next auction
        ══════════════════════════════════════════════════════════════ --}}
        @elseif($phase === 'ended')

            @php
                $soldCount = $auction->lots->where('status', 'sold')->count();
                $unsoldCount = $auction->lots->where('status', 'unsold')->count();
                $nextLive = $region->nextGoesLiveAt();
            @endphp

            <div class="card p-6 text-center">
                <div class="mx-auto w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Auction complete</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">{{ $auction->title }}</p>
                <div class="flex items-center justify-center gap-6 text-sm mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $soldCount }}</div>
                        <div class="text-gray-400 text-xs uppercase tracking-wide">Sold</div>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-gray-400">{{ $unsoldCount }}</div>
                        <div class="text-gray-400 text-xs uppercase tracking-wide">Unsold</div>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-gray-700 dark:text-gray-300">{{ $lotCount }}</div>
                        <div class="text-gray-400 text-xs uppercase tracking-wide">Total lots</div>
                    </div>
                </div>
                <a href="{{ route('auctions.show', $auction->slug) }}" class="btn btn-outline btn-sm mb-6">View full results</a>
                <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                    <p class="text-xs uppercase tracking-widest text-gray-400 mb-1">Next auction</p>
                    <p class="text-teal-600 dark:text-teal-400 font-semibold">{{ $nextLive->format('l, d M Y \a\t H:i') }}</p>
                    @auth
                        @if($isMemberOfThisRegion)
                            <a href="{{ route('community.create-lot') }}" class="btn btn-primary btn-sm mt-4">List for next week</a>
                        @endif
                    @endauth
                </div>
            </div>

            {{-- My lots quick link for sellers --}}
            @auth
                @if($isMemberOfThisRegion)
                    <div class="text-center">
                        <a href="{{ route('community.my-lots') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">View my listings &rarr;</a>
                    </div>
                @endif
            @endauth

        @endif

    </div>

    @push('scripts')
    <script>
    function communityCountdown(iso) {
        return {
            days: 0, hours: 0, minutes: 0, seconds: 0,
            _timer: null,
            start() {
                this.tick();
                this._timer = setInterval(() => this.tick(), 1000);
            },
            tick() {
                const diff = new Date(iso) - new Date();
                if (diff <= 0) {
                    this.days = this.hours = this.minutes = this.seconds = 0;
                    clearInterval(this._timer);
                    return;
                }
                this.days    = Math.floor(diff / 86400000);
                this.hours   = Math.floor((diff % 86400000) / 3600000);
                this.minutes = Math.floor((diff % 3600000) / 60000);
                this.seconds = Math.floor((diff % 60000) / 1000);
            },
            pad(n) { return String(n).padStart(2, '0'); }
        }
    }
    </script>
    @endpush

</x-app-layout>
