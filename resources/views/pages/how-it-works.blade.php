<x-app-layout>
    <x-slot name="title">How It Works - {{ config('branding.name') }}</x-slot>
    <x-slot name="description">How {{ config('branding.name') }} works for bidders, auctioneers, and local communities. Four auction formats plus peer-to-peer Community Auctions. Free registration and real-time bidding.</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8">How It Works</h1>

        <!-- For Bidders -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6">For Bidders</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">1</div>
                    <h3 class="text-xl font-semibold mb-2">Create a Free Account</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Register as a bidder in seconds. No fees, no deposits — just sign up and you're ready to go. Install the app on your phone for the best experience.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">2</div>
                    <h3 class="text-xl font-semibold mb-2">Discover Auctioneers</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Browse auctions, explore auctioneer profiles, and follow your favourites to stay updated on their latest sales.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">3</div>
                    <h3 class="text-xl font-semibold mb-2">Watchlist & Bid</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Save lots to your watchlist and bid directly from there. Choose your auction format — bid in English or Live auctions, buy in Dutch auctions, or place secret bids in sealed auctions. Get outbid alerts, one-tap rebid, and urgency timers to help you win.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">4</div>
                    <h3 class="text-xl font-semibold mb-2">Win & Collect</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Won lots are clearly marked. Some lots may be subject to confirmation by the auctioneer — you'll be notified once confirmed. Receive an email summary, then arrange payment and collection directly with the auctioneer.
                    </p>
                </div>
            </div>
        </div>

        <!-- Auction Formats -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6">Auction Formats</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="card p-6 border-t-4 border-t-blue-500">
                    <h3 class="text-xl font-semibold mb-3 text-blue-700 dark:text-blue-300">English (Traditional)</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        The classic format most people know. Price starts low and bidders compete to push it higher.
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Real-time bidding with live updates</li>
                        <li>✓ Proxy bidding — set your max, system bids for you</li>
                        <li>✓ Soft close — last-second bids extend the timer</li>
                        <li>✓ Reserve prices and buyer's premium</li>
                        <li>✓ Maximum 12-hour duration</li>
                    </ul>
                </div>

                <div class="card p-6 border-t-4 border-t-amber-500">
                    <h3 class="text-xl font-semibold mb-3 text-amber-700 dark:text-amber-300">Dutch (Descending Price)</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Price starts high and drops automatically. First buyer to act wins at the current price. Fast and exciting.
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Automatic price drops on a timer</li>
                        <li>✓ Four drop strategies for different tension curves</li>
                        <li>✓ Lots run sequentially with configurable gaps</li>
                        <li>✓ Multi-quantity lots supported</li>
                        <li>✓ Auctioneer sets duration, floor price, and strategy</li>
                    </ul>
                </div>

                <div class="card p-6 border-t-4 border-t-purple-500">
                    <h3 class="text-xl font-semibold mb-3 text-purple-700 dark:text-purple-300">Sealed (Silent Bid / Tender)</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        All bids are secret. No one can see what others have bid — not even the auctioneer. Winner revealed only after close.
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Highest or lowest bid wins (auctioneer chooses)</li>
                        <li>✓ Tender document (PDF) per lot for lowest-bid mode</li>
                        <li>✓ Update your bid any time before closing</li>
                        <li>✓ Minimum 2 bidders required per lot</li>
                        <li>✓ Reserve prices supported</li>
                        <li>✓ All lots open and close together</li>
                    </ul>
                </div>

                <div class="card p-6 border-t-4 border-t-red-500">
                    <h3 class="text-xl font-semibold mb-3 text-red-700 dark:text-red-300">Live (Auctioneer-Paced)</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        The real auction-room experience online. Lots run one after another, the auctioneer calls "going once, going twice", and bidders rush in before the hammer falls.
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Presenting → "Who'll open?" → bidding → going once → going twice → sold</li>
                        <li>✓ Lots with no opening bid in 30s close immediately as no-interest</li>
                        <li>✓ Bidding stays open as long as bids land within 8 seconds of each other</li>
                        <li>✓ Airport-style chime announces when bidding opens</li>
                        <li>✓ Phase countdown with colour-coded urgency</li>
                        <li>✓ "You have the highest bid" banner so you never re-bid unnecessarily</li>
                        <li>✓ Subject-to-confirmation sales — auctioneer confirms or rejects</li>
                        <li>✓ Minimum 2 bidders per lot (single-bidder-over-reserve still wins)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Community Auctions -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-teal-600 dark:text-teal-400 mb-3">Community Auctions</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-3xl">
                A peer-to-peer auction format built for local WhatsApp groups. Any community member can list items at a flat R{{ (int) config('community.starting_bid', 20) }} starting bid — no reserves. The community shows up on auction night, the winner collects directly from the seller, and the platform takes {{ rtrim(rtrim(number_format((float) config('community.commission_percent', 5), 1), '0'), '.') }}% commission on sold lots.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-6 border-t-4 border-t-teal-500">
                    <h3 class="text-xl font-semibold mb-3 text-teal-700 dark:text-teal-300">If you want to sell</h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Sign up free as a bidder (one account does both)</li>
                        <li>✓ Join your local community region</li>
                        <li>✓ List items with photo + description — no listing fees</li>
                        <li>✓ Confirm or decline the winning bid within 24 hours</li>
                        <li>✓ Coordinate collection &amp; payment offline (cash, EFT)</li>
                        <li>✓ Settle the {{ rtrim(rtrim(number_format((float) config('community.commission_percent', 5), 1), '0'), '.') }}% via PayFast on a monthly invoice</li>
                    </ul>
                </div>
                <div class="card p-6 border-t-4 border-t-teal-500">
                    <h3 class="text-xl font-semibold mb-3 text-teal-700 dark:text-teal-300">If you want to bid</h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Browse upcoming community auctions in your area</li>
                        <li>✓ Bid live on auction night — same UX as a Live auction</li>
                        <li>✓ At least 2 distinct bidders required for a valid sale</li>
                        <li>✓ Pay the seller directly when you collect (cash or EFT)</li>
                        <li>✓ Persistent non-payment leads to an auto-block</li>
                        <li>✓ No platform fees — sellers pay the {{ rtrim(rtrim(number_format((float) config('community.commission_percent', 5), 1), '0'), '.') }}%, not buyers</li>
                    </ul>
                </div>
            </div>

            <div class="card p-5 bg-teal-50 dark:bg-teal-900/20 border-l-4 border-teal-500 mb-6">
                <h3 class="font-bold mb-2">Community Agents</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Each community is fronted by a local <strong>Agent</strong> — someone with a 50+ member WhatsApp buy/sell group who runs the marketing for that region. Agents earn a share of the platform commission on every sale: 50/50 on the first R{{ number_format((int) config('community.commission_tier1_cap', 1000)) }} of monthly commission, 70% to the agent on everything above. <a href="{{ route('agents.landing') }}" class="text-teal-700 dark:text-teal-300 underline">Become an Agent &rarr;</a>
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('community.sell-landing') }}" class="btn btn-primary bg-teal-600 hover:bg-teal-700 border-teal-600">Learn how to sell</a>
                <a href="{{ route('communities.index') }}" class="btn btn-outline">Find a community near you</a>
            </div>
        </div>

        <!-- For Auctioneers -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6">For Auctioneers</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">1</div>
                    <h3 class="text-xl font-semibold mb-2">Register & Go</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Register as an auctioneer and your account is activated instantly. No activation fee, no waiting.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">2</div>
                    <h3 class="text-xl font-semibold mb-2">Purchase Credits</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Top up your credit balance (minimum R100). Credits are only deducted when your auction goes live — not when you create lots.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">3</div>
                    <h3 class="text-xl font-semibold mb-2">Create & List Lots</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Choose your auction format (English, Dutch, Sealed, or Live), upload lots with photos, set starting prices and reserves. Schedule your start and end times.
                    </p>
                </div>

                <div class="card p-6">
                    <div class="text-4xl font-bold text-primary-600 mb-4">4</div>
                    <h3 class="text-xl font-semibold mb-2">Go Live & Earn</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Publish your auction — it auto-posts to Facebook and you can share to WhatsApp groups with the Broadcast Builder. Confirm or reject subject-to-confirmation lots, track collections, send WhatsApp reminders, and relist unsold lots with one click.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6">Pricing</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Two simple fees. No monthly charges, no hidden costs, no payout delays.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-6 border-l-4 border-primary-500">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Lot Listing Fee</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                        Charged from your credit balance when your auction goes live. Based on images per lot:
                    </p>
                    <div class="flex gap-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary-600">{{ $pricing['tier_basic'] ?? 'R1' }}</div>
                            <div class="text-xs text-gray-500">1 image</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary-600">{{ $pricing['tier_pro'] ?? 'R5' }}</div>
                            <div class="text-xs text-gray-500">2-5 images</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary-600">{{ $pricing['tier_premium'] ?? 'R20' }}</div>
                            <div class="text-xs text-gray-500">6+ images</div>
                        </div>
                    </div>
                </div>
                <div class="card p-6 border-l-4 border-primary-500">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">1% Commission on Sold Lots</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        When a lot sells, a 1% commission on the sale value (including buyer's premium if applicable) is deducted from your credit balance. Unsold lots incur no commission.
                    </p>
                </div>
            </div>

            <div class="card p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700">
                <p class="text-sm text-green-800 dark:text-green-200">
                    <strong>No-bid lots relist free.</strong> If a lot receives zero bids, you can relist it at no additional cost.
                </p>
            </div>
        </div>

        <!-- Features -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-6">Platform Features</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Real-Time Bidding</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Bids update live as they happen. Bidders see the current price instantly without refreshing the page.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Proxy Bidding</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Set your maximum bid and the system automatically bids for you in increments, keeping your max private until needed. Available on English auctions.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Soft Close</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        A bid placed in the final minutes automatically extends the lot's closing time, giving all bidders a fair chance. English auctions only.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Dutch Drop Strategies</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Four strategies for Dutch auctions — Constant, Fast Sell, Max Value, and High Drama — each creating a different pace and tension curve.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Sealed Bidding</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Secret bids that no one can see until the auction ends. Choose highest or lowest bid wins. Update your bid any time before closing.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Live Auction Mode</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Run a real-time auction like the room. Lots cycle through presenting, bidding, going once, and going twice with a chime and phase countdown. Confirm or reject the winning bid from your control room.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Tender Documents</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        For lowest-bid sealed auctions, auctioneers can attach a PDF tender document per lot. Bidders view and download the document before submitting their sealed bid — ideal for formal tenders and RFQs.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Smart Bidding UX</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Urgency-aware timers that change colour as time runs out, "going once/twice" indicators, outbid alerts with one-tap rebid, reserve met celebrations, and a sticky mobile bid bar for quick bidding on your phone.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Auctioneer Profiles & Followers</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Each auctioneer has a branded public profile with their auctions, bio, and contact details. Bidders can follow favourites to build lasting relationships.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Staff Management</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Invite team members as staff with role-based permissions — lot managers, auction managers, or collections managers — each with tailored access.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">WhatsApp Sharing & Broadcast</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Share auctions and lots to WhatsApp with one tap. Use the Broadcast Builder to compose multi-lot messages and send them to your WhatsApp groups to drive traffic.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Facebook Auto-Posting</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Auctions are automatically posted to the {{ config('branding.name') }} Facebook page when published and again when they go live, reaching a wider audience.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Subject to Confirmation</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Auctioneers can mark lots as "subject to confirmation" — the winning bid is held pending approval. Confirm or reject from the report page, and the bidder is notified automatically.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Collections Tracking</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Manage payments and collections in one place. Confirm payments, send WhatsApp reminders, and track outstanding balances per bidder.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Live Watchlist</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Save lots to your watchlist and bid directly from there. Live countdown timers, real-time bid updates, and winning/outbid indicators keep you informed at a glance.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Install as an App (PWA)</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Add {{ config('branding.name') }} to your home screen and use it like a native app — no app store download needed. Works on Android, iOS, and desktop.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Map-Based Discovery</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Find auctioneers near you using the interactive map on the homepage, showing locations across South Africa.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Image Optimisation</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Uploaded photos are automatically converted to WebP, resized, and rotated correctly — no editing required from the auctioneer.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">In-App Notifications</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Auctioneers can send notifications to their followers about upcoming auctions. Bidders see alerts via the notification bell — no app install needed.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Auctioneer Ratings</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Bidders can rate auctioneers after participating in their auctions, helping the community identify trusted sellers.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Reserve Prices & Buyer's Premium</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Auctioneers can set hidden reserve prices per lot and an optional buyer's premium percentage per auction.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Relist Unsold Lots</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Unsold lots can be bulk-relisted into new draft auctions. Lots with no bids qualify for a free relist — no additional credit charge.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Winner Notifications</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        When an auction ends, winning bidders automatically receive an email summary of all lots they've won with totals.
                    </p>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold mb-3">Live Auction Reports</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Real-time and post-auction reports showing bids, sales, unsold lots, and per-auction financials for auctioneers.
                    </p>
                </div>
            </div>
        </div>

        <!-- White-Label / For Established Auctioneers -->
        <div class="mb-12">
            <div class="card p-8 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40 border-2 border-indigo-200 dark:border-indigo-800">
                <div class="flex items-start gap-4 mb-4">
                    <div class="text-4xl">🎨</div>
                    <div>
                        <h2 class="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mb-1">Already have a brand? Keep it.</h2>
                        <p class="text-gray-600 dark:text-gray-400">Run {{ config('branding.name') }} under your own identity with White-Label Branding.</p>
                    </div>
                </div>

                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    Established auctioneers with an existing brand can run their public auction page with their own logo, colours, and favicon — so your bidders see <em>you</em>, not us. You get the full {{ config('branding.name') }} auction engine, with your identity on top.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-indigo-100 dark:border-indigo-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Your colours</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pick a primary and secondary brand colour — the entire auction page re-themes to match.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-indigo-100 dark:border-indigo-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Your logo &amp; favicon</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Your logo replaces ours in the header, and your favicon shows in the browser tab.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-indigo-100 dark:border-indigo-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Your welcome message</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Add a custom hero message on your public auction page to greet your bidders.</p>
                    </div>
                </div>

                @php
                    $whiteLabelWhatsapp = 'https://wa.me/' . preg_replace('/\D/', '', config('regional.whatsapp.platform_number'))
                        . '?text=' . rawurlencode('Hi, I\'d like to know more about White-Label Branding on ' . config('branding.name') . '.');
                @endphp
                <div class="flex flex-col sm:flex-row gap-3">
                    @auth
                        @if(auth()->user()->isAuctioneer())
                            <a href="{{ route('seller.profile') }}" class="btn btn-primary">Enable White-Label in My Profile</a>
                        @else
                            <a href="{{ $whiteLabelWhatsapp }}" target="_blank" rel="noopener" class="btn btn-primary">Ask About White-Label on WhatsApp</a>
                        @endif
                    @else
                        <a href="{{ route('register', ['role' => 'auctioneer']) }}" class="btn btn-primary">Register as an Auctioneer</a>
                        <a href="{{ $whiteLabelWhatsapp }}" target="_blank" rel="noopener" class="btn btn-outline">Ask About White-Label on WhatsApp</a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-lg p-12">
            <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-xl mb-8">Join {{ config('branding.name') }} today and discover South Africa's auction marketplace.</p>
            <div class="flex gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        Register as Bidder
                    </a>
                    <a href="{{ route('register', ['role' => 'auctioneer']) }}" class="bg-primary-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-primary-600 transition border-2 border-white">
                        Become an Auctioneer
                    </a>
                @else
                    <a href="{{ route('auctions.index') }}" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        Browse Auctions
                    </a>
                @endguest
            </div>
        </div>

        <!-- Built by Bidwright -->
        <div class="mt-16 border-t border-gray-200 dark:border-gray-700 pt-10 text-center">
            <h2 class="text-2xl font-bold mb-3">Want to run auctions like this yourself?</h2>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-6">
                The auction engine powering {{ config('branding.name') }} is built by Bidwright &mdash; custom
                online auction software for English, Dutch, sealed-bid, live, and community auctions.
                Launch your own branded auction platform.
            </p>
            <a href="https://bidwright.bidall.co.za" target="_blank" rel="noopener"
               title="Bidwright — custom online auction software development"
               class="btn btn-outline inline-flex items-center justify-center min-h-[44px] px-8 rounded-xl">
                Explore Bidwright auction software &rarr;
            </a>
        </div>
    </div>

    @push('scripts')
    @php
        $hiwBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'How It Works'],
            ],
        ];

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'What is an English auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'The classic auction format. Price starts low and bidders compete to push it higher. The highest bid wins when the timer runs out. Includes soft close protection and proxy bidding.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a Dutch auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'A descending price auction. The price starts high and drops automatically over time. The first buyer to act wins at the current price. Fast-paced with multiple drop strategies.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a Sealed auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'A silent bid auction where all bids are secret until the auction ends. No one can see what others have bid. The auctioneer chooses whether the highest or lowest bid wins.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a Live auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'An auctioneer-paced real-time auction. Lots cycle through presenting, a 30-second "Who\'ll open?" call, live bidding (kept open as long as bids land within 8 seconds of each other), then going once and going twice before the hammer falls. Lots that get no opening bid close immediately as no-interest. Bidders see phase countdowns and a "you have the highest bid" banner so they know when to act.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How much does it cost to list on ' . config('branding.name') . '?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Pricing is based on images per lot: R1 for 1 image (Basic), R5 for 2-5 images (Pro), R20 for 6+ images (Premium). Plus a 1% commission on sold lots. No subscription fees — pay-as-you-go credits.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Is it free to bid on ' . config('branding.name') . '?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Yes, creating an account and bidding is completely free for buyers. Some auctions may require a refundable deposit to participate.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is proxy bidding?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Proxy bidding lets you set a maximum bid amount. The system automatically bids on your behalf, only increasing your bid when someone outbids you, up to your maximum. Available on English auctions only.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a Community Auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'A peer-to-peer auction format for local WhatsApp groups. Any member can list items at a flat R20 starting bid (no reserves), the community bids together on auction night, and the winner collects directly from the seller. Platform commission is 5% on sold lots, paid by the seller. Communities are fronted by local Agents who handle marketing.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How much does it cost to sell on a Community Auction?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Free to list, with no upfront fees. The platform takes a 5% commission on the hammer price only when your lot sells. Unsold lots cost nothing. Settlement is monthly via PayFast, billed in one invoice covering all your sold lots.',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($hiwBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush
</x-app-layout>
