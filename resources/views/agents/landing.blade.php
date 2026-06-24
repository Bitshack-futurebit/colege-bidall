<x-app-layout>
    <x-slot name="title">Become a Community Agent</x-slot>
    <x-slot name="description">Earn a share of platform commission by running a local community auction. Built for South African WhatsApp group admins.</x-slot>

    @php
        $tier1Cap = (int) config('community.commission_tier1_cap', 1000);
        $payoutMin = (int) config('community.agent_payout_min', 500);
        $minMembers = 50;

        // Decide what the primary CTA does based on visitor state
        $user = auth()->user();
        $agent = $user?->agent;
        // For logged-out visitors: point at /agent/apply so the auth middleware
        // captures it as the intended URL — they get bounced through login and
        // land back on the apply form, not stranded on the dashboard.
        $ctaUrl = route('agent.apply');
        $ctaLabel = 'Log in or sign up to apply';
        $ctaTone = 'apply';
        if ($user) {
            if ($user->isAdmin() || $user->isAuctioneer() || $user->isStaff()) {
                $ctaTone = 'ineligible';
            } elseif ($agent && $agent->status === 'active') {
                $ctaUrl = route('agent.dashboard');
                $ctaLabel = 'Open agent dashboard';
                $ctaTone = 'active';
            } elseif ($agent && $agent->status === 'pending') {
                $ctaUrl = route('agent.apply');
                $ctaLabel = 'View application status';
                $ctaTone = 'pending';
            } elseif ($agent && in_array($agent->status, ['suspended', 'terminated'])) {
                $ctaTone = 'inactive';
            } else {
                $ctaUrl = route('agent.apply');
                $ctaLabel = 'Apply now';
                $ctaTone = 'apply';
            }
        }
    @endphp

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-teal-600 to-teal-800 text-white">
        <div class="max-w-5xl mx-auto px-4 py-16 md:py-24">
            <span class="inline-block px-3 py-1 text-xs uppercase tracking-wider bg-white/15 rounded-full mb-4">For local WhatsApp group admins</span>
            <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">Earn from your local community auction.</h1>
            <p class="text-xl text-teal-50 max-w-3xl mb-8">
                You already run a WhatsApp buy-and-sell group. We bring the auction tech, the payment rails, and the seller protection. You bring the community — and earn a share of every sale.
            </p>

            @if($ctaTone === 'ineligible')
                <div class="bg-white/15 backdrop-blur rounded-lg p-4 text-sm">
                    Your account type ({{ ucfirst($user->role) }}) can't double as a community agent. Sign in with a separate bidder account to apply.
                </div>
            @elseif($ctaTone === 'inactive')
                <div class="bg-white/15 backdrop-blur rounded-lg p-4 text-sm">
                    Your previous agent application is closed. Contact platform support if you'd like to discuss reapplying.
                </div>
            @else
                <a href="{{ $ctaUrl }}" class="inline-block px-6 py-3 bg-white text-teal-700 rounded-lg font-bold hover:bg-teal-50 transition">{{ $ctaLabel }} &rarr;</a>
            @endif
        </div>
    </section>

    {{-- What you do --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">What an agent actually does</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="card p-6">
                <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <h3 class="font-bold mb-1">Promote auctions</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Share auction nights and lot listings inside your WhatsApp group. Drive turnout.</p>
            </div>
            <div class="card p-6">
                <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M9 7a3 3 0 116 0 3 3 0 01-6 0z"/></svg>
                </div>
                <h3 class="font-bold mb-1">Bring new sellers</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Each signup via your referral link is attributed to you and counts toward your performance.</p>
            </div>
            <div class="card p-6">
                <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <h3 class="font-bold mb-1">Build your patch</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">As your community grows, your monthly earnings tilt in your favour automatically (more on that below).</p>
            </div>
        </div>

        <div class="mt-10 p-5 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded">
            <h3 class="font-bold mb-1">What you don't do</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                You're <strong>not the auction admin</strong>. You don't moderate listings, handle disputes, or settle payouts. The platform owns those. Your job is the front-end relationship and growth.
            </p>
        </div>
    </section>

    {{-- How earnings work --}}
    <section class="bg-gray-50 dark:bg-gray-900/50 py-12 border-y border-gray-200 dark:border-gray-700">
        <div class="max-w-5xl mx-auto px-4">
            <h2 class="text-2xl md:text-3xl font-bold mb-2 text-center">How earnings work</h2>
            <p class="text-gray-600 dark:text-gray-400 text-center max-w-2xl mx-auto mb-8">
                The platform takes 5% commission on every sold lot. That commission is split between us and you, on a sliding ladder that resets each month.
            </p>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="card p-6">
                    <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Tier 1</div>
                    <h3 class="text-2xl font-bold mb-2">First R{{ number_format($tier1Cap) }} of monthly commission</h3>
                    <div class="text-3xl font-bold text-teal-600 mb-2">50 / 50</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Split evenly between platform and you. Resets on the 1st of each month.</p>
                </div>
                <div class="card p-6 ring-2 ring-teal-500">
                    <div class="text-xs uppercase tracking-wide text-teal-600 mb-1">Tier 2 — your reward</div>
                    <h3 class="text-2xl font-bold mb-2">Everything above R{{ number_format($tier1Cap) }}</h3>
                    <div class="text-3xl font-bold text-teal-600 mb-2">70 / 30</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">You take 70% of marginal commission. The bigger your community grows, the more your share dominates.</p>
                </div>
            </div>

            <div class="mt-8 card p-6">
                <h3 class="font-bold mb-3">Worked example — what you'd earn at scale</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                <th class="py-2">Monthly commission your community generates</th>
                                <th class="py-2 text-right">Platform takes</th>
                                <th class="py-2 text-right">You take</th>
                                <th class="py-2 text-right">Your blended %</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 dark:text-gray-300">
                            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2">R500</td><td class="py-2 text-right">R250</td><td class="py-2 text-right text-teal-600 font-semibold">R250</td><td class="py-2 text-right">50%</td></tr>
                            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2">R1,000</td><td class="py-2 text-right">R500</td><td class="py-2 text-right text-teal-600 font-semibold">R500</td><td class="py-2 text-right">50%</td></tr>
                            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2">R2,000</td><td class="py-2 text-right">R800</td><td class="py-2 text-right text-teal-600 font-semibold">R1,200</td><td class="py-2 text-right">60%</td></tr>
                            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2">R5,000</td><td class="py-2 text-right">R1,700</td><td class="py-2 text-right text-teal-600 font-semibold">R3,300</td><td class="py-2 text-right">66%</td></tr>
                            <tr><td class="py-2">R10,000</td><td class="py-2 text-right">R3,200</td><td class="py-2 text-right text-teal-600 font-semibold">R6,800</td><td class="py-2 text-right">68%</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-3">Payouts are claimable once your monthly earnings reach R{{ number_format($payoutMin) }}. Approved within a few business days.</p>
            </div>
        </div>
    </section>

    {{-- Qualifications --}}
    <section class="max-w-5xl mx-auto px-4 py-12">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">Who qualifies</h2>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="card p-6 text-center">
                <div class="text-4xl font-bold text-teal-600 mb-2">{{ $minMembers }}+</div>
                <h3 class="font-semibold mb-1">WhatsApp group members</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">A local buy/sell group you already run or co-admin.</p>
            </div>
            <div class="card p-6 text-center">
                <div class="w-12 h-12 mx-auto mb-2 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="font-semibold mb-1">Local presence</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Your group is centred on a real geographical community — that's the patch you'd represent.</p>
            </div>
            <div class="card p-6 text-center">
                <div class="w-12 h-12 mx-auto mb-2 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <h3 class="font-semibold mb-1">Trusted reputation</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">You're known and respected in your group. Your endorsement matters.</p>
            </div>
        </div>
    </section>

    {{-- How to apply --}}
    <section class="bg-gray-50 dark:bg-gray-900/50 py-12 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-3xl mx-auto px-4">
            <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center">How to apply</h2>
            <ol class="space-y-4">
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">1</span>
                    <div><strong>Sign up or log in</strong> as a regular bidder if you haven't already.</div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">2</span>
                    <div><strong>Submit a screenshot</strong> of your WhatsApp group's info screen showing the member count, plus a short bio.</div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">3</span>
                    <div><strong>We review</strong> in 1-3 business days, then either approve or come back to you with questions.</div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center">4</span>
                    <div><strong>You get an active dashboard</strong> with your referral link, ladder progress, and payout button. We assign you to the right community region.</div>
                </li>
            </ol>

            @if(in_array($ctaTone, ['apply', 'pending', 'active']))
                <div class="text-center mt-10">
                    <a href="{{ $ctaUrl }}" class="inline-block px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold transition">{{ $ctaLabel }} &rarr;</a>
                </div>
            @elseif(!$user)
                <div class="text-center mt-10">
                    <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold transition">Log in to apply &rarr;</a>
                    <div class="mt-2 text-sm text-gray-500">No account yet? <a href="{{ route('register') }}" class="text-teal-600 hover:underline">Sign up first</a>.</div>
                </div>
            @endif
        </div>
    </section>

    {{-- Built by Bidwright --}}
    <section class="max-w-3xl mx-auto px-4 pb-12">
        <div class="border-t border-gray-200 dark:border-gray-700 pt-10 text-center">
            <h2 class="text-2xl font-bold mb-3">The platform behind the auctions</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ config('branding.name') }} runs on Bidwright &mdash; custom online auction software for
                English, Dutch, sealed-bid, live, and community auctions. Want your own auction platform?
            </p>
            <a href="https://bidwright.bidall.co.za" target="_blank" rel="noopener"
               title="Bidwright — custom online auction software development"
               class="inline-flex items-center justify-center min-h-[44px] px-8 border border-teal-600 text-teal-700 dark:text-teal-400 hover:bg-teal-600 hover:text-white rounded-xl font-bold transition">
                Discover Bidwright auction software &rarr;
            </a>
        </div>
    </section>
</x-app-layout>
