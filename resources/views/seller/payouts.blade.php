<x-app-layout>
    <x-slot name="title">Payouts</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button & Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.accounting') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Payouts</h1>
            </div>

            <!-- Balance Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">Available Now</h3>
                        <div class="text-3xl font-bold text-green-900 dark:text-green-100">
                            {{ formatCurrency($stats['available_balance']) }}
                        </div>
                        <p class="text-xs text-green-700 dark:text-green-300 mt-1">Cleared funds</p>
                    </div>
                </div>
                <div class="card bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border-yellow-200 dark:border-yellow-800">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">Pending Clearance</h3>
                        <div class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">
                            {{ formatCurrency($stats['pending_clearance']) }}
                        </div>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">48-hour hold</p>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Minimum Payout</h3>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($stats['minimum_payout']) }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Pending Requests</h3>
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $stats['pending_count'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Payout Form -->
            @if($stats['can_request_payout'] && $stats['pending_count'] === 0)
                <div class="card mb-8" id="request-form">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Request Payout</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Withdrawals are processed within {{ config('platform.payout.payout_processing_days', 5) }} business days
                        </p>
                    </div>
                    <form action="{{ route('seller.payouts.request') }}" method="POST" class="p-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Amount -->
                            <div class="md:col-span-2">
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Amount to Withdraw <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">R</span>
                                    <input type="number"
                                           name="amount"
                                           id="amount"
                                           step="0.01"
                                           min="{{ $stats['minimum_payout'] }}"
                                           max="{{ $stats['available_balance'] }}"
                                           value="{{ old('amount', $stats['available_balance']) }}"
                                           class="input pl-8"
                                           required>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Available: {{ formatCurrency($stats['available_balance']) }}
                                    ({{ formatCurrency(config('platform.payout.minimum_balance', 100)) }} min. balance reserved)
                                    @if($stats['pending_clearance'] > 0)
                                        <br>
                                        <span class="text-yellow-600">+ {{ formatCurrency($stats['pending_clearance']) }} clearing (48hr hold)</span>
                                    @endif
                                </p>
                                @error('amount')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Bank Name -->
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Bank Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="bank_name"
                                       id="bank_name"
                                       value="{{ old('bank_name') }}"
                                       class="input"
                                       placeholder="e.g., FNB, Standard Bank"
                                       required>
                                @error('bank_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Account Holder -->
                            <div>
                                <label for="account_holder" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Account Holder Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="account_holder"
                                       id="account_holder"
                                       value="{{ old('account_holder', auth()->user()->name) }}"
                                       class="input"
                                       required>
                                @error('account_holder')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Account Number -->
                            <div>
                                <label for="account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Account Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="account_number"
                                       id="account_number"
                                       value="{{ old('account_number') }}"
                                       class="input"
                                       required>
                                @error('account_number')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Branch Code -->
                            <div>
                                <label for="branch_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Branch Code
                                </label>
                                <input type="text"
                                       name="branch_code"
                                       id="branch_code"
                                       value="{{ old('branch_code') }}"
                                       class="input"
                                       placeholder="Optional">
                                @error('branch_code')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Your banking details are secure and encrypted
                            </p>
                            <button type="submit" class="btn btn-primary">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            @elseif(!$stats['can_request_payout'])
                <div class="card mb-8">
                    <div class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Withdrawal Requirements Not Met</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            To withdraw you need at least <strong>{{ formatCurrency($stats['minimum_payout']) }}</strong> available
                            after keeping a <strong>{{ formatCurrency(config('platform.payout.minimum_balance', 100)) }}</strong> minimum balance in your account.
                        </p>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600 dark:text-gray-400">
                                Account balance: <strong>{{ formatCurrency($stats['credit_balance']) }}</strong>
                            </p>
                            @if($stats['pending_clearance'] > 0)
                                <p class="text-gray-600 dark:text-gray-400">
                                    Pending clearance (48hr hold): <strong class="text-yellow-600">-{{ formatCurrency($stats['pending_clearance']) }}</strong>
                                </p>
                            @endif
                            <p class="text-gray-600 dark:text-gray-400">
                                Minimum balance reserve: <strong class="text-red-600">-{{ formatCurrency(config('platform.payout.minimum_balance', 100)) }}</strong>
                            </p>
                            <p class="border-t border-gray-200 dark:border-gray-600 pt-2 text-gray-900 dark:text-gray-100">
                                Available to withdraw: <strong class="text-green-600">{{ formatCurrency($stats['available_balance']) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($stats['pending_count'] > 0)
                <div class="card mb-8">
                    <div class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Request Pending</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            You have a payout request pending approval. You can submit a new request once this one is processed.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Payout History -->
            <div class="card">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Payout History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Bank Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Reference
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($payouts as $payout)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $payout->created_at->format('d M Y') }}
                                        <div class="text-xs text-gray-500">{{ $payout->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency($payout->amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ $payout->bank_name }}</div>
                                        <div class="text-xs text-gray-500">•••{{ substr($payout->account_number, -4) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($payout->status === 'completed')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                Completed
                                            </span>
                                            @if($payout->processed_at)
                                                <div class="text-xs text-gray-500 mt-1">{{ $payout->processed_at->format('d M Y') }}</div>
                                            @endif
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $payout->reference ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-gray-500">No payout history yet</p>
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
