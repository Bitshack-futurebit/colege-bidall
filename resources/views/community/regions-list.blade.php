<x-app-layout>
    <x-slot name="title">Community Auctions</x-slot>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Community Auctions</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Buy and sell with your neighbours. No auctioneer, no hassle — just a weekly community auction for your area.
            </p>
        </div>

        @if($regions->isEmpty())
            <div class="card p-8 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="1.5"/></svg>
                </div>
                <p class="text-gray-500">No community regions active yet. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    $myRegionId = auth()->check() ? auth()->user()->community_region_id : null;
                @endphp
                @foreach($regions as $region)
                    <div class="card p-5 hover:shadow-lg hover:border-primary-300 dark:hover:border-primary-700 transition group flex flex-col">
                        <a href="{{ route('community.region', $region) }}" class="block flex-1">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-lg group-hover:text-primary-600 transition">{{ $region->name }}</h3>
                                    @if($region->metro_area)
                                        <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/></svg>
                                            {{ $region->metro_area }}
                                        </p>
                                    @endif
                                </div>
                                @if($region->pilot_mode)
                                    <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded whitespace-nowrap shrink-0">Pilot</span>
                                @endif
                            </div>

                            @if($region->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">{{ $region->description }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $region->users_count }} member{{ $region->users_count === 1 ? '' : 's' }}
                                </span>
                                @forelse($region->schedules as $sch)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $days[$sch->goes_live_day] ?? '' }} {{ substr($sch->goes_live_time, 0, 5) }}
                                    </span>
                                    @break
                                @empty
                                @endforelse
                            </div>
                        </a>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <a href="{{ route('community.region', $region) }}" class="text-xs text-primary-600 hover:underline">Explore &rarr;</a>
                            @if($myRegionId === $region->id)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Your community
                                </span>
                            @elseif(auth()->check())
                                <form method="POST" action="{{ route('community.join', $region) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1.5 text-xs font-medium bg-primary-600 hover:bg-primary-700 text-white rounded transition">
                                        Join community
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                   class="px-3 py-1.5 text-xs font-medium bg-primary-600 hover:bg-primary-700 text-white rounded transition">
                                    Join community
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
