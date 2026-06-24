<x-app-layout>
    <x-slot name="title">Manage Auctioneers</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Manage Auctioneers</h1>

            <!-- Filter Bar -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex gap-4">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by business name..."
                               class="input flex-1">

                        <select name="status" class="input">
                            <option value="">All Status</option>
                            <option value="activated" {{ request('status') === 'activated' ? 'selected' : '' }}>Activated</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Activation</option>
                        </select>

                        <select name="white_label" class="input">
                            <option value="">All Branding</option>
                            <option value="enabled" {{ request('white_label') === 'enabled' ? 'selected' : '' }}>White-Label Active</option>
                            <option value="configured_not_active" {{ request('white_label') === 'configured_not_active' ? 'selected' : '' }}>Configured (disabled)</option>
                            <option value="none" {{ request('white_label') === 'none' ? 'selected' : '' }}>Standard (no branding)</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'status', 'white_label']))
                            <a href="{{ route('admin.auctioneers.index') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Auctioneers List -->
            @if($auctioneers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($auctioneers as $auctioneer)
                        <div class="card card-hover">
                            <div class="p-6">
                                <div class="flex items-start gap-4 mb-4">
                                    @if($auctioneer->logo)
                                        <img src="{{ Storage::url($auctioneer->logo) }}"
                                             alt="{{ $auctioneer->business_name }}"
                                             class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                                {{ substr($auctioneer->business_name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate mb-1">
                                            {{ $auctioneer->business_name }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $auctioneer->user->email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Status</span>
                                        @if($auctioneer->is_activated)
                                            <span class="badge badge-success">Activated</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Branding</span>
                                        @if($auctioneer->isWhiteLabel())
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded" style="background-color: {{ $auctioneer->brand_primary_color }}20; color: {{ $auctioneer->brand_primary_color }};">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $auctioneer->brand_primary_color }};"></span>
                                                White-Label
                                            </span>
                                        @elseif($auctioneer->brand_primary_color)
                                            <span class="badge" style="background-color: #6b728020; color: #6b7280;">Configured</span>
                                        @else
                                            <span class="text-gray-400">Standard</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Auctions</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $auctioneer->auctions->count() }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Total Lots</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $auctioneer->auctions->sum(fn($e) => $e->lots->count()) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Credit Balance</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency($auctioneer->credit_balance) }}
                                        </span>
                                    </div>

                                    @if($auctioneer->city || $auctioneer->province)
                                        <div class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>{{ $auctioneer->city }}{{ $auctioneer->city && $auctioneer->province ? ', ' : '' }}{{ $auctioneer->province }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.auctioneers.show', $auctioneer) }}" class="btn btn-sm btn-primary flex-1">
                                            View Details
                                        </a>
                                        <a href="{{ route('auctioneer.show', $auctioneer) }}" target="_blank" class="btn btn-sm btn-outline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $auctioneers->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <p class="text-gray-600 dark:text-gray-400">No auctioneers found.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
