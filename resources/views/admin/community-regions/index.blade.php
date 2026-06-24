<x-app-layout>
    <x-slot name="title">Community Regions - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">&larr; Back to Dashboard</a>
        </div>

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold">Community Regions</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Regional community auctions where any user can list and bid.</p>
            </div>
            <a href="{{ route('admin.community-regions.create') }}" class="btn btn-primary">New Region</a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Metro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bidders</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Auctions</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pilot</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($regions as $region)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $region->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $region->slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $region->metro_area ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                                    @forelse($region->schedules as $sch)
                                        <div class="text-xs">
                                            <span class="font-medium">{{ $sch->name }}</span>
                                            <span class="text-gray-500 font-mono">&middot; {{ $days[$sch->goes_live_day] ?? '?' }} {{ substr($sch->goes_live_time, 0, 5) }}</span>
                                        </div>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">No schedules</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $region->bidder_count }}
                                    <span class="text-xs text-gray-500">/ {{ $region->bidder_threshold }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($region->auction_count > 0)
                                        <a href="{{ route('admin.auctions.index', ['community' => 1, 'region_id' => $region->id]) }}" class="text-primary-600 hover:text-primary-800">
                                            {{ $region->auction_count }} &rarr;
                                        </a>
                                    @else
                                        <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.community-regions.toggle-pilot', $region) }}">
                                        @csrf
                                        <button type="submit" class="inline-block px-2 py-0.5 text-xs {{ $region->pilot_mode ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }} rounded hover:opacity-80">
                                            {{ $region->pilot_mode ? 'Pilot ON' : 'Standard' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.community-regions.toggle-active', $region) }}">
                                        @csrf
                                        <button type="submit" class="inline-block px-2 py-0.5 text-xs {{ $region->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded hover:opacity-80">
                                            {{ $region->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.community-regions.edit', $region) }}" class="text-sm text-primary-600 hover:text-primary-800">Edit</a>
                                        <form method="POST" action="{{ route('admin.community-regions.destroy', $region) }}"
                                              onsubmit="return confirm('Delete this region?{{ $region->auction_count > 0 ? ' ' . $region->auction_count . ' past auction(s) will also be deleted.' : '' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    No community regions yet. Create one to enable community auctions in that area.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
