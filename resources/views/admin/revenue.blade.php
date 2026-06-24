<x-app-layout>
    <x-slot name="title">Revenue Dashboard</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Revenue Dashboard</h1>

            <!-- Date Filter -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex gap-4">
                        <select name="period" class="input" onchange="this.form.submit()">
                            <option value="today" {{ request('period', 'all') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('period', 'all') === 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('period', 'all') === 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="year" {{ request('period', 'all') === 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ request('period', 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                        </select>
                    </form>
                </div>
            </div>

            @php
                // Compute platform_fees directly in view — controller changes unreliable on this server
                $commQuery = \App\Models\CreditTransaction::where('type', 'lot_close');
                $_period = request('period', 'all');
                if ($_period === 'today')  $commQuery->whereDate('created_at', today());
                elseif ($_period === 'week')  $commQuery->where('created_at', '>=', now()->startOfWeek());
                elseif ($_period === 'month') $commQuery->where('created_at', '>=', now()->startOfMonth());
                elseif ($_period === 'year')  $commQuery->where('created_at', '>=', now()->startOfYear());
                $revenue['platform_fees'] = abs($commQuery->sum('amount'));
                $revenue['total'] = ($revenue['lot_fees'] ?? 0) + $revenue['platform_fees'];
            @endphp

            <!-- Revenue Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ formatCurrency($revenue['total']) }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Platform Fees</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($revenue['platform_fees']) }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            From {{ $revenue['lots_sold'] }} lots sold
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Lot Fees</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($revenue['lot_fees']) }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            {{ $revenue['lots_created'] }} lots created
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- By Revenue Source -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Revenue by Source</h2>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Platform Fees ({{ config('platform.pricing.platform_fee_percent') }}%)</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency($revenue['platform_fees']) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $revenue['total'] > 0 ? ($revenue['platform_fees'] / $revenue['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between mb-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Lot Fees</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency($revenue['lot_fees']) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $revenue['total'] > 0 ? ($revenue['lot_fees'] / $revenue['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Auctioneers -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Top Revenue Generators</h2>
                        @if($topAuctioneers->count() > 0)
                            <div class="space-y-4">
                                @foreach($topAuctioneers as $auctioneer)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            @if($auctioneer->logo)
                                                <img src="{{ Storage::url($auctioneer->logo) }}" alt="{{ $auctioneer->business_name }}" class="w-10 h-10 rounded-full object-cover">
                                            @else
                                                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                                    <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                                        {{ substr($auctioneer->business_name, 0, 1) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $auctioneer->business_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $auctioneer->total_lots }} lots sold</div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-green-600 dark:text-green-400">
                                                {{ formatCurrency($auctioneer->total_revenue) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No data yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Revenue by Auctioneer -->
            <div class="card mb-8" x-data="{ search: '' }">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Revenue by Auctioneer</h2>
                        <input x-model="search" type="text" placeholder="Search auctioneer..."
                               class="input w-full sm:w-64 text-sm">
                    </div>
                    @if(isset($auctioneerRevenue) && $auctioneerRevenue->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auctioneer</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lot Fees</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Commissions (1%)</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ledger</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($auctioneerRevenue ?? [] as $auctioneer)
                                        <tr x-show="search === '' || '{{ strtolower($auctioneer->business_name) }}'.includes(search.toLowerCase())"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $auctioneer->business_name }}
                                                <div class="text-xs text-gray-500">{{ $auctioneer->user->email }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
                                                {{ formatCurrency($auctioneer->lot_fees_revenue) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
                                                {{ formatCurrency($auctioneer->commissions_revenue) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-green-600 dark:text-green-400">
                                                {{ formatCurrency($auctioneer->total_revenue) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('admin.auctioneers.credit-ledger', $auctioneer) }}"
                                                   class="text-xs text-primary-600 hover:underline">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No revenue data yet.</p>
                    @endif
                </div>
            </div>

            <!-- Recent High-Value Transactions -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Recent High-Value Transactions</h2>
                    @if($highValueTransactions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($highValueTransactions as $transaction)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $transaction->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                {{ $transaction->user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                {{ formatCurrency($transaction->amount) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No transactions yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
