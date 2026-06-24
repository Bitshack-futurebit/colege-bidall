<x-app-layout>
    <x-slot name="title">Lot #{{ $lot->lot_number }} {{ $lot->title }}</x-slot>
    <x-slot name="description">{{ $lot->auction->title }} by {{ $lot->auction->auctioneer->business_name }}. {{ $lot->description ? Str::limit($lot->description, 120) : 'Browse and bid on ' . config('branding.name') . ', South Africa\'s online auction platform.' }}</x-slot>
    @if($lot->images->count() > 0)
    <x-slot name="ogImage">{{ $lot->images->first()->optimized_url }}</x-slot>
    @endif

    @php
        $shouldNoIndex = $lot->auction->status === 'ended'
            && $lot->bids->count() === 0
            && $lot->auction->updated_at->lt(now()->subDays(30));
    @endphp
    @if($shouldNoIndex)
        <x-slot name="noIndex">1</x-slot>
    @endif

    @php
        $auctionUrl = $lot->auction->is_community && $lot->auction->communityRegion
            ? route('community.region', $lot->auction->communityRegion)
            : route('auctions.show', $lot->auction);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Buttons -->
            <div class="flex items-center gap-4 mb-6">
                @if(request()->has('ref') && request()->get('ref') === 'won')
                    <a href="{{ route('dashboard.won') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Won Lots
                    </a>
                @elseif(request()->has('ref') && request()->get('ref') === 'watchlist')
                    <a href="{{ route('dashboard.watchlist') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Watchlist
                    </a>
                @else
                    <a href="{{ $auctionUrl }}#lot-{{ $lot->id }}" class="btn btn-outline">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Auction
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Images -->
                <div>
                    @if($lot->images->count() > 0)
                        @php $imageUrls = $lot->images->pluck('optimized_url')->values()->toArray(); @endphp
                        <div x-data="{
                            images: @js($imageUrls),
                            currentIndex: 0,
                            lightbox: false,
                            get currentImage() { return this.images[this.currentIndex]; },
                            openLightbox(index) {
                                if (index !== undefined) this.currentIndex = index;
                                this.lightbox = true;
                            },
                            closeLightbox() { this.lightbox = false; },
                            prev() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length; },
                            next() { this.currentIndex = (this.currentIndex + 1) % this.images.length; }
                        }" @keydown.escape.window="closeLightbox()" @keydown.arrow-left.window="if(lightbox) prev()" @keydown.arrow-right.window="if(lightbox) next()">
                            <!-- Main Image -->
                            <div class="w-full aspect-[3/4] sm:aspect-[4/3] bg-gray-100 dark:bg-gray-800 rounded-lg mb-4 overflow-hidden cursor-pointer" @click="openLightbox()">
                                <img :src="currentImage" alt="{{ $lot->title }}" class="w-full h-full object-contain">
                            </div>

                            @if($lot->images->count() > 1)
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                    @foreach($lot->images as $index => $image)
                                        <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded overflow-hidden cursor-pointer hover:opacity-75 transition"
                                             :class="currentIndex === {{ $index }} ? 'ring-2 ring-primary-500' : ''"
                                             @click="currentIndex = {{ $index }}">
                                            <img src="{{ $image->thumbnail_url }}"
                                                 class="w-full h-full object-contain"
                                                 alt="Thumbnail">
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Fullscreen Lightbox -->
                            <template x-teleport="body">
                                <div x-show="lightbox" x-cloak
                                     class="fixed inset-0 z-[9999] bg-black/95"
                                     @click="closeLightbox()"
                                     @keydown.escape.window="closeLightbox()"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0">

                                    <!-- Close button — top right -->
                                    <button @click.stop="closeLightbox()" class="absolute top-4 right-4 z-[10000] text-white hover:text-gray-300 p-2 bg-black/50 rounded-full">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <!-- Image counter -->
                                    <div class="absolute top-4 left-4 text-white/70 text-sm" x-show="images.length > 1">
                                        <span x-text="(currentIndex + 1) + ' / ' + images.length"></span>
                                    </div>

                                    <!-- Centered image container -->
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <!-- Previous arrow -->
                                        <button x-show="images.length > 1" @click.stop="prev()"
                                                class="absolute left-2 sm:left-4 text-white/70 hover:text-white p-2">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>

                                        <!-- Full image -->
                                        <img :src="currentImage" alt="{{ $lot->title }}"
                                             class="max-h-[90vh] max-w-[95vw] object-contain select-none"
                                             style="touch-action: pinch-zoom;"
                                             @click.stop>

                                        <!-- Next arrow -->
                                        <button x-show="images.length > 1" @click.stop="next()"
                                                class="absolute right-2 sm:right-4 text-white/70 hover:text-white p-2">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    @else
                        <div class="w-full aspect-[3/4] sm:aspect-[4/3] bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <span class="text-gray-400">No image</span>
                        </div>
                    @endif
                </div>

                <!-- Bidding Section -->
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $lot->title }}</h1>
                        <div class="flex gap-2 flex-shrink-0">
                            @if($lot->isWithdrawn())
                                <span class="badge bg-gray-500 text-white text-base px-4 py-2">Withdrawn</span>
                            @elseif($lot->status === 'live')
                                <span class="badge badge-danger">Live</span>
                            @endif
                            <x-contact-seller-button :lot="$lot" />
                            <x-whatsapp-share :lot="$lot" />
                        </div>
                    </div>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Lot #{{ $lot->lot_number }} in <a href="{{ $auctionUrl }}" class="text-primary-600 hover:underline">{{ $lot->auction->title }}</a>
                    </p>

                    @if($lot->isWithdrawn())
                        <div class="bg-gray-50 dark:bg-gray-800 border-l-4 border-gray-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">Lot Withdrawn</h3>
                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                        <p>This lot was withdrawn from the auction on {{ $lot->withdrawn_at->format('M d, Y \a\t H:i') }} and is no longer available for bidding.</p>
                                        @if($lot->withdrawal_reason)
                                            <p class="mt-2"><strong>Reason:</strong> {{ $lot->withdrawal_reason }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($lot->description)
                        <div class="prose dark:prose-invert mb-6">
                            {{ $lot->description }}
                        </div>
                    @endif

                    @if($lot->tender_document)
                        <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-purple-800 dark:text-purple-200">Tender Document</p>
                                    <p class="text-sm text-purple-600 dark:text-purple-400">Review this document before placing your bid.</p>
                                </div>
                                <a href="{{ Storage::url($lot->tender_document) }}" target="_blank"
                                   class="ml-auto btn btn-sm bg-purple-600 hover:bg-purple-700 text-white">
                                    View PDF
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Pricing / Bidding Section -->
                    @if(!$lot->isWithdrawn())
                        @if($lot->auction->isDutch() && $lot->dutch_start_price)
                        {{-- Dutch Auction Buy Card --}}
                        <div class="card mb-6 {{ $lot->isLive() ? 'border-2 border-amber-500' : '' }}"
                             x-data="dutchBuy({{ $lot->id }}, {{ $lot->dutch_start_price }}, {{ $lot->dutch_floor_price }}, {{ $lot->dutch_drop_amount }}, {{ $lot->dutch_drop_interval }}, '{{ $lot->isLive() ? ($lot->dutch_start_time ? $lot->dutch_start_time->toIso8601String() : ($lot->auction->start_time ? $lot->auction->start_time->toIso8601String() : '')) : '' }}', {{ $lot->dutch_end_time ? $lot->dutch_end_time->timestamp : ($lot->end_time ? $lot->end_time->timestamp : 0) }}, {{ $lot->quantity }}, {{ $lot->quantity_sold }}, {{ $lot->total_bids }}, '{{ $lot->dutch_drop_strategy ?: 'constant' }}')"
                             x-init="start()">
                            <div class="p-6">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current Price</div>
                                <div class="text-4xl font-bold text-amber-600 dark:text-amber-400 mb-2" x-text="formattedPrice">
                                    {{ $lot->isLive() ? formatCurrency($lot->getCurrentDutchPrice()) : formatCurrency($lot->dutch_start_price) }}
                                </div>

                                {{-- Price drop countdown --}}
                                <div x-show="!atFloor && nextDropIn > 0" class="mb-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                                    <div class="text-sm text-amber-800 dark:text-amber-200">
                                        Next price drop in <span class="font-bold text-lg" x-text="nextDropIn + 's'"></span>
                                    </div>
                                </div>
                                <div x-show="atFloor && floorCountdown > 0" class="mb-4 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                    <div class="font-bold text-lg text-red-700 dark:text-red-300" x-text="'Last chance! ' + floorCountdown + ' seconds remaining'"></div>
                                </div>
                                <div x-show="atFloor && floorCountdown <= 0" class="mb-4 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                    <div class="font-bold text-red-700 dark:text-red-300">Too late! This lot has closed.</div>
                                </div>

                                {{-- Subject to Confirmation notice (shown after first buy) --}}
                                @if($lot->subject_to_confirmation && $lot->confirmation_message)
                                    <div x-show="totalBids > 0" x-cloak
                                         class="mb-4 p-3 rounded-lg border-2 bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-600">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <div class="text-sm font-semibold text-amber-800 dark:text-amber-200">Subject to Confirmation</div>
                                                <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ $lot->confirmation_message }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Time remaining --}}
                                @if($lot->isLive() && $lot->end_time)
                                    <div class="mb-4">
                                        <div class="text-xs font-medium uppercase tracking-wide mb-2 text-gray-500 dark:text-gray-400">Closing In</div>
                                        <div class="flex items-center gap-1">
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor(timeRemaining / (1000*60*60))).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">HRS</span>
                                            </div>
                                            <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor((timeRemaining / (1000*60)) % 60)).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">MIN</span>
                                            </div>
                                            <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor((timeRemaining / 1000) % 60)).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">SEC</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Quantity info --}}
                                @if($lot->quantity > 1)
                                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Available</span>
                                            <span class="font-semibold" x-text="quantityRemaining + ' of ' + {{ $lot->quantity }}">{{ $lot->quantityRemaining() }} of {{ $lot->quantity }}</span>
                                        </div>
                                    </div>
                                @endif

                                {{-- Buy actions --}}
                                @if($lot->isLive() && !$lot->isDutchSoldOut())
                                    @auth
                                        @if($isOwner)
                                            <x-owner-bid-notice />
                                        @elseif($isRegistered)
                                            <div class="space-y-3" x-show="!(atFloor && floorCountdown <= 0)">
                                                @if($lot->quantity > 1)
                                                    <div>
                                                        <label class="text-sm text-gray-600 dark:text-gray-400 mb-1 block">Quantity</label>
                                                        <input type="number" x-model="buyQuantity" min="1" :max="quantityRemaining" class="input w-32">
                                                    </div>
                                                @endif
                                                <button @click="buy()" :disabled="buying" class="btn btn-accent w-full text-lg py-3" :class="atFloor && floorCountdown > 0 ? 'animate-pulse bg-red-600 hover:bg-red-700 border-red-600' : ''">
                                                    <span x-show="!buying && !(atFloor && floorCountdown > 0)">Bid Now at <span x-text="formattedPrice"></span></span>
                                                    <span x-show="!buying && atFloor && floorCountdown > 0">Last Chance — Bid Now!</span>
                                                    <span x-show="buying" x-cloak>Bidding...</span>
                                                </button>
                                                <div x-show="buyMessage" x-cloak :class="buySuccess ? 'text-green-600' : 'text-red-600'" class="text-sm text-center" x-text="buyMessage"></div>
                                            </div>
                                        @elseif($lot->auction->requiresRegistration())
                                            <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                                                <p class="text-yellow-800 dark:text-yellow-200 mb-3">Register for this event to buy</p>
                                                <form method="POST" action="{{ route('auctions.register', $lot->auction) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary w-full">Register Now</button>
                                                </form>
                                            </div>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary w-full">Login to Bid</a>
                                    @endauth
                                @elseif($lot->isDutchSoldOut() || in_array($lot->status, ['sold', 'pending_confirmation']))
                                    <div class="rounded-lg p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="font-semibold text-green-700 dark:text-green-300">
                                            {{ $lot->status === 'pending_confirmation' ? 'Sold — Subject to Confirmation' : 'Sold Out' }}
                                        </p>
                                    </div>
                                @elseif($lot->status === 'unsold')
                                    <div class="rounded-lg p-4 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center gap-3">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300">Not Sold</p>
                                    </div>
                                @else
                                    <div class="rounded-lg p-4 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center gap-3">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300">
                                            @if($lot->status === 'draft') Coming Soon @else Not Available @endif
                                        </p>
                                    </div>
                                @endif

                                {{-- Stats --}}
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Buyers</div>
                                        <div class="text-lg font-semibold" x-text="totalBids">{{ $lot->total_bids }}</div>
                                    </div>
                                    @if($lot->quantity > 1)
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Sold</div>
                                        <div class="text-lg font-semibold" x-text="quantitySold">{{ $lot->quantity_sold }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @elseif($lot->auction->isSealed())
                        {{-- Sealed Auction Bid Card --}}
                        <div class="card mb-6 {{ $lot->isLive() ? 'border-2 border-purple-500' : '' }}"
                             x-data="{
                                lotId: {{ $lot->id }},
                                amount: {{ $userSealedBid ? $userSealedBid->amount : "''" }},
                                currentBid: {{ $userSealedBid ? $userSealedBid->amount : 'null' }},
                                hasExistingBid: {{ $userSealedBid ? 'true' : 'false' }},
                                submitting: false,
                                message: '',
                                error: false,
                                endTime: '{{ $lot->end_time ? $lot->end_time->toIso8601String() : '' }}',
                                timeRemaining: 0,
                                ended: {{ $lot->auction->status === 'ended' ? 'true' : 'false' }},
                                timer: null,
                                formatCurrency(v) {
                                    return '{{ config('regional.currency.symbol', 'R') }}' + Number(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                },
                                async submit() {
                                    this.submitting = true;
                                    this.message = '';
                                    try {
                                        const res = await fetch('/api/lots/' + this.lotId + '/sealed-bid', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                            body: JSON.stringify({ amount: parseFloat(this.amount) })
                                        });
                                        const data = await res.json();
                                        if (data.success) {
                                            this.currentBid = data.amount;
                                            this.hasExistingBid = true;
                                            this.error = false;
                                            this.message = data.isUpdate ? 'Bid updated successfully' : 'Bid placed successfully';
                                        } else {
                                            this.error = true;
                                            this.message = data.message || 'Failed to place bid';
                                        }
                                    } catch (e) {
                                        this.error = true;
                                        this.message = 'Network error. Please try again.';
                                    }
                                    this.submitting = false;
                                },
                                startTimer() {
                                    if (!this.endTime) return;
                                    const end = new Date(this.endTime).getTime();
                                    this.timer = setInterval(() => {
                                        this.timeRemaining = Math.max(0, end - Date.now());
                                        if (this.timeRemaining <= 0) {
                                            clearInterval(this.timer);
                                            this.ended = true;
                                        }
                                    }, 1000);
                                    this.timeRemaining = Math.max(0, end - Date.now());
                                }
                             }"
                             x-init="startTimer()">
                            <div class="p-6">
                                {{-- Header --}}
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        Sealed Auction — {{ $lot->auction->isSealedHighest() ? 'Highest Bid Wins' : 'Lowest Bid Wins' }}
                                    </span>
                                </div>

                                @if($lot->reserve_price)
                                    <div class="mb-4 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700">
                                        @if($lot->auction->isSealedHighest())
                                            <p class="text-sm text-purple-800 dark:text-purple-200">This lot has a reserve price. Your bid must meet or exceed the reserve to win.</p>
                                        @else
                                            <p class="text-sm text-purple-800 dark:text-purple-200">Maximum price: <span class="font-semibold">{{ formatCurrency($lot->reserve_price) }}</span>. Bids must be at or below this amount.</p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Countdown timer --}}
                                @if($lot->isLive())
                                    <div class="mb-4">
                                        <div class="text-xs font-medium uppercase tracking-wide mb-2 text-gray-500 dark:text-gray-400">Closing In</div>
                                        <div class="flex items-center gap-1">
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor(timeRemaining / (1000*60*60))).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">HRS</span>
                                            </div>
                                            <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor((timeRemaining / (1000*60)) % 60)).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">MIN</span>
                                            </div>
                                            <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                            <div class="flex flex-col items-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 min-w-[3.5rem]">
                                                <span class="text-2xl font-bold font-mono leading-none text-gray-900 dark:text-gray-100" x-text="String(Math.floor((timeRemaining / 1000) % 60)).padStart(2,'0')">00</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">SEC</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Subject to Confirmation notice (shown after user places a bid) --}}
                                @if($lot->subject_to_confirmation && $lot->confirmation_message)
                                    <div x-show="hasExistingBid" x-cloak
                                         class="mb-4 p-3 rounded-lg border-2 bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-600">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <div class="text-sm font-semibold text-amber-800 dark:text-amber-200">Subject to Confirmation</div>
                                                <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ $lot->confirmation_message }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- User's current bid --}}
                                <div x-show="hasExistingBid" x-cloak class="mb-4 p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700">
                                    <div class="text-sm text-purple-700 dark:text-purple-300">Your current bid</div>
                                    <div class="text-xl font-bold text-purple-600 dark:text-purple-400" x-text="formatCurrency(currentBid)"></div>
                                </div>

                                {{-- Bid form --}}
                                @if($lot->isLive())
                                    @auth
                                        @if($isOwner)
                                            <x-owner-bid-notice />
                                        @elseif($isRegistered)
                                            <div class="space-y-3" x-show="!ended">
                                                <div>
                                                    <label class="text-sm text-gray-600 dark:text-gray-400 mb-1 block">
                                                        Enter your bid{{ $lot->auction->isSealedLowest() && $lot->reserve_price ? ' (maximum: ' . formatCurrency($lot->reserve_price) . ')' : '' }}
                                                    </label>
                                                    <input type="number"
                                                           x-model="amount"
                                                           step="0.01"
                                                           min="0.01"
                                                           class="input"
                                                           placeholder="Enter amount"
                                                           :disabled="submitting">
                                                </div>
                                                <button @click="submit()"
                                                        :disabled="submitting || !amount"
                                                        class="btn w-full text-lg py-3 bg-purple-600 hover:bg-purple-700 text-white border-purple-600">
                                                    <span x-show="!submitting" x-text="hasExistingBid ? 'Update Sealed Bid' : 'Place Sealed Bid'">Place Sealed Bid</span>
                                                    <span x-show="submitting" x-cloak>Submitting...</span>
                                                </button>
                                                <div x-show="message" x-cloak
                                                     :class="error ? 'text-red-600' : 'text-green-600'"
                                                     class="text-sm text-center" x-text="message"></div>
                                            </div>

                                            {{-- Info text --}}
                                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    Your bid is secret. No one can see it until the auction ends. You can update your bid any time before closing.
                                                    @if($lot->auction->isSealedHighest())
                                                        The highest bid wins.
                                                    @else
                                                        The lowest bid wins.
                                                    @endif
                                                    A minimum of 2 bidders is required for the lot to sell.
                                                </p>
                                            </div>
                                        @elseif($lot->auction->requiresRegistration())
                                            <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                                                <p class="text-yellow-800 dark:text-yellow-200 mb-3">Register for this auction to place a sealed bid</p>
                                                <form method="POST" action="{{ route('auctions.register', $lot->auction) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary w-full">Register Now</button>
                                                </form>
                                            </div>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary w-full">Login to Bid</a>
                                    @endauth
                                @elseif($lot->status === 'sold' || $lot->status === 'pending_confirmation')
                                    <div class="space-y-3">
                                        <div class="rounded-lg p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700">
                                            <div class="text-sm text-green-700 dark:text-green-300 mb-1">Winning Bid</div>
                                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency($lot->current_bid) }}</div>
                                            @if($lot->status === 'pending_confirmation')
                                                <div class="text-sm text-amber-600 dark:text-amber-400 mt-2 font-medium">Subject to Confirmation</div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($lot->status === 'unsold')
                                    <div class="rounded-lg p-4 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300">Not Sold</p>
                                        @if($lot->reserve_price && $lot->auction->isSealedHighest())
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Reserve not met or insufficient bidders</p>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Insufficient bidders or reserve not met</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="rounded-lg p-4 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300">
                                            @if($lot->status === 'draft') Coming Soon @else Not Available @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @elseif($lot->auction->isLiveFormat())
                            @if(!$lot->auction->isLive())
                                {{-- Auction not live yet (or already ended) — skip the phase-ful bid card --}}
                                <div class="card mb-6 p-6 text-center">
                                    @if($lot->auction->isUpcoming() || $lot->auction->isDraft())
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold uppercase tracking-wide mb-3">
                                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                            Live Auction
                                        </div>
                                        <p class="text-2xl font-bold mb-1 text-gray-900 dark:text-gray-100">
                                            @if($lot->auction->start_time && $lot->auction->start_time->isFuture())
                                                Starts {{ $lot->auction->start_time->diffForHumans() }}
                                            @else
                                                Starting soon
                                            @endif
                                        </p>
                                        @if($lot->auction->start_time)
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $lot->auction->start_time->format('D d M Y — H:i') }}</p>
                                        @endif
                                    @else
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                            @if($lot->status === 'sold') SOLD
                                            @elseif($lot->status === 'unsold') Not sold
                                            @elseif($lot->status === 'withdrawn') Withdrawn
                                            @else Auction ended
                                            @endif
                                            @if($lot->current_bid && $lot->status === 'sold')
                                                — <span class="text-red-600">{{ formatCurrency($lot->current_bid) }}</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            @else
                        {{-- Live (Automated) Auction Bid Card — mobile-first, phase-aware --}}
                        <div class="card mb-6 {{ $lot->isLive() ? 'border-2 border-red-500' : '' }}"
                             x-data="livePulse({
                                lotId: {{ $lot->id }},
                                currentBid: {{ $lot->current_bid ?? $lot->starting_bid ?? 0 }},
                                startingBid: {{ $lot->starting_bid ?? 0 }},
                                increment: {{ $lot->increment }},
                                totalBids: {{ $lot->total_bids }},
                                hasTopBid: {{ $lot->winning_bidder_id === auth()->id() ? 'true' : 'false' }},
                                hasReserve: {{ $lot->hasReserve() ? 'true' : 'false' }},
                                reserveMet: {{ $lot->isReserveMet() ? 'true' : 'false' }},
                                proxyMax: {{ $userProxyMax ?? 'null' }},
                                proxyEnabled: {{ $proxyEnabled ? 'true' : 'false' }},
                                phase: @js($lot->live_phase ?? 'presenting'),
                                phaseEndsAt: @js($lot->live_phase_ends_at?->toIso8601String()),
                                liveOpensAt: @js($lot->live_opens_at?->toIso8601String()),
                                distinctBidders: {{ $lot->distinctLiveBidderCount() }},
                                acceptsBids: {{ $lot->acceptsLiveBids() ? 'true' : 'false' }},
                                isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
                                status: @js($lot->status),
                                userId: {{ auth()->id() ?? 0 }},
                                winningBidderId: {{ $lot->winning_bidder_id ?? 'null' }},
                                userHasBid: {{ $userBid ? 'true' : 'false' }},
                                isCommunity: {{ $lot->isCommunityLot() ? 'true' : 'false' }},
                                declinedAt: @js($lot->declined_at?->toIso8601String()),
                             })"
                             x-init="init()">
                            <div class="p-5 sm:p-6">
                                {{-- Phase Banner — big, unmissable --}}
                                <div class="relative -mx-5 sm:-mx-6 -mt-5 sm:-mt-6 mb-5 px-5 py-4 sm:py-5 text-center text-white font-black uppercase tracking-tight transition-colors"
                                     :class="{
                                         'bg-blue-600': phase === 'presenting',
                                         'bg-gray-600': phase === 'intermission',
                                         'bg-teal-600': phase === 'open_call',
                                         'bg-green-600': phase === 'active',
                                         'bg-amber-500 animate-pulse': phase === 'going_once',
                                         'bg-orange-600 animate-pulse': phase === 'going_twice',
                                         'bg-red-800': phase === 'closed',
                                     }">
                                    <button type="button" @click.stop="toggleSfx()" :title="sfxEnabled ? 'Mute sound' : 'Unmute sound'" class="absolute top-2 right-2 p-1 rounded hover:bg-black/20 focus:outline-none focus:ring-2 focus:ring-white/50">
                                        <svg x-show="sfxEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M9 9H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V5L9 9z"/></svg>
                                        <svg x-show="!sfxEnabled" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9H5a1 1 0 00-1 1v4a1 1 0 001 1h4l4 4V5L9 9zM17 9l6 6m0-6l-6 6"/></svg>
                                    </button>
                                    <div class="text-2xl sm:text-3xl md:text-4xl leading-none" x-text="phaseLabel()"></div>
                                    <div class="text-base sm:text-lg mt-2 font-mono" x-show="showPhaseCountdown()" x-cloak x-text="phaseCountdownLabel()"></div>
                                </div>

                                {{-- Prominent "You're winning" banner --}}
                                <div x-show="hasTopBid && totalBids > 0 && phase !== 'closed'" x-cloak
                                     class="mb-4 p-4 rounded-lg border-2 border-green-500 bg-green-50 dark:bg-green-900/30 flex items-center gap-3 animate-pulse">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1z"/>
                                    </svg>
                                    <div>
                                        <div class="text-lg font-bold text-green-800 dark:text-green-200 leading-tight">You have the highest bid</div>
                                        <div class="text-sm text-green-700 dark:text-green-300">No need to re-bid unless you're outbid.</div>
                                    </div>
                                </div>

                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1" x-text="totalBids > 0 ? 'Current Bid' : 'Starting Bid'"></div>
                                <div class="text-4xl font-bold mb-4 transition-colors"
                                     :class="hasTopBid && totalBids > 0 && phase !== 'closed' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                     x-text="formatCurrency(currentBid)"></div>

                                @if($lot->hasReserve())
                                    <div class="mb-4 p-3 rounded-lg border-2 transition-all"
                                         :class="reserveMet ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500'">
                                        <div class="text-sm font-semibold" :class="reserveMet ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200'" x-text="reserveMet ? 'Reserve met' : 'Reserve not met'"></div>
                                    </div>
                                @endif

                                {{-- 2-bidder validation window progress --}}
                                <div class="mb-4 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-gray-700 dark:text-gray-300">Distinct bidders</span>
                                        <span class="font-bold"
                                              :class="distinctBidders >= 2 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'"
                                              x-text="distinctBidders + ' / 2'"></span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="validationWindowLabel()"></div>
                                </div>

                                {{-- Bid feedback --}}
                                @auth
                                <div x-show="bidFeedback" x-cloak
                                     class="mb-4 p-3 rounded-lg border-2"
                                     :class="bidError ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : 'bg-green-50 dark:bg-green-900/20 border-green-500'">
                                    <div class="text-sm font-medium" :class="bidError ? 'text-red-800 dark:text-red-200' : 'text-green-800 dark:text-green-200'" x-text="bidFeedback"></div>
                                </div>
                                @endauth

                                @auth
                                    @if($isOwner && $lot->isLive())
                                        <x-owner-bid-notice />
                                    @elseif($lot->isLive())
                                        <div class="space-y-3">
                                            <div>
                                                <label class="label">Your bid</label>
                                                <input type="number"
                                                       step="0.01"
                                                       :min="minimumBid"
                                                       :value="minimumBid"
                                                       x-model.number="bidAmount"
                                                       :disabled="!acceptsBids"
                                                       class="input text-lg">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="'Minimum bid: ' + formatCurrency(minimumBid)"></p>
                                            </div>

                                            <button @click="placeLiveBid()"
                                                    :disabled="submitting || !acceptsBids || phase === 'closed'"
                                                    class="btn btn-primary w-full py-4 text-lg font-bold"
                                                    :class="{
                                                        'bg-gray-500 hover:bg-gray-600': acceptsBids && hasTopBid && totalBids > 0,
                                                        'bg-red-600 hover:bg-red-700': acceptsBids && !(hasTopBid && totalBids > 0),
                                                        'opacity-50 cursor-not-allowed': !acceptsBids || submitting,
                                                    }">
                                                <span x-show="!submitting" x-text="bidButtonLabel()"></span>
                                                <span x-show="submitting" x-cloak>Placing bid…</span>
                                            </button>

                                            @if($proxyEnabled)
                                                <details class="text-sm">
                                                    <summary class="cursor-pointer text-gray-700 dark:text-gray-300">Set a proxy (max) bid</summary>
                                                    <div class="mt-3 space-y-2">
                                                        <input type="number"
                                                               step="0.01"
                                                               :min="minimumBid"
                                                               x-model.number="proxyAmount"
                                                               placeholder="Max you'd pay"
                                                               class="input">
                                                        <button @click="setProxy()"
                                                                :disabled="submitting"
                                                                class="btn btn-outline w-full" x-text="proxyMax ? 'Update proxy' : 'Set proxy'"></button>
                                                        <button x-show="proxyMax" @click="cancelProxy()" class="text-xs text-red-600">Cancel proxy</button>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>

                                        {{-- Sticky mobile bid bar (bottom, one-hand reach) --}}
                                        <div x-show="phase !== 'closed'"
                                             class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t-2 shadow-2xl z-40 px-3 py-2 safe-bottom transition-colors"
                                             :class="hasTopBid && totalBids > 0 ? 'border-green-500' : 'border-red-500'"
                                             style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));">
                                            <div x-show="hasTopBid && totalBids > 0" x-cloak class="text-center text-xs font-bold text-green-700 dark:text-green-400 mb-1">
                                                ✓ You have the highest bid
                                            </div>
                                            <button @click="placeLiveBid()"
                                                    :disabled="submitting || !acceptsBids"
                                                    class="w-full rounded-lg font-bold text-base py-3 transition-colors"
                                                    :class="{
                                                        'bg-gray-500 hover:bg-gray-600 text-white': acceptsBids && hasTopBid && totalBids > 0,
                                                        'bg-red-600 hover:bg-red-700 text-white': acceptsBids && !(hasTopBid && totalBids > 0),
                                                        'bg-gray-300 text-gray-500': !acceptsBids,
                                                    }"
                                                    style="min-height: 52px;">
                                                <span x-show="!submitting" x-text="bidButtonLabel()"></span>
                                                <span x-show="submitting" x-cloak>Placing…</span>
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary w-full py-3">Login to bid</a>
                                @endauth

                                @if($lot->status === 'sold' || $lot->status === 'unsold')
                                    <div class="rounded-lg p-4 bg-gray-100 dark:bg-gray-800 mt-4 text-center">
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                            @if($lot->status === 'sold') SOLD @else Not sold @endif
                                            @if($lot->current_bid && $lot->status === 'sold')
                                                <span class="text-red-600">{{ formatCurrency($lot->current_bid) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                            @endif

                        @else
                        {{-- Standard English Auction Bid Card --}}
                        <div class="card mb-6 {{ $lot->isLive() ? 'border-2 border-primary-500' : '' }}"
                             x-data="bidding({{ $lot->id }}, {{ $lot->current_bid ?? $lot->starting_bid ?? 0 }}, {{ $lot->increment }}, '{{ $lot->end_time ? $lot->end_time->toIso8601String() : '' }}', {{ $lot->total_bids }}, {{ $lot->winning_bidder_id === auth()->id() ? 'true' : 'false' }}, {{ $userProxyMax ?? 'null' }}, {{ $proxyEnabled ? 'true' : 'false' }}, {{ $lot->hasReserve() ? 'true' : 'false' }}, {{ $lot->isReserveMet() ? 'true' : 'false' }})"
                             x-init="init()">
                        <div class="p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2" x-text="totalBids > 0 ? 'Current Bid' : 'Starting Bid'">{{ $lot->current_bid ? 'Current Bid' : 'Starting Bid' }}</div>
                            <div class="text-4xl font-bold text-primary-600 dark:text-primary-400 mb-4" x-text="formatCurrency(currentBid)">
                                {{ formatCurrency($lot->current_bid ?? $lot->starting_bid ?? 0) }}
                            </div>

                            <!-- Reserve Price Indicator (reactive) -->
                            @if($lot->hasReserve())
                                <div class="mb-4 p-3 rounded-lg border-2 transition-all duration-300"
                                     :class="reserveMet ? 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-600' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 dark:border-yellow-600'">
                                    <div class="flex items-center gap-2">
                                        <template x-if="reserveMet">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </template>
                                        <template x-if="!reserveMet">
                                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </template>
                                        <div>
                                            <div class="text-sm font-semibold" :class="reserveMet ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200'" x-text="reserveMet ? 'Reserve Met' : 'Reserve Not Met'"></div>
                                            <div class="text-xs" :class="reserveMet ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300'" x-text="reserveMet ? 'Lot will sell at current bid' : 'Bid must exceed reserve to win'"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Reserve Met Celebration (flashes when reserve is crossed) --}}
                                <div x-show="showReserveCelebration" x-cloak
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="mb-4 p-4 rounded-lg bg-gradient-to-r from-green-400 to-emerald-500 text-white text-center">
                                    <div class="text-lg font-bold">Reserve Price Met!</div>
                                    <div class="text-sm opacity-90">This lot will definitely sell</div>
                                </div>
                            @endif

                            {{-- Bid Feedback Banner (confirmation / outbid / error) --}}
                            @auth
                            <div x-show="showBidFeedback" x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-200"
                                 class="mb-4 p-3 rounded-lg border-2"
                                 :class="{
                                     'bg-green-50 dark:bg-green-900/20 border-green-500': bidFeedbackType === 'success',
                                     'bg-red-50 dark:bg-red-900/20 border-red-500': bidFeedbackType === 'outbid' || bidFeedbackType === 'error',
                                 }">
                                <div class="flex items-center gap-2">
                                    <template x-if="bidFeedbackType === 'success'">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </template>
                                    <template x-if="bidFeedbackType === 'outbid'">
                                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    </template>
                                    <template x-if="bidFeedbackType === 'error'">
                                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    </template>
                                    <span class="text-sm font-medium"
                                          :class="{
                                              'text-green-800 dark:text-green-200': bidFeedbackType === 'success',
                                              'text-red-800 dark:text-red-200': bidFeedbackType === 'outbid' || bidFeedbackType === 'error',
                                          }"
                                          x-text="bidFeedback"></span>
                                </div>
                                {{-- One-tap rebid button when outbid --}}
                                <button x-show="bidFeedbackType === 'outbid'" x-cloak
                                        @click="placeBid(minimumBid); showBidFeedback = false;"
                                        class="mt-2 w-full btn bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2">
                                    <span x-text="'Bid ' + formatCurrency(minimumBid) + ' Now'"></span>
                                </button>
                            </div>
                            @endauth

                            {{-- Subject to Confirmation notice (shown after first bid) --}}
                            @if($lot->subject_to_confirmation && $lot->confirmation_message)
                                <div x-show="totalBids > 0" x-cloak
                                     class="mb-4 p-3 rounded-lg border-2 bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-600">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <div class="text-sm font-semibold text-amber-800 dark:text-amber-200">Subject to Confirmation</div>
                                            <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ $lot->confirmation_message }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Time Remaining (urgency-aware) -->
                            @if($lot->isLive())
                                <div class="mb-4">
                                    <div class="text-xs font-medium uppercase tracking-wide mb-2"
                                         :class="timerLabelClass">
                                        <span x-show="!goingText">Closing In</span>
                                        <span x-show="goingText" x-cloak class="text-red-600 dark:text-red-400 text-sm font-bold animate-pulse" x-text="goingText"></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="flex flex-col items-center rounded-lg px-3 py-2 min-w-[3.5rem] transition-colors duration-300"
                                             :class="timerColorClass">
                                            <span class="text-2xl font-bold font-mono leading-none"
                                                  :class="timerTextClass"
                                                  x-text="String(Math.floor(timeRemaining / (1000*60*60))).padStart(2,'0')">00</span>
                                            <span class="text-xs mt-0.5" :class="timerLabelClass">HRS</span>
                                        </div>
                                        <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                        <div class="flex flex-col items-center rounded-lg px-3 py-2 min-w-[3.5rem] transition-colors duration-300"
                                             :class="timerColorClass">
                                            <span class="text-2xl font-bold font-mono leading-none"
                                                  :class="timerTextClass"
                                                  x-text="String(Math.floor((timeRemaining / (1000*60)) % 60)).padStart(2,'0')">00</span>
                                            <span class="text-xs mt-0.5" :class="timerLabelClass">MIN</span>
                                        </div>
                                        <span class="text-2xl font-bold text-gray-400 px-0.5">:</span>
                                        <div class="flex flex-col items-center rounded-lg px-3 py-2 min-w-[3.5rem] transition-colors duration-300"
                                             :class="timerColorClass">
                                            <span class="text-2xl font-bold font-mono leading-none"
                                                  :class="timerTextClass"
                                                  x-text="String(Math.floor((timeRemaining / 1000) % 60)).padStart(2,'0')">00</span>
                                            <span class="text-xs mt-0.5" :class="timerLabelClass">SEC</span>
                                        </div>
                                    </div>

                                    {{-- Soft close notice --}}
                                    <div x-show="urgencyLevel === 'urgent' || urgencyLevel === 'critical'" x-cloak
                                         class="mt-2 text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded px-3 py-1.5">
                                        Auction will extend if a bid is placed in the final minutes
                                    </div>
                                </div>

                                {{-- Bid gap nudge (only when outbid) --}}
                                @auth
                                <div x-show="bidGapText && !hasTopBid && totalBids > 0" x-cloak
                                     class="mb-4 text-center text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    <span x-text="bidGapText"></span>
                                </div>
                                @endauth

                                <!-- Bidding Actions -->
                                @auth
                                    @if($isOwner)
                                        <x-owner-bid-notice />
                                    @elseif($isRegistered)
                                        <!-- Winning badge (shown reactively via Alpine) -->
                                        <div x-show="hasTopBid" x-cloak
                                             class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-400 dark:border-yellow-600 rounded-lg p-6">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="bg-yellow-400 dark:bg-yellow-500 rounded-full p-2">
                                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1zm3.5 11.066l-.492-1.567a6.755 6.755 0 0 0 1.989 0l-.493 1.567h-1.004zM9.666 3.621h4.668a1 1 0 1 1 0 2h-4.668a1 1 0 1 1 0-2z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-lg font-bold text-yellow-900 dark:text-yellow-100">You're Winning!</p>
                                                    <p class="text-sm text-yellow-800 dark:text-yellow-200">You have the highest bid</p>
                                                </div>
                                            </div>
                                            <div class="bg-white/50 dark:bg-gray-800/50 rounded px-3 py-2">
                                                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                                                    Check back to see if you're still winning
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Active Proxy Badge -->
                                        <div x-show="proxyMax && proxyEnabled" x-cloak
                                             class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">Auto-bidding active</p>
                                                    <p class="text-sm text-blue-700 dark:text-blue-300">Up to <span x-text="formatCurrency(proxyMax)" class="font-bold"></span></p>
                                                </div>
                                                <button @click="cancelProxy()" :disabled="proxyLoading"
                                                        class="text-sm px-3 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/60 transition">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Bid form (hidden when winning and no proxy tab needed) -->
                                        <div x-show="!hasTopBid || (proxyEnabled && !proxyMax)" class="space-y-4">
                                            <!-- Tab Switcher (only when proxy enabled) -->
                                            <div x-show="proxyEnabled" class="flex border-b border-gray-200 dark:border-gray-700 mb-2">
                                                <button @click="bidMode = 'manual'" type="button"
                                                        :class="bidMode === 'manual' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                                        class="px-4 py-2 text-sm font-medium border-b-2 transition">
                                                    Place Bid
                                                </button>
                                                <button @click="bidMode = 'proxy'" type="button"
                                                        :class="bidMode === 'proxy' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                                        class="px-4 py-2 text-sm font-medium border-b-2 transition">
                                                    Set Max Bid
                                                </button>
                                            </div>

                                            <!-- Manual Bid Tab -->
                                            <div x-show="bidMode === 'manual' || !proxyEnabled">
                                                <div x-show="!hasTopBid" class="flex flex-col sm:flex-row gap-2">
                                                    <input type="number"
                                                           x-model="customAmount"
                                                           :min="minimumBid"
                                                           :step="increment"
                                                           placeholder="Custom amount"
                                                           class="input flex-1">
                                                    <button @click="placeBid(parseFloat(customAmount))" class="btn btn-primary w-full sm:w-auto">
                                                        Place Bid
                                                    </button>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                    Minimum increment: {{ formatCurrency($lot->increment) }}
                                                </p>
                                            </div>

                                            <!-- Proxy Bid Tab -->
                                            <div x-show="bidMode === 'proxy' && proxyEnabled" x-cloak>
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    <input type="number"
                                                           x-model="proxyAmount"
                                                           :min="minimumBid"
                                                           :step="increment"
                                                           placeholder="Your maximum bid"
                                                           class="input flex-1">
                                                    <button @click="setProxy(parseFloat(proxyAmount))"
                                                            :disabled="proxyLoading"
                                                            class="btn btn-primary w-full sm:w-auto">
                                                        <span x-show="!proxyLoading">Set Max Bid</span>
                                                        <span x-show="proxyLoading" x-cloak>Setting...</span>
                                                    </button>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                    The platform will auto-bid the minimum increment needed to keep you winning, up to your max. You can cancel at any time.
                                                </p>
                                            </div>
                                        </div>
                                    @elseif($lot->auction->requiresRegistration())
                                        <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                                            <p class="text-yellow-800 dark:text-yellow-200 mb-3">Register for this event to bid</p>
                                            <form method="POST" action="{{ route('auctions.register', $lot->auction) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-primary w-full">Register Now</button>
                                            </form>
                                        </div>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary w-full">Login to Bid</a>
                                @endauth
                            @else
                                <div class="rounded-lg p-4 flex items-center gap-3
                                    @if($lot->status === 'sold' || $lot->status === 'pending_confirmation') bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700
                                    @elseif($lot->status === 'unsold') bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                    @elseif($lot->status === 'withdrawn') bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-700
                                    @else bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                    @endif">
                                    <div class="rounded-full p-2 flex-shrink-0
                                        @if($lot->status === 'sold' || $lot->status === 'pending_confirmation') bg-green-100 dark:bg-green-800
                                        @elseif($lot->status === 'withdrawn') bg-orange-100 dark:bg-orange-800
                                        @else bg-gray-200 dark:bg-gray-600
                                        @endif">
                                        @if($lot->status === 'sold' || $lot->status === 'pending_confirmation')
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @elseif($lot->status === 'withdrawn')
                                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold
                                            @if($lot->status === 'sold' || $lot->status === 'pending_confirmation') text-green-700 dark:text-green-300
                                            @elseif($lot->status === 'withdrawn') text-orange-700 dark:text-orange-300
                                            @else text-gray-700 dark:text-gray-300
                                            @endif">
                                            @if($lot->status === 'sold') Sold
                                            @elseif($lot->status === 'pending_confirmation') Sold — Subject to Confirmation
                                            @elseif($lot->status === 'unsold') Not Sold
                                            @elseif($lot->status === 'withdrawn') Withdrawn
                                            @elseif($lot->status === 'pending') Coming Soon
                                            @else Bidding Not Open
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($lot->status === 'sold') This lot has been won
                                            @elseif($lot->status === 'pending_confirmation') The auctioneer will confirm or reject this sale
                                            @elseif($lot->status === 'unsold')
                                                @if($lot->total_bids === 0) No bids were placed on this lot
                                                @else Reserve price was not met
                                                @endif
                                            @elseif($lot->status === 'withdrawn') This lot has been removed from the auction
                                            @elseif($lot->status === 'pending') This lot opens when the auction starts
                                            @else This lot is not currently open for bidding
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Stats -->
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Bids</div>
                                    <div class="text-lg font-semibold" x-text="totalBids">{{ $lot->total_bids }}</div>
                                </div>
                                @if($lot->hasReserve())
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Reserve</div>
                                        <div class="text-lg font-semibold">
                                            <span x-show="reserveMet" class="text-green-600">Met</span>
                                            <span x-show="!reserveMet" x-cloak class="text-yellow-600">Not Met</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Sticky Mobile Bid Bar (visible on small screens when bid form is scrolled out of view) --}}
                            @if($lot->isLive() && !$isOwner)
                            @auth
                            <div x-show="timeRemaining > 0 && !hasEnded" x-cloak
                                 class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-t-2 border-primary-500 shadow-lg p-3 lg:hidden">
                                <div class="flex items-center gap-3 max-w-xl mx-auto">
                                    <div class="flex-shrink-0">
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="totalBids > 0 ? 'Current' : 'Starting'"></div>
                                        <div class="text-lg font-bold text-primary-600 dark:text-primary-400" x-text="formatCurrency(currentBid)"></div>
                                    </div>
                                    <div class="flex-1 flex gap-2">
                                        <template x-if="!hasTopBid">
                                            <button @click="placeBid(minimumBid)"
                                                    class="flex-1 btn btn-primary py-3 text-sm font-bold">
                                                <span x-text="'Bid ' + formatCurrency(minimumBid)"></span>
                                            </button>
                                        </template>
                                        <template x-if="hasTopBid">
                                            <div class="flex-1 text-center py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-bold">
                                                You're Winning!
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex-shrink-0 text-right">
                                        <div class="text-xs" :class="timerLabelClass" x-text="goingText || 'Ends in'"></div>
                                        <div class="text-sm font-mono font-bold" :class="timerTextClass"
                                             x-text="String(Math.floor(timeRemaining / (1000*60*60))).padStart(2,'0') + ':' + String(Math.floor((timeRemaining / (1000*60)) % 60)).padStart(2,'0') + ':' + String(Math.floor((timeRemaining / 1000) % 60)).padStart(2,'0')"></div>
                                    </div>
                                </div>
                            </div>
                            @endauth
                            @endif

                            <!-- Bidding Ended Modal -->
                            <div x-show="showEndedModal"
                                 x-cloak
                                 @click.away="closeEndedModal()"
                                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                                 style="display: none;">
                                <div @click.stop
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform scale-90"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center"
                                     :class="{
                                         'border-4 border-green-500': endedModalColor === 'green',
                                         'border-4 border-red-500': endedModalColor === 'red',
                                         'border-4 border-gray-500': endedModalColor === 'gray'
                                     }">

                                    <!-- Icon -->
                                    <div class="mb-6">
                                        <div class="mx-auto w-24 h-24 rounded-full flex items-center justify-center"
                                             :class="{
                                                 'bg-green-100 dark:bg-green-900': endedModalColor === 'green',
                                                 'bg-red-100 dark:bg-red-900': endedModalColor === 'red',
                                                 'bg-gray-100 dark:bg-gray-700': endedModalColor === 'gray'
                                             }">
                                            <!-- Trophy for winner -->
                                            <template x-if="endedModalColor === 'green'">
                                                <svg class="w-16 h-16 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.946.946 0 0 0-.584.859 6.753 6.753 0 0 0 6.138 6.66l2.103 6.681a1 1 0 0 0 .95.691h1.596a1 1 0 0 0 .95-.691l2.103-6.681a6.753 6.753 0 0 0 6.138-6.66.946.946 0 0 0-.584-.859 47.17 47.17 0 0 0-3.071-.543V2.62a1 1 0 0 0-1-1h-9.5a1 1 0 0 0-1 1zm3.5 11.066l-.492-1.567a6.755 6.755 0 0 0 1.989 0l-.493 1.567h-1.004zM9.666 3.621h4.668a1 1 0 1 1 0 2h-4.668a1 1 0 1 1 0-2z"/>
                                                </svg>
                                            </template>
                                            <!-- X for loser -->
                                            <template x-if="endedModalColor === 'red'">
                                                <svg class="w-16 h-16 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </template>
                                            <!-- No entry sign for no bids -->
                                            <template x-if="endedModalColor === 'gray'">
                                                <svg class="w-16 h-16 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" stroke-width="2" fill="none"/>
                                                    <line x1="4" y1="12" x2="20" y2="12" stroke-width="2.5"/>
                                                </svg>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <h2 class="text-3xl font-bold mb-4"
                                        :class="{
                                            'text-green-600 dark:text-green-400': endedModalColor === 'green',
                                            'text-red-600 dark:text-red-400': endedModalColor === 'red',
                                            'text-gray-600 dark:text-gray-400': endedModalColor === 'gray'
                                        }"
                                        x-text="endedModalTitle">
                                        BIDDING CLOSED
                                    </h2>

                                    <!-- Message -->
                                    <p class="text-lg text-gray-700 dark:text-gray-300 mb-6" x-text="endedModalMessage">
                                        This lot has ended.
                                    </p>

                                    <!-- Current Bid Display -->
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Final Bid</div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="formatCurrency(currentBid)">
                                            R0.00
                                        </div>
                                    </div>

                                    <!-- Close Button -->
                                    <button @click="closeEndedModal()"
                                            class="btn w-full"
                                            :class="{
                                                'btn-primary bg-green-600 hover:bg-green-700': endedModalColor === 'green',
                                                'bg-red-600 hover:bg-red-700 text-white': endedModalColor === 'red',
                                                'btn-outline': endedModalColor === 'gray'
                                            }">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif {{-- end isDutch/isEnglish --}}
                    @endif {{-- end !isWithdrawn --}}

                    <!-- Pending Confirmation Notice (for winning bidder) -->
                    @auth
                        @if($lot->status === 'pending_confirmation' && $lot->winning_bidder_id === auth()->id())
                            <div class="card mb-6 border-amber-300 dark:border-amber-600">
                                <div class="p-6">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-700 dark:text-amber-400">Awaiting Confirmation</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                You have the highest bid of <span class="font-semibold text-primary-600">{{ formatCurrency($lot->current_bid) }}</span> on this lot.
                                                This sale is subject to confirmation by the auctioneer. You will be notified once the sale is confirmed or rejected.
                                            </p>
                                            @if($lot->confirmation_message)
                                                <p class="text-sm text-amber-700 dark:text-amber-400 mt-2 italic">"{{ $lot->confirmation_message }}"</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Payment Section (won lots) -->
                    @auth
                        @if($lot->status === 'sold' && $lot->winning_bidder_id === auth()->id())
                            <div class="card mb-6">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">You Won This Lot</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        Winning bid: <span class="font-semibold text-primary-600">{{ formatCurrency($lot->current_bid) }}</span>
                                        @if($lot->auction->buyers_premium_percentage > 0)
                                            + {{ $lot->auction->buyers_premium_percentage }}% buyer's premium =
                                            <span class="font-semibold">{{ formatCurrency($lot->getTotalAmountDue()) }}</span>
                                        @endif
                                    </p>

                                    @if($lot->payment_status === 'paid_platform' || $lot->payment_status === 'paid_offline')
                                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center gap-3">
                                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-green-900 dark:text-green-100">Payment Complete</p>
                                                <p class="text-sm text-green-700 dark:text-green-300">Contact the auctioneer to arrange collection.</p>
                                            </div>
                                        </div>
                                        @if($lot->auction->auctioneer->whatsapp_number)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lot->auction->auctioneer->whatsapp_number) }}?text=Hi, I have paid for Lot %23{{ $lot->lot_number }} ({{ urlencode($lot->title) }}) from {{ urlencode($lot->auction->title) }}. I would like to arrange collection."
                                               target="_blank" class="btn btn-primary w-full mt-3">
                                                Contact Auctioneer on WhatsApp
                                            </a>
                                        @endif
                                    @else
                                        @if($lot->auction->hasOnlinePayment())
                                            <form action="{{ route('direct-payment.lots') }}" method="POST" class="mb-3">
                                                @csrf
                                                <input type="hidden" name="lot_ids" value="{{ $lot->id }}">
                                                <button type="submit" class="btn btn-primary w-full">
                                                    Pay Now — {{ formatCurrency($lot->getTotalAmountDue()) }}
                                                </button>
                                            </form>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-3">
                                                Or contact the auctioneer to arrange offline payment
                                            </p>
                                        @else
                                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                                <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Arrange Collection</p>
                                                <p class="text-sm text-blue-700 dark:text-blue-300">Contact the auctioneer to arrange payment and collection.</p>
                                            </div>
                                        @endif
                                        @if($lot->auction->auctioneer->whatsapp_number)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lot->auction->auctioneer->whatsapp_number) }}?text=Hi, I won Lot %23{{ $lot->lot_number }} ({{ urlencode($lot->title) }}) from {{ urlencode($lot->auction->title) }}. I would like to arrange payment and collection."
                                               target="_blank" class="btn btn-primary w-full mt-3">
                                                Contact Auctioneer on WhatsApp
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Watchlist (not shown for Dutch or Live auctions) -->
                    @if(!$lot->auction->isDutch() && !$lot->auction->isLiveFormat())
                    @auth
                        <button onclick="toggleWatchlist({{ $lot->id }})"
                                class="btn btn-outline w-full mb-6">
                            @if($isWatchlisted)
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                                Remove from Watchlist
                            @else
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                </svg>
                                Add to Watchlist
                            @endif
                        </button>
                    @endauth
                    @endif

                    <!-- Bid History -->
                    @if($lot->auction->isSealed())
                        {{-- Sealed: only show winning bid after auction ends --}}
                        @if($lot->auction->status === 'ended' && $lot->status === 'sold' && $lot->winningBidder)
                            <div class="card">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Result</h3>
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold">Winner: Paddle #{{ $lot->winningBidder->paddle_number }}</span>
                                        <span class="font-semibold text-green-600">{{ formatCurrency($lot->current_bid) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @elseif($lot->bids->count() > 0)
                        <div class="card">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bid History</h3>
                                <div class="space-y-3">
                                    @foreach($lot->bids->take(10) as $bid)
                                        @php
                                            $ps = $bid->user->profileScore();
                                            $psBadge = $ps >= 100
                                                ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
                                                : ($ps >= 80
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                                    : ($ps >= 50
                                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'));
                                        @endphp
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-semibold">Paddle #{{ $bid->user->paddle_number }}</span>
                                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $psBadge }}" title="Profile score">{{ $ps }}%</span>
                                                @if($bid->is_proxy)
                                                    <span class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded font-medium">Auto</span>
                                                @endif
                                                <span class="text-sm text-gray-500">{{ $bid->placed_at->diffForHumans() }}</span>
                                            </div>
                                            <span class="font-semibold text-primary-600">{{ formatCurrency($bid->amount) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $lotSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $lot->title,
            'description' => $lot->description ?? $lot->title,
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format((float)($lot->current_bid ?: $lot->starting_bid), 2, '.', ''),
                'priceCurrency' => 'ZAR',
                'availability' => 'https://schema.org/' . ($lot->status === 'live' ? 'InStock' : ($lot->status === 'sold' ? 'SoldOut' : 'Discontinued')),
                'url' => url()->current(),
            ],
            'seller' => [
                '@type' => 'Organization',
                'name' => $lot->auction->auctioneer->business_name,
            ],
        ];
        if ($lot->images->count() > 0) {
            $lotSchema['image'] = $lot->images->first()->optimized_url;
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Auctions', 'item' => route('auctions.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $lot->auction->title, 'item' => route('auctions.show', $lot->auction)],
                ['@type' => 'ListItem', 'position' => 4, 'name' => 'Lot #' . $lot->lot_number],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($lotSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script>
    function toggleWatchlist(lotId) {
        fetch(`/lots/${lotId}/watchlist`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
    // Drop strategy phase definitions (mirrors PHP Lot::DROP_STRATEGIES)
    const DROP_STRATEGIES = {
        constant: { phases: [{ range: 1.0, drop_mult: 1.0, interval_mult: 1.0 }] },
        fast_sell: { phases: [
            { range: 0.30, drop_mult: 3.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.5, interval_mult: 2.0 },
        ]},
        max_value: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
        high_drama: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.75 },
            { range: 0.40, drop_mult: 0.5, interval_mult: 1.5 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
    };

    function calcDutchPrice(elapsedSec, startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return floorPrice;

        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0, priceDropped = 0;

        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;

            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            const phaseTime = dropsInPhase * effectiveInterval;
            const timeIntoPhase = elapsedSec - timeConsumed;

            if (timeIntoPhase < phaseTime) {
                const dropsCompleted = Math.floor(timeIntoPhase / effectiveInterval);
                priceDropped += dropsCompleted * effectiveDrop;
                return Math.max(startPrice - priceDropped, floorPrice);
            }

            priceDropped += dropsInPhase * effectiveDrop;
            timeConsumed += phaseTime;
        }
        return floorPrice;
    }

    function calcDutchNextDrop(elapsedSec, startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return 0;

        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0;

        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;

            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            const phaseTime = dropsInPhase * effectiveInterval;
            const timeIntoPhase = elapsedSec - timeConsumed;

            if (timeIntoPhase < phaseTime) {
                return effectiveInterval - (timeIntoPhase % effectiveInterval);
            }
            timeConsumed += phaseTime;
        }
        return 0;
    }

    function calcFloorReachedAt(startPrice, floorPrice, baseDropAmount, baseDropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return 0;
        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let timeConsumed = 0, priceDropped = 0;
        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = baseDropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(baseDropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;
            const dropsInPhase = Math.ceil(phaseRange / effectiveDrop);
            for (let d = 1; d <= dropsInPhase; d++) {
                priceDropped += effectiveDrop;
                if (startPrice - priceDropped <= floorPrice) {
                    return timeConsumed + d * effectiveInterval;
                }
            }
            timeConsumed += dropsInPhase * effectiveInterval;
        }
        return timeConsumed;
    }

    function fmtPrice(price) {
        return 'R' + parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function dutchBuy(lotId, startPrice, floorPrice, dropAmount, dropInterval, startTimeIso, endTimestamp, quantity, quantitySold, totalBids, strategy) {
        return {
            currentPrice: startPrice,
            formattedPrice: fmtPrice(startPrice),
            nextDropIn: 0,
            atFloor: false,
            floorCountdown: 0,
            timeRemaining: 0,
            quantityRemaining: quantity - quantitySold,
            quantitySold: quantitySold,
            totalBids: totalBids,
            buyQuantity: 1,
            buying: false,
            buyMessage: '',
            buySuccess: false,
            _interval: null,

            start() {
                this.tick();
                this._interval = setInterval(() => this.tick(), 1000);
            },

            tick() {
                // Update time remaining
                if (endTimestamp > 0) {
                    this.timeRemaining = Math.max(0, (endTimestamp * 1000) - Date.now());
                }

                // Update Dutch price
                if (!startTimeIso) {
                    this.currentPrice = startPrice;
                    this.formattedPrice = fmtPrice(startPrice);
                    return;
                }

                const startMs = new Date(startTimeIso).getTime();
                const nowMs = Date.now();

                if (nowMs < startMs) {
                    this.currentPrice = startPrice;
                    this.formattedPrice = fmtPrice(startPrice);
                    return;
                }

                const elapsedSec = Math.floor((nowMs - startMs) / 1000);
                const price = calcDutchPrice(elapsedSec, startPrice, floorPrice, dropAmount, dropInterval, strategy);

                this.currentPrice = price;
                this.formattedPrice = fmtPrice(price);
                this.atFloor = price <= floorPrice;
                this.nextDropIn = this.atFloor ? 0 : calcDutchNextDrop(elapsedSec, startPrice, floorPrice, dropAmount, dropInterval, strategy);

                if (this.atFloor && startTimeIso) {
                    const floorReachedAt = calcFloorReachedAt(startPrice, floorPrice, dropAmount, dropInterval, strategy);
                    const floorDeadlineMs = startMs + (floorReachedAt + {{ App\Models\Lot::DUTCH_FLOOR_BUFFER }}) * 1000;
                    this.floorCountdown = Math.max(0, Math.ceil((floorDeadlineMs - nowMs) / 1000));
                } else {
                    this.floorCountdown = 0;
                }
            },

            async buy() {
                this.buying = true;
                this.buyMessage = '';
                try {
                    const res = await fetch('/api/lots/' + lotId + '/buy', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ quantity: parseInt(this.buyQuantity) })
                    });
                    const data = await res.json();
                    this.buySuccess = data.success;
                    this.buyMessage = data.message;
                    if (data.success) {
                        this.quantityRemaining = data.quantityRemaining;
                        this.quantitySold = (quantity - data.quantityRemaining);
                        this.totalBids++;
                        if (data.soldOut) {
                            setTimeout(() => location.reload(), 2000);
                        }
                    }
                } catch (e) {
                    this.buyMessage = 'Network error. Please try again.';
                    this.buySuccess = false;
                }
                this.buying = false;
            },

            destroy() {
                if (this._interval) clearInterval(this._interval);
            }
        };
    }

    // Dutch upcoming: redirect to auction page when it goes live
    @if($lot->auction->isDutch() && $lot->auction->status === 'upcoming')
    (function() {
        const checkLive = setInterval(async () => {
            try {
                const r = await fetch('/api/auctions/{{ $lot->auction->slug }}/status');
                if (!r.ok) return;
                const json = await r.json();
                if (json.data?.auction?.status === 'live') {
                    clearInterval(checkLive);
                    window.location.href = '{{ $auctionUrl }}';
                }
            } catch (e) {}
        }, 3000);
    })();
    @endif

    // Prevent double-click on payment forms
    document.querySelectorAll('form[action*="payment/lots"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            }
        });
    });
    </script>
    @endpush
</x-app-layout>
