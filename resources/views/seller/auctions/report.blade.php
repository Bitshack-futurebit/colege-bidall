<x-app-layout>
    <x-slot name="title">{{ $auction->title }} - Financial Report</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ filter: 'all' }">
        <!-- Back link -->
        <div class="mb-6">
            <a href="{{ route('seller.auctions.index') }}" class="btn btn-outline">&larr; Back to My Auctions</a>
        </div>

        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">{{ $auction->title }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
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
                        &middot;
                        {{ $auction->start_time ? $auction->start_time->format('d M Y H:i') : 'Not scheduled' }}
                        @if($auction->end_time)
                            &ndash; {{ $auction->end_time->format('d M Y H:i') }}
                        @endif
                        @if($auction->buyers_premium_percentage > 0)
                            &middot; {{ $auction->buyers_premium_percentage }}% Buyer's Premium
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <a href="{{ route('auctions.show', $auction->slug) }}" class="btn btn-outline text-sm" target="_blank">View Public &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Lots</p>
                <p class="text-xl font-bold mt-1">{{ $summary['total_lots'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Sold</p>
                <p class="text-xl font-bold mt-1 text-green-600">{{ $summary['sold'] }}</p>
            </div>
            @if($summary['pending_confirmation'] > 0)
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Confirmation</p>
                <p class="text-xl font-bold mt-1 text-amber-600">{{ $summary['pending_confirmation'] }}</p>
            </div>
            @endif
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Unsold</p>
                <p class="text-xl font-bold mt-1 text-gray-500">{{ $summary['unsold'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Sales (Hammer)</p>
                <p class="text-xl font-bold mt-1 text-green-600">{{ formatCurrency($summary['total_sales']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Buyer's Premium</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($summary['total_buyers_premium']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Due</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($summary['total_due']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Lot Fees</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($summary['total_lot_fees']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Commission (1%)</p>
                <p class="text-xl font-bold mt-1">{{ formatCurrency($summary['total_commission']) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Net to You</p>
                <p class="text-xl font-bold mt-1 {{ $summary['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ formatCurrency($summary['net']) }}</p>
            </div>
        </div>

        <!-- Status Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-lg font-bold">Lots ({{ $lots->count() }})</h2>
                <select x-model="filter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm w-full sm:w-auto">
                    <option value="all">All Lots</option>
                    <option value="sold">Sold ({{ $summary['sold'] }})</option>
                    <option value="unsold_reserve">Unsold - Reserve Not Met ({{ $lots->filter(fn($l) => $l->status === 'unsold' && $l->total_bids > 0)->count() }})</option>
                    <option value="unsold_no_bids">Unsold - No Bids ({{ $lots->filter(fn($l) => $l->status === 'unsold' && $l->total_bids == 0)->count() }})</option>
                    <option value="pending_confirmation">Pending Confirmation ({{ $lots->filter(fn($l) => $l->status === 'pending_confirmation')->count() }})</option>
                    <option value="active">Active ({{ $summary['active'] }})</option>
                    <option value="pending">Pending ({{ $summary['pending'] }})</option>
                </select>
            </div>

            @if($lots->isEmpty())
                <div class="p-8 text-center text-gray-500">No lots in this auction.</div>
            @else
                <div style="max-height: 70vh; overflow: auto;">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-20">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Lot #</th>
                                <th class="px-3 py-2 text-left font-medium">Title</th>
                                <th class="px-3 py-2 text-right font-medium">Starting</th>
                                <th class="px-3 py-2 text-right font-medium">Reserve</th>
                                <th class="px-3 py-2 text-right font-medium">Hammer</th>
                                <th class="px-3 py-2 text-right font-medium">BP</th>
                                <th class="px-3 py-2 text-right font-medium">Total Due</th>
                                <th class="px-3 py-2 text-center font-medium">Bids</th>
                                <th class="px-3 py-2 text-right font-medium">Lot Fee</th>
                                <th class="px-3 py-2 text-right font-medium">Commission</th>
                                <th class="px-3 py-2 text-left font-medium">Payment</th>
                                <th class="px-3 py-2 text-left font-medium">Winner</th>
                            </tr>
                            <tr class="bg-gray-100 dark:bg-gray-700 font-semibold border-b-2 border-gray-300 dark:border-gray-600">
                                <td class="px-3 py-2" colspan="2">Totals (Sold)</td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-right text-green-600">{{ formatCurrency($summary['total_sales']) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($summary['total_buyers_premium']) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($summary['total_due']) }}</td>
                                <td class="px-3 py-2 text-center">{{ $lots->sum('total_bids') }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($summary['total_lot_fees']) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency($summary['total_commission']) }}</td>
                                <td class="px-3 py-2" colspan="2"></td>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($lots as $lot)
                                @php
                                    $fin = $lotFinancials[$lot->id];
                                    $isUnsoldReserve = $lot->status === 'unsold' && $lot->total_bids > 0;
                                    $isUnsoldNoBids = $lot->status === 'unsold' && $lot->total_bids == 0;

                                    $filterClass = match(true) {
                                        $lot->status === 'sold' => 'sold',
                                        $lot->status === 'pending_confirmation' => 'pending_confirmation',
                                        $isUnsoldReserve => 'unsold_reserve',
                                        $isUnsoldNoBids => 'unsold_no_bids',
                                        $lot->status === 'active' => 'active',
                                        default => 'pending',
                                    };

                                    $paymentDisplay = match($lot->payment_status) {
                                        'paid_platform' => '<span class="text-green-600">Paid Online</span>',
                                        'paid_offline' => '<span class="text-green-600">Paid Offline</span>',
                                        'awaiting_collection' => '<span class="text-amber-600">Awaiting Collection</span>',
                                        default => $lot->status === 'sold' ? '<span class="text-red-600">Pending</span>' : '-',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                    x-show="filter === 'all' || filter === '{{ $filterClass }}'">
                                    <td class="px-3 py-2 font-medium">{{ $lot->lot_number }}</td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('lots.show', $lot) }}" class="text-primary-600 hover:underline" target="_blank">
                                            {{ Str::limit($lot->title, 30) }}
                                        </a>
                                        <div class="text-xs mt-0.5">
                                            @if($lot->status === 'sold')
                                                <span class="text-green-600 font-medium">Sold</span>
                                            @elseif($lot->status === 'pending_confirmation')
                                                <span class="text-amber-600 font-medium">Pending Confirmation</span>
                                            @elseif($isUnsoldReserve)
                                                <span class="text-orange-600 font-medium">Unsold (reserve)</span>
                                            @elseif($isUnsoldNoBids)
                                                <span class="text-gray-500 font-medium">Unsold (no bids)</span>
                                            @elseif($lot->status === 'active')
                                                <span class="text-blue-600 font-medium">Active</span>
                                            @else
                                                <span class="text-gray-400 font-medium">{{ ucfirst($lot->status) }}</span>
                                            @endif
                                            @if($lot->trashed())
                                                <span class="text-red-400 font-medium ml-1">(deleted)</span>
                                            @endif
                                        </div>
                                        @if($lot->status === 'pending_confirmation')
                                            <div class="flex gap-1 mt-1">
                                                <form action="{{ route('seller.lots.confirm-sale', $lot) }}" method="POST" onsubmit="return confirm('Confirm sale of Lot #{{ $lot->lot_number }}?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-600 hover:bg-green-700 text-white">Confirm</button>
                                                </form>
                                                <form action="{{ route('seller.lots.reject-sale', $lot) }}" method="POST" onsubmit="return confirm('Reject sale of Lot #{{ $lot->lot_number }}? This will mark it as unsold.')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-600 hover:bg-red-700 text-white">Reject</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ formatCurrency($lot->starting_bid) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">
                                        {{ $lot->reserve_price ? formatCurrency($lot->reserve_price) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium {{ $lot->isSold() ? 'text-green-600' : '' }}">
                                        {{ $fin['hammer'] > 0 ? formatCurrency($fin['hammer']) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">
                                        {{ $fin['buyers_premium'] > 0 ? formatCurrency($fin['buyers_premium']) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium">
                                        {{ $fin['total_due'] > 0 ? formatCurrency($fin['total_due']) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $lot->total_bids }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ formatCurrency($fin['lot_fee']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">
                                        {{ $fin['commission'] > 0 ? formatCurrency($fin['commission']) : '-' }}
                                    </td>
                                    <td class="px-3 py-2">{!! $paymentDisplay !!}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($lot->winningBidder)
                                            {{ $lot->winningBidder->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
