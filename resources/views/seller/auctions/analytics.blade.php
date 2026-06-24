<x-app-layout>
    <x-slot name="title">Auction Analytics - {{ $auction->title }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="{{ route('seller.auctions.show', $auction) }}" class="btn btn-outline mb-2">← Back to Auction</a>
                <h1 class="text-3xl font-bold">{{ $auction->title }} - Analytics</h1>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Lots</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $auction->lots->count() }}
                </div>
            </div>

            <div class="card p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Bids</div>
                <div class="text-3xl font-bold text-primary-600">
                    {{ $auction->lots->sum('total_bids') }}
                </div>
            </div>

            <div class="card p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Items Sold</div>
                <div class="text-3xl font-bold text-green-600">
                    {{ $auction->lots->where('status', 'sold')->count() }}
                </div>
            </div>

            <div class="card p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Value</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ formatCurrency($auction->lots->where('status', 'sold')->sum('current_bid')) }}
                </div>
            </div>
        </div>

        <!-- Top Performing Lots -->
        <div class="card mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-6">Top Performing Lots</h2>

                @php
                    $topLots = $auction->lots->sortByDesc('total_bids')->take(10);
                @endphp

                @if($topLots->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4">Lot #</th>
                                    <th class="text-left py-3 px-4">Title</th>
                                    <th class="text-left py-3 px-4">Starting Bid</th>
                                    <th class="text-left py-3 px-4">Current Bid</th>
                                    <th class="text-left py-3 px-4">Total Bids</th>
                                    <th class="text-left py-3 px-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topLots as $lot)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-3 px-4">{{ $lot->lot_number }}</td>
                                        <td class="py-3 px-4">{{ $lot->title }}</td>
                                        <td class="py-3 px-4">{{ formatCurrency($lot->starting_bid) }}</td>
                                        <td class="py-3 px-4 font-semibold text-primary-600">
                                            {{ formatCurrency($lot->current_bid ?? $lot->starting_bid) }}
                                        </td>
                                        <td class="py-3 px-4">{{ $lot->total_bids }}</td>
                                        <td class="py-3 px-4">
                                            <span class="status-{{ $lot->status }}">{{ ucfirst($lot->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-gray-600 dark:text-gray-400 py-8">No lots yet</p>
                @endif
            </div>
        </div>

        <!-- Bidding Activity -->
        <div class="card">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-6">Auction Performance</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold mb-4">Lot Status Distribution</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span>Live</span>
                                <span class="font-semibold">{{ $auction->lots->where('status', 'live')->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Sold</span>
                                <span class="font-semibold">{{ $auction->lots->where('status', 'sold')->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Unsold</span>
                                <span class="font-semibold">{{ $auction->lots->where('status', 'unsold')->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Pending</span>
                                <span class="font-semibold">{{ $auction->lots->where('status', 'pending')->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-4">Bidding Statistics</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span>Average Bids per Lot</span>
                                <span class="font-semibold">
                                    {{ $auction->lots->count() > 0 ? round($auction->lots->sum('total_bids') / $auction->lots->count(), 1) : 0 }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Highest Bid</span>
                                <span class="font-semibold">{{ formatCurrency($auction->lots->max('current_bid') ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Average Sale Price</span>
                                <span class="font-semibold">
                                    @php
                                        $soldLots = $auction->lots->where('status', 'sold');
                                        $avgPrice = $soldLots->count() > 0 ? $soldLots->avg('current_bid') : 0;
                                    @endphp
                                    {{ formatCurrency($avgPrice) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
