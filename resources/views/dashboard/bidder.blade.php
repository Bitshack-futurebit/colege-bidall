<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Welcome back, {{ $user->name }}!</h1>

            {{-- Community Agent status block PARKED — community/agent subsystem dormant in standalone product. --}}

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <a href="{{ route('auctions.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Browse Auctions</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Discover new auctions</p>
                </a>

                <a href="{{ route('dashboard.watchlist') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Watchlist</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stats['watchlist'] }} items saved</p>
                </a>

                <a href="{{ route('dashboard.following') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Following</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stats['following'] }} auctioneers</p>
                </a>

                <a href="{{ route('dashboard.won') }}" class="card card-hover p-6 text-center relative">
                    @if($stats['won_unpaid'] > 0)
                        <span class="absolute top-3 right-3 inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold">
                            {{ $stats['won_unpaid'] }}
                        </span>
                    @endif
                    <svg class="w-12 h-12 mx-auto mb-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Won Lots</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $stats['won_unpaid'] > 0 ? $stats['won_unpaid'] . ' unpaid' : 'View history' }}
                    </p>
                </a>

                {{-- My Community Listings card PARKED — community subsystem dormant in standalone product. --}}
            </div>

            <!-- Winning Lots -->
            @if($winningLots->count() > 0)
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">You're Winning</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($winningLots as $lot)
                        <div class="card border-2 border-green-500">
                            <div class="p-6">
                                <div class="badge badge-success mb-3">You have the top bid!</div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $lot->title }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $lot->auction->title }}</p>
                                <div class="text-2xl font-bold text-green-600 mb-2">{{ formatCurrency($lot->current_bid) }}</div>
                                <a href="{{ route('lots.show', $lot) }}" class="btn btn-primary w-full">View Lot</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Won Lots (Awaiting Collection) -->
            @php
                $awaitingLots = $wonLots->filter(fn($lot) => $lot->payment_status === 'awaiting_collection');
            @endphp
            @if($awaitingLots->count() > 0)
            <div class="mb-8">
                <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-4">
                    <h2 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-2">Collection Required</h2>
                    <p class="text-blue-800 dark:text-blue-200 mb-4">You have {{ $awaitingLots->count() }} lot(s) awaiting collection. Contact the auctioneer to arrange payment and collection.</p>
                    <a href="{{ route('dashboard.won') }}" class="btn btn-primary">View Won Lots</a>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
