<x-app-layout>
    <x-slot name="title">Auctioneer Dashboard</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Auctioneer Dashboard</h1>
                <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Auction
                </a>
            </div>

            {{-- Credit Balance / Buy Credits card PARKED — free standalone product has no
                 credits or payments. Restore for a paid deployment. --}}

            <!-- Invite Link -->
            <div class="card mb-8" x-data="{ copied: false }">
                <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Your Invite Link</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Share this link to grow your followers on {{ config('branding.name') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="text" readonly value="{{ route('auctioneer.join', $auctioneer->slug) }}" class="input text-sm flex-1 sm:w-80 bg-gray-50 dark:bg-gray-800" id="invite-link-input">
                        <button type="button"
                                @click="navigator.clipboard.writeText(document.getElementById('invite-link-input').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="btn btn-primary whitespace-nowrap"
                                :class="copied ? 'bg-green-600 hover:bg-green-700' : ''">
                            <span x-show="!copied">Copy Link</span>
                            <span x-show="copied">Copied!</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Draft Auctions</div>
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['draft_auctions'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Live Auctions</div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['live_auctions'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Lots</div>
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_lots'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Lots Sold</div>
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['lots_sold'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Followers</div>
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['followers'] }}</div>
                    </div>
                </div>

                <a href="{{ route('seller.unsold-lots') }}" class="card card-hover">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Unsold Lots</div>
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['unsold_lots'] }}</div>
                        @if($stats['free_relist_lots'] > 0)
                            <div class="text-xs text-green-600 dark:text-green-400 mt-1 font-medium">
                                {{ $stats['free_relist_lots'] }} free relist{{ $stats['free_relist_lots'] > 1 ? 's' : '' }} available
                            </div>
                        @endif
                    </div>
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-6 mb-8">
                <a href="{{ route('seller.auctions.create') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Create Auction</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Start a new auction event</p>
                </a>

                <a href="{{ route('seller.unsold-lots') }}" class="card card-hover p-6 text-center relative">
                    @if($stats['free_relist_lots'] > 0)
                        <span class="absolute top-3 right-3 w-6 h-6 bg-green-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            {{ $stats['free_relist_lots'] }}
                        </span>
                    @endif
                    <svg class="w-12 h-12 mx-auto mb-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Relist Lots</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if($stats['unsold_lots'] > 0)
                            {{ $stats['unsold_lots'] }} unsold lot{{ $stats['unsold_lots'] > 1 ? 's' : '' }} to relist
                        @else
                            No unsold lots yet
                        @endif
                    </p>
                </a>

                <a href="{{ route('seller.collections') }}" class="card card-hover p-6 text-center relative">
                    @if(($stats['awaiting_collection'] ?? 0) > 0)
                        <span class="absolute top-3 right-3 w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            {{ $stats['awaiting_collection'] }}
                        </span>
                    @endif
                    <svg class="w-12 h-12 mx-auto mb-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Collections</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if(($stats['awaiting_collection'] ?? 0) > 0)
                            {{ $stats['awaiting_collection'] }} awaiting payment
                        @else
                            No pending collections
                        @endif
                    </p>
                </a>

                <a href="{{ route('seller.accounting') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Accounting</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">View sales & request payouts</p>
                </a>

                <a href="{{ route('seller.analytics') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">View Analytics</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Track your performance</p>
                </a>

                @if(!($isStaff ?? false))
                <a href="{{ route('seller.staff') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Manage Staff</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Invite & manage team members</p>
                </a>
                @endif

                <a href="{{ route('seller.followers') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Manage Followers</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">View and contact your {{ $stats['followers'] }} followers</p>
                </a>

                <a href="{{ route('seller.notifications') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Push Notifications</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Notify your {{ $stats['followers'] }} followers</p>
                </a>

                <a href="{{ route('seller.whatsapp-blast') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">WhatsApp Blast</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Share auctions to your groups</p>
                </a>

                <a href="{{ route('seller.bidder-insights') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Bidder Insights</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">View engagement & browsing data</p>
                </a>
            </div>

            <!-- Recent Events -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Recent Auctions</h2>
                    <a href="{{ route('seller.auctions.index') }}" class="text-primary-600 hover:text-primary-700">View All</a>
                </div>

                @if($recentAuctions->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentAuctions as $auction)
                            <div class="card card-hover">
                                <div class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-4 mb-2">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    @if($auction->status === 'ended')
                                                        <a href="{{ route('seller.auctions.live-report', $auction) }}" class="hover:text-primary-600">
                                                            {{ $auction->title }}
                                                        </a>
                                                    @elseif($auction->status === 'live')
                                                        <a href="{{ route('seller.auctions.live-report', $auction) }}" class="hover:text-primary-600">
                                                            {{ $auction->title }}
                                                        </a>
                                                    @else
                                                        <a href="{{ route('seller.auctions.show', $auction) }}" class="hover:text-primary-600">
                                                            {{ $auction->title }}
                                                        </a>
                                                    @endif
                                                </h3>
                                                @if($auction->status === 'live')
                                                    <span class="badge badge-danger animate-pulse">Live</span>
                                                @elseif($auction->status === 'upcoming')
                                                    <span class="badge badge-info">Upcoming</span>
                                                @elseif($auction->status === 'draft')
                                                    <span class="badge badge-secondary">Draft</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($auction->status) }}</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $auction->lots->count() }} lots •
                                                Starts: {{ $auction->start_time->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            @if($auction->status === 'draft')
                                                <a href="{{ route('seller.auctions.show', $auction) }}" class="btn btn-outline">Edit</a>
                                                <a href="{{ route('seller.auctions.lots.create', $auction) }}" class="btn btn-primary">Add Lots</a>
                                            @endif
                                            @if(in_array($auction->status, ['live', 'ended']))
                                                <a href="{{ route('seller.auctions.live-report', $auction) }}" class="btn btn-outline">Report</a>
                                            @endif
                                            @if(in_array($auction->status, ['upcoming', 'live']))
                                                @php
                                                    $waText = "* {$auction->title} *\n\n";
                                                    $waText .= $auction->start_time->isFuture()
                                                        ? "Starts: " . $auction->start_time->format('M d, Y \a\t H:i') . "\n"
                                                        : "LIVE NOW - Ends: " . ($auction->end_time ? $auction->end_time->format('M d, Y \a\t H:i') : 'TBA') . "\n";
                                                    $waText .= $auction->lots->count() . " Lots available\n\n";
                                                    $waText .= "Browse & bid now:\n" . route('auctions.show', $auction->slug) . "\n\n";
                                                    $waText .= "Powered by " . config('branding.name');
                                                @endphp
                                                <a href="https://api.whatsapp.com/send?text={{ rawurlencode($waText) }}"
                                                   target="_blank"
                                                   class="btn bg-green-600 hover:bg-green-700 text-white"
                                                   title="Share to WhatsApp">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </a>
                                            @endif
                                            <a href="{{ route('seller.auctions.show', $auction) }}" class="btn btn-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card">
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">You haven't created any events yet.</p>
                            <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary">Create Your First Auction</a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
