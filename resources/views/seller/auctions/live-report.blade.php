<x-app-layout>
    <x-slot name="title">{{ $auction->title }} - {{ $auction->status === 'live' ? 'Live' : 'Auction' }} Report</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="liveReport()"
         x-init="@if($auction->status === 'live')startPolling()@else lastUpdated = new Date().toLocaleTimeString()@endif">

        <!-- Back link -->
        <div class="mb-6">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline">&larr; Back to Auctions</a>
            @else
                <a href="{{ route('seller.auctions.index') }}" class="btn btn-outline">&larr; Back to My Auctions</a>
            @endif
        </div>

        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold">{{ $auction->title }}</h1>
                        @if($auction->status === 'live')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                Live
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                Ended
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $auction->start_time->format('d M Y H:i') }}
                        &ndash; {{ $auction->end_time->format('d M Y H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400" x-text="lastUpdated ? 'Updated ' + lastUpdated : ''"></span>
                    <a href="{{ route('auctions.show', $auction->slug) }}" class="btn btn-primary text-sm">View Auction</a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Lots</p>
                <p class="text-2xl font-bold mt-1" x-text="summary.total_lots">-</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">With Bids</p>
                <p class="text-2xl font-bold mt-1 text-green-600" x-text="summary.with_bids">-</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">No Bids</p>
                <p class="text-2xl font-bold mt-1 text-red-600" x-text="summary.no_bids">-</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $auction->status === 'live' ? 'Running Total' : 'Total Sales' }}</p>
                <p class="text-2xl font-bold mt-1 text-green-600" x-text="summary.total_current_bids">-</p>
            </div>
        </div>

        <!-- Filter & Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-lg font-bold">
                    Lots (<span x-text="filteredLots.length"></span>)
                </h2>
                <select x-model="filter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm w-full sm:w-auto">
                    <option value="all">All Lots</option>
                    <option value="with_bids">With Bids</option>
                    <option value="no_bids">No Bids</option>
                    @if($auction->status === 'ended')
                        <option value="sold">Sold</option>
                        <option value="unsold">Unsold</option>
                    @endif
                </select>
            </div>

            <div style="max-height: 70vh; overflow: auto;">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-20">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Lot #</th>
                            <th class="px-3 py-2 text-left font-medium">Title</th>
                            <th class="px-3 py-2 text-right font-medium">Starting</th>
                            <th class="px-3 py-2 text-right font-medium">Reserve</th>
                            <th class="px-3 py-2 text-right font-medium">Current Bid</th>
                            <th class="px-3 py-2 text-center font-medium">Bids</th>
                            <th class="px-3 py-2 text-center font-medium">Status</th>
                            <th class="px-3 py-2 text-right font-medium">Time Left</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="lot in filteredLots" :key="lot.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                :class="lot.total_bids > 0 ? 'bg-green-50/50 dark:bg-green-900/10' : ''">
                                <td class="px-3 py-2 font-medium" x-text="lot.lot_number"></td>
                                <td class="px-3 py-2" x-text="lot.title"></td>
                                <td class="px-3 py-2 text-right text-gray-500" x-text="formatCurrency(lot.starting_bid)"></td>
                                <td class="px-3 py-2 text-right text-gray-500" x-text="lot.reserve_price ? formatCurrency(lot.reserve_price) : '-'"></td>
                                <td class="px-3 py-2 text-right font-medium"
                                    :class="lot.total_bids > 0 ? 'text-green-600' : 'text-gray-400'"
                                    x-text="lot.total_bids > 0 ? lot.formatted_current_bid : '-'"></td>
                                <td class="px-3 py-2 text-center" x-text="lot.total_bids"></td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': lot.total_bids > 0,
                                              'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': lot.status === 'active' && lot.total_bids === 0,
                                              'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': lot.status === 'pending',
                                              'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': lot.status === 'unsold',
                                          }"
                                          x-text="lot.status.charAt(0).toUpperCase() + lot.status.slice(1)">
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap" x-text="lot.time_remaining || '-'"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredLots.length === 0">
                            <td colspan="8" class="px-3 py-8 text-center text-gray-500">No lots match this filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function liveReport() {
            const initialData = @json($initialData);

            return {
                lots: initialData.lots,
                summary: initialData.summary,
                filter: 'all',
                lastUpdated: null,
                polling: null,

                get filteredLots() {
                    if (this.filter === 'with_bids') return this.lots.filter(l => l.total_bids > 0);
                    if (this.filter === 'no_bids') return this.lots.filter(l => l.total_bids === 0);
                    if (this.filter === 'sold') return this.lots.filter(l => l.status === 'sold');
                    if (this.filter === 'unsold') return this.lots.filter(l => l.status === 'unsold');
                    return this.lots;
                },

                async fetchData() {
                    try {
                        const res = await fetch('/api/auctions/{{ $auction->id }}/seller-report', {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) {
                            console.error('Live report fetch failed:', res.status, res.statusText);
                            return;
                        }
                        const data = await res.json();
                        this.lots = data.lots;
                        this.summary = data.summary;
                        this.lastUpdated = new Date().toLocaleTimeString();
                    } catch (e) {
                        console.error('Live report fetch error:', e);
                    }
                },

                startPolling() {
                    this.lastUpdated = new Date().toLocaleTimeString();
                    this.polling = setInterval(() => this.fetchData(), 60000);
                },

                formatCurrency(amount) {
                    if (amount === null || amount === undefined) return '-';
                    return 'R' + Number(amount).toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                destroy() {
                    if (this.polling) clearInterval(this.polling);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
