<x-app-layout>
    <x-slot name="title">Promo Codes - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">&larr; Back to Dashboard</a>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Promo Codes</h1>
            <a href="{{ route('admin.promo-codes.create') }}" class="btn btn-primary">Create New Code</a>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Settings</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Usage</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($promoCodes as $code)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-primary-600 dark:text-primary-400">{{ $code->code }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $code->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($code->is_free_account)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Free Account</span>
                                    @elseif($code->custom_lot_fee !== null)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">R{{ $code->custom_lot_fee }}/lot</span>
                                    @elseif($code->custom_tier_basic !== null || $code->custom_tier_pro !== null || $code->custom_tier_premium !== null)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded">Custom Tiers</span>
                                    @else
                                        <span class="text-gray-400 text-xs">Standard</span>
                                    @endif
                                    @if($code->bonus_credits > 0)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded ml-1">+{{ formatCurrency($code->bonus_credits) }}</span>
                                    @endif
                                    @if($code->free_relist_reset)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded ml-1">{{ ucfirst($code->free_relist_reset) }} relist reset</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-semibold">{{ $code->times_used }}</span>
                                    @if($code->max_uses)
                                        / {{ $code->max_uses }}
                                    @else
                                        <span class="text-gray-400">/ unlimited</span>
                                    @endif
                                    @if($code->auctioneers_count > 0)
                                        <span class="text-xs text-gray-500">({{ $code->auctioneers_count }} active)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($code->expires_at)
                                        <span class="{{ $code->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : '' }}">
                                            {{ $code->expires_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($code->is_active && $code->isValid())
                                        <span class="inline-block px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">Active</span>
                                    @elseif(!$code->is_active)
                                        <span class="inline-block px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full">Inactive</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">Expired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm space-x-2">
                                    <a href="{{ route('admin.promo-codes.edit', $code) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">Edit</a>
                                    <form method="POST" action="{{ route('admin.promo-codes.toggle', $code) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="{{ $code->is_active ? 'text-red-600 hover:text-red-800 dark:text-red-400' : 'text-green-600 hover:text-green-800 dark:text-green-400' }}">
                                            {{ $code->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No promo codes yet. <a href="{{ route('admin.promo-codes.create') }}" class="text-primary-600 hover:underline">Create one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
