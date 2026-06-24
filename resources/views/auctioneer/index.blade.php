<x-app-layout>
    <x-slot name="title">Auctioneers</x-slot>
    <x-slot name="description">Browse auctioneers on {{ config('branding.name') }}. Find trusted sellers, follow your favorites, and never miss an auction.</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Auctioneers</h1>
                <p class="text-gray-600 dark:text-gray-400">Browse auctioneers, follow your favorites, and discover upcoming auctions.</p>
            </div>

            @if($auctioneers->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($auctioneers as $auctioneer)
                        <div class="card card-hover">
                            <a href="{{ route('auctioneer.show', $auctioneer) }}">
                                @if($auctioneer->banner_image)
                                    <div class="h-24 rounded-t-lg overflow-hidden">
                                        <img src="{{ Storage::url($auctioneer->banner_image) }}" alt="" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="h-24 rounded-t-lg bg-gradient-to-r from-primary-500 to-primary-700"></div>
                                @endif
                            </a>

                            <div class="p-5 -mt-10 relative">
                                <a href="{{ route('auctioneer.show', $auctioneer) }}">
                                    @if($auctioneer->logo)
                                        <img src="{{ Storage::url($auctioneer->logo) }}" alt="" class="w-16 h-16 rounded-lg object-cover border-2 border-white dark:border-gray-800 shadow-md mb-3">
                                    @else
                                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-md mb-3">
                                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ substr($auctioneer->business_name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                </a>

                                <a href="{{ route('auctioneer.show', $auctioneer) }}">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-primary-600 mb-1">
                                        {{ $auctioneer->business_name }}
                                    </h3>
                                </a>

                                @if($auctioneer->user->city || $auctioneer->user->province)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-3">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $auctioneer->user->city }}{{ $auctioneer->user->city && $auctioneer->user->province ? ', ' : '' }}{{ $auctioneer->user->province }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $auctioneer->auctions_count }} {{ Str::plural('auction', $auctioneer->auctions_count) }}</span>

                                    @auth
                                        @if(auth()->user()->role !== 'auctioneer' || auth()->user()->auctioneer->id !== $auctioneer->id)
                                            <form method="POST" action="{{ route('auctioneer.follow.toggle', $auctioneer) }}">
                                                @csrf
                                                <button type="submit" class="btn {{ auth()->user()->isFollowingAuctioneer($auctioneer->id) ? 'btn-primary' : 'btn-outline' }} btn-sm font-bold">
                                                    <svg class="w-4 h-4 mr-1" fill="{{ auth()->user()->isFollowingAuctioneer($auctioneer->id) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                    </svg>
                                                    {{ auth()->user()->isFollowingAuctioneer($auctioneer->id) ? 'Following' : 'Follow' }}
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-600 dark:text-gray-400">No auctioneers yet.</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    @php
        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Auctioneers'],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush
</x-app-layout>
