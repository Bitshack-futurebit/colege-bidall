<x-app-layout>
    <x-slot name="title">{{ ($whiteLabel ?? null)?->isActive() ? $whiteLabel->businessName() . ' Auctions' : 'Browse Auctions' }}</x-slot>
    <x-slot name="description">Browse live and upcoming auctions on {{ ($whiteLabel ?? null)?->isActive() ? $whiteLabel->businessName() : config('branding.name') }}. Find great deals from trusted South African auctioneers.</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    @if(($whiteLabel ?? null)?->isActive())
                        <a href="{{ route('auctioneer.show', $whiteLabel->slug()) }}" class="btn btn-outline">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    @elseif(auth()->check())
                        <a href="{{ route('dashboard') }}" class="btn btn-outline">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="btn btn-outline">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    @endif
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ ($whiteLabel ?? null)?->isActive() ? $whiteLabel->businessName() . ' Auctions' : 'Browse Auctions' }}</h1>
                </div>

                <!-- Filters -->
                <div class="flex gap-4">
                    <select onchange="window.location.href='?status='+this.value" class="input w-full sm:w-auto">
                        <option value="">All Auctions</option>
                        <option value="live" {{ request('status') == 'live' ? 'selected' : '' }}>Live</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>
            </div>

            <!-- Auctions Grid -->
            @if($auctions->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($auctions as $auction)
                        <div class="card card-hover">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4 gap-2 flex-wrap">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($auction->status === 'live')
                                            <span class="badge badge-danger animate-pulse">Live Now</span>
                                        @elseif($auction->status === 'draft' && $auction->is_community)
                                            <span class="badge bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">Open for listings</span>
                                        @else
                                            <span class="badge badge-info">{{ optional($auction->start_time)->format('M d, Y') }}</span>
                                        @endif
                                        @if($auction->is_community)
                                            <span class="badge bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">Community</span>
                                        @endif
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $auction->lots_count }} lots</span>
                                </div>

                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    <a href="{{ route('auctions.show', $auction) }}" class="hover:text-primary-600">
                                        {{ $auction->title }}
                                    </a>
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    By <a href="{{ route('auctioneer.show', $auction->auctioneer) }}" class="hover:text-primary-600">
                                        {{ $auction->auctioneer->business_name }}
                                    </a>
                                </p>

                                @if($auction->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                        {{ $auction->description }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        @if($auction->start_time)
                                            <div>Starts: {{ $auction->start_time->format('M d, H:i') }}</div>
                                        @endif
                                        @if($auction->end_time && !$auction->isCommunity())
                                            <div>Ends: {{ $auction->end_time->format('M d, H:i') }}</div>
                                        @endif
                                    </div>
                                </div>

                                @if($auction->requiresDeposit())
                                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded px-3 py-2 mb-4">
                                        <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                            Deposit required: {{ formatCurrency($auction->deposit_amount) }}
                                        </p>
                                    </div>
                                @endif

                                <a href="{{ route('auctions.show', $auction) }}" class="btn {{ $auction->status === 'live' ? 'btn-accent' : 'btn-primary' }} w-full">
                                    {{ $auction->status === 'live' ? 'Bid Now' : 'View Auction' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $auctions->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400">No auctions found.</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    @php
        $auctionsBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Auctions'],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($auctionsBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush
</x-app-layout>
