<x-app-layout>
    <x-slot name="title">Following</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Following</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Auctioneers you're following</p>
                </div>
            </div>

            @if($followedAuctioneers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($followedAuctioneers as $follow)
                        @php $auctioneer = $follow->auctioneer; @endphp
                        <div class="card card-hover">
                            <div class="p-6">
                                <!-- Auctioneer Header -->
                                <div class="flex items-start gap-4 mb-4">
                                    @if($auctioneer->logo)
                                        <img src="{{ Storage::url($auctioneer->logo) }}"
                                             alt="{{ $auctioneer->business_name }}"
                                             class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ substr($auctioneer->business_name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ $auctioneer->business_name }}
                                        </h3>
                                        @if($auctioneer->user->city || $auctioneer->user->province)
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $auctioneer->user->city }}{{ $auctioneer->user->city && $auctioneer->user->province ? ', ' : '' }}{{ $auctioneer->user->province }}
                                            </p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">
                                            Following since {{ $follow->created_at->format('M Y') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Upcoming Auctions -->
                                @if($auctioneer->auctions->count() > 0)
                                    <div class="mb-4">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Upcoming Auctions ({{ $auctioneer->auctions->count() }})
                                        </p>
                                        <div class="space-y-2">
                                            @foreach($auctioneer->auctions->take(2) as $auction)
                                                <div class="text-sm">
                                                    <p class="text-gray-900 dark:text-gray-100 font-medium truncate">{{ $auction->title }}</p>
                                                    <p class="text-xs text-gray-500">
                                                        <span class="badge badge-{{ $auction->status === 'live' ? 'danger' : 'info' }} text-xs mr-1">
                                                            {{ ucfirst($auction->status) }}
                                                        </span>
                                                        {{ $auction->start_time->format('M d, Y') }}
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 mb-4">No upcoming auctions</p>
                                @endif

                                <!-- Actions -->
                                <div class="flex gap-2">
                                    <a href="{{ route('auctioneer.show', $auctioneer) }}" class="btn btn-primary flex-1">
                                        View Profile
                                    </a>
                                    <form method="POST" action="{{ route('auctioneer.follow.toggle', $auctioneer) }}" class="flex-shrink-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline">
                                            Unfollow
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $followedAuctioneers->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No followed auctioneers yet</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Start following auctioneers to see their latest events here</p>
                        <a href="{{ route('auctions.index') }}" class="btn btn-primary">
                            Browse Auctions
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
