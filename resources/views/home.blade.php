<x-app-layout>
    <x-slot name="title">{{ config('branding.name') }} - {{ config('branding.tagline') }}</x-slot>
    <x-slot name="description">South Africa's most affordable online auction platform. Browse live auctions, bid on items, or become an auctioneer.</x-slot>

    {{-- ── Presentation slideshow (replaces the map) ──
         Edit the $slides array below to change the deck. Each slide:
           eyebrow  = small label above the headline
           title    = the big headline
           body     = supporting sentence (optional)
           bullets  = optional list of points
         Present with the Next/Back buttons, the dots, or the ← → arrow keys. --}}
    @php
        $slides = [
            [
                'eyebrow' => 'Auctioneering College of SA',
                'title'   => 'Online Auctions',
                'body'    => 'The modern channel for the craft you have taught since 1988.',
            ],
            [
                'eyebrow' => 'The Shift',
                'title'   => 'The auction floor is moving online',
                'body'    => 'Property, vehicles, livestock and estates increasingly cross the block online. Your graduates are expected to know these platforms on day one.',
            ],
            [
                'eyebrow' => 'The Gap',
                'title'   => 'You teach every type of auction — except one',
                'body'    => 'The online auction is the single format your course predates. That is the gap we close together — without changing anything you already do.',
            ],
            [
                'eyebrow' => 'The Opportunity',
                'title'   => 'Lead the digital ring',
                'body'    => 'Be first to teach online auctioneering in South Africa. Extend your leadership into the channel the industry is adopting now.',
            ],
            [
                'eyebrow' => 'The Platform',
                'title'   => 'Hands-on, not theory',
                'body'    => 'A live online auction platform built for training. Every student runs auctions and bids in them — peers supply the live floor.',
            ],
            [
                'eyebrow' => 'Every Format',
                'title'   => 'All four auction types, faithfully',
                'bullets' => [
                    'English — ascending bids with soft-close anti-sniping',
                    'Dutch — descending price, first to act wins',
                    'Sealed / Tender — bids secret until close',
                    'Live — auctioneer-paced ring: presenting → going once → going twice → sold',
                ],
            ],
            [
                'eyebrow' => 'The Legal Layer',
                'title'   => 'Online auctions carry their own law',
                'body'    => 'POPIA, the CPA, electronic bid records and audit trails — taught live, on a real platform, the way the industry runs it.',
            ],
            [
                'eyebrow' => 'Your Identity, Extended',
                'title'   => 'You teach the craft. We add the channel.',
                'body'    => 'Nothing of yours is replaced. The chant, the ring, the ethics and the law stay exactly as they are — this is one more practical short course under the ACSA name.',
            ],
            [
                'eyebrow' => 'Let’s Begin',
                'title'   => 'Bid live — right now',
                'body'    => 'Have your phone ready. In the next two minutes, you will run and win a real online auction on this platform.',
            ],
        ];
    @endphp

    <section class="py-10 sm:py-16 bg-gradient-to-b from-primary-50 to-white dark:from-gray-900 dark:to-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                x-data="{
                    current: 0,
                    slides: @js($slides),
                    next() { if (this.current < this.slides.length - 1) this.current++; },
                    prev() { if (this.current > 0) this.current--; },
                    go(i) { this.current = i; },
                }"
                x-on:keydown.window.arrow-right="next()"
                x-on:keydown.window.arrow-left="prev()"
            >
                {{-- Slide frame --}}
                <div class="relative overflow-hidden rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 min-h-[420px] sm:min-h-[480px]">
                    <template x-for="(slide, i) in slides" :key="i">
                        <div x-show="current === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             class="absolute inset-0 flex flex-col items-center justify-center text-center p-8 sm:p-14">
                            <div class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-primary-600 dark:text-primary-400 mb-4" x-text="slide.eyebrow"></div>
                            <h2 class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-5 leading-tight max-w-3xl" x-text="slide.title"></h2>
                            <template x-if="slide.body">
                                <p class="text-base sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl leading-relaxed" x-text="slide.body"></p>
                            </template>
                            <template x-if="slide.bullets">
                                <ul class="mt-2 space-y-3 text-left max-w-xl mx-auto">
                                    <template x-for="b in slide.bullets" :key="b">
                                        <li class="flex items-start gap-3 text-base sm:text-lg text-gray-700 dark:text-gray-300">
                                            <svg class="w-5 h-5 mt-1 flex-shrink-0 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="b"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </template>
                    {{-- Slide counter --}}
                    <div class="absolute top-4 right-5 text-xs font-medium text-gray-400 dark:text-gray-500" x-text="(current + 1) + ' / ' + slides.length"></div>
                </div>

                {{-- Controls --}}
                <div class="flex items-center justify-between gap-4 mt-5">
                    <button type="button" x-on:click="prev()" :disabled="current === 0"
                            class="btn btn-outline flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Back
                    </button>
                    <div class="flex items-center gap-2">
                        <template x-for="(slide, i) in slides" :key="i">
                            <button type="button" x-on:click="go(i)" :aria-label="'Go to slide ' + (i + 1)"
                                    class="w-2.5 h-2.5 rounded-full transition-colors"
                                    :class="current === i ? 'bg-primary-600 dark:bg-primary-400' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400'"></button>
                        </template>
                    </div>
                    <button type="button" x-on:click="next()" :disabled="current === slides.length - 1"
                            class="btn btn-primary flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

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
