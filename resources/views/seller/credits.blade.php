<x-app-layout>
    <x-slot name="title">Buy Credits</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Buy Credits</h1>
            </div>

            @if($auctioneer->credit_balance < config('platform.pricing.minimum_deposit', 100))
            <!-- Minimum Deposit Notice -->
            <div class="card bg-gradient-to-r from-primary-500 to-primary-600 text-white mb-8">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Minimum Deposit Required</h3>
                            <p class="text-primary-100 mb-4">
                                To start creating events, please deposit a minimum of <strong>{{ formatCurrency(config('platform.pricing.minimum_deposit', 100)) }}</strong> in credits.
                            </p>
                            <div class="text-sm text-primary-100">
                                <strong>What you can create with R100:</strong>
                                <ul class="mt-2 space-y-1 ml-4">
                                    <li>• 100 Basic lots (1 image each)</li>
                                    <li>• 20 Pro lots (5 images each)</li>
                                    <li>• 5 Premium lots (20 images each)</li>
                                    <li>• Or any combination totaling R100</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Current Balance -->
            <div class="card mb-8 bg-gradient-to-br from-primary-500 to-primary-600 text-white">
                <div class="p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90 mb-2">Current Credit Balance</div>
                            <div class="text-5xl font-bold mb-2">{{ formatCurrency($auctioneer->credit_balance) }}</div>
                            <a href="{{ route('seller.transactions') }}" class="text-sm opacity-90 hover:opacity-100 inline-flex items-center gap-1">
                                View transaction history
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <svg class="w-24 h-24 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Purchase Credits Form -->
            <div class="max-w-2xl mx-auto mb-8">
                <div class="card">
                    <div class="p-8">
                        <div class="text-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Purchase Credits</h2>
                            <p class="text-gray-600 dark:text-gray-400">Pay-as-you-go credit system. Credits are deducted when you create lots.</p>
                        </div>

                        <form method="POST" action="{{ route('seller.credits.purchase') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount (ZAR)</label>
                                <input type="number"
                                       name="amount"
                                       min="{{ config('platform.pricing.minimum_deposit', 100) }}"
                                       step="10"
                                       placeholder="Enter amount (min R{{ config('platform.pricing.minimum_deposit', 100) }})"
                                       required
                                       class="input w-full text-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Minimum purchase: {{ formatCurrency(config('platform.pricing.minimum_deposit', 100)) }}</p>
                            </div>

                            @php($blinkAvailable = !empty(config('services.blink.api_key')) && !empty(config('services.blink.wallet_id')))
                            @if($blinkAvailable)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment method</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment_method" value="payfast" class="peer sr-only" checked>
                                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center peer-checked:border-primary-500 peer-checked:ring-2 peer-checked:ring-primary-500 transition">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">Card</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">PayFast · ZAR</div>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment_method" value="blink" class="peer sr-only">
                                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center peer-checked:border-primary-500 peer-checked:ring-2 peer-checked:ring-primary-500 transition">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">Bitcoin</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Lightning</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="payment_method" value="payfast">
                            @endif

                            <button type="submit" class="btn btn-primary w-full text-lg py-3">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Proceed to Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- How Credits Work -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            How Credits Work
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">When lots go live</p>
                                <p class="text-gray-600 dark:text-gray-400">Credits are deducted based on tier:</p>
                                <ul class="mt-2 space-y-1 text-gray-600 dark:text-gray-400">
                                    <li>• Basic (1 image): {{ formatPrice('platform.pricing.lot_fees.basic') }}</li>
                                    <li>• Pro (2-5 images): {{ formatPrice('platform.pricing.lot_fees.pro') }}</li>
                                    <li>• Premium (6+ images): {{ formatPrice('platform.pricing.lot_fees.premium') }}</li>
                                </ul>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">When lots sell</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ config('platform.pricing.platform_fee_percent') }}% platform fee deducted from your credits</p>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">Unsold lots</p>
                                <p class="text-gray-600 dark:text-gray-400">No additional fees - you only pay the initial lot fee</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Example Calculation -->
                <div class="card bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Example Calculation
                        </h3>
                        <div class="space-y-3 text-sm text-blue-800 dark:text-blue-200">
                            <div>
                                <p class="font-medium">Pro lot with 3 images</p>
                                <p>Sells for {{ formatCurrency(1000) }}</p>
                            </div>
                            <div class="border-t border-blue-200 dark:border-blue-700 pt-3 space-y-2">
                                <div class="flex justify-between">
                                    <span>Lot fee (when published):</span>
                                    <span class="font-medium">{{ formatPrice('platform.pricing.lot_fees.pro') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Platform fee ({{ config('platform.pricing.platform_fee_percent') }}% of {{ formatCurrency(1000) }}):</span>
                                    <span class="font-medium">{{ formatCurrency(1000 * config('platform.pricing.platform_fee_percent') / 100) }}</span>
                                </div>
                                <div class="border-t border-blue-200 dark:border-blue-700 pt-2 flex justify-between font-bold">
                                    <span>Total cost:</span>
                                    <span>{{ formatCurrency(getPriceValue('platform.pricing.lot_fees.pro') + (1000 * config('platform.pricing.platform_fee_percent') / 100)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
