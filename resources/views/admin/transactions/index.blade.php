<x-app-layout>
    <x-slot name="title">Transactions</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Transactions</h1>

            <!-- Filter Bar -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex gap-4">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by user or reference..."
                               class="input flex-1">

                        <select name="type" class="input">
                            <option value="">All Types</option>
                            <option value="activation_fee" {{ request('type') === 'activation_fee' ? 'selected' : '' }}>Activation Fee</option>
                            <option value="credit_purchase" {{ request('type') === 'credit_purchase' ? 'selected' : '' }}>Credit Purchase</option>
                            <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Auction Deposit</option>
                            <option value="lot_payment" {{ request('type') === 'lot_payment' ? 'selected' : '' }}>Lot Payment</option>
                            <option value="deposit_refund" {{ request('type') === 'deposit_refund' ? 'selected' : '' }}>Deposit Refund</option>
                            <option value="platform_fee" {{ request('type') === 'platform_fee' ? 'selected' : '' }}>Platform Fee</option>
                        </select>

                        <select name="status" class="input">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'type', 'status']))
                            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            @if($transactions->count() > 0)
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($transactions as $transaction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-mono text-gray-900 dark:text-gray-100">
                                                {{ $transaction->reference ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $transaction->user->name }}</div>
                                                <div class="text-gray-500">{{ $transaction->user->email }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaction->amount > 0 ? '+' : '' }}{{ formatCurrency(abs($transaction->amount)) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-outline">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div class="card">
                        <div class="p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Transactions</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($transactions->total()) }}</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Completed Value</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency($summary['completed_value']) }}
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Pending Value</div>
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ formatCurrency($summary['pending_value']) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <p class="text-gray-600 dark:text-gray-400">No transactions found.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
