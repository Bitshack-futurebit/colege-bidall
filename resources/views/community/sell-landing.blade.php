<x-app-layout>
    <x-slot name="title">Sell in Your Community Auction</x-slot>
    <x-slot name="description">No listing fees. R20 starting bid. Sell your stuff at your local community auction night. Free seller protection built in.</x-slot>

    @php
        $startingBid = (int) config('community.starting_bid', 20);
        $commissionPct = (float) config('community.commission_percent', 5);
        $confirmationHours = (int) config('community.confirmation_window_hours', 24);
        $minImages = (int) config('community.min_images', 1);
        $minDesc = (int) config('community.min_description_length', 20);
        $feeThreshold = (float) config('community.fee_debt_block_threshold', 50);
        $feeGraceDays = (int) config('community.fee_debt_age_block_days', 30);
        $minLots = (int) config('community.min_lots_for_viability', 5);
        $minBidders = (int) config('community.min_bidders_for_viability', 20);

        // CTA decision based on visitor state
        $user = auth()->user();
        $ctaUrl = route('login');
        $ctaLabel = 'Log in or sign up to start';
        $ctaTone = 'auth';

        if ($user) {
            if ($user->isAdmin() || $user->isAuctioneer() || $user->isStaff()) {
                $ctaTone = 'ineligible';
            } elseif ($user->community_region_id) {
                $ctaUrl = route('community.create-lot');
                $ctaLabel = 'List an item now';
                $ctaTone = 'ready';
            } else {
                $ctaUrl = route('communities.index');
                $ctaLabel = 'Find your community';
                $ctaTone = 'pickregion';
            }
        }
    @endphp

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-teal-600 to-teal-800 text-white">
        <div class="max-w-5xl mx-auto px-4 py-16 md:py-24">
            <span class="inline-block px-3 py-1 text-xs uppercase tracking-wider bg-white/15 rounded-full mb-4">Community auctions</span>
            <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">Turn your unused items into local cash.</h1>
            <p class="text-xl text-teal-50 max-w-3xl mb-8">
                Every lot starts at R{{ $startingBid }}. No reserves. No listing fees. The community shows up, bids, and the winner collects directly from you.
            </p>

            @if($ctaTone === 'ineligible')
                <div class="bg-white/15 backdrop-blur rounded-lg p-4 text-sm">
                    Your account type ({{ ucfirst($user->role) }}) sells through its own dashboard, not the community auction. Use a separate bidder account to participate.
                </div>
            @else
                <a href="{{ $ctaUrl }}" class="inline-block px-6 py-3 bg-white text-teal-700 rounded-lg font-bold hover:bg-teal-50 transition">{{ $ctaLabel }} &rarr;</a>
            @endif
        </div>
    </section>

    {{-- The 5 steps --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">How it works</h2>
        <ol class="space-y-5 max-w-3xl mx-auto">
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">1</span>
                <div>
                    <h3 class="font-bold mb-1">Sign up as a bidder</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">One free account does everything — bid AND sell. Takes 30 seconds.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">2</span>
                <div>
                    <h3 class="font-bold mb-1">Join your local community</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pick the region you live in. You can only list in one community at a time (so the auction stays local).</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">3</span>
                <div>
                    <h3 class="font-bold mb-1">List your item</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Add a clear photo, write {{ $minDesc }}+ characters of description. Every lot starts at R{{ $startingBid }} — let the bidders set the value.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">4</span>
                <div>
                    <h3 class="font-bold mb-1">Auction night happens</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Your community gathers online — lots run one at a time, real auctioneer's cadence: presenting, bidding, "going once, going twice, sold."</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">5</span>
                <div>
                    <h3 class="font-bold mb-1">Confirm or decline within {{ $confirmationHours }} hours</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">If you're happy with the winning bid, accept and arrange collection with the buyer. If it's a lowball, decline (with limits — see below).</p>
                </div>
            </li>
        </ol>
    </section>

    {{-- Costs --}}
    <section class="bg-gray-50 dark:bg-gray-900/50 py-12 border-y border-gray-200 dark:border-gray-700">
        <div class="max-w-5xl mx-auto px-4">
            <h2 class="text-2xl md:text-3xl font-bold mb-2 text-center">What it costs</h2>
            <p class="text-gray-600 dark:text-gray-400 text-center max-w-2xl mx-auto mb-8">
                We only get paid when you do.
            </p>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="card p-6 text-center">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Listing fee</div>
                    <div class="text-3xl font-bold text-teal-600 mt-2 mb-1">R0</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">List as many items as you want, no upfront cost.</p>
                </div>
                <div class="card p-6 text-center ring-2 ring-teal-500">
                    <div class="text-xs uppercase tracking-wide text-teal-600">If your lot sells</div>
                    <div class="text-3xl font-bold text-teal-600 mt-2 mb-1">{{ rtrim(rtrim(number_format($commissionPct, 1), '0'), '.') }}%</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Platform commission on the hammer price. You keep the rest.</p>
                </div>
                <div class="card p-6 text-center">
                    <div class="text-xs uppercase tracking-wide text-gray-500">If your lot doesn't sell</div>
                    <div class="text-3xl font-bold text-teal-600 mt-2 mb-1">R0</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Relist into next week's auction free.</p>
                </div>
            </div>

            <div class="mt-8 card p-5">
                <h3 class="font-bold mb-2">A worked example</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    You list a couch. Bidding ends at R800. Here's the math:
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div class="text-[11px] uppercase text-gray-500">Hammer price</div>
                        <div class="font-bold text-lg">R800</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div class="text-[11px] uppercase text-gray-500">Platform fee ({{ rtrim(rtrim(number_format($commissionPct, 1), '0'), '.') }}%)</div>
                        <div class="font-bold text-lg text-red-600">−R{{ number_format(800 * $commissionPct / 100, 0) }}</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div class="text-[11px] uppercase text-gray-500">You receive</div>
                        <div class="font-bold text-lg text-green-600">R{{ number_format(800 - (800 * $commissionPct / 100), 0) }}</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div class="text-[11px] uppercase text-gray-500">When platform paid</div>
                        <div class="font-bold text-sm">Monthly invoice</div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">
                    The buyer pays you directly (cash, EFT, however you arrange). You settle the {{ rtrim(rtrim(number_format($commissionPct, 1), '0'), '.') }}% with the platform via PayFast.
                </p>
            </div>

            {{-- How settlement actually works --}}
            <div class="mt-6 card p-5 border-l-4 border-teal-500">
                <h3 class="font-bold mb-2">When and how you pay the platform</h3>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <li class="flex gap-2">
                        <span class="text-teal-600 font-bold">•</span>
                        <span><strong>Pay-as-you-earn.</strong> Nothing upfront. Each sold lot adds its {{ rtrim(rtrim(number_format($commissionPct, 1), '0'), '.') }}% to a running balance you can pay anytime.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-teal-600 font-bold">•</span>
                        <span><strong>Monthly reminder.</strong> On the 1st of each month we send an in-app notification with your outstanding balance and a one-click PayFast link to settle.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-teal-600 font-bold">•</span>
                        <span><strong>You're only blocked from listing if your balance exceeds R{{ number_format($feeThreshold, 0) }} <em>and</em> some of it is older than {{ $feeGraceDays }} days.</strong> Means a single big sale won't lock you out — you have a {{ $feeGraceDays }}-day grace window to settle.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-teal-600 font-bold">•</span>
                        <span><strong>Buyer didn't collect?</strong> Mark the lot as not paid — the {{ rtrim(rtrim(number_format($commissionPct, 1), '0'), '.') }}% on that sale is automatically waived.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Protection / rules --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">You're protected — and so is the auction</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="card p-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="font-bold">Decline a lowball within {{ $confirmationHours }} hours</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    If a winning bid is way below what your item is worth, you can decline. Use it sparingly — repeated declines trigger a 30-day suspension to keep the auction credible.
                </p>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <h3 class="font-bold">Buyer didn't collect? Strike them.</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Mark "buyer didn't pay" on any sold lot the winner ghosted. Two strikes in 6 months and they're auto-blocked from bidding. You owe nothing on a voided sale.
                </p>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    <h3 class="font-bold">2-bidder rule keeps it real</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    A lot needs at least 2 distinct bidders to count as a sale. Single-bidder lots go unsold so the price reflects real demand, not a friend's friendly bid.
                </p>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <h3 class="font-bold">Relist if it doesn't sell</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Unsold lot? One click moves it into next week's auction. Always free.
                </p>
            </div>
            <div class="card p-6 md:col-span-2">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <h3 class="font-bold">Quality threshold — auction needs critical mass</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Each region typically needs at least <strong>{{ $minLots }} lots</strong> and <strong>{{ $minBidders }} active bidders</strong> for the auction to go live. If thresholds aren't met, all listed lots roll forward to next week's auction automatically — nobody loses out, the auction just waits until the community shows up. (Exact thresholds may vary per region.)
                </p>
            </div>
        </div>
    </section>

    {{-- What makes a good listing --}}
    <section class="bg-gray-50 dark:bg-gray-900/50 py-12 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-3xl mx-auto px-4">
            <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center">What makes a listing actually sell</h2>
            <div class="card p-6 space-y-3">
                <div class="flex gap-3">
                    <span class="text-green-600 font-bold">✓</span>
                    <div><strong>Clear photo</strong> — natural light, item filling the frame, shows any flaws honestly. Min {{ $minImages }} image, more is better.</div>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 font-bold">✓</span>
                    <div><strong>Specific description</strong> — brand, size, condition, age. Bidders bid more confidently when they know exactly what they're getting.</div>
                </div>
                <div class="flex gap-3">
                    <span class="text-green-600 font-bold">✓</span>
                    <div><strong>Be honest about flaws</strong> — a chip, a scratch, a missing piece. Disclosed problems don't kill bids; surprises do.</div>
                </div>
                <div class="flex gap-3">
                    <span class="text-red-600 font-bold">✗</span>
                    <div><strong>Don't list things you can't deliver locally.</strong> Buyers expect to collect from you in your community.</div>
                </div>
                <div class="flex gap-3">
                    <span class="text-red-600 font-bold">✗</span>
                    <div><strong>Don't list duplicates of the same item in one auction</strong> — one lot per item per week.</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="max-w-3xl mx-auto px-4 py-12 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-3">Ready to clear out the garage?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Sign up, join your local community, list your first item. We'll do the rest.
        </p>
        @if($ctaTone !== 'ineligible')
            <a href="{{ $ctaUrl }}" class="inline-block px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold transition">{{ $ctaLabel }} &rarr;</a>
        @else
            <a href="{{ route('communities.index') }}" class="inline-block px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold transition">Browse communities &rarr;</a>
        @endif
    </section>

    {{-- Built by Bidwright --}}
    <section class="max-w-3xl mx-auto px-4 pb-12">
        <div class="border-t border-gray-200 dark:border-gray-700 pt-10 text-center">
            <h2 class="text-2xl font-bold mb-3">The technology behind community auctions</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ config('branding.name') }} is built by Bidwright &mdash; custom online auction software for
                English, Dutch, sealed-bid, live, and community auctions. Want a platform like this for your business?
            </p>
            <a href="https://bidwright.bidall.co.za" target="_blank" rel="noopener"
               title="Bidwright — custom online auction software development"
               class="inline-flex items-center justify-center min-h-[44px] px-8 border border-teal-600 text-teal-700 dark:text-teal-400 hover:bg-teal-600 hover:text-white rounded-xl font-bold transition">
                Explore Bidwright auction software &rarr;
            </a>
        </div>
    </section>
</x-app-layout>
