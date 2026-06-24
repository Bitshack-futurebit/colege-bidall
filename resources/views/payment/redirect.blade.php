<x-app-layout>
    <x-slot name="title">Processing Payment...</x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                        <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Redirecting to Payment Gateway...</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">Please wait while we redirect you to complete your payment securely.</p>

                    @if(isset($payment['amount']))
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                        <h2 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Payment Details</h2>
                        <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <div class="flex justify-between">
                                <span>Amount:</span>
                                <span class="font-semibold">{{ formatCurrency($payment['amount']) }}</span>
                            </div>
                            @if(isset($payment['type']))
                            <div class="flex justify-between">
                                <span>Type:</span>
                                <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $payment['type'])) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <form id="payment-form" action="{{ $payment['redirect_url'] }}" method="POST">
                        @foreach($payment['form_data'] as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <button type="submit" class="btn btn-primary btn-lg">
                            Continue to Payment
                        </button>
                    </form>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-6">
                        If you are not redirected automatically, click the button above.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Auto-submit form after 2 seconds
    setTimeout(function() {
        document.getElementById('payment-form').submit();
    }, 2000);
    </script>
    @endpush
</x-app-layout>
