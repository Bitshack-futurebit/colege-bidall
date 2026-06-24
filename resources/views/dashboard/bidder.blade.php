<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Welcome back, {{ $user->name }}!</h1>

            {{-- Show only for users actively engaged in the agent program (not the recruitment CTA — that's a public landing page) --}}
            @php $agent = $user->agent; @endphp
            @if($agent && $agent->status === 'pending')
                <div class="card mb-6 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500">
                    <div>
                        <h2 class="font-bold text-amber-900 dark:text-amber-200">Agent application under review</h2>
                        <p class="text-sm text-amber-800 dark:text-amber-300">We'll be in touch shortly.</p>
                    </div>
                    <a href="{{ route('agent.apply') }}" class="text-sm text-amber-800 dark:text-amber-300 hover:underline whitespace-nowrap">View status &rarr;</a>
                </div>
            @elseif($agent && $agent->status === 'active')
                <div class="card mb-6 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-teal-50 dark:bg-teal-900/20 border-l-4 border-teal-500">
                    <div>
                        <h2 class="font-bold text-teal-900 dark:text-teal-200">You're a Community Agent</h2>
                        <p class="text-sm text-teal-800 dark:text-teal-300">Referral code: <span class="font-mono font-bold tracking-wider">{{ $agent->referral_code }}</span></p>
                    </div>
                    <a href="{{ route('agent.dashboard') }}" class="btn bg-teal-600 hover:bg-teal-700 text-white whitespace-nowrap">Open agent dashboard &rarr;</a>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
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

                @php $communityListedCount = \Schema::hasColumn('lots', 'seller_user_id') ? \App\Models\Lot::where('seller_user_id', auth()->id())->count() : 0; @endphp
                <a href="{{ route('community.my-lots') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">My Community Listings</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $communityListedCount }} listed</p>
                </a>
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
