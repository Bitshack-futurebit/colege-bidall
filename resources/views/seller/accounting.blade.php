<x-app-layout>
    <x-slot name="title">Accounting</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button & Header -->
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Accounting</h1>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Account Balance -->
                <div class="card bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Credit Balance</h3>
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                            {{ formatCurrency($stats['credit_balance']) }}
                        </div>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                            <a href="{{ route('seller.credits') }}" class="hover:underline">Top up credits →</a>
                        </p>
                    </div>
                </div>

                <!-- Lot Fees Paid -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Lot Fees</h3>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($stats['lot_fees_paid']) }}
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Charged when auction goes live</p>
                    </div>
                </div>

                <!-- Commission Paid -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Commission (1%)</h3>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($stats['commissions_paid']) }}
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Deducted on sold lots</p>
                    </div>
                </div>
            </div>

            <!-- Total Fees -->
            <div class="card mb-8">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Total Platform Fees</h3>
                            <p class="text-sm text-gray-500 mt-1">Lot fees + 1% commission on sold lots</p>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ formatCurrency($stats['lot_fees_paid'] + $stats['commissions_paid']) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Ledger Link -->
            <div>
                <a href="{{ route('seller.credit-ledger') }}" class="btn btn-outline w-full sm:w-auto">
                    View Full Credit Ledger →
                </a>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">All credit purchases, lot fees, and commissions in one place.</p>
            </div>
        </div>
    </div>
</x-app-layout>
