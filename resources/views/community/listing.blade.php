<x-app-layout>
    <x-slot name="title">Community Auctions</x-slot>
    <x-slot name="description">Find your local community auction on {{ config('branding.name') }}. Anyone can list, every lot is local, every Sunday at 6pm.</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Community Auctions</h1>
                <p class="text-gray-600 dark:text-gray-400">Local auctions built by the community. Any member can list, every lot starts at R20, every Sunday at 6pm.</p>
            </div>

            @if($regions->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($regions as $region)
                        @php
                            $auctioneer = \App\Models\Auctioneer::where('slug', 'community-' . $region->slug)->first();
                            $auction = $latestAuctions[$region->id] ?? null;
                            $myRegion = auth()->check() && auth()->user()->community_region_id === $region->id;
                        @endphp
                        <div class="card card-hover flex flex-col">
                            <a href="{{ route('community.region', $region) }}">
                                @if($auctioneer?->banner_image)
                                    <div class="h-24 rounded-t-lg overflow-hidden relative">
                                        <img src="{{ Storage::url($auctioneer->banner_image) }}" alt="" class="w-full h-full object-cover">
                                        @if($region->pilot_mode)
                                            <span class="absolute top-2 right-2 px-2 py-0.5 text-[10px] bg-amber-400 text-amber-900 rounded uppercase font-semibold tracking-wide">Pilot</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="h-24 rounded-t-lg bg-gradient-to-r from-teal-500 to-teal-700 flex items-center justify-center relative">
                                        <svg class="w-10 h-10 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        @if($region->pilot_mode)
                                            <span class="absolute top-2 right-2 px-2 py-0.5 text-[10px] bg-amber-400 text-amber-900 rounded uppercase font-semibold tracking-wide">Pilot</span>
                                        @endif
                                    </div>
                                @endif
                            </a>

                            <div class="p-5 -mt-10 relative flex flex-col flex-1">
                                <a href="{{ route('community.region', $region) }}">
                                    @if($auctioneer?->logo)
                                        <img src="{{ Storage::url($auctioneer->logo) }}" alt="" class="w-16 h-16 rounded-lg object-cover border-2 border-white dark:border-gray-800 shadow-md mb-3">
                                    @else
                                        <div class="w-16 h-16 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-md mb-3">
                                            <span class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ substr($region->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </a>

                                <a href="{{ route('community.region', $region) }}">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-teal-600 mb-1">
                                        {{ $region->name }}
                                    </h3>
                                </a>

                                @if($region->metro_area)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-2">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $region->metro_area }}
                                    </p>
                                @endif

                                {{-- Auction status badge --}}
                                @if($auction)
                                    <div class="mb-3">
                                        @if($auction->status === 'live')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                Live now
                                            </span>
                                        @elseif($auction->status === 'upcoming')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Lineup locked &mdash; {{ $auction->goes_live_at ? $auction->goes_live_at->format('D d M, g:ia') : 'upcoming' }}
                                            </span>
                                        @elseif($auction->status === 'draft')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                                                Building lineup &mdash; {{ $auction->goes_live_at ? $auction->goes_live_at->format('D d M, g:ia') : '' }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div class="flex items-center justify-between gap-2 mt-auto pt-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $region->member_count }} {{ Str::plural('member', $region->member_count) }}</span>

                                    @if($myRegion)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 dark:text-teal-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Your community
                                        </span>
                                    @elseif(auth()->check())
                                        <form method="POST" action="{{ route('community.join', $region) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm font-bold bg-teal-600 hover:bg-teal-700 text-white">Join</button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline font-bold border-teal-600 text-teal-600 hover:bg-teal-600 hover:text-white">Join</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-600 dark:text-gray-400">No active communities yet. Check back soon.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
