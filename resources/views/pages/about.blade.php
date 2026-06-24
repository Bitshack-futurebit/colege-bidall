<x-app-layout>
    <x-slot name="title">About Us - {{ config('branding.name') }}</x-slot>
    <x-slot name="description">Learn about {{ config('branding.name') }}, South Africa's most affordable online auction platform. Four auction formats, peer-to-peer Community Auctions, real-time bidding, and a pay-as-you-go credit system for auctioneers.</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8">About {{ config('branding.name') }}</h1>

        <div class="prose dark:prose-invert max-w-none">
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">Our Mission</h2>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ config('branding.name') }} is South Africa's premier online auction platform, connecting buyers and sellers
                    through a transparent, efficient, and user-friendly marketplace. We empower auctioneers to reach a wider
                    audience while providing buyers with access to unique items from across the country.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card p-6 text-center">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-xl font-semibold mb-2">Transparency</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Fair, open bidding with real-time updates, soft close, and multiple auction formats.
                    </p>
                </div>

                <div class="card p-6 text-center">
                    <div class="text-4xl mb-4">🤝</div>
                    <h3 class="text-xl font-semibold mb-2">Trust</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Verified auctioneers with ratings, direct communication, and reliable auction management.
                    </p>
                </div>

                <div class="card p-6 text-center">
                    <div class="text-4xl mb-4">🚀</div>
                    <h3 class="text-xl font-semibold mb-2">Innovation</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Four auction formats, proxy bidding, live watchlists, and modern technology making auctions accessible to everyone.
                    </p>
                </div>
            </div>

            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">Four Auction Formats</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                        <h3 class="font-semibold text-lg mb-2 text-blue-800 dark:text-blue-200">English (Traditional)</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            The classic auction format. Price starts low and bidders compete to push it higher. Highest bid wins when the timer runs out. Includes soft close protection and proxy bidding.
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
                        <h3 class="font-semibold text-lg mb-2 text-amber-800 dark:text-amber-200">Dutch (Descending Price)</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Price starts high and drops automatically over time. First buyer to act wins at the current price. Fast-paced and exciting with multiple drop strategies to build tension.
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700">
                        <h3 class="font-semibold text-lg mb-2 text-purple-800 dark:text-purple-200">Sealed (Silent Bid / Tender)</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            All bids are secret until the auction ends. No one — not even the auctioneer — can see what others have bid. The auctioneer chooses whether the highest or lowest bid wins, and can attach a tender document (PDF) for lowest-bid tenders.
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700">
                        <h3 class="font-semibold text-lg mb-2 text-red-800 dark:text-red-200">Live (Auctioneer-Paced)</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            The real auction-room experience online. Lots cycle through presenting, open bidding, going once, and going twice — complete with an opening chime, phase countdown, and subject-to-confirmation hammer.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card p-8 mb-8 border-l-4 border-teal-500">
                <h2 class="text-2xl font-bold text-teal-600 dark:text-teal-400 mb-4">Community Auctions</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    Beyond the four classic formats, {{ config('branding.name') }} runs <strong>Community Auctions</strong> — a peer-to-peer model built for local WhatsApp groups. Any community member can list items, the community bids together on auction night, and the winner collects directly from the seller.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-700">
                        <h3 class="font-semibold mb-1">Flat R{{ (int) config('community.starting_bid', 20) }} starting bid</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">No reserves. The market sets the price. Free to list — sellers only pay the {{ rtrim(rtrim(number_format((float) config('community.commission_percent', 5), 1), '0'), '.') }}% platform fee on lots that sell.</p>
                    </div>
                    <div class="p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-700">
                        <h3 class="font-semibold mb-1">Local agents</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Each region is fronted by a local Agent — someone with a 50+ member WhatsApp group — who runs the community marketing and earns a share of platform commission.</p>
                    </div>
                    <div class="p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-700">
                        <h3 class="font-semibold mb-1">Built-in protection</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sellers can decline lowball bids within 24 hours. Buyers who don't pay get strikes — auto-blocked after two in 6 months.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('community.sell-landing') }}" class="btn btn-primary bg-teal-600 hover:bg-teal-700 border-teal-600">How to sell</a>
                    <a href="{{ route('agents.landing') }}" class="btn btn-outline">Become an Agent</a>
                    <a href="{{ route('communities.index') }}" class="btn btn-outline">Browse Communities</a>
                </div>
            </div>

            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">Why Choose Us?</h2>

                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-lg mb-2">For Buyers</h3>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Access to auctions across South Africa from one platform</li>
                            <li>Four auction formats — English, Dutch, Sealed, and Live — each with a unique experience</li>
                            <li>Live auction mode with phase countdowns, opening chime, and "you have the highest bid" banner</li>
                            <li>Real-time bidding with live countdown timers and instant bid updates</li>
                            <li>Proxy bidding — set your maximum and let the system bid for you (English auctions)</li>
                            <li>Soft close protection — last-second bids extend the timer fairly (English auctions)</li>
                            <li>Smart bidding UX — urgency timers, outbid alerts, one-tap rebid, and "going once/twice" indicators</li>
                            <li>Sticky mobile bid bar — bid quickly without scrolling on your phone</li>
                            <li>Watchlist with live bidding — monitor and bid on saved lots from one page</li>
                            <li>Follow auctioneers you trust and get notified of their new auctions</li>
                            <li>In-app notification bell — never miss an important update</li>
                            <li>Rate auctioneers to help the community find trusted sellers</li>
                            <li>Install as an app on your phone — no app store needed (PWA)</li>
                            <li>Mobile-friendly interface for bidding anywhere, anytime</li>
                            <li>Direct contact with auctioneers via WhatsApp, phone, or website</li>
                            <li>Email summary when you win lots</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-semibold text-lg mb-2">For Auctioneers</h3>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Instant activation — register and start creating auctions right away</li>
                            <li>Four auction formats to suit any sale: English, Dutch, Sealed, and Live</li>
                            <li>Live auction control room — run a real-time sale with presenting, bidding, going once/twice, and confirm/reject hammer</li>
                            <li>Pay-as-you-go credits: only pay when your auction goes live</li>
                            <li>Simple lot pricing based on images: R1 (1 image), R5 (2-5), R20 (6+)</li>
                            <li>Only two platform fees — lot listing fee and 1% commission on sold lots</li>
                            <li>Your own branded profile page with ratings, followers, bio, and contact details</li>
                            <li>Send push notifications to your followers about upcoming auctions</li>
                            <li>WhatsApp Broadcast Builder — compose and share auction info to your WhatsApp groups</li>
                            <li>WhatsApp share buttons on auctions and individual lots</li>
                            <li>Automatic Facebook posting when auctions are published and go live</li>
                            <li>Subject to Confirmation — mark lots that need your approval before the sale is final</li>
                            <li>Tender Documents — attach a PDF per lot on lowest-bid sealed auctions for formal tender submissions</li>
                            <li>White-Label Branding — run your public auction page under your own logo, colours, and favicon</li>
                            <li>Staff management — invite team members with role-based permissions</li>
                            <li>Reserve prices and buyer's premium options per auction</li>
                            <li>Relist unsold lots — bulk relist into new auctions with free relists for no-bid lots</li>
                            <li>Collections tracking — manage payments and follow up with bidders via WhatsApp</li>
                            <li>Optional registration and deposit requirements per auction</li>
                            <li>Built-in image optimisation — WebP conversion and auto-rotation</li>
                            <li>Detailed per-auction reports with live and post-auction data</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card p-8 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950 dark:to-primary-900 border-2 border-primary-200 dark:border-primary-800">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">Our Platform</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    Built with cutting-edge technology, {{ config('branding.name') }} provides a seamless auction experience
                    for both buyers and sellers. Our platform features:
                </p>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-gray-700 dark:text-gray-300">
                    <li>✓ Four auction formats: English, Dutch, Sealed, and Live</li>
                    <li>✓ Community Auctions — peer-to-peer for local WhatsApp groups</li>
                    <li>✓ Community Agent program with tiered commission sharing</li>
                    <li>✓ Live auction mode with phase countdown and opening chime</li>
                    <li>✓ Real-time bidding with live countdown timers</li>
                    <li>✓ Proxy bidding — automatic bidding up to your max</li>
                    <li>✓ Automatic soft close extensions (English)</li>
                    <li>✓ Smart bidding UX with urgency indicators and one-tap rebid</li>
                    <li>✓ Dutch auctions with configurable drop strategies</li>
                    <li>✓ Sealed auctions with secret bids</li>
                    <li>✓ Tender documents (PDF) for lowest-bid sealed auctions</li>
                    <li>✓ White-label branding — your logo, colours, and favicon on your auction page</li>
                    <li>✓ Subject to Confirmation — auctioneer approval workflow</li>
                    <li>✓ Progressive Web App — install on your phone like a native app</li>
                    <li>✓ Mobile-responsive design with sticky bid bar</li>
                    <li>✓ WebP image optimisation and auto-rotation</li>
                    <li>✓ Map-based auctioneer discovery</li>
                    <li>✓ Auctioneer profiles, ratings, and follower system</li>
                    <li>✓ In-app notifications for auctioneers and bidders</li>
                    <li>✓ WhatsApp sharing and Broadcast Builder for auctioneers</li>
                    <li>✓ Automatic Facebook posting on publish and go-live</li>
                    <li>✓ Staff accounts with role-based permissions</li>
                    <li>✓ Live watchlist with direct bidding</li>
                    <li>✓ Reserve prices and buyer's premium</li>
                    <li>✓ Bulk relist unsold lots</li>
                    <li>✓ Collections management with WhatsApp reminders</li>
                    <li>✓ Optional auction deposits and registration</li>
                    <li>✓ Automated winner email notifications</li>
                    <li>✓ Pay-as-you-go credit system — two simple fees</li>
                </ul>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-12">
            <h2 class="text-2xl font-bold mb-4">Join Us Today</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Whether you're buying or selling, {{ config('branding.name') }} is your gateway to South Africa's auction marketplace.
            </p>
            <div class="flex gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                        Get Started
                    </a>
                    <a href="{{ route('how-it-works') }}" class="btn btn-outline btn-lg">
                        Learn More
                    </a>
                @else
                    <a href="{{ route('auctions.index') }}" class="btn btn-primary btn-lg">
                        Browse Auctions
                    </a>
                @endguest
            </div>
        </div>

        <!-- Built by Bidwright -->
        <div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-10 text-center">
            <h2 class="text-2xl font-bold mb-3">The technology behind {{ config('branding.name') }}</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-6">
                {{ config('branding.name') }} is powered by Bidwright &mdash; custom online auction software
                supporting English, Dutch, sealed-bid, live, and community auctions with real-time bidding.
                Want a platform like this for your own business?
            </p>
            <a href="https://bidwright.bidall.co.za" target="_blank" rel="noopener"
               title="Bidwright — custom online auction software development"
               class="btn btn-outline inline-flex items-center justify-center min-h-[44px] px-8 rounded-xl">
                Discover Bidwright auction software &rarr;
            </a>
        </div>
    </div>

    @push('scripts')
    @php
        $aboutBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'About Us'],
            ],
        ];

        $aboutOrg = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('branding.name'),
            'url' => url('/'),
            'logo' => asset('images/gavel-logo.svg'),
            'description' => 'South Africa\'s most affordable online auction platform. English, Dutch, Sealed, Live, and peer-to-peer Community Auctions with real-time bidding.',
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
    @endphp
    <script type="application/ld+json">{!! json_encode($aboutBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($aboutOrg, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush
</x-app-layout>
