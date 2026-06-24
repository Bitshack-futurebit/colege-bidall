<x-app-layout>
    <x-slot name="title">My Followers</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">My Followers</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    {{ $followers->total() }} {{ Str::plural('follower', $followers->total()) }}
                </p>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="label">Search</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search by name or email..."
                                   class="input">
                        </div>

                        <!-- Location Filter -->
                        <div>
                            <label for="location" class="label">Location</label>
                            <input type="text"
                                   id="location"
                                   name="location"
                                   value="{{ request('location') }}"
                                   placeholder="City or province..."
                                   class="input">
                        </div>

                        <!-- Sort -->
                        <div>
                            <label for="sort" class="label">Sort By</label>
                            <select id="sort" name="sort" class="input">
                                <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="md:col-span-4 flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            @if(request()->hasAny(['search', 'location', 'sort']))
                                <a href="{{ route('seller.followers') }}" class="btn btn-outline">
                                    Clear Filters
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @if($followers->count() > 0)
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Follower</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Following Since</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contact</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($followers as $follower)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                                    <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                                        {{ substr($follower->user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $follower->user->name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $follower->user->email }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if($follower->user->city || $follower->user->province)
                                                {{ $follower->user->city }}{{ $follower->user->city && $follower->user->province ? ', ' : '' }}{{ $follower->user->province }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $follower->created_at->format('M d, Y') }}
                                            <div class="text-xs text-gray-400">
                                                {{ $follower->created_at->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-2">
                                                @if($follower->user->email)
                                                    <a href="mailto:{{ $follower->user->email }}"
                                                       class="btn btn-outline btn-sm flex items-center gap-1"
                                                       title="Email {{ $follower->user->name }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        Email
                                                    </a>
                                                @endif
                                                @if($follower->user->phone)
                                                    <a href="tel:{{ $follower->user->phone }}"
                                                       class="btn btn-outline btn-sm flex items-center gap-1"
                                                       title="Call {{ $follower->user->phone }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                        </svg>
                                                        Call
                                                    </a>
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
                    {{ $followers->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No followers yet</h3>
                        <p class="text-gray-600 dark:text-gray-400">Share your profile to attract followers!</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
