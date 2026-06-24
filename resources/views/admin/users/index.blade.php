<x-app-layout>
    <x-slot name="title">Manage Users</x-slot>

    <div class="py-12" x-data="{
        mailModal: false,
        mailUserName: '',
        mailUserEmail: '',
        openMail(id, name, email) {
            document.getElementById('quick-mail-user-id').value = id;
            this.mailUserName = name;
            this.mailUserEmail = email;
            this.mailModal = true;
        },
        detailModal: false,
        detailUser: null,
        detailLoading: false,
        detailData: null,
        async openDetail(userId, userName, paddleNumber, bidsCount, wonCount, totalSpend, lastBidAt) {
            this.detailUser = { id: userId, name: userName, paddle: paddleNumber, bids: bidsCount, won: wonCount, spend: totalSpend, lastBid: lastBidAt };
            this.detailData = null;
            this.detailLoading = true;
            this.detailModal = true;
            try {
                const res = await fetch('/admin/users/' + userId + '/quick-stats');
                if (res.ok) this.detailData = await res.json();
            } catch (e) {}
            this.detailLoading = false;
        }
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
                    <strong>Error:</strong>
                    <ul class="mt-1 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Back to Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>

            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Manage Users</h1>
            </div>

            <!-- Filter Bar -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" class="flex flex-col sm:flex-row gap-4">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by name or email..."
                               class="input flex-1">

                        <select name="role" class="input w-full sm:w-auto">
                            <option value="">All Roles</option>
                            <option value="bidder" {{ request('role') === 'bidder' ? 'selected' : '' }}>Bidders</option>
                            <option value="auctioneer" {{ request('role') === 'auctioneer' ? 'selected' : '' }}>Auctioneers</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
                        </select>

                        <select name="status" class="input w-full sm:w-auto">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'role', 'status']))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            @if($users->count() > 0)
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Paddle</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Joined</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($users as $user)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-4 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @if($user->paddle_number)
                                                <span class="font-mono">#{{ $user->paddle_number }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'auctioneer' ? 'primary' : 'info') }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                            @if($user->role === 'auctioneer' && $user->auctioneer)
                                                @if($user->auctioneer->is_activated)
                                                    <span class="badge badge-success text-xs ml-1">Activated</span>
                                                @else
                                                    <span class="badge badge-warning text-xs ml-1">Not Activated</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            @if($user->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Suspended</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                                            @php $idStatus = $user->idStatus(); @endphp
                                            @if($idStatus === 'verified')
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Verified
                                                </span>
                                            @elseif($idStatus === 'pending')
                                                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">Pending</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                            <div class="flex gap-1.5 justify-end">
                                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline">View</a>

                                                @if($user->role === 'bidder')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline"
                                                            @click="openDetail({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->paddle_number ? '#'.$user->paddle_number : '' }}', {{ $user->bids_count }}, {{ $user->won_lots_count }}, {{ $user->total_spend ?? 0 }}, '{{ $user->last_bid_at ? \Carbon\Carbon::parse($user->last_bid_at)->diffForHumans() : '' }}')">
                                                        Details
                                                    </button>
                                                @endif

                                                <button type="button"
                                                        class="btn btn-sm btn-outline text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                                        @click="openMail({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}')">
                                                    Mail
                                                </button>

                                                @if($user->is_active)
                                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                                onclick="return confirm('Are you sure you want to suspend this user?')">
                                                            Suspend
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20">
                                                            Activate
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($user->role !== 'admin' && $user->id !== auth()->id())
                                                    <form method="POST" action="{{ route('admin.users.delete', $user) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                                onclick="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.')">
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
                    {{ $users->links() }}
                </div>
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <p class="text-gray-600 dark:text-gray-400">No users found.</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Bidder Details Modal -->
        <div x-show="detailModal"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="detailModal = false">

            <div class="absolute inset-0 bg-black/50" @click="detailModal = false"></div>

            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="detailUser?.name"></h2>
                            <span class="text-sm text-gray-500 font-mono" x-show="detailUser?.paddle" x-text="detailUser?.paddle"></span>
                        </div>
                        <button type="button" @click="detailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Bids Placed</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="detailUser?.bids ?? 0"></div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Lots Won</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="detailUser?.won ?? 0"></div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Spend</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="'R' + Number(detailUser?.spend ?? 0).toLocaleString('en-ZA', {minimumFractionDigits: 2})"></div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Last Active</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="detailUser?.lastBid || 'Never'"></div>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div x-show="detailLoading" class="text-center py-6">
                        <svg class="animate-spin h-6 w-6 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 mt-2">Loading details...</p>
                    </div>

                    <!-- Extended Stats (from API) -->
                    <template x-if="detailData">
                        <div>
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Auctions Participated</div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="detailData.auctions_participated"></div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Following</div>
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="detailData.following_count"></div>
                                </div>
                            </div>

                            <!-- Won Lots -->
                            <template x-if="detailData.won_lots && detailData.won_lots.length > 0">
                                <div class="mb-4">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Won Lots</h3>
                                    <div class="space-y-2">
                                        <template x-for="lot in detailData.won_lots" :key="lot.id">
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div class="min-w-0 flex-1 mr-3">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="lot.title"></div>
                                                    <div class="text-xs text-gray-500" x-text="lot.auction + ' &middot; ' + lot.date"></div>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <div class="font-bold text-gray-900 dark:text-gray-100" x-text="lot.price"></div>
                                                    <span class="text-xs px-1.5 py-0.5 rounded-full"
                                                          :class="{
                                                              'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': lot.payment_status === 'paid',
                                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400': lot.payment_status === 'collection',
                                                              'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': lot.payment_status === 'offline',
                                                              'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': lot.payment_status === 'unpaid'
                                                          }"
                                                          x-text="lot.payment_label"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Recent Bids -->
                            <template x-if="detailData.recent_bids && detailData.recent_bids.length > 0">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Recent Bids</h3>
                                    <div class="space-y-2">
                                        <template x-for="bid in detailData.recent_bids" :key="bid.id">
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div class="min-w-0 flex-1 mr-3">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="bid.lot_title"></div>
                                                    <div class="text-xs text-gray-500" x-text="bid.auction + ' &middot; ' + bid.date"></div>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <div class="font-bold text-gray-900 dark:text-gray-100" x-text="bid.amount"></div>
                                                    <span x-show="bid.is_winning" class="text-xs text-green-600">Winning</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Footer -->
                    <div class="mt-6 flex gap-3">
                        <a :href="'/admin/users/' + detailUser?.id" class="btn btn-primary flex-1 text-center">Full Profile</a>
                        <button type="button" @click="detailModal = false" class="btn btn-outline flex-1">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Mail Modal -->
        <div x-show="mailModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="mailModal = false">

            <div class="absolute inset-0 bg-black/50" @click="mailModal = false"></div>

            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Send Email</h2>
                        <button type="button" @click="mailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        To: <span class="font-medium text-gray-900 dark:text-gray-100" x-text="mailUserName"></span>
                        &lt;<span x-text="mailUserEmail"></span>&gt;
                    </p>

                    <form method="POST" action="{{ route('admin.broadcast.send') }}">
                        @csrf
                        <input type="hidden" name="user_id" id="quick-mail-user-id" value="">

                        <div class="mb-4">
                            <label class="label">Subject</label>
                            <input type="text" name="subject" required class="input" placeholder="Email subject...">
                        </div>

                        <div class="mb-6">
                            <label class="label">Message</label>
                            <textarea name="message" rows="6" required class="input" placeholder="Write your message..."></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="mailModal = false" class="btn btn-outline flex-1">Cancel</button>
                            <button type="submit" class="btn btn-primary flex-1">Send Email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
