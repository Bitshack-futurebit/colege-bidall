<x-app-layout>
    <x-slot name="title">Analytics</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Analytics</h1>
            </div>

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Auctions</div>
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_auctions'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Lots</div>
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_lots'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $stats['lots_sold'] }} sold ({{ $stats['total_lots'] > 0 ? round(($stats['lots_sold'] / $stats['total_lots']) * 100) : 0 }}%)
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Sales</div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ formatCurrency($stats['total_sales']) }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Avg Sale Price</div>
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($stats['avg_sale_price']) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events Performance -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Recent Auctions Performance</h2>

                    @if($recentAuctions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Auction</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lots</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sold</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Bids</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sales</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentAuctions as $auction)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-6 py-4">
                                                @if(in_array($auction->status, ['live', 'ended']))
                                                    <a href="{{ route('seller.auctions.live-report', $auction) }}" class="font-medium text-primary-600 hover:underline">
                                                        {{ $auction->title }}
                                                    </a>
                                                @else
                                                    <a href="{{ route('seller.auctions.show', $auction) }}" class="font-medium text-primary-600 hover:underline">
                                                        {{ $auction->title }}
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge badge-{{ $auction->status === 'live' ? 'danger' : ($auction->status === 'upcoming' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($auction->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $auction->lots->count() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $auction->lots->where('status', 'sold')->count() }}
                                                <span class="text-sm text-gray-500">
                                                    ({{ $auction->lots->count() > 0 ? round(($auction->lots->where('status', 'sold')->count() / $auction->lots->count()) * 100) : 0 }}%)
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $auction->lots->sum('total_bids') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                                {{ formatCurrency($auction->lots->where('status', 'sold')->sum('current_bid')) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $auction->start_time->format('M d, Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No auctions yet.</p>
                    @endif
                </div>
            </div>

            <!-- Top Performing Lots -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Top Performing Lots</h2>

                    @if($topLots->count() > 0)
                        <div class="space-y-4">
                            @foreach($topLots as $lot)
                                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    @if($lot->images->count() > 0)
                                        <img src="{{ $lot->images->first()->thumbnail_url }}"
                                             alt="{{ $lot->title }}"
                                             class="w-16 h-16 rounded object-cover">
                                    @endif
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $lot->title }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Lot #{{ $lot->lot_number }} in {{ $lot->auction->title }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Final Bid</div>
                                        <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                            {{ formatCurrency($lot->current_bid) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $lot->total_bids }} {{ Str::plural('bid', $lot->total_bids) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No sold lots yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
