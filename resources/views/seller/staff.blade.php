<x-app-layout>
    <x-slot name="title">Staff Management</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Back to Dashboard -->
            <div class="mb-4">
                <a href="{{ route('seller.dashboard') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1 w-fit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Staff Management</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Invite and manage staff members for {{ $auctioneer->business_name }}</p>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 text-green-800 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-800 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Invite Link Display -->
            @if(session('invite_url'))
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4" x-data="{ copied: false }">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <span class="font-semibold text-blue-800 dark:text-blue-300">Invite Link Generated</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ session('invite_url') }}" class="flex-1 text-sm bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded-lg px-3 py-2 text-gray-700 dark:text-gray-300" id="invite-url-input">
                        <button @click="navigator.clipboard.writeText(document.getElementById('invite-url-input').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="btn btn-primary text-sm" :class="copied ? 'bg-green-600 hover:bg-green-600' : ''">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied!</span>
                        </button>
                    </div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Share this link with your staff member. It expires in 7 days.</p>
                </div>
            @endif

            <!-- Generate Invite -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Invite Staff Member</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Generate an invite link to send to your staff. They will create their own account using the link.</p>

                    <form action="{{ route('seller.staff.invite') }}" method="POST" class="flex flex-col sm:flex-row gap-4">
                        @csrf
                        <div class="flex-1">
                            <label for="staff_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Staff Role</label>
                            <select name="staff_role" id="staff_role" class="form-select w-full" required>
                                <option value="">Select a role...</option>
                                <option value="lot_manager">Lot Manager - Create & edit lots</option>
                                <option value="auction_manager">Auction Manager - Manage auctions & lots</option>
                                <option value="collections_manager">Collections Manager - Track payments & collections</option>
                            </select>
                            @error('staff_role')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:self-end">
                            <button type="submit" class="btn btn-primary w-full sm:w-auto">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Generate Invite Link
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Active Staff Members -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Staff Members
                        @if($staffMembers->count() > 0)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $staffMembers->count() }})</span>
                        @endif
                    </h2>

                    @if($staffMembers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Name</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Email</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Role</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Status</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Joined</th>
                                        <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($staffMembers as $member)
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $member->user->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->user->phone ?? '' }}</div>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">{{ $member->user->email }}</td>
                                            <td class="py-3 px-4">
                                                @if($member->staff_role === 'lot_manager')
                                                    <span class="badge badge-info">Lot Manager</span>
                                                @elseif($member->staff_role === 'auction_manager')
                                                    <span class="badge badge-primary">Auction Manager</span>
                                                @elseif($member->staff_role === 'collections_manager')
                                                    <span class="badge badge-warning">Collections Manager</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($member->is_active)
                                                    <span class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-sm text-red-600 dark:text-red-400">
                                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">{{ $member->created_at->format('d M Y') }}</td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <form action="{{ route('seller.staff.toggle', $member) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm {{ $member->is_active ? 'btn-outline text-amber-600 border-amber-300 hover:bg-amber-50 dark:border-amber-700 dark:hover:bg-amber-900/30' : 'btn-outline text-green-600 border-green-300 hover:bg-green-50 dark:border-green-700 dark:hover:bg-green-900/30' }}">
                                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('seller.staff.remove', $member) }}" method="POST"
                                                          onsubmit="return confirm('Remove {{ $member->user->name }} from staff? Their account will be converted to a regular bidder.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline text-red-600 border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/30">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">No staff members yet. Generate an invite link above to get started.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pending Invites -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Pending Invites
                        @if($pendingInvites->count() > 0)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $pendingInvites->count() }})</span>
                        @endif
                    </h2>

                    @if($pendingInvites->count() > 0)
                        <div class="space-y-4">
                            @foreach($pendingInvites as $invite)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            @if($invite->staff_role === 'lot_manager')
                                                <span class="badge badge-info">Lot Manager</span>
                                            @elseif($invite->staff_role === 'auction_manager')
                                                <span class="badge badge-primary">Auction Manager</span>
                                            @elseif($invite->staff_role === 'collections_manager')
                                                <span class="badge badge-warning">Collections Manager</span>
                                            @endif
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                Expires {{ $invite->expires_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                                            <code class="text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded truncate max-w-xs sm:max-w-md">{{ route('register.staff', $invite->token) }}</code>
                                            <button @click="navigator.clipboard.writeText('{{ route('register.staff', $invite->token) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="text-xs text-primary-600 hover:text-primary-700 font-medium shrink-0">
                                                <span x-show="!copied">Copy</span>
                                                <span x-show="copied" x-cloak>Copied!</span>
                                            </button>
                                        </div>
                                    </div>
                                    <form action="{{ route('seller.staff.invite.revoke', $invite) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline text-red-600 border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/30">
                                            Revoke
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-600 dark:text-gray-400 py-4">No pending invites.</p>
                    @endif
                </div>
            </div>

            <!-- Role Descriptions -->
            <div class="mt-8 card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Role Permissions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Lot Manager</h3>
                            <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                                <li>- Create & edit lots</li>
                                <li>- Upload lot images</li>
                                <li>- Delete draft lots</li>
                                <li>- View auctions</li>
                            </ul>
                        </div>
                        <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                            <h3 class="font-semibold text-primary-800 dark:text-primary-300 mb-2">Auction Manager</h3>
                            <ul class="text-sm text-primary-700 dark:text-primary-400 space-y-1">
                                <li>- All Lot Manager permissions</li>
                                <li>- Create & edit auctions</li>
                                <li>- End auctions</li>
                                <li>- View reports</li>
                            </ul>
                        </div>
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/30 rounded-lg border border-orange-300 dark:border-orange-700">
                            <h3 class="font-semibold text-orange-900 dark:text-orange-200 mb-2">Collections Manager</h3>
                            <ul class="text-sm text-orange-800 dark:text-orange-300 space-y-1">
                                <li>- View collections</li>
                                <li>- Confirm offline payments</li>
                                <li>- Send WhatsApp reminders</li>
                                <li>- Suspend/unsuspend bidders</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
