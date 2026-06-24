<x-app-layout>
    <x-slot name="title">Admin Dashboard</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Admin Dashboard</h1>

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Users</div>
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_users'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $stats['bidders'] }} bidders, {{ $stats['auctioneers'] }} auctioneers
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Auctions</div>
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_auctions'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $stats['live_auctions'] }} live now
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Platform Revenue</div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ formatCurrency($stats['platform_revenue']) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">All time</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Bids</div>
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_bids']) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Across all auctions</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                <a href="{{ route('admin.users.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Manage Users</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stats['total_users'] }} users</p>
                </a>

                <a href="{{ route('admin.auctioneers.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Manage Auctioneers</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $stats['auctioneers'] }} auctioneers
                        @if($stats['white_label_auctioneers'] > 0)
                            &middot; <span class="text-primary-600 dark:text-primary-400 font-semibold">{{ $stats['white_label_auctioneers'] }} white-label</span>
                        @endif
                    </p>
                </a>

                <a href="{{ route('admin.auctions.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Manage Events</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stats['total_auctions'] }} auctions</p>
                </a>

                <a href="{{ route('admin.revenue') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Revenue Reports</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ formatCurrency($stats['platform_revenue']) }}</p>
                </a>

                <a href="{{ route('admin.broadcast') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Email Broadcast</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stats['total_users'] }} recipients</p>
                </a>

                <a href="{{ route('admin.notifications.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Push Notifications</h3>
                    @php $pushSubCount = \App\Models\PushSubscription::count(); @endphp
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pushSubCount }} subscriber{{ $pushSubCount !== 1 ? 's' : '' }}</p>
                </a>

                <a href="{{ route('admin.promo-codes.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Promo Codes</h3>
                    @php $activePromos = \App\Models\PromoCode::where('is_active', true)->count(); @endphp
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $activePromos }} active</p>
                </a>

                <a href="{{ route('admin.terms.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Terms & Conditions</h3>
                    @php $termsCount = \App\Models\TermsVersion::count(); @endphp
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $termsCount }} version{{ $termsCount !== 1 ? 's' : '' }}</p>
                </a>

                <a href="{{ route('admin.community-regions.index') }}" class="card card-hover p-6 text-center">
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Community Regions</h3>
                    @php
                        $regionCount = \Schema::hasTable('community_regions')
                            ? \App\Models\CommunityRegion::where('is_active', true)->count()
                            : 0;
                    @endphp
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $regionCount }} active</p>
                </a>

                <a href="{{ route('admin.buyer-strikes.index') }}" class="card card-hover p-6 text-center relative">
                    @php
                        $activeStrikes = \Schema::hasTable('buyer_strikes')
                            ? \App\Models\BuyerStrike::whereNull('reversed_at')->count()
                            : 0;
                        $disabledBuyers = \Schema::hasColumn('users', 'bidding_disabled')
                            ? \App\Models\User::where('bidding_disabled', true)->count()
                            : 0;
                    @endphp
                    @if($disabledBuyers > 0)
                        <span class="absolute top-3 right-3 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">
                            {{ $disabledBuyers }}
                        </span>
                    @endif
                    <svg class="w-12 h-12 mx-auto mb-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Buyer Strikes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if($disabledBuyers > 0)
                            {{ $disabledBuyers }} blocked
                        @elseif($activeStrikes > 0)
                            {{ $activeStrikes }} active
                        @else
                            None active
                        @endif
                    </p>
                </a>

                <a href="{{ route('admin.agents.index') }}" class="card card-hover p-6 text-center relative">
                    @php
                        $pendingAgents = \Schema::hasTable('agents')
                            ? \App\Models\Agent::where('status', 'pending')->count()
                            : 0;
                        $activeAgents  = \Schema::hasTable('agents')
                            ? \App\Models\Agent::where('status', 'active')->count()
                            : 0;
                    @endphp
                    @if($pendingAgents > 0)
                        <span class="absolute top-3 right-3 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full bg-amber-500 text-white text-xs font-bold">
                            {{ $pendingAgents }}
                        </span>
                    @endif
                    <svg class="w-12 h-12 mx-auto mb-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Community Agents</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if($pendingAgents > 0)
                            {{ $pendingAgents }} pending review
                        @else
                            {{ $activeAgents }} active
                        @endif
                    </p>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Recent Activity -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Activity</h2>
                        @if($recentActivity->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentActivity as $activity)
                                    <div class="flex items-start gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                        <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $activity->description }}</p>
                                            <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('admin.activity') }}" class="btn btn-outline w-full mt-4">View All Activity</a>
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No recent activity.</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Transactions</h2>
                        @if($recentTransactions->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentTransactions as $transaction)
                                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $transaction->user->name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }} •
                                                {{ $transaction->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaction->amount > 0 ? '+' : '' }}{{ formatCurrency(abs($transaction->amount)) }}
                                            </p>
                                            <span class="text-xs badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline w-full mt-4">View All Transactions</a>
                        @else
                            <p class="text-center text-gray-600 dark:text-gray-400 py-8">No recent transactions.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
