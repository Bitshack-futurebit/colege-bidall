<x-app-layout>
    <x-slot name="title">Credit Ledger</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.accounting') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Credit Ledger</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Current balance: <span class="font-semibold {{ $auctioneer->credit_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ formatCurrency($auctioneer->credit_balance) }}</span>
                        &mdash; {{ $creditTransactions->total() }} transactions total
                    </p>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="card p-4 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                        <select name="type" class="input text-sm">
                            <option value="">All types</option>
                            <option value="purchase"       {{ request('type') === 'purchase'       ? 'selected' : '' }}>Purchase (credit buy)</option>
                            <option value="lot_live"       {{ request('type') === 'lot_live'       ? 'selected' : '' }}>Lot fee (go live)</option>
                            <option value="lot_close"      {{ request('type') === 'lot_close'      ? 'selected' : '' }}>Commission (auction end)</option>
                            <option value="sale_income"    {{ request('type') === 'sale_income'    ? 'selected' : '' }}>Sale income (bidder paid)</option>
                            <option value="payout"         {{ request('type') === 'payout'         ? 'selected' : '' }}>Payout (withdrawal)</option>
                            <option value="adjustment"     {{ request('type') === 'adjustment'     ? 'selected' : '' }}>Admin adjustment</option>
                            <option value="refund"         {{ request('type') === 'refund'         ? 'selected' : '' }}>Refund</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary text-sm">Filter</button>
                    @if(request('type'))
                        <a href="{{ route('seller.credit-ledger') }}" class="btn btn-outline text-sm">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Ledger table -->
            <div class="card">
                @if($creditTransactions->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($creditTransactions as $tx)
                                    @php
                                        $typeLabels = [
                                            'purchase'     => ['label' => 'Credit Purchase',     'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
                                            'lot_live'     => ['label' => 'Lot Fee',              'color' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'],
                                            'lot_close'    => ['label' => 'Commission',           'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'],
                                            'lot_creation' => ['label' => 'Lot Creation',         'color' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                                            'sale_income'  => ['label' => 'Sale Income',          'color' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                                            'payout'       => ['label' => 'Payout',               'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'],
                                            'adjustment'   => ['label' => 'Admin Adjustment',     'color' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300'],
                                            'refund'       => ['label' => 'Refund',               'color' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300'],
                                        ];
                                        $meta = $typeLabels[$tx->type] ?? ['label' => $tx->type, 'color' => 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $tx->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $meta['color'] }}">
                                                {{ $meta['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-right whitespace-nowrap {{ $tx->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $tx->amount >= 0 ? '+' : '' }}{{ formatCurrency($tx->amount) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {{ formatCurrency($tx->balance_after) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $tx->description }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $creditTransactions->links() }}
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        No transactions found
                        @if(request('type'))
                            for this type. <a href="{{ route('seller.credit-ledger') }}" class="text-primary-600 hover:underline">Clear filter</a>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
