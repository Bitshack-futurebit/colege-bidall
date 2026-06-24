<x-app-layout>
    <x-slot name="title">{{ $auction->title }}</x-slot>
    <x-slot name="description">{{ $auction->title }} - {{ $auction->lots->count() }} lots by {{ $auction->auctioneer->business_name }} on {{ config('branding.name') }}.</x-slot>
    @php
        $firstImage = $auction->lots->flatMap->images->first();
        $shouldNoIndex = $auction->status === 'ended'
            && $auction->lots->sum(fn ($l) => $l->bids->count()) === 0;
    @endphp
    @if($firstImage && $firstImage->optimized_path)
        <x-slot name="ogImage">{{ asset('storage/' . $firstImage->optimized_path) }}</x-slot>
    @endif
    @if($shouldNoIndex)
        <x-slot name="noIndex">1</x-slot>
    @endif

    <!-- Auctioneer Banner Card -->
    <div class="bg-gray-900">
        @if($auction->auctioneer->banner_image)
            <div class="w-full h-24 sm:h-32 overflow-hidden">
                <img src="{{ Storage::url($auction->auctioneer->banner_image) }}"
                     alt=""
                     class="w-full h-full object-cover opacity-60">
            </div>
        @else
            <div class="w-full h-24 sm:h-32 bg-gradient-to-r from-primary-600 to-primary-800"></div>
        @endif
    </div>
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Logo -->
                <a href="{{ route('auctioneer.show', $auction->auctioneer) }}" class="flex-shrink-0 -mt-10">
                    @if($auction->auctioneer->logo)
                        <img src="{{ Storage::url($auction->auctioneer->logo) }}"
                             alt="{{ $auction->auctioneer->business_name }}"
                             class="w-14 h-14 sm:w-16 sm:h-16 rounded-lg object-cover border-2 border-gray-700 shadow-lg bg-white">
                    @else
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-primary-100 rounded-lg flex items-center justify-center border-2 border-gray-700 shadow-lg">
                            <span class="text-xl font-bold text-primary-600">{{ substr($auction->auctioneer->business_name, 0, 1) }}</span>
                        </div>
                    @endif
                </a>

                <!-- Name & Location -->
                <div class="flex-1 min-w-0">
                    <a href="{{ route('auctioneer.show', $auction->auctioneer) }}" class="text-white font-semibold text-sm sm:text-base hover:underline truncate block">
                        {{ $auction->auctioneer->business_name }}
                    </a>
                    @if($auction->auctioneer->user->city || $auction->auctioneer->user->province)
                        <p class="text-gray-400 text-xs sm:text-sm truncate">
                            {{ $auction->auctioneer->user->city }}{{ $auction->auctioneer->user->city && $auction->auctioneer->user->province ? ', ' : '' }}{{ $auction->auctioneer->user->province }}
                        </p>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" @click="$dispatch('open-rules-modal')" class="btn btn-sm bg-white/10 hover:bg-white/20 text-white border border-white/30 font-bold" title="View Auction Rules">
                        <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="hidden sm:inline">Rules</span>
                    </button>

                    <x-whatsapp-share :auction="$auction" />

                    @if(config('regional.features.whatsapp') && $auction->auctioneer->whatsapp_number)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $auction->auctioneer->whatsapp_number) }}"
                           target="_blank"
                           class="btn btn-success btn-sm"
                           title="WhatsApp">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </a>
                    @endif
                    @auth
                        @if(auth()->user()->role !== 'auctioneer' || auth()->user()->auctioneer->id !== $auction->auctioneer->id)
                            <form method="POST" action="{{ route('auctioneer.follow.toggle', $auction->auctioneer) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn {{ auth()->user()->isFollowingAuctioneer($auction->auctioneer->id) ? 'btn-primary' : 'bg-primary-600 hover:bg-primary-700 text-white border border-primary-600' }} btn-sm font-bold">
                                    <svg class="w-4 h-4 sm:mr-1" fill="{{ auth()->user()->isFollowingAuctioneer($auction->auctioneer->id) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    <span class="hidden sm:inline">{{ auth()->user()->isFollowingAuctioneer($auction->auctioneer->id) ? 'Following' : 'Follow' }}</span>
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Auction Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $auction->title }}</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($auction->isDutch())
                            <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Dutch Auction</span>
                        @elseif($auction->isSealed())
                            <span class="badge bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Sealed Auction</span>
                        @elseif($auction->isLiveFormat())
                            <span class="badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Live Auction</span>
                        @endif
                        @if($auction->status === 'live')
                            <span class="badge badge-danger animate-pulse">Live Now</span>
                        @elseif($auction->status === 'upcoming')
                            <span class="badge badge-info">Upcoming</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst($auction->status) }}</span>
                        @endif
                    </div>
                </div>

                @if($auction->isDutch())
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>Dutch Auction:</strong> Prices start high and drop over time. Each lot has its own drop rate.
                            First to bid wins! Click "Bid Now" when the price is right for you.
                        </p>
                    </div>
                @elseif($auction->isSealed())
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-purple-800 dark:text-purple-200">
                            <strong>Sealed Auction ({{ ucfirst($auction->sealed_mode) }} Bid Wins):</strong>
                            Bids are secret — no one can see other bids until the auction ends.
                            @if($auction->isSealedHighest())
                                Place the highest bid to win.
                            @else
                                Place the lowest bid to win.
                            @endif
                            Each lot requires at least 2 bidders.
                        </p>
                    </div>
                @elseif($auction->isLiveFormat())
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-red-800 dark:text-red-200">
                            @if($auction->is_community)
                                <strong>Community Auction:</strong> Lots run one at a time with a real auctioneer's cadence — a quick presentation, a "Who'll open?" call, then live bidding. Once bids stop, the auctioneer goes "going once… going twice… sold." Each lot needs 2 bidders to be valid — single-bidder lots go unsold. No reserve: the seller can confirm or decline the winning bid within 24 hours, with penalties for repeat declines.
                            @else
                                <strong>Live Auction:</strong> Lots run one at a time with a real auctioneer's cadence — a quick presentation, a "Who'll open?" call, then live bidding. Once bids stop, the auctioneer goes "going once… going twice… sold." Each lot needs 2 bidders to be valid (single bidder over reserve also wins).
                            @endif
                        </p>
                    </div>
                @endif

                @if($auction->description)
                    <div class="prose dark:prose-invert mb-6">
                        {{ $auction->description }}
                    </div>
                @endif

                <!-- Auction Details -->
                @php
                    // Live-format auctions (community + auctioneer-run live) don't have a
                    // meaningful auction-level end time — they end when the last lot closes.
                    // Hide the End Time card and shrink the grid for those.
                    $showEndTime = !$auction->isLiveFormat();
                @endphp
                <div class="grid grid-cols-1 @if($showEndTime) md:grid-cols-3 @else md:grid-cols-2 @endif gap-4 mb-6">
                    <div class="card">
                        <div class="p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Start Time</div>
                            <div class="font-semibold">{{ $auction->start_time->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                    @if($showEndTime)
                        <div class="card">
                            <div class="p-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">End Time</div>
                                <div class="font-semibold">{{ $auction->end_time ? $auction->end_time->format('M d, Y H:i') : 'Auto-calculated' }}</div>
                            </div>
                        </div>
                    @endif
                    <div class="card">
                        <div class="p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Lots</div>
                            <div class="font-semibold">{{ $auction->lots->count() }}</div>
                        </div>
                    </div>
                </div>

                @if($auction->requiresDeposit())
                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                        <p class="text-yellow-800 dark:text-yellow-200">
                            <strong>Deposit Required:</strong> {{ formatCurrency($auction->deposit_amount) }} deposit required to bid on this auction.
                        </p>
                    </div>
                @endif

                <!-- Registration Status -->
                @if($auction->requiresRegistration())
                    @auth
                        @if(!$isRegistered)
                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-blue-800 dark:text-blue-200">Register for this auction to start bidding</p>
                                    @php $blinkAvailable = !empty(config('services.blink.api_key')) && !empty(config('services.blink.wallet_id')); @endphp
                                    <form method="POST" action="{{ route('auctions.register', $auction) }}" class="flex flex-col items-end gap-2">
                                        @csrf
                                        @if($auction->requiresDeposit() && $blinkAvailable)
                                            <div class="grid grid-cols-2 gap-2 w-full">
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="payment_method" value="payfast" class="peer sr-only" checked>
                                                    <div class="rounded-lg border border-blue-300 dark:border-blue-700 px-3 py-1.5 text-center text-xs font-semibold text-blue-800 dark:text-blue-200 peer-checked:bg-primary-600 peer-checked:text-white peer-checked:border-primary-600 transition">
                                                        Card
                                                    </div>
                                                </label>
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="payment_method" value="blink" class="peer sr-only">
                                                    <div class="rounded-lg border border-blue-300 dark:border-blue-700 px-3 py-1.5 text-center text-xs font-semibold text-blue-800 dark:text-blue-200 peer-checked:bg-primary-600 peer-checked:text-white peer-checked:border-primary-600 transition">
                                                        Bitcoin
                                                    </div>
                                                </label>
                                            </div>
                                        @endif
                                        <button type="submit" class="btn btn-primary whitespace-nowrap">
                                            Register Now
                                            @if($auction->requiresDeposit())
                                                ({{ formatCurrency($auction->deposit_amount) }} deposit)
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
                                <p class="text-green-800 dark:text-green-200 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    You are registered for this auction
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <p class="text-blue-800 dark:text-blue-200">Login to register and bid on this auction</p>
                                <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                            </div>
                        </div>
                    @endauth
                @else
                    @guest
                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <p class="text-blue-800 dark:text-blue-200">Login to bid on this auction</p>
                                <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                            </div>
                        </div>
                    @endguest
                @endif
            </div>

            <!-- Search and Filter -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Lots</h2>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <input type="text"
                           placeholder="Search lots..."
                           class="input w-full sm:w-auto"
                           onkeyup="filterLots(this.value)">
                    <select onchange="window.location.href='?sort='+this.value" class="input w-full sm:w-auto">
                        <option value="lot_number" {{ request('sort') == 'lot_number' ? 'selected' : '' }}>Lot Number</option>
                        <option value="ending_soon" {{ request('sort') == 'ending_soon' ? 'selected' : '' }}>Ending Soon</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Lots Grid -->
            @php
                if (!isset($showWatchlistButton)) {
                    $showWatchlistButton = auth()->check() && auth()->user()->isBidder();
                    $watchlistedLotIds = $showWatchlistButton
                        ? auth()->user()->watchlist()->whereIn('lot_id', $auction->lots->pluck('id'))->pluck('lot_id')->toArray()
                        : [];
                }
                // Watchlist disabled for Live (sequential cadence — lots move too fast)
                if ($auction->isLiveFormat()) {
                    $showWatchlistButton = false;
                    $watchlistedLotIds = [];
                }
            @endphp
            <!-- BIDALL-WL:{{ ($showWatchlistButton ?? false) ? 'on' : 'off' }} auth:{{ auth()->check() ? auth()->user()->role : 'guest' }} -->

            {{-- Auction Results Summary (ended auctions) --}}
            @if($auction->status === 'ended' && $auction->lots->count() > 0)
                @php
                    $soldLots = $auction->lots->whereIn('status', ['sold', 'pending_confirmation']);
                    $pendingLots = $auction->lots->where('status', 'pending_confirmation');
                    $unsoldLots = $auction->lots->where('status', 'unsold');
                    $totalRevenue = $soldLots->sum('current_bid');
                    $myWins = auth()->check()
                        ? $auction->lots->filter(fn($l) => $l->winning_bidder_id === auth()->id() && in_array($l->status, ['sold','pending_confirmation']))
                        : collect();
                @endphp
                <div class="card p-5 mb-6 border-2 border-gray-300 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Auction Results</h2>
                        <span class="badge bg-gray-500 text-white">Ended</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $auction->lots->count() }}</div>
                            <div class="text-xs uppercase text-gray-500">Total Lots</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $soldLots->count() }}</div>
                            <div class="text-xs uppercase text-gray-500">Sold{{ $pendingLots->count() > 0 ? ' (' . $pendingLots->count() . ' pending)' : '' }}</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="text-2xl font-bold text-gray-500">{{ $unsoldLots->count() }}</div>
                            <div class="text-xs uppercase text-gray-500">Unsold</div>
                        </div>
                        <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ formatCurrency($totalRevenue) }}</div>
                            <div class="text-xs uppercase text-gray-500">Hammer Total</div>
                        </div>
                    </div>

                    @auth
                        @if($myWins->count() > 0)
                            <div class="mt-4 p-3 rounded bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700">
                                <div class="font-bold text-green-800 dark:text-green-200 mb-1">
                                    You won {{ $myWins->count() }} {{ Str::plural('lot', $myWins->count()) }}
                                </div>
                                <ul class="text-sm text-green-700 dark:text-green-300 space-y-0.5">
                                    @foreach($myWins as $win)
                                        <li>
                                            <a href="{{ route('lots.show', $win) }}" class="hover:underline">Lot #{{ $win->lot_number }} — {{ $win->title }}</a>
                                            <span class="font-semibold">{{ formatCurrency($win->current_bid) }}</span>
                                            @if($win->status === 'pending_confirmation')
                                                <span class="text-xs text-amber-700 dark:text-amber-400">(awaiting seller confirmation)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endauth
                </div>
            @endif

            {{-- Pre-live countdown banner — appears in the last hour before go-live for upcoming auctions --}}
            @if($auction->status === 'upcoming' && $auction->start_time && $auction->start_time->diffInMinutes(now(), false) >= -60 && $auction->start_time->isFuture())
                <div class="rounded-xl p-6 mb-6 text-center text-white shadow-lg
                    {{ $auction->is_community ? 'bg-gradient-to-r from-teal-600 to-teal-800' : 'bg-gradient-to-r from-red-600 to-red-800' }}"
                     x-data="auctionStartsCountdown('{{ $auction->start_time->toIso8601String() }}')"
                     x-init="init()">
                    <div class="text-xs uppercase tracking-widest opacity-80 mb-1">
                        {{ $auction->is_community ? 'Community auction starts in' : 'Auction starts in' }}
                    </div>
                    <div class="text-5xl md:text-6xl font-bold tabular-nums mb-2" x-text="label">—</div>
                    <div class="text-sm opacity-90">
                        Goes live at <strong>{{ $auction->start_time->format('H:i') }}</strong> on {{ $auction->start_time->format('D, d M') }}
                    </div>
                </div>
            @endif

            @if($auction->lots->count() > 0)
                {{-- Sequential Live: featured current lot + queue --}}
                @if($auction->isLiveFormat() && $auction->status === 'live')
                    @php
                        $activeLiveLot = $auction->lots->firstWhere('status', 'live');
                        $pendingLiveLots = $auction->lots->whereIn('status', ['draft', 'pending'])->sortBy('lot_number');
                        $completedLiveLots = $auction->lots->whereIn('status', ['sold', 'unsold', 'pending_confirmation'])->sortByDesc('lot_number');
                    @endphp

                    @if($activeLiveLot)
                        {{-- Auction-level watcher: reload when the active lot changes --}}
                        <div x-data="{
                            currentActiveLotId: {{ $activeLiveLot->id }},
                            init() {
                                this.timer = setInterval(async () => {
                                    try {
                                        const res = await fetch('/api/auctions/{{ $auction->id }}/status', { headers: { 'Accept': 'application/json' } });
                                        if (!res.ok) return;
                                        const payload = await res.json();
                                        const data = payload.data || {};
                                        const live = (data.lots || []).find(l => l.status === 'live');
                                        if (live && live.id !== this.currentActiveLotId) {
                                            window.location.reload();
                                        } else if (!live && data.auction && data.auction.status === 'ended') {
                                            window.location.reload();
                                        }
                                    } catch (e) {}
                                }, 5000);
                            }
                        }" x-init="init()"></div>

                        @if($activeLiveLot->live_phase === \App\Models\Lot::LIVE_PHASE_OPENING)
                            {{-- Community auction warm-up: big bold 5-min countdown before the first lot's PRESENTING phase --}}
                            <div class="mb-8 card border-2 border-amber-400 dark:border-amber-600 overflow-hidden"
                                 x-data="{
                                     endsAt: @js($activeLiveLot->live_phase_ends_at?->toIso8601String()),
                                     remaining: '00:00',
                                     done: false,
                                     init() {
                                         this.tick();
                                         this.timer = setInterval(() => this.tick(), 1000);
                                     },
                                     tick() {
                                         const ms = new Date(this.endsAt).getTime() - Date.now();
                                         if (ms <= 0) {
                                             this.remaining = '00:00';
                                             if (!this.done) {
                                                 this.done = true;
                                                 setTimeout(() => window.location.reload(), 1500);
                                             }
                                             return;
                                         }
                                         const total = Math.ceil(ms / 1000);
                                         const m = Math.floor(total / 60);
                                         const s = total % 60;
                                         this.remaining = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                     }
                                 }"
                                 x-init="init()">
                                <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 px-6 py-12 sm:py-16 text-center">
                                    <div class="text-sm sm:text-base uppercase tracking-widest text-amber-700 dark:text-amber-300 font-semibold mb-3">
                                        Auction Starting In
                                    </div>
                                    <div class="text-7xl sm:text-9xl font-black text-amber-600 dark:text-amber-400 tabular-nums leading-none mb-4"
                                         x-text="remaining">05:00</div>
                                    <div class="text-sm sm:text-base text-amber-700 dark:text-amber-300 max-w-md mx-auto">
                                        The first lot will be presented when the countdown reaches zero. Get comfortable.
                                    </div>
                                </div>
                            </div>
                        @else
                        <div class="mb-8 card border-2 border-red-500 overflow-hidden"
                             x-data="livePulse({
                                lotId: {{ $activeLiveLot->id }},
                                currentBid: {{ $activeLiveLot->current_bid ?? $activeLiveLot->starting_bid ?? 0 }},
                                startingBid: {{ $activeLiveLot->starting_bid ?? 0 }},
                                increment: {{ $activeLiveLot->increment }},
                                totalBids: {{ $activeLiveLot->total_bids }},
                                hasTopBid: {{ auth()->check() && $activeLiveLot->winning_bidder_id === auth()->id() ? 'true' : 'false' }},
                                hasReserve: {{ $activeLiveLot->hasReserve() ? 'true' : 'false' }},
                                reserveMet: {{ $activeLiveLot->isReserveMet() ? 'true' : 'false' }},
                                proxyMax: null,
                                proxyEnabled: {{ $auction->allow_proxy_bidding ? 'true' : 'false' }},
                                phase: @js($activeLiveLot->live_phase ?? 'presenting'),
                                phaseEndsAt: @js($activeLiveLot->live_phase_ends_at?->toIso8601String()),
                                liveOpensAt: @js($activeLiveLot->live_opens_at?->toIso8601String()),
                                distinctBidders: {{ $activeLiveLot->distinctLiveBidderCount() }},
                                acceptsBids: {{ $activeLiveLot->acceptsLiveBids() ? 'true' : 'false' }},
                                isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
                                status: @js($activeLiveLot->status),
                                userId: {{ auth()->id() ?? 0 }},
                                winningBidderId: {{ $activeLiveLot->winning_bidder_id ?? 'null' }},
                                userHasBid: {{ $activeLiveLotUserHasBid ? 'true' : 'false' }},
                                isCommunity: {{ $auction->isCommunity() ? 'true' : 'false' }},
                                declinedAt: @js($activeLiveLot->declined_at?->toIso8601String()),
                             })"
                             x-init="init()">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100 px-5 pt-5 mb-3 flex items-center gap-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                                <span>On the block now</span>
                            </h3>

                            <div class="md:flex">
                                <div class="md:w-1/2 relative bg-gray-100 dark:bg-gray-800">
                                    @if($activeLiveLot->images->count() > 0)
                                        <img src="{{ $activeLiveLot->images->first()->optimized_url }}"
                                             alt="{{ $activeLiveLot->title }}"
                                             class="w-full h-64 sm:h-80 md:h-96 object-contain">
                                    @endif
                                </div>
                                <div class="md:w-1/2 p-5 sm:p-6 flex flex-col">
                                    {{-- Phase banner --}}
                                    <div class="mb-4 rounded-xl px-4 py-4 text-center font-black tracking-tight uppercase transition-colors relative"
                                         :class="{
                                            'bg-blue-600 text-white': phase === 'presenting',
                                            'bg-gray-600 text-white': phase === 'intermission',
                                            'bg-teal-600 text-white': phase === 'open_call',
                                            'bg-green-600 text-white': phase === 'active',
                                            'bg-amber-500 text-white animate-pulse': phase === 'going_once',
                                            'bg-orange-600 text-white animate-pulse': phase === 'going_twice',
                                            'bg-red-700 text-white': phase === 'closed',
                                         }">
                                        <button @click.stop="toggleSfx()" :title="sfxEnabled ? 'Mute sound' : 'Unmute sound'" class="absolute top-2 right-2 p-1 rounded hover:bg-black/20">
                                            <svg x-show="sfxEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M9 9H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V5L9 9z"/></svg>
                                            <svg x-show="!sfxEnabled" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V5L9 9zM17 9l6 6m0-6l-6 6"/></svg>
                                        </button>
                                        <div class="text-2xl sm:text-3xl md:text-4xl leading-none" x-text="phaseLabel()"></div>
                                        <div class="text-base sm:text-lg mt-2 font-mono" x-show="showPhaseCountdown()" x-cloak x-text="phaseCountdownLabel()"></div>
                                    </div>

                                    <div class="text-sm text-gray-500 dark:text-gray-400">Lot #{{ $activeLiveLot->lot_number }}</div>
                                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                        <a href="{{ route('lots.show', $activeLiveLot) }}" class="hover:text-red-600">{{ $activeLiveLot->title }}</a>
                                    </h2>

                                    {{-- Prominent "You're winning" banner --}}
                                    <div x-show="hasTopBid && totalBids > 0 && phase !== 'closed'" x-cloak
                                         class="mb-3 p-3 rounded-lg border-2 border-green-500 bg-green-50 dark:bg-green-900/30 flex items-center gap-2 animate-pulse">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1z"/>
                                        </svg>
                                        <div>
                                            <div class="text-sm font-bold text-green-800 dark:text-green-200 leading-tight">You have the highest bid</div>
                                            <div class="text-xs text-green-700 dark:text-green-300">No need to re-bid unless outbid.</div>
                                        </div>
                                    </div>

                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1" x-text="totalBids > 0 ? 'Current Bid' : 'Starting Bid'"></div>
                                    <div class="text-3xl sm:text-4xl font-bold mb-4 transition-colors"
                                         :class="hasTopBid && totalBids > 0 && phase !== 'closed' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                         x-text="formatCurrency(currentBid)"></div>

                                    @if($activeLiveLot->hasReserve())
                                        <div class="mb-3 p-2 rounded-lg border-2 text-center text-sm font-semibold"
                                             :class="reserveMet ? 'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-200' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 text-yellow-800 dark:text-yellow-200'"
                                             x-text="reserveMet ? 'Reserve met' : 'Reserve not met'"></div>
                                    @endif

                                    <div class="mb-4 p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm flex items-center justify-between">
                                        <span class="text-gray-700 dark:text-gray-300">Distinct bidders</span>
                                        <span class="font-bold" :class="distinctBidders >= 2 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'" x-text="distinctBidders + ' / 2'"></span>
                                    </div>

                                    @auth
                                        @if($activeLiveLot->isOwnedBy(auth()->user()))
                                            <div class="mt-auto">
                                                <x-owner-bid-notice />
                                            </div>
                                        @else
                                        <div x-show="bidFeedback" x-cloak class="mb-3 p-2 rounded-lg border-2 text-sm font-medium"
                                             :class="bidError ? 'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200' : 'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-200'"
                                             x-text="bidFeedback"></div>

                                        <div class="mt-auto space-y-3">
                                            <div>
                                                <label class="label">Your bid</label>
                                                <input type="number"
                                                       step="0.01"
                                                       :min="minimumBid"
                                                       :value="minimumBid"
                                                       x-model.number="bidAmount"
                                                       :disabled="!acceptsBids"
                                                       class="input text-lg">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="'Minimum bid: ' + formatCurrency(minimumBid)"></p>
                                            </div>

                                            <button @click="placeLiveBid()"
                                                    :disabled="submitting || !acceptsBids || phase === 'closed'"
                                                    class="btn btn-primary w-full py-4 text-lg font-bold"
                                                    :class="{
                                                        'bg-gray-500 hover:bg-gray-600': acceptsBids && hasTopBid && totalBids > 0,
                                                        'bg-red-600 hover:bg-red-700': acceptsBids && !(hasTopBid && totalBids > 0),
                                                        'opacity-50 cursor-not-allowed': !acceptsBids || submitting,
                                                    }">
                                                <span x-show="!submitting" x-text="bidButtonLabel()"></span>
                                                <span x-show="submitting" x-cloak>Placing bid…</span>
                                            </button>

                                            @if($auction->isCommunity())
                                                <div class="grid grid-cols-3 gap-2">
                                                    <button @click="placeLiveBid(1)"
                                                            :disabled="submitting || !acceptsBids || phase === 'closed'"
                                                            class="btn btn-outline text-sm py-2"
                                                            :class="{ 'opacity-50 cursor-not-allowed': !acceptsBids || submitting }">
                                                        <div class="text-[10px] uppercase text-gray-500">Next</div>
                                                        <div class="font-bold" x-text="formatCurrency(bidForNSteps(1))"></div>
                                                    </button>
                                                    <button @click="placeLiveBid(2)"
                                                            :disabled="submitting || !acceptsBids || phase === 'closed'"
                                                            class="btn btn-outline text-sm py-2"
                                                            :class="{ 'opacity-50 cursor-not-allowed': !acceptsBids || submitting }">
                                                        <div class="text-[10px] uppercase text-gray-500">+2 steps</div>
                                                        <div class="font-bold" x-text="formatCurrency(bidForNSteps(2))"></div>
                                                    </button>
                                                    <button @click="placeLiveBid(5)"
                                                            :disabled="submitting || !acceptsBids || phase === 'closed'"
                                                            class="btn btn-outline text-sm py-2"
                                                            :class="{ 'opacity-50 cursor-not-allowed': !acceptsBids || submitting }">
                                                        <div class="text-[10px] uppercase text-gray-500">+5 steps</div>
                                                        <div class="font-bold" x-text="formatCurrency(bidForNSteps(5))"></div>
                                                    </button>
                                                </div>
                                            @endif

                                            <a href="{{ route('lots.show', $activeLiveLot) }}" class="text-xs text-center text-gray-600 dark:text-gray-400 hover:text-red-600 block">View full lot details →</a>
                                        </div>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary w-full py-3 mt-auto">Login to bid</a>
                                    @endauth
                                </div>
                            </div>

                            {{-- Sticky mobile bid bar --}}
                            @auth
                            @if(!$activeLiveLot->isOwnedBy(auth()->user()))
                                <div x-show="phase !== 'closed'"
                                     class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t-2 shadow-2xl z-40 px-3 py-2 transition-colors"
                                     :class="hasTopBid && totalBids > 0 ? 'border-green-500' : 'border-red-500'"
                                     style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));">
                                    <div x-show="hasTopBid && totalBids > 0" x-cloak class="text-center text-xs font-bold text-green-700 dark:text-green-400 mb-1">
                                        ✓ You have the highest bid
                                    </div>
                                    <button @click="placeLiveBid()"
                                            :disabled="submitting || !acceptsBids"
                                            class="w-full rounded-lg font-bold text-base py-3 transition-colors"
                                            :class="{
                                                'bg-gray-500 hover:bg-gray-600 text-white': acceptsBids && hasTopBid && totalBids > 0,
                                                'bg-red-600 hover:bg-red-700 text-white': acceptsBids && !(hasTopBid && totalBids > 0),
                                                'bg-gray-300 text-gray-500': !acceptsBids,
                                            }"
                                            style="min-height: 52px;">
                                        <span x-show="!submitting" x-text="bidButtonLabel()"></span>
                                        <span x-show="submitting" x-cloak>Placing…</span>
                                    </button>
                                </div>
                            @endif
                            @endauth
                        </div>
                        @endif {{-- /opening-vs-live-pulse --}}
                    @endif

                    @if($pendingLiveLots->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Coming up ({{ $pendingLiveLots->count() }})</h3>
                            <div class="flex gap-2 overflow-x-auto pb-2 -mx-2 px-2">
                                @foreach($pendingLiveLots->take(8) as $upLot)
                                    <div class="flex-shrink-0 w-32 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        @if($upLot->images->count() > 0)
                                            <img src="{{ $upLot->images->first()->optimized_url }}" alt="" class="w-full h-24 object-cover">
                                        @endif
                                        <div class="p-2">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Lot #{{ $upLot->lot_number }}</div>
                                            <div class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">{{ $upLot->title }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($completedLiveLots->count() > 0)
                        <details class="mb-8">
                            <summary class="cursor-pointer text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
                                Completed ({{ $completedLiveLots->count() }})
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                                @foreach($completedLiveLots as $cLot)
                                    <a href="{{ route('lots.show', $cLot) }}" class="card opacity-80 hover:opacity-100 transition-opacity">
                                        @if($cLot->images->count() > 0)
                                            <img src="{{ $cLot->images->first()->thumbnail_url }}" alt="{{ $cLot->title }}" class="w-full h-40 object-contain rounded-t-lg bg-gray-100 dark:bg-gray-800">
                                        @endif
                                        <div class="p-3">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs text-gray-500">Lot #{{ $cLot->lot_number }}</span>
                                                <span class="badge {{ $cLot->status === 'sold' ? 'badge-secondary' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">{{ ucfirst($cLot->status) }}</span>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1 line-clamp-1">{{ $cLot->title }}</div>
                                            @if($cLot->status === 'sold')
                                                <div class="text-xs text-green-600">Sold at {{ formatCurrency($cLot->current_bid) }}</div>
                                            @else
                                                <div class="text-xs text-gray-500">Unsold</div>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endif

                {{-- Sequential Dutch: featured layout --}}
                @if($auction->isDutch() && $auction->status === 'live')
                    @php
                        $activeLot = $auction->lots->firstWhere('status', 'live');
                        $pendingLots = $auction->lots->where('status', 'draft')->sortBy('lot_number');
                        $completedLots = $auction->lots->whereIn('status', ['sold', 'unsold', 'pending_confirmation'])->sortByDesc('lot_number');
                        $upNextLots = $pendingLots->take(3);
                    @endphp

                    {{-- Currently Active Lot (or next lot during gap) --}}
                    @if(!$activeLot && $pendingLots->count() > 0)
                        @php $nextUpLot = $pendingLots->first(); @endphp
                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                                <span class="w-3 h-3 bg-amber-500 rounded-full animate-pulse"></span> Up Next
                            </h3>
                            <div class="card border-2 border-amber-300 dark:border-amber-700" data-lot-id="{{ $nextUpLot->id }}" data-lot-status="{{ $nextUpLot->status }}">
                                <div class="md:flex">
                                    @if($nextUpLot->images->count() > 0)
                                        <div class="md:w-1/2 relative bg-gray-100 dark:bg-gray-800" x-data="{ currentIndex: 0, total: {{ $nextUpLot->images->count() }} }">
                                                @foreach($nextUpLot->images as $index => $image)
                                                    <img src="{{ $index === 0 ? $image->optimized_url : '' }}"
                                                         {!! $index > 0 ? 'x-bind:src="currentIndex === ' . $index . ' ? \'' . $image->optimized_url . '\' : \'\'"' : '' !!}
                                                         alt="{{ $nextUpLot->title }}"
                                                         class="w-full h-72 md:h-80 object-contain"
                                                         x-show="currentIndex === {{ $index }}" x-cloak>
                                                @endforeach
                                            @if($nextUpLot->images->count() > 1)
                                                <button @click.stop="currentIndex = (currentIndex - 1 + total) % total"
                                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                                </button>
                                                <button @click.stop="currentIndex = (currentIndex + 1) % total"
                                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="p-6 md:w-1/2 flex flex-col justify-between">
                                        <div>
                                            <span class="text-sm text-gray-500">Lot #{{ $nextUpLot->lot_number }}</span>
                                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $nextUpLot->title }}</h2>
                                            @if($nextUpLot->description)
                                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">{{ $nextUpLot->description }}</p>
                                            @endif
                                            <div class="text-lg text-gray-700 dark:text-gray-300 mb-2">Starts at <span class="font-bold text-amber-600">{{ formatCurrency($nextUpLot->dutch_start_price) }}</span></div>
                                        </div>
                                        @if($nextUpLot->dutch_start_time)
                                            <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 text-center"
                                                 x-data="{ countdown: '', started: false }"
                                                 x-init="setInterval(() => { const d = Math.max(0, Math.floor((new Date('{{ $nextUpLot->dutch_start_time->toIso8601String() }}').getTime() - Date.now()) / 1000)); if (d <= 0) { countdown = 'Starting now...'; if (!started) { started = true; setTimeout(() => window.location.reload(), 2000); } } else { const m = Math.floor(d / 60); const s = d % 60; countdown = (m > 0 ? m + 'm ' : '') + s + 's'; } }, 1000)">
                                                <div class="text-sm text-amber-700 dark:text-amber-300 font-medium mb-1">Bidding starts in</div>
                                                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400" x-text="countdown">—</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($activeLot)
                        @php $inCountdown = $activeLot->isInDutchCountdown(); @endphp
                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                                <span class="w-3 h-3 {{ $inCountdown ? 'bg-amber-500' : 'bg-red-500' }} rounded-full animate-pulse"></span>
                                {{ $inCountdown ? 'Get Ready' : 'Now Live' }}
                            </h3>
                            <div class="card border-2 {{ $inCountdown ? 'border-amber-300 dark:border-amber-700' : 'border-amber-400 dark:border-amber-600' }}" data-lot-id="{{ $activeLot->id }}" data-lot-status="{{ $activeLot->status }}"
                                 x-data="{ currentBid: {{ $activeLot->current_bid ?? $activeLot->starting_bid ?? 0 }}, totalBids: {{ $activeLot->total_bids }} }">
                                <div class="md:flex">
                                    @if($activeLot->images->count() > 0)
                                        <div class="md:w-1/2 relative bg-gray-100 dark:bg-gray-800" x-data="{ currentIndex: 0, total: {{ $activeLot->images->count() }} }">
                                            @foreach($activeLot->images as $index => $image)
                                                <img src="{{ $index === 0 ? $image->optimized_url : '' }}"
                                                     {!! $index > 0 ? 'x-bind:src="currentIndex === ' . $index . ' ? \'' . $image->optimized_url . '\' : \'\'"' : '' !!}
                                                     alt="{{ $activeLot->title }}"
                                                     class="w-full h-72 md:h-80 object-contain"
                                                     x-show="currentIndex === {{ $index }}" x-cloak>
                                            @endforeach
                                            @if($activeLot->images->count() > 1)
                                                <button @click.stop="currentIndex = (currentIndex - 1 + total) % total"
                                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                                </button>
                                                <button @click.stop="currentIndex = (currentIndex + 1) % total"
                                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="p-6 md:w-1/2 flex flex-col justify-between">
                                        <div>
                                            <span class="text-sm text-gray-500">Lot #{{ $activeLot->lot_number }}</span>
                                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $activeLot->title }}</h2>
                                            @if($activeLot->description)
                                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">{{ $activeLot->description }}</p>
                                            @endif
                                            <div x-data="{ ...dutchPrice({{ $activeLot->id }}, {{ $activeLot->dutch_start_price }}, {{ $activeLot->dutch_floor_price }}, {{ $activeLot->dutch_drop_amount }}, {{ $activeLot->dutch_drop_interval }}, '{{ $activeLot->dutch_start_time ? $activeLot->dutch_start_time->toIso8601String() : ($auction->start_time ? $auction->start_time->toIso8601String() : '') }}', {{ $activeLot->dutch_end_time ? $activeLot->dutch_end_time->timestamp : ($activeLot->end_time ? $activeLot->end_time->timestamp : 0) }}, {{ $activeLot->quantity }}, {{ $activeLot->quantity_sold }}, '{{ $activeLot->dutch_drop_strategy ?: 'constant' }}'), ...dutchBuyInline({{ $activeLot->id }}) }" x-init="start()">

                                                {{-- GET READY countdown --}}
                                                <div x-show="inCountdown" class="text-center">
                                                    <div class="text-lg text-amber-700 dark:text-amber-300 font-bold mb-2 uppercase tracking-wide">Get Ready!</div>
                                                    <div class="text-6xl font-bold text-amber-600 dark:text-amber-400 mb-3 tabular-nums" x-text="countdownSeconds">--</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">Price drops start at <span class="font-semibold text-amber-600">{{ formatCurrency($activeLot->dutch_start_price) }}</span></div>
                                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Floor price: {{ formatCurrency($activeLot->dutch_floor_price) }}</div>
                                                </div>

                                                {{-- Active price drops --}}
                                                <div x-show="!inCountdown">
                                                    <div class="text-sm text-gray-500 mb-1">Current Price</div>
                                                    <div class="text-4xl font-bold text-amber-600 dark:text-amber-400 mb-2" x-text="formattedPrice">{{ formatCurrency($activeLot->getCurrentDutchPrice()) }}</div>
                                                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-2">
                                                        <span x-show="nextDropIn > 0 && !atFloor">Next drop in <span class="font-semibold text-amber-600" x-text="nextDropIn + 's'"></span></span>
                                                        <span x-show="atFloor && floorCountdown > 0" class="text-red-600 font-bold" x-text="'Last chance! ' + floorCountdown + 's remaining'"></span>
                                                        <span x-show="atFloor && floorCountdown <= 0" class="text-red-600 font-bold">Lot closing...</span>
                                                    </div>
                                                    @if($activeLot->quantity > 1)
                                                        <div class="text-sm text-gray-500"><span x-text="remaining">{{ $activeLot->quantityRemaining() }}</span> of {{ $activeLot->quantity }} remaining</div>
                                                    @endif
                                                    <div class="mt-4">
                                                        <div x-show="!bought && !(atFloor && floorCountdown <= 0)" class="flex gap-2">
                                                            @auth
                                                                @if($activeLot->isOwnedBy(auth()->user()))
                                                                    <span class="btn flex-1 text-center text-lg py-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700">Your lot — no bidding</span>
                                                                @elseif($auction->requiresRegistration() && !$isRegistered)
                                                                    <span class="btn btn-accent flex-1 text-center text-lg py-3 opacity-75">Registration Required</span>
                                                                @else
                                                                    <button @click="buy()" :disabled="buying" class="btn btn-accent flex-1 text-lg py-3" :class="atFloor && floorCountdown > 0 ? 'animate-pulse bg-red-600 hover:bg-red-700 border-red-600' : ''">
                                                                        <span x-show="!buying && !(atFloor && floorCountdown > 0)">Bid Now</span>
                                                                        <span x-show="!buying && atFloor && floorCountdown > 0">Last Chance — Bid Now!</span>
                                                                        <span x-show="buying">Bidding...</span>
                                                                    </button>
                                                                @endif
                                                            @else
                                                                <a href="{{ route('login') }}" class="btn btn-accent flex-1 text-center text-lg py-3">Login to Bid</a>
                                                            @endauth
                                                        </div>
                                                        <div x-show="atFloor && floorCountdown <= 0 && !bought" class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-3 text-center">
                                                            <div class="text-red-700 dark:text-red-300 font-bold">Too late! This lot has closed.</div>
                                                        </div>
                                                        <div x-show="bought" class="bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-lg p-3 text-center">
                                                            <div class="text-green-700 dark:text-green-300 font-semibold" x-text="message"></div>
                                                        </div>
                                                        <div x-show="error" class="mt-2 text-red-600 dark:text-red-400 text-sm text-center" x-text="error"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Up Next (skip first pending lot if it's already featured above) --}}
                    @php $upNextLots = !$activeLot && $pendingLots->count() > 0 ? $pendingLots->slice(1)->take(3) : $upNextLots; @endphp
                    @if($upNextLots->count() > 0)
                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Up Next</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach($upNextLots as $nextLot)
                                    <div class="card p-4 flex gap-4 items-center">
                                        @if($nextLot->images->count() > 0)
                                            <img src="{{ $nextLot->images->first()->thumbnail_url }}" alt="{{ $nextLot->title }}" class="w-20 h-20 object-cover rounded">
                                        @else
                                            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center text-gray-400 text-xs">No image</div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs text-gray-500">Lot #{{ $nextLot->lot_number }}</div>
                                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $nextLot->title }}</h4>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Starts at {{ formatCurrency($nextLot->dutch_start_price) }}</div>
                                            @if($nextLot->dutch_start_time)
                                                <div class="text-xs text-amber-600 dark:text-amber-400 mt-1"
                                                     x-data="{ label: '' }"
                                                     x-init="setInterval(() => { const d = Math.max(0, Math.floor((new Date('{{ $nextLot->dutch_start_time->toIso8601String() }}').getTime() - Date.now()) / 1000)); if (d <= 0) { label = 'Starting soon...'; } else { const m = Math.floor(d / 60); const s = d % 60; label = 'Starts in ' + m + ':' + String(s).padStart(2, '0'); } }, 1000)">
                                                    <span x-text="label">Starts {{ $nextLot->dutch_start_time->diffForHumans() }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Schedule --}}
                    <div class="mb-8" x-data="{ showSchedule: false }">
                        <button @click="showSchedule = !showSchedule" class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-3">
                            <svg class="w-4 h-4 transition-transform" :class="showSchedule ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Lot Schedule ({{ $auction->lots->count() }} lots)
                        </button>
                        <div x-show="showSchedule" x-collapse class="bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Lot</th>
                                        <th class="px-4 py-2 text-left">Title</th>
                                        <th class="px-4 py-2 text-right">Start Price</th>
                                        <th class="px-4 py-2 text-center">Status</th>
                                        <th class="px-4 py-2 text-right">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($auction->lots->sortBy('lot_number') as $sLot)
                                        <tr class="{{ $sLot->status === 'live' ? 'bg-amber-50 dark:bg-amber-900/20 font-semibold' : '' }}">
                                            <td class="px-4 py-2">#{{ $sLot->lot_number }}</td>
                                            <td class="px-4 py-2 truncate max-w-[200px]">
                                                <a href="{{ route('lots.show', $sLot) }}" class="hover:text-primary-600">{{ $sLot->title }}</a>
                                            </td>
                                            <td class="px-4 py-2 text-right">{{ formatCurrency($sLot->dutch_start_price) }}</td>
                                            <td class="px-4 py-2 text-center">
                                                @if($sLot->status === 'live')
                                                    <span class="badge badge-danger text-xs">Live</span>
                                                @elseif($sLot->status === 'sold')
                                                    <span class="badge badge-secondary text-xs">Sold</span>
                                                @elseif($sLot->status === 'unsold')
                                                    <span class="badge badge-secondary text-xs">Unsold</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">Pending</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-500">
                                                {{ $sLot->dutch_start_time ? $sLot->dutch_start_time->format('H:i') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Completed Lots --}}
                    @if($completedLots->count() > 0)
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Completed</h3>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($completedLots as $cLot)
                            <div class="card opacity-75">
                                @if($cLot->images->count() > 0)
                                    <a href="{{ route('lots.show', $cLot) }}">
                                        <img src="{{ $cLot->images->first()->thumbnail_url }}" alt="{{ $cLot->title }}" class="w-full h-48 object-contain rounded-t-lg bg-gray-100 dark:bg-gray-800">
                                    </a>
                                @endif
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm text-gray-500">Lot #{{ $cLot->lot_number }}</span>
                                        <span class="badge {{ $cLot->status === 'sold' ? 'badge-secondary' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">{{ ucfirst($cLot->status) }}</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        <a href="{{ route('lots.show', $cLot) }}" class="hover:text-primary-600">{{ $cLot->title }}</a>
                                    </h3>
                                    @if($cLot->status === 'sold')
                                        <div class="text-sm text-green-600">Sold at {{ formatCurrency($cLot->current_bid) }}</div>
                                    @else
                                        <div class="text-sm text-gray-500">Unsold</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif(!($auction->isLiveFormat() && $auction->status === 'live'))
                {{-- Standard grid layout (English / upcoming Live / ended Live) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="lots-grid">
                    @foreach($auction->lots as $lot)
                        <div id="lot-{{ $lot->id }}" style="scroll-margin-top: 5rem;" class="card {{ $auction->isDutch() ? '' : 'card-hover' }} lot-item {{ $lot->isWithdrawn() ? 'opacity-75' : '' }}" data-title="{{ strtolower($lot->title) }}" data-lot-id="{{ $lot->id }}" data-lot-status="{{ $lot->status }}"
                             x-data="{ @if(!$auction->isDutch()) watchlisted: {{ in_array($lot->id, $watchlistedLotIds ?? []) ? 'true' : 'false' }}, loading: false, async toggle() { this.loading = true; const res = await fetch('/api/lots/{{ $lot->id }}/watchlist', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }); const data = await res.json(); this.watchlisted = data.watchlisted; this.loading = false; }, @endif currentBid: {{ $lot->current_bid ?? $lot->starting_bid ?? 0 }}, totalBids: {{ $lot->total_bids }} }">
                            @if($lot->images->count() > 0)
                                <div class="relative bg-gray-100 dark:bg-gray-800 rounded-t-lg" x-data="{ currentIndex: 0, total: {{ $lot->images->count() }} }">
                                    <!-- Image — use thumbnail (300px) for cards -->
                                    @if(!$auction->isDutch() || $auction->status !== 'live')<a href="{{ route('lots.show', $lot) }}">@endif
                                        @foreach($lot->images as $index => $image)
                                            <img src="{{ $index === 0 ? $image->thumbnail_url : '' }}"
                                                 {!! $index > 0 ? 'x-bind:src="currentIndex === ' . $index . ' ? \'' . $image->thumbnail_url . '\' : \'\'"' : '' !!}
                                                 alt="{{ $lot->title }}"
                                                 class="w-full h-64 sm:h-48 object-contain {{ $lot->isWithdrawn() ? 'grayscale' : '' }}"
                                                 loading="{{ $loop->parent->index < 6 ? 'eager' : 'lazy' }}"
                                                 x-show="currentIndex === {{ $index }}"
                                                 x-cloak>
                                        @endforeach
                                    @if(!$auction->isDutch() || $auction->status !== 'live')</a>@endif

                                    <!-- Navigation Arrows (only show if multiple images) -->
                                    @if($lot->images->count() > 1)
                                        <button @click.stop="currentIndex = (currentIndex - 1 + total) % total"
                                                class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                            </svg>
                                        </button>
                                        <button @click.stop="currentIndex = (currentIndex + 1) % total"
                                                class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>

                                        <!-- Dot Indicators -->
                                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-2">
                                            @foreach($lot->images as $index => $image)
                                                <button @click.stop="currentIndex = {{ $index }}"
                                                        class="w-3 h-3 rounded-full transition"
                                                        :class="currentIndex === {{ $index }} ? 'bg-white' : 'bg-white/50'">
                                                </button>
                                            @endforeach
                                        </div>

                                        <!-- Image Counter -->
                                        <div class="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
                                            <span x-text="currentIndex + 1"></span>/<span x-text="total"></span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                @if(!$auction->isDutch() || $auction->status !== 'live')<a href="{{ route('lots.show', $lot) }}">@endif
                                    <div class="w-full h-64 sm:h-48 bg-gray-100 dark:bg-gray-800 rounded-t-lg flex items-center justify-center">
                                        <span class="text-gray-400">No image</span>
                                    </div>
                                @if(!$auction->isDutch() || $auction->status !== 'live')</a>@endif
                            @endif

                            @auth
                                @if($lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                                    <div class="bg-green-600 text-white px-4 py-2.5 flex items-center gap-2">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1z"/>
                                        </svg>
                                        <span class="font-bold text-sm">Congratulations, you won!</span>
                                        <span class="text-green-100 text-sm ml-auto">{{ formatCurrency($lot->current_bid) }}</span>
                                    </div>
                                @elseif($lot->status === 'pending_confirmation' && $lot->winning_bidder_id === auth()->id())
                                    <div class="bg-amber-500 text-white px-4 py-2.5 flex items-center gap-2">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                        <span class="font-bold text-sm">You have the winning bid — awaiting seller confirmation</span>
                                        <span class="text-amber-50 text-sm ml-auto">{{ formatCurrency($lot->current_bid) }}</span>
                                    </div>
                                @endif
                            @endauth

                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Lot #{{ $lot->lot_number }}</span>
                                    <div class="flex items-center gap-2">
                                    @if($lot->isWithdrawn())
                                        <span class="badge bg-gray-500 text-white">Withdrawn</span>
                                    @elseif($lot->status === 'sold')
                                        @auth
                                            @if($lot->winning_bidder_id === auth()->id())
                                                <span class="badge bg-green-600 text-white">Won</span>
                                            @else
                                                <span class="badge badge-secondary">Sold</span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">Sold</span>
                                        @endauth
                                    @elseif($lot->status === 'pending_confirmation')
                                        @auth
                                            @if($lot->winning_bidder_id === auth()->id())
                                                <span class="badge bg-amber-500 text-white">Winning — Pending</span>
                                            @else
                                                <span class="badge bg-amber-100 text-amber-800">Pending Confirmation</span>
                                            @endif
                                        @else
                                            <span class="badge bg-amber-100 text-amber-800">Pending Confirmation</span>
                                        @endauth
                                    @elseif($lot->status === 'unsold')
                                        <span class="badge badge-secondary">Unsold</span>
                                    @elseif($lot->status === 'live')
                                        <span class="badge badge-danger">Live</span>
                                    @endif
                                    <x-contact-seller-button :lot="$lot" size="icon" />
                                    <x-whatsapp-share :lot="$lot" :auction="$auction" size="icon" />
                                    </div>
                                </div>

                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    @if($auction->isDutch() && $auction->status === 'live')
                                        {{ $lot->title }}
                                    @else
                                        <a href="{{ route('lots.show', $lot) }}" class="hover:text-primary-600">{{ $lot->title }}</a>
                                    @endif
                                </h3>

                                @if($auction->isDutch() && $lot->dutch_start_price)
                                    {{-- Dutch Lot Card — buy buttons inside dutchPrice scope for floorCountdown access --}}
                                    <div x-data="{ ...dutchPrice({{ $lot->id }}, {{ $lot->dutch_start_price }}, {{ $lot->dutch_floor_price }}, {{ $lot->dutch_drop_amount }}, {{ $lot->dutch_drop_interval }}, '{{ $lot->isLive() ? ($lot->dutch_start_time ? $lot->dutch_start_time->toIso8601String() : ($auction->start_time ? $auction->start_time->toIso8601String() : '')) : '' }}', {{ $lot->dutch_end_time ? $lot->dutch_end_time->timestamp : ($lot->end_time ? $lot->end_time->timestamp : 0) }}, {{ $lot->quantity }}, {{ $lot->quantity_sold }}, '{{ $lot->dutch_drop_strategy ?: 'constant' }}'), ...dutchBuyInline({{ $lot->id }}) }" x-init="start()">
                                        <div class="mb-4">
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Current Price</div>
                                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="formattedPrice">
                                                {{ $lot->isLive() ? formatCurrency($lot->getCurrentDutchPrice()) : formatCurrency($lot->dutch_start_price) }}
                                            </div>
                                            @if($lot->isLive())
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="nextDropIn > 0 && !atFloor">
                                                    Next drop in <span class="font-semibold text-amber-600" x-text="nextDropIn + 's'"></span>
                                                </div>
                                                <div class="text-xs font-bold mt-1 text-red-600" x-show="atFloor && floorCountdown > 0" x-text="'Last chance! ' + floorCountdown + 's left'"></div>
                                                <div class="text-xs font-bold text-red-600 mt-1" x-show="atFloor && floorCountdown <= 0">Too late!</div>
                                            @endif
                                            @if($lot->quantity > 1)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    <span x-text="remaining + '/' + {{ $lot->quantity }} + ' remaining'">{{ $lot->quantityRemaining() }}/{{ $lot->quantity }} remaining</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            <span x-text="totalBids + (totalBids === 1 ? ' buyer' : ' buyers')">{{ $lot->total_bids }} {{ Str::plural('buyer', $lot->total_bids) }}</span>
                                            @if($lot->isDutchSoldOut())
                                                <span class="text-red-600 font-semibold">Sold Out</span>
                                            @endif
                                        </div>

                                        <div class="flex gap-2">
                                            @if($lot->isWithdrawn())
                                                <span class="btn btn-outline flex-1 opacity-50 cursor-default">Withdrawn</span>
                                            @elseif($lot->isDutchSoldOut() || $lot->status === 'sold')
                                                <span class="btn btn-outline flex-1 opacity-50 cursor-default">Sold Out</span>
                                            @elseif($lot->status === 'unsold')
                                                <span class="btn btn-outline flex-1 opacity-50 cursor-default">Unsold</span>
                                            @elseif($lot->isLive())
                                                <template x-if="!bought && !(atFloor && floorCountdown <= 0)">
                                                    <div class="flex-1 flex flex-col gap-1">
                                                        @auth
                                                            @if($lot->isOwnedBy(auth()->user()))
                                                                <span class="btn flex-1 text-center bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700">Your lot</span>
                                                            @elseif($auction->requiresRegistration() && !$isRegistered)
                                                                <span class="btn btn-accent flex-1 text-center opacity-75">Registration Required</span>
                                                            @else
                                                                <button @click="buy()" :disabled="buying" class="btn btn-accent flex-1" :class="atFloor && floorCountdown > 0 ? 'animate-pulse bg-red-600 hover:bg-red-700 border-red-600' : ''">
                                                                    <span x-show="!buying && !(atFloor && floorCountdown > 0)">Bid Now</span>
                                                                    <span x-show="!buying && atFloor && floorCountdown > 0">Last Chance!</span>
                                                                    <span x-show="buying">Bidding...</span>
                                                                </button>
                                                            @endif
                                                        @else
                                                            <a href="{{ route('login') }}" class="btn btn-accent flex-1">Login to Bid</a>
                                                        @endauth
                                                        <div x-show="error" class="text-red-600 text-xs text-center" x-text="error"></div>
                                                    </div>
                                                </template>
                                                <template x-if="atFloor && floorCountdown <= 0 && !bought">
                                                    <div class="flex-1 text-center text-red-600 dark:text-red-400 font-bold text-sm py-2">Too late! This lot has closed.</div>
                                                </template>
                                                <template x-if="bought">
                                                    <div class="flex-1 text-center text-green-600 dark:text-green-400 font-semibold text-sm py-2" x-text="message"></div>
                                                </template>
                                            @else
                                                <span class="btn btn-outline flex-1 opacity-50 cursor-default">Upcoming</span>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($auction->isSealed())
                                    {{-- Sealed Lot Card --}}
                                    @if($auction->hasEnded())
                                        <div class="mb-4">
                                            @if($lot->status === 'sold')
                                                <div class="text-sm text-gray-600 dark:text-gray-400">Winning Bid</div>
                                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency($lot->current_bid) }}</div>
                                            @else
                                                <div class="text-sm text-gray-600 dark:text-gray-400">Result</div>
                                                <div class="text-lg font-semibold text-gray-500">Unsold</div>
                                            @endif
                                        </div>
                                    @else
                                        @if($lot->hasReserve())
                                            <div class="mb-4">
                                                <div class="text-xs text-gray-500">
                                                    @if($auction->isSealedHighest())
                                                        Has reserve price
                                                    @else
                                                        Maximum: {{ formatCurrency($lot->reserve_price) }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        @if($lot->isLive())
                                            <div class="mb-4 text-sm" x-data="lotCountdown({{ $lot->end_time->timestamp }})" x-init="start()">
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 flex-shrink-0" :class="urgent ? 'text-red-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span :class="urgent ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-600 dark:text-gray-400'"
                                                          x-text="remaining <= 0 ? 'Ended' : label">
                                                        Ends {{ $lot->end_time->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="text-xs text-purple-600 dark:text-purple-400 mb-4">
                                            Bids are secret until auction ends
                                        </div>
                                    @endif

                                    <div class="flex gap-2">
                                        @if($lot->isWithdrawn())
                                            <a href="{{ route('lots.show', $lot) }}" class="btn btn-outline flex-1">View Details</a>
                                        @elseif(auth()->check() && $lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                                            <a href="{{ route('lots.show', $lot) }}" class="btn bg-green-600 hover:bg-green-700 text-white flex-1">View Won Lot</a>
                                        @else
                                            <a href="{{ route('lots.show', $lot) }}" class="btn {{ $lot->isLive() ? 'btn-accent' : 'btn-primary' }} flex-1">
                                                {{ $lot->isLive() ? 'Place Sealed Bid' : 'View Lot' }}
                                            </a>
                                        @endif
                                        @if(($showWatchlistButton ?? false))
                                            <button @click="toggle()" :disabled="loading"
                                                    :title="watchlisted ? 'Remove from watchlist' : 'Add to watchlist'"
                                                    class="btn btn-outline px-3"
                                                    :class="watchlisted ? 'text-primary-600 border-primary-600' : ''">
                                                <svg class="w-5 h-5" :fill="watchlisted ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    {{-- Standard English Lot Card --}}
                                    <div class="mb-4">
                                        <div class="text-sm text-gray-600 dark:text-gray-400" x-text="totalBids > 0 ? 'Current Bid' : 'Starting Bid'">{{ $lot->total_bids > 0 ? 'Current Bid' : 'Starting Bid' }}</div>
                                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400"
                                             x-text="'R' + parseFloat(currentBid).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')">
                                            {{ formatCurrency($lot->total_bids > 0 ? $lot->current_bid : $lot->starting_bid) }}
                                        </div>
                                        @if($lot->hasReserve())
                                            <div class="mt-2">
                                                @if($lot->isReserveMet())
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Reserve Met
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Reserve Not Met
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if($lot->isLive())
                                        <div class="mb-4 text-sm" x-data="lotCountdown({{ $lot->end_time->timestamp }})" x-init="start()">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4 flex-shrink-0" :class="urgent ? 'text-red-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span :class="urgent ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-600 dark:text-gray-400'"
                                                      x-text="remaining <= 0 ? 'Ended' : label">
                                                    Ends {{ $lot->end_time->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        <span x-text="totalBids + (totalBids === 1 ? ' bid' : ' bids')">{{ $lot->total_bids }} {{ Str::plural('bid', $lot->total_bids) }}</span>
                                        @if($lot->hasReserve())
                                            <span class="{{ $lot->reserve_met ? 'text-green-600' : 'text-yellow-600' }}">
                                                Reserve {{ $lot->reserve_met ? 'Met' : 'Not Met' }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex gap-2">
                                        @if($lot->isWithdrawn())
                                            <a href="{{ route('lots.show', $lot) }}" class="btn btn-outline flex-1">
                                                View Details
                                            </a>
                                        @elseif(auth()->check() && $lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                                            <a href="{{ route('lots.show', $lot) }}" class="btn bg-green-600 hover:bg-green-700 text-white flex-1">
                                                View Won Lot
                                            </a>
                                        @else
                                            <a href="{{ route('lots.show', $lot) }}" class="btn {{ $lot->isLive() ? 'btn-accent' : 'btn-primary' }} flex-1">
                                                {{ $lot->isLive() ? 'Bid Now' : 'View Lot' }}
                                            </a>
                                        @endif
                                        @if(($showWatchlistButton ?? false))
                                            <button @click="toggle()" :disabled="loading"
                                                    :title="watchlisted ? 'Remove from watchlist' : 'Add to watchlist'"
                                                    class="btn btn-outline px-3"
                                                    :class="watchlisted ? 'text-primary-600 border-primary-600' : ''">
                                                <svg class="w-5 h-5" :fill="watchlisted ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- All lots displayed (no pagination) -->
                @endif
            @else
                <div class="text-center py-12">
                    <p class="text-gray-600 dark:text-gray-400">No lots in this auction yet.</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    {{-- Pre-live countdown component (shows the "auction starts in X" banner during the final hour) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('auctionStartsCountdown', (startIso) => ({
                label: '—',
                _timer: null,
                init() {
                    this.tick();
                    this._timer = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    const remaining = Math.max(0, Math.floor((new Date(startIso).getTime() - Date.now()) / 1000));
                    if (remaining <= 0) {
                        this.label = 'Going live…';
                        clearInterval(this._timer);
                        // Reload after 2s so the page picks up the new live state.
                        setTimeout(() => window.location.reload(), 2000);
                        return;
                    }
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    this.label = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },
            }));
        });
    </script>

    @php
        $auctionBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Auctions', 'item' => route('auctions.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $auction->title],
            ],
        ];

        $auctionEvent = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $auction->title,
            'startDate' => $auction->start_time->toIso8601String(),
            'endDate' => $auction->end_time ? $auction->end_time->toIso8601String() : $auction->start_time->toIso8601String(),
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => [
                '@type' => 'VirtualLocation',
                'url' => url()->current(),
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name' => $auction->auctioneer->business_name,
                'url' => route('auctioneer.show', $auction->auctioneer),
            ],
            'description' => $auction->description ?? $auction->title . ' auction on ' . config('branding.name'),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($auctionBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($auctionEvent, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script>
    function filterLots(query) {
        const items = document.querySelectorAll('.lot-item');
        const searchTerm = query.toLowerCase();

        items.forEach(item => {
            const title = item.dataset.title;
            if (title.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function lotCountdown(endTimestamp) {
        return {
            remaining: 0,
            urgent: false,
            label: '',
            _interval: null,

            start() {
                this.tick();
                this._interval = setInterval(() => this.tick(), 1000);
            },

            tick() {
                this.remaining = Math.max(0, endTimestamp - Math.floor(Date.now() / 1000));
                this.urgent = this.remaining > 0 && this.remaining < 300;

                if (this.remaining <= 0) {
                    this.label = 'Ended';
                    if (this._interval) clearInterval(this._interval);
                } else {
                    const h = Math.floor(this.remaining / 3600);
                    const m = Math.floor((this.remaining % 3600) / 60);
                    const s = this.remaining % 60;
                    const pad = n => String(n).padStart(2, '0');
                    this.label = 'Ends in ' + pad(h) + ':' + pad(m) + ':' + pad(s);
                }
            },

            destroy() {
                if (this._interval) clearInterval(this._interval);
            }
        };
    }

    // Live (Automated) auction featured block on auction show page.
    // Smoothly counts down the current phase client-side and refetches status on poll / visibility.
    function liveAuctionFeatured({ lotId, phase, phaseEndsAt, acceptsBids }) {
        return {
            lotId,
            phase,
            phaseEndsAt,
            acceptsBids,
            _countdown: 0,
            _tick: null,
            _poll: null,

            init() {
                this.updateCountdown();
                this._tick = setInterval(() => this.updateCountdown(), 500);
                this._poll = setInterval(() => this.refetch(), 3000);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) this.refetch();
                });
            },

            destroy() {
                clearInterval(this._tick);
                clearInterval(this._poll);
            },

            updateCountdown() {
                if (!this.phaseEndsAt) { this._countdown = 0; return; }
                const ms = new Date(this.phaseEndsAt).getTime() - Date.now();
                this._countdown = Math.max(0, Math.ceil(ms / 1000));
            },

            phaseLabel() {
                return ({
                    presenting: 'Presenting…',
                    intermission: 'Next lot coming up',
                    open_call: 'Who\'ll open?',
                    active: 'Bidding open',
                    going_once: 'Going once!',
                    going_twice: 'Going twice!',
                    closed: 'Sold!',
                })[this.phase] || '';
            },

            showCountdown() {
                return this._countdown > 0 && this.phase !== 'closed';
            },

            countdownLabel() {
                return this._countdown + 's';
            },

            async refetch() {
                try {
                    const res = await fetch(`/api/lots/${this.lotId}/status`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.livePhase !== undefined) this.phase = data.livePhase;
                    if (data.livePhaseEndsAt !== undefined) this.phaseEndsAt = data.livePhaseEndsAt;
                    if (data.acceptsBids !== undefined) this.acceptsBids = !!data.acceptsBids;
                    // If the active lot has changed (closed and next lot started), reload to refocus.
                    if (data.status && data.status !== 'live') {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (e) { /* swallow transient network errors */ }
            },
        };
    }

    // Drop strategy phase definitions (mirrors PHP Lot::DROP_STRATEGIES)
    const DROP_STRATEGIES = {
        constant: { phases: [{ range: 1.0, drop_mult: 1.0, interval_mult: 1.0 }] },
        fast_sell: { phases: [
            { range: 0.30, drop_mult: 3.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.5, interval_mult: 2.0 },
        ]},
        max_value: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
        high_drama: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.75 },
            { range: 0.40, drop_mult: 0.5, interval_mult: 1.5 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
    };

    function calcDutchPrice(elapsedSec, startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return floorPrice;

        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0, priceDropped = 0;

        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;

            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            const phaseTime = dropsInPhase * effectiveInterval;
            const timeIntoPhase = elapsedSec - timeConsumed;

            if (timeIntoPhase < phaseTime) {
                const dropsCompleted = Math.floor(timeIntoPhase / effectiveInterval);
                priceDropped += dropsCompleted * effectiveDrop;
                return Math.max(startPrice - priceDropped, floorPrice);
            }

            priceDropped += dropsInPhase * effectiveDrop;
            timeConsumed += phaseTime;
        }
        return floorPrice;
    }

    function calcDutchNextDrop(elapsedSec, startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return 0;

        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0;

        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;

            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            const phaseTime = dropsInPhase * effectiveInterval;
            const timeIntoPhase = elapsedSec - timeConsumed;

            if (timeIntoPhase < phaseTime) {
                return effectiveInterval - (timeIntoPhase % effectiveInterval);
            }
            timeConsumed += phaseTime;
        }
        return 0;
    }

    // Calculate the elapsed seconds at which the floor price is first reached
    function calcFloorReachedAt(startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return 0;
        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0, priceDropped = 0;
        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;
            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            for (let d = 1; d <= dropsInPhase; d++) {
                priceDropped += effectiveDrop;
                if (startPrice - priceDropped <= floorPrice) {
                    return timeConsumed + d * effectiveInterval;
                }
            }
            timeConsumed += dropsInPhase * effectiveInterval;
        }
        return timeConsumed;
    }

    function fmtPrice(price) {
        return 'R' + parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function dutchPrice(lotId, startPrice, floorPrice, dropAmount, dropInterval, startTimeIso, endTimestamp, quantity, quantitySold, strategy) {
        return {
            currentPrice: startPrice,
            formattedPrice: fmtPrice(startPrice),
            nextDropIn: 0,
            atFloor: false,
            floorCountdown: 0,
            inCountdown: false,
            countdownSeconds: 0,
            remaining: quantity - quantitySold,
            _interval: null,

            start() {
                this.tick();
                this._interval = setInterval(() => this.tick(), 1000);
            },

            tick() {
                if (!startTimeIso) {
                    this.currentPrice = startPrice;
                    this.formattedPrice = fmtPrice(startPrice);
                    this.inCountdown = false;
                    return;
                }

                const startMs = new Date(startTimeIso).getTime();
                const nowMs = Date.now();

                if (nowMs < startMs) {
                    // In "Get Ready" countdown — drops haven't started yet
                    this.inCountdown = true;
                    this.countdownSeconds = Math.ceil((startMs - nowMs) / 1000);
                    this.currentPrice = startPrice;
                    this.formattedPrice = fmtPrice(startPrice);
                    return;
                }

                // Drops are active
                this.inCountdown = false;
                this.countdownSeconds = 0;

                const elapsedSec = Math.floor((nowMs - startMs) / 1000);
                const price = calcDutchPrice(elapsedSec, startPrice, floorPrice, dropAmount, dropInterval, strategy);

                this.currentPrice = price;
                this.formattedPrice = fmtPrice(price);
                this.atFloor = price <= floorPrice;
                this.nextDropIn = this.atFloor ? 0 : calcDutchNextDrop(elapsedSec, startPrice, floorPrice, dropAmount, dropInterval, strategy);

                if (this.atFloor && startTimeIso) {
                    const floorReachedAt = calcFloorReachedAt(startPrice, floorPrice, dropAmount, dropInterval, strategy);
                    const floorDeadlineMs = startMs + (floorReachedAt + {{ App\Models\Lot::DUTCH_FLOOR_BUFFER }}) * 1000;
                    this.floorCountdown = Math.max(0, Math.ceil((floorDeadlineMs - nowMs) / 1000));
                } else {
                    this.floorCountdown = 0;
                }
            },

            destroy() {
                if (this._interval) clearInterval(this._interval);
            }
        };
    }

    function dutchBuyInline(lotId) {
        return {
            buying: false,
            bought: false,
            message: '',
            error: '',

            async buy() {
                this.buying = true;
                this.error = '';

                try {
                    const res = await fetch('/api/lots/' + lotId + '/buy', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ quantity: 1 }),
                    });

                    const data = await res.json();

                    if (data.success) {
                        this.bought = true;
                        this.message = data.message || 'Purchased!';
                        // Reload after a moment so the page updates
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        this.error = data.message || 'Purchase failed.';
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                }

                this.buying = false;
            }
        };
    }

    // Auto-reload when upcoming auction goes live
    @if($auction->status === 'upcoming' && $auction->start_time)
    (function() {
        const startMs = new Date('{{ $auction->start_time->toIso8601String() }}').getTime();
        const msUntilStart = startMs - Date.now();

        // Schedule reload at start time (+ 2s buffer for scheduler)
        if (msUntilStart > 0) {
            setTimeout(() => window.location.reload(), msUntilStart + 2000);
        } else {
            // Start time already passed but still upcoming — poll until live
            const poll = setInterval(async () => {
                try {
                    const r = await fetch('/api/auctions/{{ $auction->slug }}/status');
                    if (!r.ok) return;
                    const json = await r.json();
                    if (json.data?.auction?.status !== 'upcoming') {
                        clearInterval(poll);
                        window.location.reload();
                    }
                } catch (e) {}
            }, 3000);
        }
    })();
    @endif

    // Poll auction status to keep bids and counts live
    @if($auction->status === 'live')
    (function() {
        const auctionId = '{{ $auction->slug }}';
        const isDutchSequential = {{ $auction->isDutch() ? 'true' : 'false' }};
        const isLiveFormat = {{ $auction->isLiveFormat() ? 'true' : 'false' }};

        let knownStatuses = {
            @foreach($auction->lots as $l)
            {{ $l->id }}: '{{ $l->status }}',
            @endforeach
        };

        // For Dutch sequential and Live formats: track which lot is currently live
        let knownActiveLotId = {{ ($auction->isDutch() || $auction->isLiveFormat()) ? ($auction->lots->firstWhere('status', 'live')?->id ?? 'null') : 'null' }};

        async function pollAuction() {
            try {
                const r = await fetch('/api/auctions/' + auctionId + '/status');
                if (!r.ok) return;
                const json = await r.json();
                if (!json.success) return;

                // Reload if auction ended
                if (json.data.auction.status === 'ended') {
                    window.location.reload();
                    return;
                }

                if (isDutchSequential || isLiveFormat) {
                    // Sequential (Dutch) / Live: only reload when a NEW lot becomes active.
                    // This preserves the 5s closed-result banner on Live auctions — the
                    // previous active lot closes (status=unsold/sold), but we don't reload
                    // until the next lot's status flips to 'live'.
                    const newActiveLot = json.data.lots.find(l => l.status === 'live');
                    const newActiveLotId = newActiveLot ? newActiveLot.id : null;

                    if (newActiveLotId && newActiveLotId !== knownActiveLotId) {
                        window.location.reload();
                        return;
                    }

                    // Update known statuses in-place (no reload for draft→live of non-active lots, etc.)
                    json.data.lots.forEach(lot => {
                        knownStatuses[lot.id] = lot.status;
                    });
                } else {
                    // English: reload if any lot status changed
                    let statusChanged = false;
                    json.data.lots.forEach(lot => {
                        if (knownStatuses[lot.id] !== undefined && knownStatuses[lot.id] !== lot.status) {
                            statusChanged = true;
                        }
                    });

                    if (statusChanged) {
                        window.location.reload();
                        return;
                    }
                }

                // Update bid counts and prices in-place
                json.data.lots.forEach(lot => {
                    const el = document.querySelector('[data-lot-id="' + lot.id + '"]');
                    if (!el) return;
                    const comp = Alpine.$data(el);
                    if (!comp) return;
                    comp.currentBid = parseFloat(lot.current_bid);
                    comp.totalBids = lot.total_bids;
                });
            } catch (e) { /* silent */ }
        }

        let interval = setInterval(pollAuction, 3000);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(interval);
            } else {
                pollAuction();
                interval = setInterval(pollAuction, 3000);
            }
        });
    })();
    @endif
    </script>
    @endpush

    <x-auctioneer-rules-modal :auctioneer="$auction->auctioneer" />
</x-app-layout>
