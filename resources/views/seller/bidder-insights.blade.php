<x-app-layout>
    <x-slot name="title">Bidder Insights</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Bidder Insights</h1>
            </div>

            <!-- Filters -->
            <div class="card mb-8">
                <div class="p-4">
                    <form method="GET" action="{{ route('seller.bidder-insights') }}" class="flex flex-col sm:flex-row gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Period</label>
                            <select name="period" onchange="this.form.submit()" class="input w-full sm:w-auto">
                                <option value="7" {{ $period === '7' ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ $period === '30' ? 'selected' : '' }}>Last 30 days</option>
                                <option value="90" {{ $period === '90' ? 'selected' : '' }}>Last 90 days</option>
                                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All time</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Auction</label>
                            <select name="auction_id" onchange="this.form.submit()" class="input w-full sm:w-auto">
                                <option value="">All Auctions</option>
                                @foreach($auctions as $auction)
                                    <option value="{{ $auction->id }}" {{ $filteredAuctionId == $auction->id ? 'selected' : '' }}>
                                        {{ Str::limit($auction->title, 40) }} ({{ ucfirst($auction->status) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Lot Views</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['lot_views']) }}</div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Auction Views</div>
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['auction_views']) }}</div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Unique Visitors</div>
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($stats['unique_visitors']) }}</div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">View-to-Bid %</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['view_to_bid_pct'] }}%</div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Avg Bids/Lot</div>
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['avg_bids_per_lot'] }}</div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mobile %</div>
                        <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ $stats['mobile_pct'] }}%</div>
                    </div>
                </div>
            </div>

            <!-- Lot Interest Alerts -->
            @if($alertLots->isNotEmpty())
                <div class="card mb-8 border-l-4 border-amber-500">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-amber-600 dark:text-amber-400 mb-4">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Lot Interest Alerts
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">These lots are getting views but no bids. Consider adjusting the starting bid or reserve price.</p>
                        <div class="space-y-3">
                            @foreach($alertLots as $alert)
                                <div class="flex items-center gap-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                    @if($alert['lot']->images->count() > 0)
                                        <img src="{{ $alert['lot']->images->first()->thumbnail_url }}" alt="" class="w-12 h-12 rounded object-cover">
                                    @endif
                                    <div class="flex-1">
                                        <a href="{{ route('lots.show', $alert['lot']) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600">
                                            {{ $alert['lot']->title }}
                                        </a>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Lot #{{ $alert['lot']->lot_number }} &middot; {{ $alert['views'] }} views &middot; {{ $alert['watchlisters'] }} watchlisted &middot; 0 bids
                                        </div>
                                    </div>
                                    <div class="text-right text-sm">
                                        <div class="text-gray-500 dark:text-gray-400">Starting bid</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency($alert['lot']->starting_bid) }}</div>
                                        @if($alert['lot']->reserve_price)
                                            <div class="text-xs text-gray-500">Reserve: {{ formatCurrency($alert['lot']->reserve_price) }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Most Viewed Lots -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Most Viewed Lots</h2>

                    @if($mostViewedLots->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lot</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Auction</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Views</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Watchlisted</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bids</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">View-to-Bid %</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current/Final Bid</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($mostViewedLots as $item)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if($item['lot']->images->count() > 0)
                                                        <img src="{{ $item['lot']->images->first()->thumbnail_url }}" alt="" class="w-10 h-10 rounded object-cover flex-shrink-0">
                                                    @endif
                                                    <div>
                                                        <a href="{{ route('lots.show', $item['lot']) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600">
                                                            {{ Str::limit($item['lot']->title, 30) }}
                                                        </a>
                                                        <div class="text-xs text-gray-500">Lot #{{ $item['lot']->lot_number }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                {{ Str::limit($item['lot']->auction->title ?? '', 25) }}
                                            </td>
                                            <td class="px-4 py-3 text-center font-semibold text-blue-600 dark:text-blue-400">{{ $item['views'] }}</td>
                                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $item['watchlisters'] }}</td>
                                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $item['bids'] }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="{{ $item['conversion'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                                    {{ $item['conversion'] }}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                                {{ formatCurrency($item['lot']->current_bid) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No lot view data yet. Views will appear here as bidders browse your lots.</p>
                    @endif
                </div>
            </div>

            <!-- Engagement & Retention -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">New vs Returning Bidders</div>
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $engagement['returning_pct'] }}%</div>
                        <div class="text-xs text-gray-500 mt-1">returning</div>
                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ $engagement['returning_bidders'] }}</span> returning &middot;
                            <span class="font-semibold">{{ $engagement['new_bidders'] }}</span> new
                            <div class="text-xs text-gray-500 mt-1">of {{ $engagement['total_period_bidders'] }} bidders</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Follower → Bidder</div>
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $engagement['follower_conversion_pct'] }}%</div>
                        <div class="text-xs text-gray-500 mt-1">all-time</div>
                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ $engagement['follower_bidders'] }}</span> of
                            <span class="font-semibold">{{ $engagement['follower_count'] }}</span> followers have bid
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Watchlist → Bid</div>
                        <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ $engagement['watchlist_conversion_pct'] }}%</div>
                        <div class="text-xs text-gray-500 mt-1">all-time</div>
                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ $engagement['watchlist_converted'] }}</span> of
                            <span class="font-semibold">{{ $engagement['watchlist_total'] }}</span> watchers placed a bid
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bid Timing + Geography -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Bid Timing Distribution -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Bid Timing Distribution</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">When bids are placed relative to each lot's close time.</p>
                        @if($bidTimingTotal > 0)
                            <div class="space-y-4">
                                @foreach([
                                    'last_5min' => ['Last 5 minutes', 'bg-red-500'],
                                    'last_hour' => ['Last hour', 'bg-amber-500'],
                                    'last_day' => ['Last 24 hours', 'bg-blue-500'],
                                    'earlier' => ['Earlier', 'bg-gray-400'],
                                ] as $key => [$label, $colorClass])
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $bidTiming[$key] }} bids ({{ $bidTimingPct[$key] }}%)
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="{{ $colorClass }} h-3 rounded-full" style="width: {{ $bidTimingPct[$key] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($bidTimingPct['last_5min'] >= 20)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                                    A significant share of bids arrive in the final 5 minutes &mdash; soft-close is doing useful work.
                                </p>
                            @endif
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No bid timing data yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Bidder Geography -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Bidder Geography</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Where your bidders live (by province).</p>
                        @if($bidderGeography->isNotEmpty())
                            @php
                                $maxGeo = max($bidderGeography->max(), 1);
                                $totalGeo = $bidderGeography->sum();
                            @endphp
                            <div class="space-y-3">
                                @foreach($bidderGeography as $province => $count)
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $province }}</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $count }} ({{ round(($count / $totalGeo) * 100, 1) }}%)
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="bg-primary-500 h-3 rounded-full" style="width: {{ round(($count / $maxGeo) * 100, 1) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No bidder location data yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Follower Growth -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Follower Growth</h2>
                    @if($followerGrowth->isNotEmpty() && $followerGrowth->sum('count') > 0)
                        <div class="flex items-end gap-1 h-40 border-b border-gray-200 dark:border-gray-700 pb-1">
                            @foreach($followerGrowth as $point)
                                @php
                                    $heightPct = $followerGrowthMax > 0 ? ($point['count'] / $followerGrowthMax) * 100 : 0;
                                @endphp
                                <div class="flex-1 flex flex-col items-center justify-end" title="{{ $point['label'] }}: {{ $point['count'] }}">
                                    <div class="w-full bg-purple-500 dark:bg-purple-600 rounded-t"
                                         style="height: {{ max($heightPct, $point['count'] > 0 ? 3 : 0) }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                            <span>{{ $followerGrowth->first()['label'] ?? '' }}</span>
                            <span class="font-medium">{{ $followerGrowth->sum('count') }} new followers in this period</span>
                            <span>{{ $followerGrowth->last()['label'] ?? '' }}</span>
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No new followers in this period.</p>
                    @endif
                </div>
            </div>

            <!-- Near Misses (Underbidders) -->
            @if($underbidders->isNotEmpty())
                <div class="card mb-8">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Near Misses</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Bidders who came 2nd on your sold lots. These are your highest-intent re-engagement targets.</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bidder</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Near Misses</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Highest Bid</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Gap to Winner</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Latest Lot</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($underbidders as $u)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $u['user']->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u['masked_email'] }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">{{ $u['near_misses'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency($u['highest_bid']) }}</td>
                                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">{{ formatCurrency($u['avg_gap']) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                <a href="{{ route('lots.show', $u['last_lot']) }}" class="hover:text-primary-600">
                                                    {{ Str::limit($u['last_lot']->title, 30) }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Two Column: Top Bidders + Peak Activity & Device -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

                <!-- Top Bidders (2/3 width) -->
                <div class="lg:col-span-2 card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Top Bidders</h2>

                        @if($topBidders->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bidder</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Auctions</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bids</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Spent</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Last Active</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($topBidders as $bidder)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $bidder['user']->name }}
                                                        @if($bidder['is_repeat'])
                                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Repeat</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $bidder['masked_email'] }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $bidder['auctions_count'] }}</td>
                                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $bidder['total_bids'] }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                                    {{ formatCurrency($bidder['total_spent']) }}
                                                </td>
                                                <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $bidder['last_active']->diffForHumans() }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No bidder data yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Peak Activity & Device Breakdown (1/3 width) -->
                <div class="space-y-8">
                    <!-- Peak Activity -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Peak Activity</h2>

                            @if($peakDay && $peakHour)
                                <p class="text-gray-700 dark:text-gray-300">
                                    Your bidders are most active on <span class="font-bold text-primary-600 dark:text-primary-400">{{ $peakDay }}s</span>
                                    between <span class="font-bold text-primary-600 dark:text-primary-400">{{ $peakHour }}</span>.
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Consider scheduling auctions to end during peak hours for maximum bidding activity.</p>
                            @else
                                <p class="text-gray-600 dark:text-gray-400">Not enough data yet to determine peak activity times.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Device Breakdown -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Device Breakdown</h2>

                            @if($stats['lot_views'] + $stats['auction_views'] > 0)
                                <div class="space-y-4">
                                    <!-- Mobile -->
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">Mobile</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $deviceBreakdown['mobile'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="bg-blue-500 h-3 rounded-full" style="width: {{ $deviceBreakdown['mobile'] }}%"></div>
                                        </div>
                                    </div>
                                    <!-- Desktop -->
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">Desktop</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $deviceBreakdown['desktop'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="bg-green-500 h-3 rounded-full" style="width: {{ $deviceBreakdown['desktop'] }}%"></div>
                                        </div>
                                    </div>
                                    <!-- Tablet -->
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600 dark:text-gray-400">Tablet</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $deviceBreakdown['tablet'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="bg-purple-500 h-3 rounded-full" style="width: {{ $deviceBreakdown['tablet'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-600 dark:text-gray-400">No device data yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
