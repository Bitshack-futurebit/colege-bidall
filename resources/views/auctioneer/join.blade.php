<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $auctioneer->business_name }} — Join & Bid</title>
    <meta name="description" content="Follow {{ $auctioneer->business_name }} to bid on their auctions and never miss a sale.">

    @if($auctioneer->logo)
        <meta property="og:image" content="{{ Storage::url($auctioneer->logo) }}">
    @endif
    <meta property="og:title" content="{{ $auctioneer->business_name }} — Join & Bid">
    <meta property="og:description" content="Follow {{ $auctioneer->business_name }} to bid on their auctions and never miss a sale.">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 antialiased">

    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-primary-600 to-primary-700 text-white min-h-[50vh] flex items-center">
        @if($auctioneer->banner_image)
            <div class="absolute inset-0">
                <img src="{{ Storage::url($auctioneer->banner_image) }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/30"></div>
            </div>
        @endif
        <div class="relative w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <!-- Auctioneer Logo/Image -->
            <div class="mb-6">
                @if($auctioneer->logo)
                    <img src="{{ Storage::url($auctioneer->logo) }}" alt="{{ $auctioneer->business_name }}" class="w-28 h-28 rounded-full mx-auto border-4 border-white shadow-lg object-cover">
                @elseif($auctioneer->profile_image)
                    <img src="{{ Storage::url($auctioneer->profile_image) }}" alt="{{ $auctioneer->business_name }}" class="w-28 h-28 rounded-full mx-auto border-4 border-white shadow-lg object-cover">
                @else
                    <div class="w-28 h-28 rounded-full mx-auto border-4 border-white shadow-lg bg-white/20 flex items-center justify-center">
                        <span class="text-4xl font-bold">{{ substr($auctioneer->business_name, 0, 1) }}</span>
                    </div>
                @endif
            </div>

            <h1 class="text-3xl sm:text-4xl font-bold mb-3">{{ $auctioneer->business_name }}</h1>

            @if($auctioneer->user)
                <p class="text-lg opacity-90 mb-2">
                    {{ $auctioneer->user->city ?? '' }}{{ $auctioneer->user->city && $auctioneer->user->province ? ', ' : '' }}{{ $auctioneer->user->province ?? '' }}
                </p>
            @endif

            <!-- Rating -->
            @if($totalRatings > 0)
                <div class="flex items-center justify-center gap-2 mb-4">
                    <div class="flex">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= round($averageRating) ? 'text-yellow-300' : 'text-white/30' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="text-sm opacity-90">{{ $averageRating }} ({{ $totalRatings }} {{ Str::plural('rating', $totalRatings) }})</span>
                </div>
            @endif

            <!-- Stats -->
            <div class="flex items-center justify-center gap-6 text-sm opacity-90 mb-8">
                @if($stats['followers'] > 0)
                    <span>{{ $stats['followers'] }} {{ Str::plural('follower', $stats['followers']) }}</span>
                @endif
                @if($stats['total_auctions'] > 0)
                    <span>{{ $stats['total_auctions'] }} {{ Str::plural('auction', $stats['total_auctions']) }}</span>
                @endif
                @if($stats['live_auctions'] > 0)
                    <span class="font-semibold">{{ $stats['live_auctions'] }} LIVE now</span>
                @endif
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('register', ['ref' => $auctioneer->slug]) }}" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg shadow-lg">
                        Register & Follow {{ $auctioneer->business_name }}
                    </a>
                    <a href="{{ route('login', ['redirect' => route('auctioneer.join', $auctioneer)]) }}" class="bg-white/10 text-white px-8 py-3 rounded-lg font-semibold hover:bg-white/20 transition border-2 border-white/50">
                        Already have an account? Login
                    </a>
                @else
                    @if($isFollowing)
                        <a href="{{ route('auctioneer.show', $auctioneer) }}" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg shadow-lg">
                            View {{ $auctioneer->business_name }}'s Auctions
                        </a>
                        <span class="inline-flex items-center gap-2 text-white/90">
                            <svg class="w-5 h-5 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Already following
                        </span>
                    @else
                        <form method="POST" action="{{ route('auctioneer.join.follow', $auctioneer) }}">
                            @csrf
                            <button type="submit" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg shadow-lg">
                                Follow {{ $auctioneer->business_name }}
                            </button>
                        </form>
                    @endif
                @endguest
            </div>
        </div>
    </div>

    <!-- What You Get Section -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 text-center mb-8">Why Bid Online?</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Never Miss a Sale</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Get notified when new auctions are listed</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Real-Time Bidding</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Live countdowns and instant bid updates</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Fair & Transparent</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">No more arguing about who bid what</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold mb-2">Bid From Anywhere</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Works on your phone, tablet, or computer</p>
            </div>
        </div>

        <!-- Current Auctions -->
        @if($auctions->count() > 0)
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 text-center mb-6">
                {{ $stats['live_auctions'] > 0 ? 'Live & Upcoming' : 'Upcoming' }} Auctions
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach($auctions as $auction)
                    <a href="{{ route('auctions.show', $auction) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                @if($auction->status === 'live')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse">LIVE</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Upcoming</span>
                                @endif
                                <span class="text-sm text-gray-500">{{ $auction->lots_count }} {{ Str::plural('lot', $auction->lots_count) }}</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $auction->title }}</h3>
                            @if($auction->status === 'live')
                                <p class="text-sm text-green-600 dark:text-green-400">Bidding open now!</p>
                            @elseif($auction->start_time)
                                <p class="text-sm text-gray-600 dark:text-gray-400">Starts {{ $auction->start_time->format('D j M, H:i') }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Register CTA (for guests) -->
        @guest
            <div class="text-center bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">Ready to start bidding?</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Create a free account in seconds. No fees, no deposits.</p>
                <a href="{{ route('register', ['ref' => $auctioneer->slug]) }}" class="inline-block bg-primary-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-primary-700 transition text-lg">
                    Create Free Account
                </a>
            </div>
        @endguest
    </div>

    <!-- Minimal Footer -->
    <div class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
        Powered by <a href="{{ route('home') }}" class="hover:text-primary-600 transition">{{ config('branding.name') }}</a>
    </div>

</body>
</html>
