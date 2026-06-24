<x-app-layout>
    <x-slot name="title">My Watchlist</x-slot>

    <div class="py-6" x-data="watchlistMonitor()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Auto-refresh Info Notice -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-4 py-3 mb-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <span class="font-semibold">How auto-refresh works:</span>
                    This page automatically updates every <span x-text="refreshInterval/1000" class="font-semibold">20</span> seconds to show the latest bids and prices.
                    To place a bid, type your amount and press <span class="font-semibold">Bid</span> before the page refreshes.
                    If you need more time, uncheck <span class="font-semibold">Auto-refresh</span> above while you enter your amount.
                </div>
            </div>

            <!-- Header with Auto-refresh Toggle -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">My Watchlist</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Monitor and bid on your watched lots</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" x-model="autoRefresh" class="rounded" @change="toggleAutoRefresh()">
                        <span>Auto-refresh (<span x-text="refreshInterval/1000"></span>s)</span>
                    </label>
                    <button @click="refreshData()" class="btn btn-outline btn-sm">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            @if($watchlist->count() > 0)
                <!-- Compact Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach($watchlist as $item)
                        @php
                            $lot = $item->lot;
                            $isWinning = $lot->winning_bidder_id === auth()->id();
                            $isLive = $lot->isLive();
                        @endphp
                        <div class="card {{ !$isLive && $lot->status === 'sold' && $lot->winning_bidder_id === auth()->id() ? 'border-2 border-green-500' : (!$isLive && $lot->status === 'sold' ? 'border-2 border-red-300 dark:border-red-700' : '') }}"
                             :class="({{ $isLive ? 'true' : 'false' }}) ? (isWinning ? '' : 'border-2 border-orange-500') : ''"
                             x-data="lotCard({{ $lot->id }}, {{ $lot->current_bid ?? $lot->starting_bid ?? 0 }}, {{ $lot->increment }}, {{ $isWinning ? 'true' : 'false' }}, {{ $lot->total_bids }}, {{ $lot->end_time->timestamp }})">

                            <div class="p-4">
                                <!-- Top Row: Title, Status, Time -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Lot #{{ $lot->lot_number }}</span>
                                            @if($isLive)
                                                <span class="badge badge-danger text-xs">Live</span>
                                            @elseif($lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">Won</span>
                                            @elseif($lot->status === 'sold')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">Sold</span>
                                            @elseif($lot->status === 'unsold')
                                                <span class="badge badge-secondary text-xs">Unsold</span>
                                            @else
                                                <span class="badge badge-secondary text-xs">{{ ucfirst($lot->status) }}</span>
                                            @endif
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 line-clamp-1">
                                            <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="hover:text-primary-600">
                                                {{ $lot->title }}
                                            </a>
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $lot->auction->title }}
                                        </p>
                                    </div>

                                    <!-- Image Thumbnail -->
                                    <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="ml-3">
                                        @if($lot->images->count() > 0)
                                            <img src="{{ $lot->images->first()->thumbnail_url }}"
                                                 alt="{{ $lot->title }}"
                                                 class="w-20 h-20 object-cover rounded">
                                        @else
                                            <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                                <span class="text-xs text-gray-400">No image</span>
                                            </div>
                                        @endif
                                    </a>
                                </div>

                                @if($isLive)
                                    <!-- Status Badge -->
                                    <div class="mb-2">
                                        <div x-show="isWinning" class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="bg-green-500 rounded-full p-1">
                                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1z"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-green-800 dark:text-green-200">Winning</span>
                                            </div>
                                        </div>
                                        <div x-show="!isWinning" class="bg-orange-50 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-700 rounded-lg px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-sm font-semibold text-orange-800 dark:text-orange-200">Outbid - Action Needed!</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Countdown Timer -->
                                    <div class="flex items-center gap-1 mb-3">
                                        <svg class="w-4 h-4 flex-shrink-0" :class="isUrgent ? 'text-red-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex items-center gap-0.5">
                                            <div class="rounded px-2 py-1 text-center"
                                                 :class="isUrgent ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-100 dark:bg-gray-700'">
                                                <span class="text-lg font-bold font-mono leading-none"
                                                      :class="isUrgent ? 'text-red-700 dark:text-red-300' : 'text-gray-800 dark:text-gray-100'"
                                                      x-text="String(Math.floor(timeRemaining / 3600)).padStart(2,'0')">00</span>
                                                <span class="text-xs block" :class="isUrgent ? 'text-red-500' : 'text-gray-400'">h</span>
                                            </div>
                                            <span class="text-lg font-bold text-gray-400 px-0.5">:</span>
                                            <div class="rounded px-2 py-1 text-center"
                                                 :class="isUrgent ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-100 dark:bg-gray-700'">
                                                <span class="text-lg font-bold font-mono leading-none"
                                                      :class="isUrgent ? 'text-red-700 dark:text-red-300' : 'text-gray-800 dark:text-gray-100'"
                                                      x-text="String(Math.floor((timeRemaining % 3600) / 60)).padStart(2,'0')">00</span>
                                                <span class="text-xs block" :class="isUrgent ? 'text-red-500' : 'text-gray-400'">m</span>
                                            </div>
                                            <span class="text-lg font-bold text-gray-400 px-0.5">:</span>
                                            <div class="rounded px-2 py-1 text-center"
                                                 :class="isUrgent ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-100 dark:bg-gray-700'">
                                                <span class="text-lg font-bold font-mono leading-none"
                                                      :class="isUrgent ? 'text-red-700 dark:text-red-300' : 'text-gray-800 dark:text-gray-100'"
                                                      x-text="String(timeRemaining % 60).padStart(2,'0')">00</span>
                                                <span class="text-xs block" :class="isUrgent ? 'text-red-500' : 'text-gray-400'">s</span>
                                            </div>
                                        </div>
                                        <span class="text-xs ml-1" :class="isUrgent ? 'text-red-500 font-semibold' : 'text-gray-400'">remaining</span>
                                    </div>

                                    <!-- Bidding Section -->
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mb-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400" x-text="totalBids > 0 ? 'Current Bid' : 'Starting Bid'">{{ $lot->total_bids > 0 ? 'Current Bid' : 'Starting Bid' }}</div>
                                                <div class="text-xl font-bold text-primary-600 dark:text-primary-400" x-text="formatCurrency(currentBid)">
                                                    {{ formatCurrency($lot->total_bids > 0 ? $lot->current_bid : $lot->starting_bid) }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs text-gray-600 dark:text-gray-400">Min Bid</div>
                                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="formatCurrency(minBid)">
                                                    {{ formatCurrency($lot->total_bids > 0 ? ($lot->current_bid + $lot->increment) : $lot->starting_bid) }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bid Form -->
                                        <div x-show="!isWinning" class="space-y-2">
                                            <div class="flex gap-2">
                                                <input type="number"
                                                       x-model="customAmount"
                                                       class="input flex-1 text-sm">
                                                <button @click="placeBid()"
                                                        :disabled="bidding || !customAmount"
                                                        class="btn btn-accent btn-sm px-4">
                                                    <span x-show="!bidding">Bid</span>
                                                    <span x-show="bidding">...</span>
                                                </button>
                                            </div>
                                            <div x-show="bidMessage" x-text="bidMessage"
                                                 :class="bidSuccess ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                                 class="text-xs text-center font-medium"></div>
                                        </div>
                                        <div x-show="isWinning">
                                            <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="btn btn-primary btn-sm w-full">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- Non-Live Lot -->
                                    @if($lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                                        <!-- Winner Banner -->
                                        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-3 mb-3">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-green-500 rounded-full p-2 flex-shrink-0">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-green-800 dark:text-green-200">Congratulations, you won!</div>
                                                    <div class="text-lg font-bold text-green-700 dark:text-green-300">{{ formatCurrency($lot->current_bid) }}</div>
                                                    <div class="text-xs text-green-600 dark:text-green-400 mt-0.5">Contact the auctioneer to arrange collection.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="btn bg-green-600 hover:bg-green-700 text-white btn-sm w-full">
                                            View Won Lot
                                        </a>
                                    @elseif($lot->status === 'sold')
                                        <!-- Outbid / Lost -->
                                        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-3 mb-3">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-red-500 rounded-full p-2 flex-shrink-0">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-red-800 dark:text-red-200">Better luck next time!</div>
                                                    <div class="text-lg font-bold text-red-700 dark:text-red-300">{{ formatCurrency($lot->current_bid) }}</div>
                                                    <div class="text-xs text-red-600 dark:text-red-400 mt-0.5">This lot was sold to another bidder.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="btn btn-outline btn-sm w-full">
                                            View Details
                                        </a>
                                    @elseif($lot->status === 'unsold')
                                        <!-- Unsold -->
                                        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-3">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-gray-400 rounded-full p-2 flex-shrink-0">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="10" stroke-width="2" fill="none"/>
                                                        <line x1="4" y1="12" x2="20" y2="12" stroke-width="2.5"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-700 dark:text-gray-300">Unsold</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">The reserve was not met. This lot may be relisted.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="btn btn-outline btn-sm w-full">
                                            View Details
                                        </a>
                                    @else
                                        <!-- Pending / Other -->
                                        <div class="mb-3">
                                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $lot->total_bids > 0 ? 'Current Price' : 'Starting Price' }}</div>
                                            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                                {{ formatCurrency($lot->total_bids > 0 ? $lot->current_bid : $lot->starting_bid) }}
                                            </div>
                                        </div>
                                        <a href="{{ route('lots.show', $lot) }}?ref=watchlist" class="btn btn-primary btn-sm w-full">
                                            View Details
                                        </a>
                                    @endif
                                @endif

                                <!-- Remove from Watchlist -->
                                <button @click="removeFromWatchlist({{ $lot->id }})"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 mt-2 w-full text-center">
                                    Remove from watchlist
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $watchlist->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Your watchlist is empty.</p>
                        <a href="{{ route('auctions.index') }}" class="btn btn-primary">Browse Auctions</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    // Registry: watchlistMonitor registers lot cards, then polls once for all
    const _wlUserId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
    const _wlCards = {}; // lotId → Alpine lotCard component ref

    function watchlistMonitor() {
        return {
            autoRefresh: true,
            refreshInterval: 20000,
            isRefreshing: false,
            intervalId: null,

            init() {
                if (this.autoRefresh) {
                    this.startAutoRefresh();
                }
                // Pause polling when tab is hidden
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stopAutoRefresh();
                    } else if (this.autoRefresh) {
                        this.refreshData();
                        this.startAutoRefresh();
                    }
                });
            },

            toggleAutoRefresh() {
                if (this.autoRefresh) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            },

            startAutoRefresh() {
                this.stopAutoRefresh();
                this.intervalId = setInterval(() => {
                    this.refreshData();
                }, this.refreshInterval);
            },

            stopAutoRefresh() {
                if (this.intervalId) {
                    clearInterval(this.intervalId);
                    this.intervalId = null;
                }
            },

            async refreshData() {
                this.isRefreshing = true;
                try {
                    // Build batch request: ids & version string from all registered cards
                    const ids = Object.keys(_wlCards);
                    if (ids.length === 0) return;

                    const v = ids.map(id => `${id}:${_wlCards[id].totalBids}`).join(',');
                    const r = await fetch(`/api/lots/batch-status?ids=${ids.join(',')}&v=${v}`);
                    if (!r.ok) return;
                    const data = await r.json();

                    // Short-circuit: nothing changed, skip all updates
                    if (!data.changed) return;

                    // Update only the changed lot cards
                    for (const [lotId, lot] of Object.entries(data.lots)) {
                        const card = _wlCards[lotId];
                        if (!card) continue;
                        card.currentBid = parseFloat(lot.currentBid);
                        card.totalBids = lot.totalBids;
                        card.endTimestamp = Math.floor(new Date(lot.endTime).getTime() / 1000);
                        card.isWinning = _wlUserId > 0 && lot.winningBidderId === _wlUserId;
                        if (!card.bidding) {
                            card.customAmount = card.minBid;
                        }
                    }
                } catch (e) { /* silent */ }
                finally {
                    this.isRefreshing = false;
                }
            }
        };
    }

    function lotCard(lotId, initialBid, increment, initialWinning, totalBids, endTimestamp) {
        return {
            lotId: lotId,
            currentBid: parseFloat(initialBid),
            increment: parseFloat(increment),
            isWinning: initialWinning,
            endTimestamp: endTimestamp,
            totalBids: totalBids,
            customAmount: totalBids > 0 ? parseFloat(initialBid) + parseFloat(increment) : parseFloat(initialBid),
            bidding: false,
            bidMessage: '',
            bidSuccess: false,
            timeRemaining: 0,
            isUrgent: false,

            get minBid() {
                return this.totalBids > 0
                    ? this.currentBid + this.increment
                    : this.currentBid;
            },

            init() {
                // Register this card so watchlistMonitor can batch-update it
                _wlCards[this.lotId] = this;
                this.updateCountdown();
                setInterval(() => this.updateCountdown(), 1000);
            },

            updateCountdown() {
                const now = Math.floor(Date.now() / 1000);
                this.timeRemaining = Math.max(0, this.endTimestamp - now);
                this.isUrgent = this.timeRemaining < 300;
            },

            formatCountdown() {
                const t = this.timeRemaining;
                if (t <= 0) return 'Ended';
                const h = Math.floor(t / 3600);
                const m = Math.floor((t % 3600) / 60);
                const s = t % 60;
                return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            },

            formatCurrency(amount) {
                return 'R' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },

            formatTimeRemaining(endTimestamp) {
                const now = Math.floor(Date.now() / 1000);
                const remaining = endTimestamp - now;

                if (remaining <= 0) return 'ended';

                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);

                if (hours > 0) {
                    return `in ${hours}h ${minutes}m`;
                } else {
                    return `in ${minutes}m`;
                }
            },

            async placeBid() {
                const bidAmount = parseFloat(this.customAmount);

                if (!bidAmount || isNaN(bidAmount) || bidAmount < this.minBid) {
                    this.bidMessage = `Minimum bid is ${this.formatCurrency(this.minBid)}`;
                    this.bidSuccess = false;
                    return;
                }

                this.bidding = true;
                this.bidMessage = '';

                try {
                    const response = await fetch(`/api/lots/${this.lotId}/bid`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ amount: bidAmount })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.currentBid = parseFloat(data.newBid);
                        this.isWinning = data.hasTopBid;
                        this.totalBids = data.totalBids || (this.totalBids + 1);
                        this.customAmount = this.minBid;
                        this.bidMessage = 'Bid placed successfully!';
                        this.bidSuccess = true;
                        setTimeout(() => { this.bidMessage = ''; }, 3000);
                    } else {
                        this.bidMessage = data.message || 'Failed to place bid';
                        this.bidSuccess = false;
                    }
                } catch (error) {
                    this.bidMessage = 'Failed to place bid. Please try again.';
                    this.bidSuccess = false;
                } finally {
                    this.bidding = false;
                }
            },

            async removeFromWatchlist(lotId) {
                if (!confirm('Remove this lot from your watchlist?')) return;

                try {
                    const response = await fetch(`/api/lots/${lotId}/watchlist`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
