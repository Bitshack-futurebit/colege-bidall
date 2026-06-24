<x-app-layout>
    <x-slot name="title">Process Payout</x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('admin.payouts.index') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Payout Request</h1>
                @if($payout->status === 'pending')
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                        Pending
                    </span>
                @elseif($payout->status === 'completed')
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Completed
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Payout Details -->
                    <div class="card">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Payout Details</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Requested</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $payout->created_at->format('d M Y H:i') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency($payout->amount) }}
                                </span>
                            </div>
                            @if($payout->status === 'completed')
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Processed</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $payout->processed_at->format('d M Y H:i') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Processed By</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $payout->processedBy->name }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Reference</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $payout->reference }}
                                    </span>
                                </div>
                            @endif
                            @if($payout->notes)
                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Notes:</span>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $payout->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div class="card">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Bank Details</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Bank Name</span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $payout->bank_name }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Account Holder</span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $payout->account_holder }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Account Number</span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100 font-mono">{{ $payout->account_number }}</p>
                            </div>
                            @if($payout->branch_code)
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Branch Code</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 font-mono">{{ $payout->branch_code }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Process/Reject Forms -->
                    @if($payout->status === 'pending')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Process Form -->
                            <div class="card border-2 border-green-200 dark:border-green-800">
                                <div class="p-6 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800">
                                    <h3 class="font-semibold text-green-900 dark:text-green-100">Approve Payout</h3>
                                </div>
                                <form action="{{ route('admin.payouts.process', $payout) }}" method="POST" class="p-6">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Bank Reference <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text"
                                                   name="reference"
                                                   class="input"
                                                   placeholder="e.g., TXN123456789"
                                                   required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Notes (Optional)
                                            </label>
                                            <textarea name="notes"
                                                      class="input"
                                                      rows="3"
                                                      placeholder="Additional processing notes..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-full">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Approve & Process
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Reject Form -->
                            <div class="card border-2 border-red-200 dark:border-red-800">
                                <div class="p-6 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800">
                                    <h3 class="font-semibold text-red-900 dark:text-red-100">Reject Payout</h3>
                                </div>
                                <form action="{{ route('admin.payouts.reject', $payout) }}" method="POST" class="p-6"
                                      onsubmit="return confirm('Are you sure you want to reject this payout request?');">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Reason <span class="text-red-500">*</span>
                                            </label>
                                            <textarea name="notes"
                                                      class="input"
                                                      rows="3"
                                                      placeholder="Reason for rejection..."
                                                      required></textarea>
                                        </div>
                                        <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white w-full">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Reject Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Recent Sales -->
                    <div class="card">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Recent Sales</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Last 10 sales from this auctioneer</p>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($salesRecords as $sale)
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $sale->lot->title }}</p>
                                            <p class="text-xs text-gray-500">{{ $sale->lot->auction->title }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-green-600 dark:text-green-400">
                                                {{ formatCurrency($sale->net_to_auctioneer) }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $sale->paid_date->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 flex gap-4">
                                        <span>Sale: {{ formatCurrency($sale->sale_price) }}</span>
                                        <span class="text-red-600">-{{ formatCurrency($sale->payment_gateway_fee) }} fee</span>
                                        <span class="text-red-600">-{{ formatCurrency($sale->platform_commission) }} (1%)</span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-gray-500">
                                    <p>No recent sales</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Auctioneer Info -->
                    <div class="card">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Auctioneer</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $payout->auctioneer->business_name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payout->auctioneer->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $payout->auctioneer->user->email }}</p>
                            </div>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('admin.auctioneers.show', $payout->auctioneer) }}" class="text-sm text-primary-600 hover:text-primary-700">
                                    View Full Profile →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Info -->
                    <div class="card">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Financial Summary</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Current Balance</span>
                                <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ formatCurrency($payout->auctioneer->payout_balance) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Sales</span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency($payout->auctioneer->total_sales) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Payouts</span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency($payout->auctioneer->total_payouts_received) }}
                                </p>
                            </div>
                            @if($payout->status === 'pending')
                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">After Payout</span>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency($payout->auctioneer->payout_balance - $payout->amount) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Warning -->
                    @if($payout->status === 'pending' && $payout->amount > $payout->auctioneer->payout_balance)
                        <div class="card border-2 border-red-500">
                            <div class="p-6">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-red-900 dark:text-red-100">Insufficient Balance</p>
                                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                            Auctioneer's balance is insufficient for this payout.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
