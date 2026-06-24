<x-app-layout>
    <x-slot name="title">Activity Logs</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Activity Logs</h1>

            <!-- Filter Bar -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex gap-4">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search activities..."
                               class="input flex-1">

                        <select name="user_id" class="input">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>

                        <select name="period" class="input">
                            <option value="">All Time</option>
                            <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'user_id', 'period']))
                            <a href="{{ route('admin.activity') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Activity Timeline -->
            @if($activities->count() > 0)
                <div class="card">
                    <div class="p-6">
                        <div class="space-y-6">
                            @foreach($activities as $activity)
                                <div class="flex items-start gap-4">
                                    <!-- Icon -->
                                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if(str_contains($activity->description, 'created'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            @elseif(str_contains($activity->description, 'updated') || str_contains($activity->description, 'edited'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            @elseif(str_contains($activity->description, 'deleted'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            @elseif(str_contains($activity->description, 'bid') || str_contains($activity->description, 'placed'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            @elseif(str_contains($activity->description, 'payment') || str_contains($activity->description, 'paid'))
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            @endif
                                        </svg>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-1">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $activity->user->name }}
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $activity->description }}
                                                </p>
                                            </div>
                                            <span class="text-xs text-gray-500 whitespace-nowrap ml-4">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        @if($activity->metadata)
                                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                @foreach($activity->metadata as $key => $value)
                                                    <span class="inline-block bg-gray-100 dark:bg-gray-800 rounded px-2 py-1 mr-2">
                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if(!$loop->last)
                                    <div class="border-b border-gray-200 dark:border-gray-700"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $activities->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">No activity found.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
