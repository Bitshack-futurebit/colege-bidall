<x-app-layout>
    <x-slot name="title">Activate Auctioneer Account</x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Activate Your Account</h1>
                        <p class="text-gray-600 dark:text-gray-400">Complete your registration to start creating auction events</p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">What's Included</h2>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Unlimited auction events</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Public auctioneer profile page</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Real-time bidding platform</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Automated payment processing</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Analytics and reporting tools</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">WhatsApp integration for buyer communication</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-800 dark:text-blue-200">Listing on our auctioneer discovery map</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Pricing</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">One-time activation fee</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice('platform.pricing.activation_fee') }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Additional Costs (pay-as-you-go):</p>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Basic lot (1 image)</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ formatPrice('platform.pricing.lot_fees.basic') }} when published</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Pro lot (up to 5 images)</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ formatPrice('platform.pricing.lot_fees.pro') }} when published</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Premium lot (up to 20 images)</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ formatPrice('platform.pricing.lot_fees.premium') }} when published</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Platform fee (on sold lots)</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ config('platform.pricing.platform_fee_percent') }}% of final bid</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-8">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Note:</strong> After activation, you'll need to purchase credits to create lots. Credits are used to pay for lot fees and platform fees as you create and sell items.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('seller.activate.process') }}">
                        @csrf
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="terms" name="terms" required class="mt-1">
                                <label for="terms" class="text-sm text-gray-600 dark:text-gray-400">
                                    I agree to the <a href="#" class="text-primary-600 hover:underline">Terms of Service</a> and understand that the activation fee is non-refundable.
                                </label>
                            </div>

                            @php($blinkAvailable = !empty(config('services.blink.api_key')) && !empty(config('services.blink.wallet_id')))
                            @if($blinkAvailable)
                                <div>
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

                            <button type="submit" class="btn btn-primary w-full btn-lg">
                                Pay {{ formatPrice('platform.pricing.activation_fee') }} & Activate Account
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Need help? <a href="#" class="text-primary-600 hover:underline">Contact Support</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
