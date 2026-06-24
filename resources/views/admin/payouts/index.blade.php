<x-app-layout>
    <x-slot name="title">Manage Payouts</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Payout Management</h1>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card border-l-4 border-yellow-500">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Pending Requests</h3>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_count'] }}</div>
                        <p class="text-sm text-gray-500 mt-1">{{ formatCurrency($stats['pending_amount']) }}</p>
                    </div>
                </div>
                <div class="card border-l-4 border-blue-500">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Processing</h3>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['processing_count'] }}</div>
                    </div>
                </div>
                <div class="card border-l-4 border-green-500">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Completed</h3>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed_count'] }}</div>
                        <p class="text-sm text-gray-500 mt-1">{{ formatCurrency($stats['completed_amount']) }}</p>
                    </div>
                </div>
                <div class="card border-l-4 border-primary-500">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Liability</h3>
                        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                            {{ formatCurrency($stats['total_liability']) }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">All auctioneers' balances</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-8">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select name="status" class="input" onchange="this.form.submit()">
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                            <select name="sort" class="input" onchange="this.form.submit()">
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>Highest Amount</option>
                                <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>Lowest Amount</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payouts Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Auctioneer
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Bank Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($payouts as $payout)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="text-gray-900 dark:text-gray-100">{{ $payout->created_at->format('d M Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $payout->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $payout->auctioneer->business_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $payout->auctioneer->user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency($payout->amount) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Balance: {{ formatCurrency($payout->auctioneer->payout_balance) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-gray-900 dark:text-gray-100">{{ $payout->bank_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $payout->account_holder }}</div>
                                        <div class="text-xs text-gray-500">•••{{ substr($payout->account_number, -4) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($payout->status === 'completed')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                Completed
                                            </span>
                                        @elseif($payout->status === 'pending')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                Pending
                                            </span>
                                        @elseif($payout->status === 'processing')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                Processing
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ ucfirst($payout->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('admin.payouts.show', $payout) }}" class="btn btn-sm btn-primary">
                                            @if($payout->status === 'pending')
                                                Process
                                            @else
                                                View
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-gray-500">No payout requests found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($payouts->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                        {{ $payouts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
