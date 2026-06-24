<x-app-layout>
    <x-slot name="title">Payment {{ ucfirst($status) }}</x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-12 text-center">
                    @if($status === 'success')
                        <div class="w-20 h-20 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">Payment Successful!</h1>
                        <p class="text-gray-600 dark:text-gray-400 mb-8">
                            Your payment of <strong>{{ formatCurrency($amount) }}</strong> has been processed successfully.
                        </p>

                        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-6">
                            <h2 class="font-semibold text-green-900 dark:text-green-100 mb-2">What's Next?</h2>
                            <div class="text-sm text-green-800 dark:text-green-200 text-left">
                                @if($type === 'activation_fee')
                                    <p class="mb-2">✓ Your auctioneer account is now activated!</p>
                                    <p class="mb-2">✓ You can now create auctions</p>
                                    <p>✓ Don't forget to purchase credits to create lots</p>
                                @elseif($type === 'credit_purchase')
                                    <p class="mb-2">✓ {{ formatCurrency($amount) }} has been added to your credit balance</p>
                                    <p>✓ You can now create lots for your auctions</p>
                                @elseif($type === 'auction_deposit')
                                    <p class="mb-2">✓ You are now registered for this auction</p>
                                    <p>✓ You can start bidding on lots</p>
                                @elseif($type === 'lot_payment' || $type === 'direct_lot_payment')
                                    <p class="mb-2">✓ Your payment has been confirmed</p>
                                    <p class="mb-2">✓ Your won lots are now marked as paid</p>
                                    <p>✓ Contact the auctioneer to arrange collection</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-4 justify-center">
                            @if($type === 'activation_fee')
                                <a href="{{ route('seller.credits') }}" class="btn btn-primary">Buy Credits</a>
                                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                            @elseif($type === 'credit_purchase')
                                <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary">Create Auction</a>
                                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                            @elseif($type === 'auction_deposit')
                                <a href="{{ route('auctions.show', $relatedId) }}" class="btn btn-primary">View Auction</a>
                                <a href="{{ route('dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                            @elseif($type === 'lot_payment' || $type === 'direct_lot_payment')
                                <a href="{{ route('dashboard.won') }}" class="btn btn-primary">View My Won Lots</a>
                                <a href="{{ route('dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                            @endif
                        </div>

                    @elseif($status === 'cancelled')
                        <div class="w-20 h-20 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">Payment Cancelled</h1>
                        <p class="text-gray-600 dark:text-gray-400 mb-8">
                            Your payment was cancelled. No charges have been made.
                        </p>

                        <div class="flex gap-4 justify-center">
                            <a href="{{ url()->previous() }}" class="btn btn-primary">Try Again</a>
                            <a href="{{ route('home') }}" class="btn btn-outline">Go to Homepage</a>
                        </div>

                    @else
                        <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                            <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">Payment Processing...</h1>
                        <p class="text-gray-600 dark:text-gray-400 mb-8">
                            Your payment is being processed. This may take a few moments.
                        </p>

                        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                Please wait while we confirm your payment. This page will update automatically.
                            </p>
                        </div>

                        @if($type === 'lot_payment')
                            <a href="{{ route('dashboard.won') }}" class="btn btn-outline">View My Won Lots</a>
                        @elseif($type === 'credit_purchase' || $type === 'activation_fee')
                            <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn btn-outline">Go to Dashboard</a>
                        @endif
                    @endif

                    @if(isset($reference))
                        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Reference: <span class="font-mono">{{ $reference }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($status === 'pending')
        @push('scripts')
        <script>
        // Auto-refresh every 5 seconds to check for payment completion
        setTimeout(function() {
            location.reload();
        }, 5000);
        </script>
        @endpush
    @endif
</x-app-layout>
