<x-app-layout>
    <x-slot name="title">{{ config('branding.name') }} - {{ config('branding.tagline') }}</x-slot>
    <x-slot name="description">South Africa's most affordable online auction platform. Browse live auctions, bid on items, or become an auctioneer.</x-slot>

    <!-- Map Section -->
    @if(config('regional.features.map_discovery'))
    <div class="py-12 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8 text-center">Find Auctions Near You</h2>
            <div class="map-container" x-data="auctioneerMap()" x-ref="mapContainer"></div>
        </div>
    </div>
    @endif

    <!-- Live Auctions -->
    @if($liveAuctions->count() > 0)
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Live Auctions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($liveAuctions as $auction)
                    <div class="card card-hover">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-danger">Live Now</span>
                                    @if($auction->is_community)
                                        <span class="badge bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-300">Community</span>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $auction->lots_count }} lots</span>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                @php $auctionUrl = $auction->is_community && $auction->communityRegion ? route('community.region', $auction->communityRegion) : route('auctions.show', $auction); @endphp
                                <a href="{{ $auctionUrl }}" class="hover:text-primary-600">
                                    {{ $auction->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                By {{ $auction->auctioneer->business_name }}
                            </p>
                            <a href="{{ $auctionUrl }}" class="btn btn-primary w-full">
                                View Auction
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Upcoming Auctions -->
    @if($upcomingAuctions->count() > 0)
    <div class="py-12 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Upcoming Auctions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($upcomingAuctions as $auction)
                    <div class="card card-hover">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-info">{{ $auction->start_time->format('M d, Y') }}</span>
                                    @if($auction->is_community)
                                        <span class="badge bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-300">Community</span>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $auction->lots_count }} lots</span>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                @php $auctionUrl = $auction->is_community && $auction->communityRegion ? route('community.region', $auction->communityRegion) : route('auctions.show', $auction); @endphp
                                <a href="{{ $auctionUrl }}" class="hover:text-primary-600">
                                    {{ $auction->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                By {{ $auction->auctioneer->business_name }}
                            </p>
                            <a href="{{ $auctionUrl }}" class="btn btn-outline w-full">
                                View Auction
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- BidWright / auction-software CTA REMOVED — not relevant to the Auction College brand. --}}

    @push('scripts')
    @php
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('branding.name'),
            'url' => url('/'),
            'logo' => asset('images/gavel-logo.svg'),
            'description' => 'South Africa\'s most affordable online auction platform. Browse live auctions, bid on items, or become an auctioneer.',
            'foundingDate' => '2025',
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'South Africa',
            ],
            'sameAs' => array_values(array_filter([
                config('branding.social.facebook'),
                config('branding.social.instagram'),
                config('branding.social.twitter'),
            ])),
        ];

        $webSiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('branding.name'),
            'url' => url('/'),
            'description' => 'South Africa\'s most affordable online auction platform. English, Dutch, and Sealed auctions.',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/auctions') . '?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($webSiteSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush
</x-app-layout>
