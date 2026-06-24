<x-app-layout>
    <x-slot name="title">{{ $auctioneer->business_name }} - Financial Report</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('admin.auctioneers.index') }}" class="btn btn-outline">&larr; Back</a>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.auctioneers.credit-ledger', $auctioneer) }}" class="btn btn-outline">Credit Ledger</a>
                <a href="{{ route('admin.auctioneers.settings', $auctioneer) }}" class="btn btn-primary">Auctioneer Settings</a>
            </div>
        </div>

        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">#{{ $auctioneer->id }} {{ $auctioneer->business_name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $auctioneer->user->name }} &middot;
                        Member since {{ $auctioneer->user->created_at->format('M d, Y') }} &middot;
                        <span class="{{ $auctioneer->is_activated ? 'text-green-600' : 'text-red-600' }}">
                            {{ $auctioneer->is_activated ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Credit Balance</p>
                    <p class="text-2xl font-bold {{ $auctioneer->credit_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ formatCurrency($auctioneer->credit_balance) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- White-Label Branding Summary -->
        @if($auctioneer->brand_primary_color)
            <div class="card p-6 mb-6">
                <div class="flex items-start justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-3">
                            <h3 class="text-lg font-semibold">White-Label Branding</h3>
                            @if($auctioneer->isWhiteLabel())
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded" style="background-color: {{ $auctioneer->brand_primary_color }}20; color: {{ $auctioneer->brand_primary_color }};">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $auctioneer->brand_primary_color }};"></span>
                                    Active
                                </span>
                            @else
                                <span class="badge badge-warning">Configured but Disabled</span>
                            @endif
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <dt class="text-gray-500 w-32">Primary colour</dt>
                                <dd class="flex items-center gap-2">
                                    <span class="inline-block w-6 h-6 rounded border border-gray-300" style="background-color: {{ $auctioneer->brand_primary_color }}"></span>
                                    <code class="text-xs">{{ $auctioneer->brand_primary_color }}</code>
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="text-gray-500 w-32">Secondary colour</dt>
                                <dd class="flex items-center gap-2">
                                    @if($auctioneer->brand_secondary_color)
                                        <span class="inline-block w-6 h-6 rounded border border-gray-300" style="background-color: {{ $auctioneer->brand_secondary_color }}"></span>
                                        <code class="text-xs">{{ $auctioneer->brand_secondary_color }}</code>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="text-gray-500 w-32">Favicon</dt>
                                <dd>
                                    @if($auctioneer->brand_favicon)
                                        <img src="{{ Storage::url($auctioneer->brand_favicon) }}" alt="favicon" class="w-6 h-6 rounded">
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="text-gray-500 w-32">Public URL</dt>
                                <dd>
                                    <a href="{{ route('auctioneer.show', $auctioneer->slug) }}?brand=1" target="_blank" class="text-primary-600 hover:underline text-xs">
                                        /auctioneer/{{ $auctioneer->slug }} &rarr;
                                    </a>
                                </dd>
                            </div>
                        </dl>
                        @if($auctioneer->brand_hero_text)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500 mb-1">Hero text</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $auctioneer->brand_hero_text }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Auctions</p>
                <p class="text-xl font-bold mt-1">{{ $stats['total_auctions'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Lots Sold / Total</p>
                <p class="text-xl font-bold mt-1">{{ $stats['total_sold'] }} / {{ $stats['total_lots'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Sales</p>
                <p class="text-xl font-bold mt-1 text-green-600">{{ formatCurrency($stats['total_sales']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Lot Fees Paid</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($stats['total_lot_fees']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Commissions Paid</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($stats['total_commissions']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Payouts</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($stats['total_payouts']) }}</p>
            </div>
        </div>

        <!-- Auction Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold">Auctions</h2>
            </div>

            @if($auctions->isEmpty())
                <div class="p-8 text-center text-gray-500">No auctions yet.</div>
            @else
                <div style="max-height: 70vh; overflow: auto;">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-20">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Auction</th>
                                <th class="px-3 py-2 text-center font-medium">Status</th>
                                <th class="px-3 py-2 text-center font-medium">Date</th>
                                <th class="px-3 py-2 text-center font-medium">Lots</th>
                                <th class="px-3 py-2 text-center font-medium">Sold</th>
                                <th class="px-3 py-2 text-center font-medium" title="Unsold with no bids">No Bids</th>
                                <th class="px-3 py-2 text-center font-medium" title="Unsold but had bids (reserve not met)">Reserve</th>
                                <th class="px-3 py-2 text-center font-medium">Sell %</th>
                                <th class="px-3 py-2 text-right font-medium">Lot Fees</th>
                                <th class="px-3 py-2 text-right font-medium">Sales</th>
                                <th class="px-3 py-2 text-right font-medium">Avg Sale</th>
                                <th class="px-3 py-2 text-right font-medium">Commission</th>
                                <th class="px-3 py-2 text-right font-medium">Net</th>
                            </tr>
                            <tr class="bg-gray-100 dark:bg-gray-700 font-semibold border-b-2 border-gray-300 dark:border-gray-600">
                                <td class="px-3 py-2" colspan="3">Totals</td>
                                <td class="px-3 py-2 text-center">{{ $stats['total_lots'] }}</td>
                                <td class="px-3 py-2 text-center text-green-600">{{ $stats['total_sold'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $auctions->sum('lots_unsold_no_bids_count') }}</td>
                                <td class="px-3 py-2 text-center">{{ $auctions->sum('lots_unsold_reserve_count') }}</td>
                                <td class="px-3 py-2 text-center">
                                    {{ $stats['total_lots'] > 0 ? round(($stats['total_sold'] / $stats['total_lots']) * 100, 1) : 0 }}%
                                </td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($stats['total_lot_fees']) }}</td>
                                <td class="px-3 py-2 text-right text-green-600">{{ formatCurrency($stats['total_sales']) }}</td>
                                <td class="px-3 py-2 text-right">
                                    {{ $stats['total_sold'] > 0 ? formatCurrency($stats['total_sales'] / $stats['total_sold']) : '-' }}
                                </td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($stats['total_commissions']) }}</td>
                                <td class="px-3 py-2 text-right {{ ($stats['total_sales'] - $stats['total_commissions']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ formatCurrency($stats['total_sales'] - $stats['total_commissions']) }}
                                </td>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($auctions as $auction)
                                @php $fin = $auctionFinancials[$auction->id]; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.auctioneers.auction-report', [$auctioneer, $auction]) }}" class="text-primary-600 hover:underline font-medium">
                                            #{{ $auction->id }} {{ Str::limit($auction->title, 35) }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                'upcoming' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                                'live' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                                'ended' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                            ];
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$auction->status] ?? '' }}">
                                            {{ ucfirst($auction->status) }}
                                        </span>
                                        @if($auction->auction_type === 'dutch')
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Dutch</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center text-gray-500 whitespace-nowrap">
                                        {{ $auction->start_time ? $auction->start_time->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $auction->lots_count }}</td>
                                    <td class="px-3 py-2 text-center font-medium {{ $auction->lots_sold_count > 0 ? 'text-green-600' : '' }}">
                                        {{ $auction->lots_sold_count }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-gray-500">{{ $auction->lots_unsold_no_bids_count }}</td>
                                    <td class="px-3 py-2 text-center text-gray-500">{{ $auction->lots_unsold_reserve_count }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($auction->lots_count > 0)
                                            <span class="{{ $fin['sell_through'] >= 50 ? 'text-green-600' : 'text-gray-500' }}">
                                                {{ $fin['sell_through'] }}%
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ formatCurrency($fin['lot_fees']) }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ ($auction->total_sales ?? 0) > 0 ? 'text-green-600' : '' }}">
                                        {{ formatCurrency($auction->total_sales ?? 0) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">
                                        {{ $fin['avg_sale'] > 0 ? formatCurrency($fin['avg_sale']) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ formatCurrency($fin['commission']) }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $fin['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ formatCurrency($fin['net']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Bottom Links -->
        <div class="mt-6 flex items-center space-x-3">
            <a href="{{ route('admin.auctioneers.settings', $auctioneer) }}" class="btn btn-primary">Auctioneer Settings &rarr;</a>
            <a href="{{ route('admin.auctioneers.credit-ledger', $auctioneer) }}" class="btn btn-outline">Credit Ledger &rarr;</a>
        </div>
    </div>
</x-app-layout>
