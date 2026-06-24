<x-app-layout>
    <x-slot name="title">My Auctions</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">&larr; Back to Dashboard</a>
            </div>
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">My Auctions</h1>
                <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Auction
                </a>
            </div>

            <!-- Filter Tabs -->
            <div x-data="{ tab: '{{ request('tab', 'all') }}' }" class="mb-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex gap-8">
                        <a href="?tab=all"
                           @click.prevent="tab = 'all'"
                           :class="tab === 'all' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                           class="py-4 px-1 border-b-2 font-medium">
                            All Auctions ({{ $auctions->total() }})
                        </a>
                        <a href="?tab=draft"
                           @click.prevent="tab = 'draft'"
                           :class="tab === 'draft' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                           class="py-4 px-1 border-b-2 font-medium">
                            Draft ({{ $stats['draft'] }})
                        </a>
                        <a href="?tab=upcoming"
                           @click.prevent="tab = 'upcoming'"
                           :class="tab === 'upcoming' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                           class="py-4 px-1 border-b-2 font-medium">
                            Upcoming ({{ $stats['upcoming'] }})
                        </a>
                        <a href="?tab=live"
                           @click.prevent="tab = 'live'"
                           :class="tab === 'live' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                           class="py-4 px-1 border-b-2 font-medium">
                            Live ({{ $stats['live'] }})
                        </a>
                        <a href="?tab=ended"
                           @click.prevent="tab = 'ended'"
                           :class="tab === 'ended' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                           class="py-4 px-1 border-b-2 font-medium">
                            Ended ({{ $stats['ended'] }})
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Auctions List -->
            @if($auctions->count() > 0)
                <div class="space-y-4">
                    @foreach($auctions as $auction)
                        <div class="card card-hover">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-3">
                                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                                    @if($auction->status === 'ended')
                                                    <a href="{{ route('seller.auctions.report', $auction) }}" class="hover:text-primary-600">
                                                        {{ $auction->title }}
                                                    </a>
                                                @elseif($auction->status === 'live')
                                                    <a href="{{ route('seller.auctions.live-report', $auction) }}" class="hover:text-primary-600">
                                                        {{ $auction->title }}
                                                    </a>
                                                @else
                                                    <a href="{{ route('seller.auctions.show', $auction) }}" class="hover:text-primary-600">
                                                        {{ $auction->title }}
                                                    </a>
                                                @endif
                                            </h3>
                                            @if($auction->status === 'live')
                                                <span class="badge badge-danger animate-pulse">Live</span>
                                            @elseif($auction->status === 'upcoming')
                                                <span class="badge badge-info">Upcoming</span>
                                            @elseif($auction->status === 'draft')
                                                <span class="badge badge-secondary">Draft</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($auction->status) }}</span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            <div>
                                                <span class="font-semibold">Start:</span>
                                                {{ $auction->start_time->format('M d, Y H:i') }}
                                            </div>
                                            <div>
                                                <span class="font-semibold">End:</span>
                                                {{ $auction->end_time->format('M d, Y H:i') }}
                                            </div>
                                            <div>
                                                <span class="font-semibold">Lots:</span>
                                                {{ $auction->lots->count() }}
                                            </div>
                                            <div>
                                                <span class="font-semibold">Total Bids:</span>
                                                {{ $auction->lots->sum('total_bids') }}
                                            </div>
                                        </div>

                                        @if($auction->status === 'ended' || $auction->status === 'live')
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm mb-4">
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                                    <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Lots Sold</div>
                                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                                        {{ $auction->lots->where('status', 'sold')->count() }}
                                                        <span class="text-sm text-gray-500">/ {{ $auction->lots->count() }}</span>
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                                    <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Total Sales</div>
                                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                                        {{ formatCurrency($auction->lots->where('status', 'sold')->sum('current_bid')) }}
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                                    <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Avg Sale</div>
                                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                                        {{ $auction->lots->where('status', 'sold')->count() > 0 ? formatCurrency($auction->lots->where('status', 'sold')->avg('current_bid')) : formatCurrency(0) }}
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                                    <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Registrations</div>
                                                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                                        {{ $auction->registrations->count() }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col gap-2 ml-6">
                                        <a href="{{ route('seller.auctions.show', $auction) }}" class="btn btn-primary whitespace-nowrap">
                                            View Details
                                        </a>

                                        @if($auction->status === 'draft')
                                            <a href="{{ route('seller.auctions.edit', $auction) }}" class="btn btn-outline whitespace-nowrap">
                                                Edit
                                            </a>
                                        @endif

                                        @if($auction->status === 'draft')
                                            <a href="{{ route('seller.auctions.lots.create', $auction) }}" class="btn btn-outline whitespace-nowrap">
                                                Add Lots
                                            </a>
                                            <form action="{{ route('seller.auctions.destroy', $auction) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Permanently delete \'{{ addslashes($auction->title) }}\' and all its lots?\n\nThis cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger whitespace-nowrap">Delete</button>
                                            </form>
                                        @endif

                                        @if($auction->status === 'live')
                                            <a href="{{ route('seller.auctions.live-report', $auction) }}" class="btn btn-outline whitespace-nowrap text-green-600 border-green-600 hover:bg-green-50 dark:hover:bg-green-900/20">
                                                Live Report
                                            </a>
                                        @endif

                                        @if($auction->status === 'ended')
                                            <a href="{{ route('seller.auctions.live-report', $auction) }}" class="btn btn-outline whitespace-nowrap">
                                                Auction Report
                                            </a>
                                            <a href="{{ route('seller.auctions.report', $auction) }}" class="btn btn-outline whitespace-nowrap">
                                                Financial Report
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $auctions->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">You haven't created any auctions yet.</p>
                        <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary">Create Your First Auction</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
