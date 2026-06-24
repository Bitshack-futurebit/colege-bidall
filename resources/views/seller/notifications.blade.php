<x-app-layout>
    <x-slot name="title">Notifications</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Notifications</h1>
            </div>

            <!-- Compose -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Send to Followers</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $followerCount }} follower{{ $followerCount !== 1 ? 's' : '' }} will receive this notification
                    </p>

                    <form method="POST" action="{{ route('seller.notifications.send') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="title" class="label">Title</label>
                                <input type="text" id="title" name="title" value="{{ old('title') }}"
                                       class="input" maxlength="100" required placeholder="e.g. New Auction Starting Soon!">
                                @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="body" class="label">Message</label>
                                <textarea id="body" name="body" rows="3"
                                          class="input" maxlength="1000" required placeholder="Tell your followers what's happening...">{{ old('body') }}</textarea>
                                @error('body') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="url" class="label">Link URL (optional)</label>
                                <input type="text" id="url" name="url" value="{{ old('url') }}"
                                       class="input" maxlength="255" placeholder="/auctions/my-auction">
                                @error('url') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary"
                                    {{ $followerCount === 0 ? 'disabled' : '' }}>
                                Send to {{ $followerCount }} Follower{{ $followerCount !== 1 ? 's' : '' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Notification History</h2>

                    @if($notifications->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400">Date</th>
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400">Title</th>
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400 hidden sm:table-cell">Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notif)
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <td class="py-3 px-2 text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                                {{ $notif->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td class="py-3 px-2 text-gray-900 dark:text-gray-100 font-medium">
                                                {{ $notif->title }}
                                            </td>
                                            <td class="py-3 px-2 text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                                                {{ Str::limit($notif->body, 60) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $notifications->links() }}</div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-8">No notifications sent yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
