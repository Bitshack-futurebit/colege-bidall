<x-app-layout>
    <x-slot name="title">Push Notifications - Admin</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Push Notifications</h1>
            </div>

            <!-- Compose -->
            <div class="card mb-8" x-data="{
                audience: '{{ old('audience', 'all_users') }}',
                auctioneerId: '{{ old('auctioneer_id', '') }}'
            }">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Send Notification</h2>

                    <form method="POST" action="{{ route('admin.notifications.send') }}">
                        @csrf
                        <div class="space-y-4">
                            <!-- Audience -->
                            <div>
                                <label for="audience" class="label">Audience</label>
                                <select id="audience" name="audience" x-model="audience" class="input">
                                    <option value="all_users">All Users ({{ $audienceCounts['all_users'] }} subscribed)</option>
                                    <option value="all_bidders">All Bidders ({{ $audienceCounts['all_bidders'] }} subscribed)</option>
                                    <option value="all_auctioneers">All Auctioneers ({{ $audienceCounts['all_auctioneers'] }} subscribed)</option>
                                    <option value="all_admins">All Admins ({{ $audienceCounts['all_admins'] }} subscribed)</option>
                                    <option value="followers">Specific Auctioneer's Followers</option>
                                </select>
                                @error('audience') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Auctioneer picker (shown when audience=followers) -->
                            <div x-show="audience === 'followers'" x-cloak>
                                <label for="auctioneer_id" class="label">Select Auctioneer</label>
                                <select id="auctioneer_id" name="auctioneer_id" x-model="auctioneerId" class="input">
                                    <option value="">-- Select Auctioneer --</option>
                                    @foreach($auctioneers as $auctioneer)
                                        <option value="{{ $auctioneer->id }}">{{ $auctioneer->business_name }} ({{ $auctioneer->user->name }})</option>
                                    @endforeach
                                </select>
                                @error('auctioneer_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="title" class="label">Title</label>
                                <input type="text" id="title" name="title" value="{{ old('title') }}"
                                       class="input" maxlength="100" required placeholder="Notification title">
                                @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="body" class="label">Message</label>
                                <textarea id="body" name="body" rows="3"
                                          class="input" maxlength="1000" required placeholder="Notification message...">{{ old('body') }}</textarea>
                                @error('body') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="url" class="label">Link URL (optional)</label>
                                <input type="text" id="url" name="url" value="{{ old('url') }}"
                                       class="input" maxlength="255" placeholder="/auctions or https://bidall.co.za/page">
                                @error('url') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Send Push Notification</button>
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
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400">Sender</th>
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400">Audience</th>
                                        <th class="text-left py-3 px-2 text-gray-600 dark:text-gray-400">Title</th>
                                        <th class="text-right py-3 px-2 text-gray-600 dark:text-gray-400">Sent</th>
                                        <th class="text-right py-3 px-2 text-gray-600 dark:text-gray-400">Failed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notif)
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <td class="py-3 px-2 text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                                {{ $notif->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td class="py-3 px-2 text-gray-600 dark:text-gray-400">
                                                {{ ucfirst($notif->sender_type) }}
                                            </td>
                                            <td class="py-3 px-2 text-gray-600 dark:text-gray-400">
                                                @if($notif->audience === 'followers' && $notif->auctioneer)
                                                    {{ $notif->auctioneer->business_name }}'s Followers
                                                @else
                                                    {{ ucwords(str_replace('_', ' ', $notif->audience)) }}
                                                @endif
                                            </td>
                                            <td class="py-3 px-2 text-gray-900 dark:text-gray-100 font-medium">
                                                {{ $notif->title }}
                                            </td>
                                            <td class="py-3 px-2 text-right text-green-600">{{ $notif->sent_count }}</td>
                                            <td class="py-3 px-2 text-right text-red-600">{{ $notif->failed_count }}</td>
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
