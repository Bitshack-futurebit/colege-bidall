<x-app-layout>
    <x-slot name="title">{{ config('branding.name') }} - {{ config('branding.tagline') }}</x-slot>
    <x-slot name="description">South Africa's most affordable online auction platform. Browse live auctions, bid on items, or become an auctioneer.</x-slot>

    {{-- ── Presentation slideshow (replaces the map) ──
         Edit the $slides array below to change the deck. Per-slide fields (all optional except title):
           variant  = 'title' | 'closing' | 'cta'  → blue accent slide
           logo     = true                          → show the ACSA logo (title slide)
           eyebrow  = small label above the headline
           title    = the big headline
           body     = supporting sentence
           bullets  = list of points (rendered with ticks)
           cards    = list of { tag, title, body } shown as a grid (2 or 4 reads best)
           footnote = small italic note (e.g. pricing disclaimer)
         Present with the Next/Back buttons, the dots, or the ← → arrow keys. --}}
    @php
        $slides = [
            [
                'variant' => 'title',
                'logo'    => true,
                'eyebrow' => 'Auctioneering College of SA',
                'title'   => 'Bridging Traditional Auctioneering with Online Execution',
                'body'    => 'A strategic programme proposal',
            ],
            [
                'eyebrow'  => 'The Strategic Opportunity',
                'title'    => 'The industry is moving online — education has not caught up',
                'body'     => 'Auctioneering is shifting from in-person formats to scalable, technology-driven online systems. Yet no South African college has integrated practical online auction operations into its core curriculum.',
                'footnote' => 'First-mover advantage: become the national leader in accredited digital auction education.',
            ],
            [
                'eyebrow' => 'Industry Context',
                'title'   => 'Online auctions are becoming the default',
                'body'    => 'Now the preferred mechanism for:',
                'bullets' => [
                    'Property disposals',
                    'Estate agency sales',
                    'Liquidation & insolvency processes',
                    'Government & municipal asset sales',
                    'Retail surplus & corporate asset recovery',
                ],
            ],
            [
                'eyebrow' => 'The Gap',
                'title'   => 'Today these skills are learned informally — not taught',
                'body'    => 'Most practitioners pick up online systems through fragmented tools and platforms, not structured education. That leaves a gap between traditional auction training and real-world digital execution. This programme is built to close it.',
            ],
            [
                'eyebrow' => 'The Proposal',
                'title'   => 'A dual-programme model',
                'cards'   => [
                    ['tag' => 'Product 1', 'title' => 'Digital Auction Fundamentals', 'body' => 'A curriculum enhancement integrated into the existing auctioneering qualification.'],
                    ['tag' => 'Product 2', 'title' => 'Certified Digital Auctioneer', 'body' => 'A standalone professional certification in end-to-end online auction operations.'],
                ],
            ],
            [
                'eyebrow' => 'Product 1 · Curriculum Enhancement',
                'title'   => 'Digital Auction Fundamentals Module',
                'body'    => 'Integrates practical online auction capability into the existing qualification — ensuring graduates are operationally competent in both traditional and digital auction environments.',
            ],
            [
                'eyebrow' => 'Product 2 · Flagship Certification',
                'title'   => 'Certified Digital Auctioneer Programme',
                'body'    => 'Establishes the College as a national centre of excellence for digital auction training — producing industry-ready professionals who can independently run end-to-end online auctions.',
            ],
            [
                'eyebrow' => 'Strategic Value to the College',
                'title'   => 'More than a course — a new vertical',
                'bullets' => [
                    'Expand the qualification offering into a new digital vertical',
                    'Attract working professionals beyond the traditional student pipeline',
                    'Develop CPD-accredited revenue streams',
                    'Strengthen partnerships with estate agencies, insolvency practitioners & corporate asset managers',
                    'Stay ahead of curriculum changes likely in the next 3–5 years',
                ],
            ],
            [
                'eyebrow'  => 'Commercial Models',
                'title'    => 'Flexible, partnership-based pricing',
                'cards'    => [
                    ['tag' => 'Per student', 'title' => 'Enrolment fee', 'body' => 'A fee per student, scaled by group size and delivery format.'],
                    ['tag' => 'Institutional', 'title' => 'Licensing model', 'body' => 'The College licenses the platform and programme.'],
                    ['tag' => 'Partnership', 'title' => 'Revenue share', 'body' => 'Shared upside on every enrolment.'],
                    ['tag' => 'Hybrid', 'title' => 'Training + access', 'body' => 'Blended delivery with software-environment access.'],
                ],
                'footnote' => 'Indicative structures for discussion — final pricing shaped to the College’s goals.',
            ],
            [
                'variant' => 'closing',
                'eyebrow' => 'Closing Position',
                'title'   => 'Be the first to bridge the craft and the channel',
                'body'    => 'This is not about adding digital content to a curriculum. It is about positioning the College as the first institution in South Africa to formally bridge traditional auctioneering with modern online auction execution.',
            ],
            [
                'variant' => 'cta',
                'eyebrow' => 'Let’s Begin',
                'title'   => 'Bid live — right now',
                'body'    => 'Have your phone ready. In the next two minutes, you will run and win a real online auction on this platform.',
            ],
        ];
    @endphp

    <section class="py-10 sm:py-16 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                x-data="{
                    current: 0,
                    slides: @js($slides),
                    accent(s) { return ['title', 'closing', 'cta'].includes(s.variant); },
                    next() { if (this.current < this.slides.length - 1) this.current++; },
                    prev() { if (this.current > 0) this.current--; },
                    go(i) { this.current = i; },
                }"
                x-on:keydown.window.arrow-right="next()"
                x-on:keydown.window.arrow-left="prev()"
            >
                {{-- Slide frame (inline min-height + flex so the single visible slide fills it;
                     avoids arbitrary Tailwind classes that aren't in the precompiled CSS) --}}
                <div class="relative overflow-hidden rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex" style="min-height: 520px">
                    <template x-for="(slide, i) in slides" :key="i">
                        <div x-show="current === i"
                             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             class="w-full flex flex-col items-center justify-center text-center p-8 sm:p-14"
                             :style="accent(slide) ? 'background: linear-gradient(135deg, rgb(var(--color-primary-600)), rgb(var(--color-primary-800)))' : ''">

                            <template x-if="slide.logo">
                                <img src="{{ config('branding.logo.default') }}" alt="{{ config('branding.name') }}" class="h-20 w-20 object-contain mb-5 bg-white rounded-full p-1 shadow">
                            </template>

                            <div class="text-xs sm:text-sm font-semibold uppercase tracking-widest mb-4"
                                 :class="accent(slide) ? 'text-white' : 'text-primary-600 dark:text-primary-400'" x-text="slide.eyebrow"></div>

                            <h2 class="text-2xl sm:text-4xl font-bold mb-5 leading-tight"
                                :class="accent(slide) ? 'text-white' : 'text-gray-900 dark:text-gray-100'" x-text="slide.title"></h2>

                            <template x-if="slide.body">
                                <p class="text-base sm:text-xl max-w-2xl leading-relaxed"
                                   :class="accent(slide) ? 'text-white' : 'text-gray-600 dark:text-gray-300'" x-text="slide.body"></p>
                            </template>

                            <template x-if="slide.bullets">
                                <ul class="mt-5 space-y-3 text-left max-w-xl mx-auto">
                                    <template x-for="b in slide.bullets" :key="b">
                                        <li class="flex items-start gap-3 text-base sm:text-lg text-gray-700 dark:text-gray-300">
                                            <svg class="w-5 h-5 mt-1 flex-shrink-0 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="b"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            <template x-if="slide.cards">
                                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full max-w-3xl">
                                    <template x-for="c in slide.cards" :key="c.title">
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 p-5 text-left">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400 mb-1" x-text="c.tag"></div>
                                            <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100 mb-1" x-text="c.title"></div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400" x-text="c.body"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="slide.footnote">
                                <p class="mt-6 text-xs sm:text-sm italic max-w-xl"
                                   :class="accent(slide) ? 'text-white' : 'text-gray-500 dark:text-gray-400'" x-text="slide.footnote"></p>
                            </template>
                        </div>
                    </template>

                    {{-- Slide counter --}}
                    <div class="absolute top-4 right-5 text-xs font-medium z-10"
                         :class="accent(slides[current]) ? 'text-white' : 'text-gray-400 dark:text-gray-500'"
                         x-text="(current + 1) + ' / ' + slides.length"></div>
                </div>

                {{-- Progress bar --}}
                <div class="bg-gray-200 dark:bg-gray-700 rounded-full mt-4 overflow-hidden" style="height: 6px">
                    <div class="rounded-full transition-all duration-300"
                         style="height: 6px; background: rgb(var(--color-primary-600))"
                         :style="'width:' + (((current + 1) / slides.length) * 100) + '%'"></div>
                </div>

                {{-- Controls --}}
                <div class="flex items-center justify-between gap-4 mt-5">
                    <button type="button" x-on:click="prev()" :disabled="current === 0"
                            :class="current === 0 ? 'opacity-50 pointer-events-none' : ''"
                            class="btn btn-outline flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Back
                    </button>
                    <div class="flex items-center gap-2">
                        <template x-for="(slide, i) in slides" :key="i">
                            <button type="button" x-on:click="go(i)" :aria-label="'Go to slide ' + (i + 1)"
                                    class="rounded-full transition-colors" style="width: 10px; height: 10px"
                                    :class="current === i ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400'"></button>
                        </template>
                    </div>
                    <button type="button" x-on:click="next()" :disabled="current === slides.length - 1"
                            :class="current === slides.length - 1 ? 'opacity-50 pointer-events-none' : ''"
                            class="btn btn-primary flex items-center justify-center gap-2">
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

    @push('scripts')
    @php
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('branding.name'),
            'url' => url('/'),
            'logo' => asset(ltrim(config('branding.logo.default'), '/')),
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
