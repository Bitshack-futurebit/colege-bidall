<x-app-layout>
    <x-slot name="title">{{ $auction->title }} - Auctioneer Dashboard</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">← Back to Dashboard</a>

            <div class="flex gap-2">
                @if(in_array($auction->status, ['upcoming', 'live']))
                    @php
                        $waText = "* {$auction->title} *\n\n";
                        $waText .= $auction->start_time->isFuture()
                            ? "Starts: " . $auction->start_time->format('M d, Y \a\t H:i') . "\n"
                            : "LIVE NOW - Ends: " . ($auction->end_time ? $auction->end_time->format('M d, Y \a\t H:i') : 'TBA') . "\n";
                        $waText .= $auction->lots->whereNull('withdrawn_at')->count() . " Lots available\n\n";
                        $waText .= "Browse & bid now:\n" . route('auctions.show', $auction->slug) . "\n\n";
                        $waText .= "Powered by " . config('branding.name');
                    @endphp
                    <a href="https://api.whatsapp.com/send?text={{ rawurlencode($waText) }}"
                       target="_blank"
                       class="btn bg-green-600 hover:bg-green-700 text-white">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Share
                    </a>
                @endif
                @if($auction->status === 'draft')
                    <a href="{{ route('seller.auctions.edit', $auction) }}" class="btn btn-primary">Edit Auction</a>
                @endif
                @if($auction->status === 'draft')
                    <button type="button" onclick="showDeleteAuctionModal()" class="btn btn-danger">Delete Auction</button>
                    @if($auction->lots->count() > 0 && isset($lotsByTier))
                        <button type="button" onclick="showPublishModal()" class="btn btn-accent">Publish Auction</button>
                    @endif
                @endif
            </div>
        </div>

        <!-- Auction Details Card -->
        <div class="card mb-6">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $auction->title }}</h1>
                        <div class="flex gap-2">
                            @if($auction->isDutch())
                                <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Dutch {{ ucfirst($auction->dutch_lot_mode ?? '') }}</span>
                            @elseif($auction->isSealed())
                                <span class="badge bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Sealed — {{ ucfirst($auction->sealed_mode) }} Wins</span>
                            @endif
                            <span class="status-{{ $auction->status }}">{{ ucfirst($auction->status) }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Start Time</div>
                        <div class="text-lg font-semibold">{{ $auction->start_time->format('M d, Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">End Time</div>
                        <div class="text-lg font-semibold">{{ $auction->end_time ? $auction->end_time->format('M d, Y H:i') : 'Auto-calculated' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Lots</div>
                        <div class="text-lg font-semibold">{{ $auction->lots->count() }}</div>
                    </div>
                </div>

                @if($auction->description)
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Description</div>
                    <p class="text-gray-700 dark:text-gray-300">{{ $auction->description }}</p>
                </div>
                @endif

                @if($auction->isDutch() && in_array($auction->status, ['live', 'ended']))
                    @php
                        $dutchLots = $auction->lots->whereNull('withdrawn_at');
                        $totalQuantity = $dutchLots->sum('quantity');
                        $totalSold = $dutchLots->sum('quantity_sold');
                        $dutchBids = \App\Models\Bid::whereIn('lot_id', $dutchLots->pluck('id'))
                            ->where('is_dutch_buy', true)->get();
                        $dutchRevenue = $dutchBids->sum(fn($b) => $b->amount * $b->quantity_bought);
                        $avgSellPrice = $dutchBids->count() > 0
                            ? $dutchBids->avg('amount') : 0;
                        $avgStartPrice = $dutchLots->avg('dutch_start_price') ?: 0;
                        $lotsAtFloor = $dutchLots->filter(fn($l) => $l->status === 'sold' && $l->current_bid <= $l->dutch_floor_price)->count();
                        $lotsUnsold = $dutchLots->where('status', 'unsold')->count();
                    @endphp
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $totalSold }}/{{ $totalQuantity }}</div>
                            <div class="text-xs text-green-600 dark:text-green-500">Units Sold</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ formatCurrency($dutchRevenue) }}</div>
                            <div class="text-xs text-blue-600 dark:text-blue-500">Revenue</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ formatCurrency($avgSellPrice) }}</div>
                            <div class="text-xs text-purple-600 dark:text-purple-500">Avg Sell Price</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $lotsAtFloor + $lotsUnsold }}</div>
                            <div class="text-xs text-amber-600 dark:text-amber-500">Hit Floor / Unsold</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Lots Section -->
        <div class="card">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Lots</h2>
                    @if($auction->status === 'draft')
                        <a href="{{ route('seller.auctions.lots.create', $auction) }}" class="btn btn-primary">Add Lot</a>
                    @endif
                </div>

                @if($auction->lots->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($auction->lots as $lot)
                            <div class="card {{ $lot->isWithdrawn() ? 'opacity-60 bg-gray-50 dark:bg-gray-800/50' : '' }}">
                                @if($lot->images->count() > 0)
                                    <div class="relative" x-data="{ currentIndex: 0, total: {{ $lot->images->count() }} }">
                                        <!-- Image — thumbnails for card grid, lazy-load off-screen -->
                                        @foreach($lot->images as $index => $image)
                                            <img src="{{ $index === 0 ? $image->thumbnail_url : '' }}"
                                                 {!! $index > 0 ? 'x-bind:src="currentIndex === ' . $index . ' ? \'' . $image->thumbnail_url . '\' : \'\'"' : '' !!}
                                                 alt="{{ $lot->title }}"
                                                 class="w-full h-48 object-cover rounded-t-lg {{ $lot->isWithdrawn() ? 'grayscale' : '' }}"
                                                 loading="{{ $loop->parent->index < 6 ? 'eager' : 'lazy' }}"
                                                 x-show="currentIndex === {{ $index }}"
                                                 x-cloak>
                                        @endforeach

                                        <!-- Navigation Arrows (only show if multiple images) -->
                                        @if($lot->images->count() > 1)
                                            <button @click.stop="currentIndex = (currentIndex - 1 + total) % total"
                                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                </svg>
                                            </button>
                                            <button @click.stop="currentIndex = (currentIndex + 1) % total"
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>

                                            <!-- Dot Indicators -->
                                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
                                                @foreach($lot->images as $index => $image)
                                                    <button @click.stop="currentIndex = {{ $index }}"
                                                            class="w-2 h-2 rounded-full transition"
                                                            :class="currentIndex === {{ $index }} ? 'bg-white' : 'bg-white/50'">
                                                    </button>
                                                @endforeach
                                            </div>

                                            <!-- Image Counter -->
                                            <div class="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
                                                <span x-text="currentIndex + 1"></span>/<span x-text="total"></span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 rounded-t-lg flex items-center justify-center">
                                        <span class="text-gray-400">No image</span>
                                    </div>
                                @endif

                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold">Lot {{ $lot->lot_number }}</span>
                                        <span class="status-{{ $lot->status }} text-xs">{{ ucfirst($lot->status) }}</span>
                                    </div>

                                    <h3 class="text-lg font-bold mb-3">{{ $lot->title }}</h3>

                                    <div class="space-y-2 mb-4 text-sm">
                                        @if($auction->isDutch() && $lot->dutch_start_price)
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Start Price:</span>
                                                <span class="font-semibold">{{ formatCurrency($lot->dutch_start_price) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Floor:</span>
                                                <span class="font-semibold">{{ formatCurrency($lot->dutch_floor_price) }}</span>
                                            </div>
                                            @if($lot->quantity > 1)
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Sold:</span>
                                                    <span class="font-semibold">{{ $lot->quantity_sold }} / {{ $lot->quantity }}</span>
                                                </div>
                                            @endif
                                            @if($lot->status === 'live')
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Current:</span>
                                                    <span class="font-semibold text-primary-600">{{ formatCurrency($lot->getCurrentDutchPrice()) }}</span>
                                                </div>
                                            @elseif($lot->status === 'sold')
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Sold at:</span>
                                                    <span class="font-semibold text-green-600">{{ formatCurrency($lot->current_bid) }}</span>
                                                </div>
                                            @endif
                                        @elseif($auction->isSealed())
                                            @if($lot->reserve_price)
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">{{ $auction->isSealedHighest() ? 'Reserve' : 'Max Price' }}:</span>
                                                    <span class="font-semibold">{{ formatCurrency($lot->reserve_price) }}</span>
                                                </div>
                                            @endif
                                            @if($auction->status === 'live')
                                                <div class="text-xs text-purple-600 dark:text-purple-400 italic mt-1">Bids sealed until auction ends</div>
                                            @elseif($lot->status === 'sold')
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Won at:</span>
                                                    <span class="font-semibold text-green-600">{{ formatCurrency($lot->current_bid) }}</span>
                                                </div>
                                            @endif
                                        @else
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Starting:</span>
                                                <span class="font-semibold">{{ formatCurrency($lot->starting_bid) }}</span>
                                            </div>
                                            @if($lot->total_bids > 0)
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Current:</span>
                                                    <span class="font-semibold text-primary-600">{{ formatCurrency($lot->current_bid) }}</span>
                                                </div>
                                            @else
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Current:</span>
                                                    <span class="italic text-gray-400">No bids yet</span>
                                                </div>
                                            @endif
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Bids:</span>
                                                <span class="font-semibold">{{ $lot->total_bids }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        @if(!$lot->isWithdrawn())
                                            @if($auction->status === 'draft')
                                                <a href="{{ route('seller.auctions.lots.edit', [$auction, $lot]) }}" class="btn btn-sm btn-outline w-full">Edit Lot</a>
                                            @endif

                                            @if($auction->status === 'draft' && !in_array($lot->status, ['live', 'sold']))
                                                {{-- Delete only available when auction is draft --}}
                                                <form action="{{ route('seller.auctions.lots.destroy', [$auction, $lot]) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Are you sure you want to delete this lot? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger w-full">Delete</button>
                                                </form>
                                            @elseif($lot->canBeWithdrawn())
                                                {{-- Withdraw available when auction is upcoming only --}}
                                                <button type="button" onclick="showWithdrawModal({{ $lot->id }}, '{{ $lot->title }}')" class="btn btn-sm btn-warning w-full">
                                                    Withdraw
                                                </button>
                                            @endif
                                        @else
                                            <div class="text-center text-sm px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                                                Withdrawn<br>{{ $lot->withdrawn_at->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">No lots added yet</p>
                        @if($auction->status === 'draft')
                            <a href="{{ route('seller.auctions.lots.create', $auction) }}" class="btn btn-primary">Add Your First Lot</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Auction Modal -->
    <div id="deleteAuctionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Delete Draft Auction</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone</p>
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-5">
                    <p class="text-sm text-red-800 dark:text-red-300">
                        <strong>The following will be permanently deleted:</strong><br>
                        &bull; Auction: <strong>{{ $auction->title }}</strong><br>
                        &bull; All {{ $auction->lots->count() }} {{ $auction->lots->count() === 1 ? 'lot' : 'lots' }} and their images<br>
                        &bull; All associated data
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="hideDeleteAuctionModal()" class="btn btn-outline flex-1">Cancel</button>
                    <form action="{{ route('seller.auctions.destroy', $auction) }}" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full">Yes, Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Publish Auction Warning Modal -->
    @isset($lotsByTier)
    <div id="publishModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-8 mx-auto p-5 border max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800 mb-8">
            <div class="mt-3">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">Publish Auction — Final Review</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Please review the details below before publishing.</p>

                {{-- Auction Details --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2 text-xs uppercase tracking-wide">Auction Details</h4>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Title</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $auction->title }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Start</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $auction->start_time->format('M d, Y \a\t H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">End</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $auction->end_time->format('M d, Y \a\t H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Active Lots</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $auction->lots->whereNull('withdrawn_at')->count() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Credit Cost Breakdown PARKED — free standalone product: publishing an
                     auction costs nothing, no credits, no platform fee. --}}

                {{-- Important Warnings --}}
                <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-amber-800 dark:text-amber-300 mb-2 text-xs uppercase tracking-wide">Important — Please Read</h4>
                    <ul class="space-y-2 text-sm text-amber-800 dark:text-amber-300">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 flex-shrink-0">&#9888;</span>
                            <span>Once published, <strong>lot details are locked</strong>. You may only withdraw lots.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 flex-shrink-0">&#9888;</span>
                            <span>This auction <strong>cannot be cancelled or deleted</strong> once published.</span>
                        </li>
                    </ul>
                </div>

                @if($canAfford)
                    {{-- Confirmation Checkbox --}}
                    <div class="flex items-start gap-3 mb-5">
                        <input type="checkbox"
                               id="publishConfirmCheck"
                               onchange="togglePublishBtn()"
                               class="mt-1 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <label for="publishConfirmCheck" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            I have read and understand the above. I confirm I want to publish this auction.
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="hidePublishModal()" class="btn btn-outline flex-1">Cancel</button>
                        <form action="{{ route('seller.auctions.go-live', $auction) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    id="publishSubmitBtn"
                                    disabled
                                    class="btn btn-accent w-full opacity-50 cursor-not-allowed">
                                Publish Auction
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex gap-3">
                        <button type="button" onclick="hidePublishModal()" class="btn btn-outline flex-1">Cancel</button>
                        <a href="{{ route('seller.credits') }}" class="btn btn-primary flex-1 text-center">Buy Credits</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endisset

    <!-- Withdraw Lot Modal -->
    <div id="withdrawModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Withdraw Lot</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Withdrawing lot: <strong id="modalLotTitle"></strong>
                </p>
                <form id="withdrawForm" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="withdrawal_reason" class="label">Reason (Optional)</label>
                        <textarea id="withdrawal_reason"
                                  name="withdrawal_reason"
                                  rows="3"
                                  class="input"
                                  placeholder="Why are you withdrawing this lot?"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="hideWithdrawModal()" class="btn btn-outline flex-1">Cancel</button>
                        <button type="submit" class="btn btn-warning flex-1">Withdraw Lot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Delete Auction Modal
        function showDeleteAuctionModal() {
            document.getElementById('deleteAuctionModal').classList.remove('hidden');
        }
        function hideDeleteAuctionModal() {
            document.getElementById('deleteAuctionModal').classList.add('hidden');
        }
        document.getElementById('deleteAuctionModal').addEventListener('click', function(e) {
            if (e.target === this) hideDeleteAuctionModal();
        });

        // Publish Modal
        function showPublishModal() {
            document.getElementById('publishModal').classList.remove('hidden');
            const checkbox = document.getElementById('publishConfirmCheck');
            if (checkbox) {
                checkbox.checked = false;
                togglePublishBtn();
            }
        }
        function hidePublishModal() {
            document.getElementById('publishModal').classList.add('hidden');
        }
        function togglePublishBtn() {
            const btn = document.getElementById('publishSubmitBtn');
            const checked = document.getElementById('publishConfirmCheck').checked;
            btn.disabled = !checked;
            btn.classList.toggle('opacity-50', !checked);
            btn.classList.toggle('cursor-not-allowed', !checked);
        }
        document.getElementById('publishModal').addEventListener('click', function(e) {
            if (e.target === this) hidePublishModal();
        });

        // Withdraw Lot Modal
        function showWithdrawModal(lotId, lotTitle) {
            document.getElementById('modalLotTitle').textContent = lotTitle;
            document.getElementById('withdrawForm').action = `/seller/auctions/{{ $auction->id }}/lots/${lotId}/withdraw`;
            document.getElementById('withdrawModal').classList.remove('hidden');
        }

        function hideWithdrawModal() {
            document.getElementById('withdrawModal').classList.add('hidden');
            document.getElementById('withdrawal_reason').value = '';
        }

        // Close modal on outside click
        document.getElementById('withdrawModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideWithdrawModal();
            }
        });
    </script>
    @endpush
</x-app-layout>
