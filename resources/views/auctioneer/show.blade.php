<x-app-layout>
    <x-slot name="title">{{ $auctioneer->business_name }}</x-slot>
    <x-slot name="description">{{ $auctioneer->business_name }}{{ $auctioneer->description ? ' - ' . Str::limit($auctioneer->description, 140) : '' }} on {{ config('branding.name') }}.</x-slot>
    @if($auctioneer->logo)
    <x-slot name="ogImage">{{ Storage::url($auctioneer->logo) }}</x-slot>
    @endif

    <!-- Banner Image -->
    @if($auctioneer->banner_image)
        <div class="w-full h-64 md:h-80 lg:h-96 bg-gradient-to-r from-primary-400 to-primary-600 relative">
            <img src="{{ Storage::url($auctioneer->banner_image) }}"
                 alt="{{ $auctioneer->business_name }} Banner"
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            @if($auctioneer->brand_hero_text)
            <div class="absolute inset-0 flex items-center justify-center">
                <h2 class="text-2xl md:text-4xl font-bold text-white text-center px-4 text-shadow-lg">{{ $auctioneer->brand_hero_text }}</h2>
            </div>
            @endif
        </div>
    @else
        <div class="w-full h-64 md:h-80 lg:h-96 bg-gradient-to-r from-primary-500 to-primary-700 relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)" />
                </svg>
            </div>
            @if($auctioneer->brand_hero_text)
            <div class="absolute inset-0 flex items-center justify-center">
                <h2 class="text-2xl md:text-4xl font-bold text-white text-center px-4 text-shadow-lg">{{ $auctioneer->brand_hero_text }}</h2>
            </div>
            @endif
        </div>
    @endif

    <div class="py-12 -mt-32 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Auctioneer Header Card -->
            <div class="card mb-8 shadow-xl">
                <div class="p-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            @if($auctioneer->logo)
                                <img src="{{ Storage::url($auctioneer->logo) }}"
                                     alt="{{ $auctioneer->business_name }}"
                                     class="w-32 h-32 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-700 shadow-md">
                            @else
                                <div class="w-32 h-32 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center border-2 border-gray-200 dark:border-gray-700 shadow-md">
                                    <span class="text-4xl font-bold text-primary-600 dark:text-primary-400">
                                        {{ substr($auctioneer->business_name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Info Section -->
                        <div class="flex-1">
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $auctioneer->business_name }}
                            </h1>

                            <div class="flex flex-wrap items-center gap-4 text-gray-600 dark:text-gray-400 mb-4">
                                @if($auctioneer->user->city || $auctioneer->user->province)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ $auctioneer->user->city }}{{ $auctioneer->user->city && $auctioneer->user->province ? ', ' : '' }}{{ $auctioneer->user->province }}</span>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Member since {{ $auctioneer->created_at->format('M Y') }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                @if($auctioneer->bio)
                                    <button @click="$dispatch('open-bio-modal')" class="btn btn-outline btn-sm">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        About Us
                                    </button>
                                @endif
                                @if($auctioneer->user->phone)
                                    <a href="tel:{{ $auctioneer->user->phone }}" class="btn btn-outline btn-sm">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        Call
                                    </a>
                                @endif

                                @if(config('regional.features.whatsapp') && $auctioneer->whatsapp_number)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $auctioneer->whatsapp_number) }}"
                                       target="_blank"
                                       class="btn btn-success btn-sm">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        WhatsApp
                                    </a>
                                @endif

                                @if($auctioneer->whatsapp_group_link)
                                    <a href="{{ $auctioneer->whatsapp_group_link }}"
                                       target="_blank"
                                       class="btn btn-success btn-sm">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Join Group
                                    </a>
                                @endif

                                {{-- Share this profile via WhatsApp. Message is built client-side because PHP \u{...} escapes
                                     and literal 4-byte UTF-8 emojis both came through as ? on this stack. JS template literals
                                     + encodeURIComponent are the proven-working path (same pattern as whatsapp-blast). --}}
                                @php
                                    $shareNext = $upcomingAuctions->first();
                                    $shareData = [
                                        'brand' => config('branding.name'),
                                        'url' => route('auctioneer.show', $auctioneer),
                                        'isCommunity' => $auctioneer->isCommunity() && $auctioneer->communityRegion(),
                                        'name' => $auctioneer->isCommunity() && ($r = $auctioneer->communityRegion())
                                            ? $r->name
                                            : $auctioneer->business_name,
                                        'location' => $auctioneer->isCommunity() && ($r = $auctioneer->communityRegion())
                                            ? ($r->metro_area ?? '')
                                            : collect([$auctioneer->user->city, $auctioneer->user->province])->filter()->implode(', '),
                                        'isLive' => $liveAuctions->count() > 0,
                                        'liveCount' => $liveAuctions->count(),
                                        'upcomingCount' => $upcomingAuctions->count(),
                                        'nextDate' => ($shareNext && $shareNext->goes_live_at)
                                            ? $shareNext->goes_live_at->format('D j M \a\t H:i')
                                            : null,
                                        'description' => $auctioneer->description
                                            ? \Illuminate\Support\Str::limit(strip_tags($auctioneer->description), 160)
                                            : '',
                                        'startBid' => (int) config('community.starting_bid', 20),
                                    ];
                                @endphp
                                <a x-data="{ msg: '' }"
                                   x-init="
                                       const d = @js($shareData);
                                       if (d.isCommunity) {
                                           let m = `\u{1F3D8}\u{FE0F} *COMMUNITY AUCTION* \u{1F3D8}\u{FE0F}\n\n*${d.name}*\n`;
                                           if (d.location) m += `\u{1F4CD} ${d.location}\n`;
                                           if (d.isLive) m += `\u{1F534} LIVE NOW\n`;
                                           else if (d.nextDate) m += `\u{1F4C5} Next: ${d.nextDate}\n`;
                                           m += `\u{1F6D2} Anyone can bid — every lot starts at R${d.startBid}\n`;
                                           m += `\u{1F4E6} Locals can list in the next auction\n\n`;
                                           m += `Powered by ${d.brand}\n\n\u{1F449} Browse & bid:\n${d.url}`;
                                           msg = m;
                                       } else {
                                           let m = `\u{1F528} *AUCTIONEER* \u{1F528}\n\n*${d.name}*\n`;
                                           if (d.location) m += `\u{1F4CD} ${d.location}\n`;
                                           const bits = [];
                                           if (d.liveCount > 0)     bits.push(`\u{1F534} ${d.liveCount} live`);
                                           if (d.upcomingCount > 0) bits.push(`\u{1F4C5} ${d.upcomingCount} upcoming`);
                                           if (bits.length)         m += bits.join(' • ') + '\n';
                                           if (d.description)       m += `\n${d.description}\n`;
                                           m += `\nPowered by ${d.brand}\n\n\u{1F449} Browse & follow:\n${d.url}`;
                                           msg = m;
                                       }
                                   "
                                   :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent(msg)"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn btn-outline btn-sm"
                                   title="Share this on WhatsApp">
                                    <svg class="w-5 h-5 mr-2 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    Share
                                </a>

                                <!-- Social Media Links -->
                                @if($auctioneer->facebook)
                                    <a href="{{ $auctioneer->facebook }}" target="_blank" class="btn btn-outline btn-sm">
                                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        Facebook
                                    </a>
                                @endif

                                @if($auctioneer->instagram)
                                    <a href="{{ $auctioneer->instagram }}" target="_blank" class="btn btn-outline btn-sm">
                                        <svg class="w-5 h-5 mr-2 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                        </svg>
                                        Instagram
                                    </a>
                                @endif

                                @if($auctioneer->tiktok)
                                    <a href="{{ $auctioneer->tiktok }}" target="_blank" class="btn btn-outline btn-sm">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                        </svg>
                                        TikTok
                                    </a>
                                @endif

                                <!-- View Rules Button -->
                                <button @click="$dispatch('open-rules-modal')" class="btn btn-outline btn-sm">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    View Rules
                                </button>

                                {{-- Follow button hidden — follow function is not part of the college product. --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $communityRegion = $auctioneer->isCommunity() ? $auctioneer->communityRegion() : null;
                $userRegionId = auth()->check() ? auth()->user()->community_region_id : null;
                $isMemberOfThisRegion = $communityRegion && $userRegionId === $communityRegion->id;
            @endphp

            @if($communityRegion)
                <!-- Community Region CTA -->
                <div class="card mb-8 border-2 border-primary-200 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-900/20">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                                    {{ $communityRegion->name }} Community Auction
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($isMemberOfThisRegion)
                                        You're a member of this community. List items into the next auction or check on your lots.
                                    @elseif(auth()->check() && $userRegionId && $userRegionId !== $communityRegion->id)
                                        You're currently a member of another community. You'll need to switch regions to list here.
                                    @elseif(auth()->check())
                                        Join this community to list items in the next auction. Members can list a few items per auction.
                                    @else
                                        Log in to join this community and list items in the next auction.
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 md:flex-shrink-0">
                                @auth
                                    @if($isMemberOfThisRegion)
                                        <a href="{{ route('community.create-lot') }}" class="btn btn-primary btn-sm">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            List a Lot
                                        </a>
                                        <a href="{{ route('community.my-lots') }}" class="btn btn-outline btn-sm">
                                            My Lots
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('community.join', $communityRegion) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                @if($userRegionId)
                                                    Switch to {{ $communityRegion->name }}
                                                @else
                                                    Join {{ $communityRegion->name }}
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                                        Log in to Join
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 sm:gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Auctions</div>
                        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                            {{ $stats['total_auctions'] }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Live Auctions</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ $stats['live_auctions'] }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Lots</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $stats['total_lots'] }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Lots Sold</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $stats['lots_sold'] }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Rating</div>
                        <div class="flex items-center gap-1">
                            @if($totalRatings > 0)
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= round($averageRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-lg font-bold text-gray-900 dark:text-gray-100 ml-1">{{ $averageRating }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $totalRatings }})</span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">No ratings yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate this Auctioneer -->
            @if($canRate)
            <div class="card mb-8" x-data="{ rating: {{ $userRating ?? 0 }}, hover: 0 }">
                <div class="p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $userRating ? 'Update your rating:' : 'Rate this auctioneer:' }}
                    </span>
                    <form method="POST" action="{{ route('auctioneer.rate', $auctioneer) }}" class="flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="rating" :value="rating">
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button"
                                        @click="rating = {{ $i }}"
                                        @mouseenter="hover = {{ $i }}"
                                        @mouseleave="hover = 0"
                                        class="p-0.5 transition-transform hover:scale-110">
                                    <svg class="w-7 h-7 transition-colors" :class="(hover || rating) >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        <button type="submit" x-show="rating > 0" x-cloak class="btn btn-primary btn-sm">Submit</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Auctions Tabs -->
            <div x-data="{ tab: 'live' }" class="mb-8">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="flex gap-8">
                        <button @click="tab = 'live'"
                                :class="tab === 'live' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                                class="py-4 px-1 border-b-2 font-medium">
                            Live Auctions ({{ $liveAuctions->count() }})
                        </button>
                        <button @click="tab = 'upcoming'"
                                :class="tab === 'upcoming' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                                class="py-4 px-1 border-b-2 font-medium">
                            Upcoming Auctions ({{ $upcomingAuctions->count() }})
                        </button>
                        <button @click="tab = 'past'"
                                :class="tab === 'past' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 dark:text-gray-400'"
                                class="py-4 px-1 border-b-2 font-medium">
                            Past Auctions ({{ $pastAuctions->count() }})
                        </button>
                    </nav>
                </div>

                <!-- Live Auctions -->
                <div x-show="tab === 'live'">
                    @if($liveAuctions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($liveAuctions as $auction)
                                @include('components.auction-card', ['auction' => $auction, 'linkTo' => 'auction'])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-600 dark:text-gray-400">No live auctions.</p>
                        </div>
                    @endif
                </div>

                <!-- Upcoming Auctions -->
                <div x-show="tab === 'upcoming'">
                    @if($upcomingAuctions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($upcomingAuctions as $auction)
                                @include('components.auction-card', ['auction' => $auction, 'linkTo' => 'auction'])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-600 dark:text-gray-400">No upcoming auctions.</p>
                        </div>
                    @endif
                </div>

                <!-- Past Auctions -->
                <div x-show="tab === 'past'">
                    @if($pastAuctions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($pastAuctions as $auction)
                                @include('components.auction-card', ['auction' => $auction, 'linkTo' => 'auction'])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-600 dark:text-gray-400">No past auctions.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $auctioneerSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $auctioneer->business_name,
            'url' => route('auctioneer.show', $auctioneer),
            'description' => $auctioneer->description ?? $auctioneer->business_name . ' on ' . config('branding.name'),
        ];
        if ($auctioneer->logo) {
            $auctioneerSchema['logo'] = Storage::url($auctioneer->logo);
        }
        $socialLinks = array_values(array_filter([
            $auctioneer->facebook,
            $auctioneer->instagram,
            $auctioneer->twitter,
            $auctioneer->linkedin,
            $auctioneer->website,
        ]));
        if ($socialLinks) {
            $auctioneerSchema['sameAs'] = $socialLinks;
        }

        $auctioneerBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Auctioneers', 'item' => route('auctioneers.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $auctioneer->business_name],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($auctioneerSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($auctioneerBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush

    <!-- Bio Modal -->
    @if($auctioneer->bio)
    <div x-data="{ open: false }"
         @open-bio-modal.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
             @click="open = false"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col"
                 @click.stop>

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        @if($auctioneer->logo)
                            <img src="{{ Storage::url($auctioneer->logo) }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                        @else
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                                <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ substr($auctioneer->business_name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">About Us</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $auctioneer->business_name }}</p>
                        </div>
                    </div>
                    <button @click="open = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!! nl2br(e($auctioneer->bio)) !!}
                    </div>
                </div>

                <div class="flex items-center justify-end p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <button @click="open = false" class="btn btn-outline">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Rules Modal -->
    <div x-data="{ open: false }"
         @open-rules-modal.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
             @click="open = false"></div>

        <!-- Modal Panel -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[85vh] flex flex-col"
                 @click.stop>

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Auctioneer Rules</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $auctioneer->business_name }}</p>
                        </div>
                    </div>
                    <button @click="open = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!! nl2br(e($auctioneer->rules ?? \App\Models\Auctioneer::DEFAULT_RULES)) !!}
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <button @click="open = false" class="btn btn-outline">
                        Close
                    </button>
                    <button @click="window.print()" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
