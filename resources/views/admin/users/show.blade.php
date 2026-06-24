<x-app-layout>
    <x-slot name="title">User Details - {{ $user->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-6">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Users
            </a>

            <!-- Header with Actions -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                </div>
                <div class="flex gap-2">
                    @if($user->is_active)
                        <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-outline text-red-600 hover:bg-red-50"
                                    onclick="return confirm('Are you sure you want to suspend this user?')">
                                Suspend User
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Activate User
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Status & Role -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Status</div>
                        <div class="mt-2">
                            @if($user->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Suspended</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Role</div>
                        <div class="mt-2">
                            <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'auctioneer' ? 'primary' : 'info') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Paddle #</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100 font-mono">
                            @if($user->paddle_number)
                                #{{ $user->paddle_number }}
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Member Since</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $user->created_at->format('M d, Y') }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Last Updated</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $user->updated_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: User Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Contact Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Email</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->email }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Phone</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->phone ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">WhatsApp</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->whatsapp ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Location</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        @if($user->city || $user->province)
                                            {{ $user->city }}{{ $user->city && $user->province ? ', ' : '' }}{{ $user->province }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                @if($user->address)
                                    <div class="md:col-span-2">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Address</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->address }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Identity Verification -->
                    <div class="card">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Identity Verification</h2>
                                @php $idStatus = $user->idStatus(); @endphp
                                @if($idStatus === 'verified')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/40 px-2.5 py-1 rounded-full">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Verified {{ $user->id_verified_at->format('d M Y') }}
                                    </span>
                                @elseif($idStatus === 'pending')
                                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2.5 py-1 rounded-full">Pending Review</span>
                                @else
                                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full">No Document</span>
                                @endif
                            </div>

                            @if($idStatus === 'none')
                                <p class="text-sm text-gray-500 dark:text-gray-400">This user has not uploaded an ID document yet.</p>
                            @else
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.users.id-document', $user) }}" target="_blank"
                                       class="inline-flex items-center gap-2 btn btn-outline btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Document
                                    </a>

                                    @if($idStatus === 'pending')
                                        <form method="POST" action="{{ route('admin.users.verify-id', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-green-600 hover:bg-green-700 text-white">
                                                Mark as Verified
                                            </button>
                                        </form>
                                    @elseif($idStatus === 'verified')
                                        <form method="POST" action="{{ route('admin.users.unverify-id', $user) }}" class="inline"
                                              onsubmit="return confirm('Remove verification? The user will need to re-submit.')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm text-red-600 hover:bg-red-50">
                                                Remove Verification
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Profile Score</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->profileScore() }}/100</div>
                                    </div>
                                    @if($idStatus === 'verified')
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Verified by</div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</div>
                                    </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($user->role === 'auctioneer' && $user->auctioneer)
                        <!-- Auctioneer Details -->
                        <div class="card">
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Auctioneer Information</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Business Name</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->auctioneer->business_name }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Activation Status</div>
                                        <div>
                                            @if($user->auctioneer->is_activated)
                                                <span class="badge badge-success">Activated</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Credit Balance</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency($user->auctioneer->credit_balance) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Payout Balance</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency($user->auctioneer->payout_balance) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Auctions</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->auctioneer->auctions->count() }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Followers</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->auctioneer->followers->count() }}</div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('admin.auctioneers.show', $user->auctioneer) }}" class="btn btn-primary">
                                        View Full Auctioneer Profile →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Auctions -->
                        @if($user->auctioneer->auctions->count() > 0)
                            <div class="card">
                                <div class="p-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Recent Auctions</h2>
                                    <div class="space-y-3">
                                        @foreach($user->auctioneer->auctions->take(5) as $auction)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $auction->title }}</div>
                                                    <div class="text-sm text-gray-500">{{ $auction->lots->count() }} lots • {{ $auction->start_time->format('M d, Y') }}</div>
                                                </div>
                                                <span class="badge badge-{{ $auction->status === 'live' ? 'danger' : ($auction->status === 'upcoming' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($auction->status) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($user->role === 'bidder')
                        <!-- Bidder Activity -->
                        <div class="card">
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Bidding Activity</h2>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Bids</div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->bids->count() }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Lots Won</div>
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $user->wonLots->count() }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Spend</div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency($bidderStats['total_spend'] ?? 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Auctions Participated</div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $bidderStats['auctions_participated'] ?? 0 }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Following</div>
                                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $bidderStats['following_count'] ?? 0 }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Watchlist Items</div>
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $user->watchlist()->count() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Won Lots -->
                        @if($user->wonLots->count() > 0)
                            <div class="card">
                                <div class="p-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Won Lots</h2>
                                    <div class="space-y-3">
                                        @foreach($user->wonLots->sortByDesc('updated_at') as $lot)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $lot->title }}</div>
                                                    <div class="text-sm text-gray-500">{{ $lot->auction->title ?? 'N/A' }} &middot; {{ $lot->updated_at->format('M d, Y') }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->current_bid) }}</div>
                                                    @if($lot->payment_status === 'paid_platform')
                                                        <span class="badge badge-success text-xs">Paid</span>
                                                    @elseif($lot->payment_status === 'awaiting_collection')
                                                        <span class="badge badge-warning text-xs">Collection</span>
                                                    @elseif($lot->payment_status === 'paid_offline')
                                                        <span class="badge badge-secondary text-xs">Offline</span>
                                                    @else
                                                        <span class="badge badge-danger text-xs">Unpaid</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Recent Bids -->
                        @if($user->bids->count() > 0)
                            <div class="card">
                                <div class="p-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Recent Bids</h2>
                                    <div class="space-y-3">
                                        @foreach($user->bids->sortByDesc('created_at')->take(5) as $bid)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $bid->lot->title }}</div>
                                                    <div class="text-sm text-gray-500">{{ $bid->lot->auction->title }} &middot; {{ $bid->placed_at->format('M d, Y H:i') }}</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency($bid->amount) }}</div>
                                                    @if($bid->lot->winning_bidder_id === $user->id)
                                                        <span class="text-xs text-green-600">Winning</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Right Column: Stats & Actions -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h2>
                            <div class="space-y-2">
                                @if($user->role === 'auctioneer' && $user->auctioneer)
                                    <a href="{{ route('admin.auctioneers.show', $user->auctioneer) }}" class="btn btn-outline w-full">
                                        View Auctioneer Profile
                                    </a>
                                @endif
                                @if($user->is_active)
                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline text-red-600 hover:bg-red-50 w-full"
                                                onclick="return confirm('Are you sure you want to suspend this user?')">
                                            Suspend User
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary w-full">
                                            Activate User
                                        </button>
                                    </form>
                                @endif

                                @if($user->role !== 'admin' && $user->id !== auth()->id())
                                    <hr class="my-2 border-gray-200 dark:border-gray-700">
                                    <form method="POST" action="{{ route('admin.users.delete', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 w-full"
                                                onclick="return confirm('Permanently delete {{ addslashes($user->name) }}? This action cannot be undone.')">
                                            Delete User
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Transactions -->
                    @if($user->transactions->count() > 0)
                        <div class="card">
                            <div class="p-6">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Recent Transactions</h2>
                                <div class="space-y-3">
                                    @foreach($user->transactions->sortByDesc('created_at')->take(5) as $transaction)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <div class="flex justify-between items-start mb-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                                </div>
                                                <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }} text-xs">
                                                    {{ ucfirst($transaction->status) }}
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ formatCurrency($transaction->amount) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $transaction->created_at->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Account Details -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Account Details</h2>
                            <div class="space-y-3 text-sm">
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">User ID</div>
                                    <div class="font-mono text-gray-900 dark:text-gray-100">{{ $user->id }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Created</div>
                                    <div class="text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Last Updated</div>
                                    <div class="text-gray-900 dark:text-gray-100">{{ $user->updated_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-600 dark:text-gray-400">Email Notifications</div>
                                    <div class="text-gray-900 dark:text-gray-100">
                                        {{ $user->email_notifications ? 'Enabled' : 'Disabled' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
