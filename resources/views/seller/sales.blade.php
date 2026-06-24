<x-app-layout>
    <x-slot name="title">Sales History</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button & Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.accounting') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Sales History</h1>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Sales</h3>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($totals['total_sales']) }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Gateway Fees</h3>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            -{{ formatCurrency($totals['total_fees']) }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Platform (1%)</h3>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            -{{ formatCurrency($totals['total_commission']) }}
                        </div>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">Net Received</h3>
                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                            {{ formatCurrency($totals['total_net']) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Sorting -->
            <div class="card mb-8">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                            <input type="date" name="from" value="{{ request('from') }}"
                                   class="input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                            <input type="date" name="to" value="{{ request('to') }}"
                                   class="input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                            <select name="sort" class="input">
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>Highest Value</option>
                                <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>Lowest Value</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn btn-primary w-full">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Lot & Event
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Sale Price
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Gateway Fee
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Platform (1%)
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Net to You
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($salesRecords as $sale)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $sale->paid_date->format('d M Y') }}
                                        <div class="text-xs text-gray-500">{{ $sale->paid_date->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $sale->lot->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $sale->lot->auction->title }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency($sale->sale_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">
                                        -{{ formatCurrency($sale->payment_gateway_fee) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">
                                        -{{ formatCurrency($sale->platform_commission) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency($sale->net_to_auctioneer) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-gray-500">No sales records found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($salesRecords->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                        {{ $salesRecords->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
