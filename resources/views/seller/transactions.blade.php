<x-app-layout>
    <x-slot name="title">Transactions - Auctioneer Dashboard</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold">Transaction History</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">View all your payment transactions</p>
            </div>
        </div>

        <div class="card">
            <div class="p-6">
                @if($transactions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4">Date</th>
                                    <th class="text-left py-3 px-4">Type</th>
                                    <th class="text-left py-3 px-4">Amount</th>
                                    <th class="text-left py-3 px-4">Status</th>
                                    <th class="text-left py-3 px-4">Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="py-3 px-4">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                        <td class="py-3 px-4 capitalize">{{ str_replace('_', ' ', $transaction->type) }}</td>
                                        <td class="py-3 px-4 font-semibold">{{ formatCurrency($transaction->amount) }}</td>
                                        <td class="py-3 px-4">
                                            @if($transaction->status === 'completed')
                                                <span class="badge badge-success">Completed</span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @else
                                                <span class="badge badge-danger">{{ ucfirst($transaction->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $transaction->payment_reference ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $transactions->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">No transactions yet</p>
                        <a href="{{ route('seller.credits') }}" class="btn btn-primary">Purchase Credits</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
