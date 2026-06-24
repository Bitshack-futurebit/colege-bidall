<x-app-layout>
    <x-slot name="title">Email Broadcast</x-slot>

    @php
        if (!isset($stats)) {
            $stats = [
                'total_users'           => \App\Models\User::where('is_active', true)->count(),
                'bidders'               => \App\Models\User::where('role', 'bidder')->where('is_active', true)->count(),
                'auctioneers'           => \App\Models\User::where('role', 'auctioneer')->where('is_active', true)->count(),
                'active_bidders'        => \App\Models\User::where('role', 'bidder')->where('is_active', true)->whereHas('bids')->count(),
                'activated_auctioneers' => \App\Models\User::where('role', 'auctioneer')->where('is_active', true)
                    ->whereHas('auctioneer', fn ($q) => $q->where('is_activated', true))->count(),
            ];
        }
        $previousBroadcasts = $previousBroadcasts ?? collect([]);
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Email Broadcast</h1>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm font-medium">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
                    <strong>Please fix the following:</strong>
                    <ul class="mt-1 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="p-6">
                    <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Warning:</strong> This will send an email to all selected users. Please review carefully before sending.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.broadcast.send') }}" x-data="{
                        allUsers: false,
                        bidders: false,
                        auctioneers: false,
                        activeBidders: false,
                        activatedAuctioneers: false,
                        auctioneerFollowers: false,
                        communityMembers: false,
                        geoRegion: false,
                        adminCopy: false,
                        toggleAll() {
                            if (this.allUsers) {
                                this.bidders = false;
                                this.auctioneers = false;
                                this.activeBidders = false;
                                this.activatedAuctioneers = false;
                                this.auctioneerFollowers = false;
                                this.communityMembers = false;
                                this.geoRegion = false;
                            }
                        },
                        toggleGroup() {
                            if (this.bidders || this.auctioneers || this.activeBidders || this.activatedAuctioneers || this.auctioneerFollowers || this.communityMembers || this.geoRegion) {
                                this.allUsers = false;
                            }
                        },
                        hasRecipients() {
                            return this.allUsers || this.bidders || this.auctioneers || this.activeBidders || this.activatedAuctioneers || this.auctioneerFollowers || this.communityMembers || this.geoRegion;
                        }
                    }">
                        @csrf

                        <!-- Recipients -->
                        <div class="mb-6">
                            <label class="label">Send To *</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="recipients[]" value="all_users" x-model="allUsers" @change="toggleAll()">
                                    <span class="text-gray-700 dark:text-gray-300">All Users ({{ $stats['total_users'] }} users)</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="recipients[]" value="bidders" x-model="bidders" @change="toggleGroup()">
                                    <span class="text-gray-700 dark:text-gray-300">All Bidders ({{ $stats['bidders'] }} users)</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="recipients[]" value="auctioneers" x-model="auctioneers" @change="toggleGroup()">
                                    <span class="text-gray-700 dark:text-gray-300">All Auctioneers ({{ $stats['auctioneers'] }} users)</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="recipients[]" value="active_bidders" x-model="activeBidders" @change="toggleGroup()">
                                    <span class="text-gray-700 dark:text-gray-300">Active Bidders ({{ $stats['active_bidders'] }} users)</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="recipients[]" value="activated_auctioneers" x-model="activatedAuctioneers" @change="toggleGroup()">
                                    <span class="text-gray-700 dark:text-gray-300">Activated Auctioneers ({{ $stats['activated_auctioneers'] }} users)</span>
                                </label>

                                {{-- Auctioneer followers --}}
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-1 space-y-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="recipients[]" value="auctioneer_followers" x-model="auctioneerFollowers" @change="toggleGroup()">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">Auctioneer Followers</span>
                                    </label>
                                    <div x-show="auctioneerFollowers" x-cloak class="ml-7">
                                        <label for="auctioneer_id" class="label text-xs mb-1">Select Auctioneer</label>
                                        <select name="auctioneer_id" id="auctioneer_id"
                                                class="input"
                                                :required="auctioneerFollowers">
                                            <option value="">— choose an auctioneer —</option>
                                            @foreach($auctioneers as $a)
                                                <option value="{{ $a->id }}" {{ old('auctioneer_id') == $a->id ? 'selected' : '' }}>
                                                    {{ $a->business_name }} ({{ $a->followers_count }} {{ Str::plural('follower', $a->followers_count) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('auctioneer_id')<p class="error-message mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                {{-- Community members --}}
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="recipients[]" value="community_members" x-model="communityMembers" @change="toggleGroup()">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">Community Members</span>
                                    </label>
                                    <div x-show="communityMembers" x-cloak class="ml-7">
                                        <label for="community_id" class="label text-xs mb-1">Select Community</label>
                                        <select name="community_id" id="community_id"
                                                class="input"
                                                :required="communityMembers">
                                            <option value="">— choose a community —</option>
                                            @foreach($communities as $c)
                                                <option value="{{ $c->id }}" {{ old('community_id') == $c->id ? 'selected' : '' }}>
                                                    {{ $c->name }} ({{ $c->users_count }} {{ Str::plural('member', $c->users_count) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('community_id')<p class="error-message mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                {{-- Geographic region --}}
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="recipients[]" value="geographic_region" x-model="geoRegion" @change="toggleGroup()">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">Geographic Region</span>
                                    </label>
                                    <div x-show="geoRegion" x-cloak class="ml-7 space-y-3">
                                        <div>
                                            <label for="geo_province" class="label text-xs mb-1">Province <span class="text-red-500">*</span></label>
                                            <select name="geo_province" id="geo_province"
                                                    class="input @error('geo_province') input-error @enderror"
                                                    :required="geoRegion">
                                                <option value="">— select province —</option>
                                                @foreach(['Eastern Cape','Free State','Gauteng','KwaZulu-Natal','Limpopo','Mpumalanga','Northern Cape','North West','Western Cape'] as $p)
                                                    <option value="{{ $p }}" {{ old('geo_province') === $p ? 'selected' : '' }}>{{ $p }}</option>
                                                @endforeach
                                            </select>
                                            @error('geo_province')<p class="error-message mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="geo_city" class="label text-xs mb-1">City <span class="text-gray-400 font-normal">(optional — leave blank for whole province)</span></label>
                                            <input type="text" name="geo_city" id="geo_city"
                                                   value="{{ old('geo_city') }}"
                                                   placeholder="e.g. Johannesburg"
                                                   class="input @error('geo_city') input-error @enderror">
                                            @error('geo_city')<p class="error-message mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <p class="text-xs text-gray-400">Only reaches users who have filled in their province on their profile.</p>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="admin_copy" value="1" x-model="adminCopy">
                                        <span class="text-gray-700 dark:text-gray-300">Send copy to {{ auth()->user()->email }}</span>
                                    </label>
                                </div>
                            </div>
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1" x-show="!hasRecipients()" x-cloak>Please select at least one recipient group.</p>
                            @error('recipients')<p class="error-message">{{ $message }}</p>@enderror
                        </div>

                        <!-- Subject -->
                        <div class="mb-6">
                            <label for="subject" class="label">Subject *</label>
                            <input id="subject"
                                   type="text"
                                   name="subject"
                                   value="{{ old('subject') }}"
                                   required
                                   placeholder="e.g., Exciting New Features Coming Soon!"
                                   class="input @error('subject') input-error @enderror">
                            @error('subject')<p class="error-message">{{ $message }}</p>@enderror
                        </div>

                        <!-- Message -->
                        <div class="mb-6">
                            <label for="message" class="label">Message *</label>
                            <textarea id="message"
                                      name="message"
                                      rows="12"
                                      required
                                      placeholder="Write your message here..."
                                      class="input @error('message') input-error @enderror">{{ old('message') }}</textarea>
                            @error('message')<p class="error-message">{{ $message }}</p>@enderror
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                You can use these variables: {name}, {email}, {platform_name}
                            </p>
                        </div>

                        <!-- Preview -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Email Preview</h3>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-white dark:bg-gray-800">
                                <div class="mb-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">From:</div>
                                    <div class="font-semibold">{{ config('app.name') }} &lt;{{ config('mail.from.address') }}&gt;</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Subject:</div>
                                    <div class="font-semibold" id="preview-subject">Your subject will appear here</div>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Message:</div>
                                    <div class="prose dark:prose-invert" id="preview-message">Your message will appear here</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4" x-data="{ sending: false }">
                            <button type="button" onclick="history.back()" class="btn btn-outline flex-1" :disabled="sending">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary flex-1"
                                    @click="if(!hasRecipients()) { $event.preventDefault(); return; } if(!confirm('Are you sure you want to send this email to all selected recipients?')) { $event.preventDefault(); return; } $nextTick(() => { sending = true })"
                                    :disabled="sending || !hasRecipients()">
                                <span x-show="!sending" class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Broadcast
                                </span>
                                <span x-show="sending" class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Sending...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Previous Broadcasts -->
            @if($previousBroadcasts->count() > 0)
                <div class="card mt-8">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Previous Broadcasts</h2>
                        <div class="space-y-4">
                            @foreach($previousBroadcasts as $broadcast)
                                <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $broadcast->subject }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ $broadcast->message }}</p>
                                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                            <span>Sent to {{ $broadcast->recipients_count }} users</span>
                                            <span>•</span>
                                            <span>{{ $broadcast->created_at->diffForHumans() }}</span>
                                            <span>•</span>
                                            <span>By {{ $broadcast->sender->name }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    // Live preview
    document.getElementById('subject').addEventListener('input', function(e) {
        document.getElementById('preview-subject').textContent = e.target.value || 'Your subject will appear here';
    });

    document.getElementById('message').addEventListener('input', function(e) {
        const message = e.target.value || 'Your message will appear here';
        document.getElementById('preview-message').textContent = message;
    });
    </script>
    @endpush
</x-app-layout>
