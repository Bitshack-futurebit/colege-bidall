<x-app-layout>
    <x-slot name="title">Contact Us - {{ config('branding.name') }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-8">Contact & Support</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Contact Information -->
            <div class="card p-8">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-6">Get in Touch</h2>

                <div class="space-y-6">
                    @if(config('regional.whatsapp.support_group_url'))
                    <div>
                        <h3 class="font-semibold text-lg mb-3">Platform Support</h3>
                        <a href="{{ config('regional.whatsapp.support_group_url') }}"
                           target="_blank"
                           class="inline-flex items-center gap-3 bg-[#25D366] hover:bg-[#128C7E] text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-md">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>WhatsApp Support Group</span>
                        </a>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                            Join our support group for quick assistance
                        </p>
                    </div>
                    @endif

                    <div class="flex items-start">
                        <div class="text-3xl mr-4">📍</div>
                        <div>
                            <h3 class="font-semibold text-lg mb-1">Location</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ config('branding.name') }}<br>
                                Johannesburg, South Africa
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="text-3xl mr-4">⏰</div>
                        <div>
                            <h3 class="font-semibold text-lg mb-1">Support Hours</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Monday - Friday: 9:00 AM - 5:00 PM SAST<br>
                                Saturday: 9:00 AM - 1:00 PM SAST<br>
                                Sunday: Closed
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card p-8">
                <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-6">Quick Help</h2>

                <div class="space-y-4">
                    <details class="group">
                        <summary class="font-semibold cursor-pointer hover:text-primary-600 transition">
                            How do I start bidding?
                        </summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            Register as a bidder, browse events, and register for events you're interested in.
                            Once registered, you can place bids on any active lot.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="font-semibold cursor-pointer hover:text-primary-600 transition">
                            How do I become an auctioneer?
                        </summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            Register as a seller, pay the one-time activation fee of {{ formatPrice('platform.pricing.activation_fee') }},
                            and you can start creating events and lots immediately.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="font-semibold cursor-pointer hover:text-primary-600 transition">
                            What are the fees?
                        </summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            Activation: {{ formatPrice('platform.pricing.activation_fee') }} (one-time).
                            Per-lot fees: Basic (1 image) {{ formatPrice('platform.pricing.tier_basic.price') }},
                            Pro (5 images) {{ formatPrice('platform.pricing.tier_pro.price') }},
                            Premium (20 images) {{ formatPrice('platform.pricing.tier_premium.price') }}.
                            Platform commission: {{ config('platform.pricing.platform_percentage') }}% of sales.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="font-semibold cursor-pointer hover:text-primary-600 transition">
                            How does payment work?
                        </summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            We use secure payment gateways (PayFast for South Africa).
                            Auctioneers purchase credits in advance, and fees are deducted when events go live.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="font-semibold cursor-pointer hover:text-primary-600 transition">
                            What is soft close?
                        </summary>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            If a bid is placed within {{ config('auction.soft_close_time') / 60 }} minutes of lot closing,
                            the closing time extends by {{ config('auction.soft_close_extension') / 60 }} minutes.
                            This prevents last-second sniping.
                        </p>
                    </details>

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Still have questions? Check our
                            <a href="{{ route('how-it-works') }}" class="text-primary-600 hover:underline">How It Works</a>
                            page or join our WhatsApp support group.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Issues -->
        <div class="card p-8 mt-8">
            <h2 class="text-2xl font-bold text-primary-600 dark:text-primary-400 mb-4">Report Technical Issues</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                If you're experiencing technical difficulties or found a bug, please join our support group and provide:
            </p>
            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-2 mb-4">
                <li>Description of the issue</li>
                <li>Page/section where it occurred</li>
                <li>Steps to reproduce the problem</li>
                <li>Screenshots if applicable</li>
            </ul>
            @if(config('regional.whatsapp.support_group_url'))
            <a href="{{ config('regional.whatsapp.support_group_url') }}"
               target="_blank"
               class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-4 py-2 rounded-lg font-semibold transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Report Issue in Support Group
            </a>
            @endif
        </div>
    </div>
</x-app-layout>
