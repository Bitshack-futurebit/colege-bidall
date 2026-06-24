<x-app-layout>
    <x-slot name="title">Manage Auctions</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Manage Auctions</h1>

            <!-- Filter Bar -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex flex-wrap gap-3">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search auctions..."
                               class="input flex-1 min-w-[200px]">

                        <select name="status" class="input">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="live" {{ request('status') === 'live' ? 'selected' : '' }}>Live</option>
                            <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Ended</option>
                        </select>

                        <select name="type" class="input">
                            <option value="">All Types</option>
                            <option value="english" {{ request('type') === 'english' ? 'selected' : '' }}>English</option>
                            <option value="dutch" {{ request('type') === 'dutch' ? 'selected' : '' }}>Dutch</option>
                            <option value="sealed" {{ request('type') === 'sealed' ? 'selected' : '' }}>Sealed</option>
                            <option value="live" {{ request('type') === 'live' ? 'selected' : '' }}>Live</option>
                        </select>

                        <select name="community" class="input">
                            <option value="">All Auctions</option>
                            <option value="1" {{ request('community') === '1' ? 'selected' : '' }}>Community Only</option>
                            <option value="0" {{ request('community') === '0' ? 'selected' : '' }}>Auctioneer Only</option>
                        </select>

                        @if($regions->count() > 0)
                            <select name="region_id" class="input">
                                <option value="">All Regions</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ (string) request('region_id') === (string) $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'status', 'type', 'community', 'region_id']))
                            <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Auctions Table -->
            @if($auctions->count() > 0)
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Auction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Owner / Region</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lots</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Registrations</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Start Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($auctions as $auction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $auction->title }}
                                                </div>
                                                @if($auction->description)
                                                    <div class="text-sm text-gray-500 line-clamp-1">
                                                        {{ $auction->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($auction->is_community)
                                                <div class="text-sm">
                                                    <div class="text-gray-900 dark:text-gray-100 font-medium">Community</div>
                                                    <div class="text-gray-500">{{ $auction->communityRegion?->name ?? '—' }}</div>
                                                </div>
                                            @else
                                                <div class="text-sm">
                                                    <div class="text-gray-900 dark:text-gray-100">
                                                        {{ $auction->auctioneer?->business_name ?? '—' }}
                                                    </div>
                                                    <div class="text-gray-500">
                                                        {{ $auction->auctioneer?->user?->name ?? '—' }}
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex gap-1 flex-wrap">
                                                <span class="badge badge-{{ $auction->status === 'live' ? 'danger' : ($auction->status === 'upcoming' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($auction->status) }}
                                                </span>
                                                @if($auction->auction_type === 'dutch')
                                                    <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Dutch</span>
                                                @elseif($auction->auction_type === 'sealed')
                                                    <span class="badge bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Sealed</span>
                                                @elseif($auction->auction_type === 'live')
                                                    <span class="badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Live</span>
                                                @endif
                                                @if($auction->is_community)
                                                    <span class="badge bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">Community</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div>{{ $auction->lots->count() }} total</div>
                                            @if($auction->status !== 'draft')
                                                <div class="text-gray-500">
                                                    {{ $auction->lots->where('status', 'sold')->count() }} sold
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $auction->registrations->count() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $auction->start_time->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <div class="flex gap-2 justify-end">
                                                <a href="{{ route('auctions.show', $auction) }}" target="_blank" class="btn btn-sm btn-outline">
                                                    View
                                                </a>
                                                @if($auction->status === 'live')
                                                    <a href="{{ route('admin.auctions.live-report', $auction) }}" class="btn btn-sm btn-outline text-green-600 hover:bg-green-50">
                                                        Live Report
                                                    </a>
                                                @endif
                                                @if(in_array($auction->status, ['live', 'upcoming']))
                                                    <form method="POST" action="{{ route('admin.auctions.facebook', $auction) }}" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline text-blue-600 hover:bg-blue-50"
                                                                onclick="return confirm('Post this auction to Facebook?')">
                                                            Post to FB
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($auction->status === 'draft' || $auction->status === 'upcoming')
                                                    <form method="POST" action="{{ route('admin.auctions.delete', $auction) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline text-red-600 hover:bg-red-50"
                                                                onclick="return confirm('Are you sure? This will delete the auction and all its lots.')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $auctions->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <p class="text-gray-600 dark:text-gray-400">No events found.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
