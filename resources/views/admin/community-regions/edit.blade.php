<x-app-layout>
    <x-slot name="title">Edit {{ $region->name }}</x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.community-regions.index') }}" class="btn btn-outline">&larr; Back</a>
        </div>

        <h1 class="text-2xl font-bold mb-6">Edit: {{ $region->name }}</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.community-regions.update', $region) }}" class="card p-6">
            @method('PUT')
            @include('admin.community-regions._form', ['region' => $region])

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn btn-primary">Save Region</button>
                <a href="{{ route('admin.community-regions.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>

        {{-- ============= PUBLIC PROFILE ============= --}}
        <form method="POST" action="{{ route('admin.community-regions.update-profile', $region) }}"
              enctype="multipart/form-data" class="card p-6 mt-8">
            @csrf
            <h2 class="text-lg font-bold mb-1">Community Public Profile</h2>
            <p class="text-sm text-gray-500 mb-4">
                What visitors see at <a href="{{ route('auctioneer.show', $auctioneer) }}" target="_blank" class="text-primary-600 hover:underline">{{ url('/auctioneer/' . $auctioneer->slug) }}</a> and on the Communities map pin.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Logo --}}
                <div>
                    <label class="label">Logo</label>
                    @if($auctioneer->logo)
                        <div class="flex items-start gap-3 mb-2">
                            <img src="{{ Storage::url($auctioneer->logo) }}" alt="" class="w-16 h-16 rounded object-cover ring-1 ring-gray-200">
                            <label class="inline-flex items-center gap-2 text-xs text-red-600 mt-1">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded">
                                Remove current logo
                            </label>
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="input">
                    <p class="text-xs text-gray-500 mt-1">Square works best. Max 8MB.</p>
                </div>

                {{-- Banner --}}
                <div>
                    <label class="label">Banner image</label>
                    @if($auctioneer->banner_image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($auctioneer->banner_image) }}" alt="" class="w-full h-20 rounded object-cover ring-1 ring-gray-200">
                            <label class="inline-flex items-center gap-2 text-xs text-red-600 mt-1">
                                <input type="checkbox" name="remove_banner" value="1" class="rounded">
                                Remove current banner
                            </label>
                        </div>
                    @endif
                    <input type="file" name="banner_image" accept="image/*" class="input">
                    <p class="text-xs text-gray-500 mt-1">Wide aspect ratio (e.g. 1200×300). Max 8MB.</p>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="label">Public description</label>
                    <textarea name="description" rows="3" maxlength="5000" class="input"
                              placeholder="What this community is about. Shown on the public profile.">{{ old('description', $auctioneer->description) }}</textarea>
                </div>

                {{-- WhatsApp number --}}
                <div>
                    <label class="label">WhatsApp number (1-on-1)</label>
                    <input type="tel" name="whatsapp_number" maxlength="32" class="input"
                           value="{{ old('whatsapp_number', $auctioneer->whatsapp_number === '0000000000' ? '' : $auctioneer->whatsapp_number) }}"
                           placeholder="+27 82 123 4567">
                    <p class="text-xs text-gray-500 mt-1">For direct messages from interested members.</p>
                </div>

                {{-- WhatsApp group link --}}
                <div>
                    <label class="label">WhatsApp group invite link</label>
                    <input type="url" name="whatsapp_group_link" maxlength="500" class="input"
                           value="{{ old('whatsapp_group_link', $auctioneer->whatsapp_group_link) }}"
                           placeholder="https://chat.whatsapp.com/...">
                    <p class="text-xs text-gray-500 mt-1">The community chat group bidders/sellers can join.</p>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn btn-primary">Save profile</button>
                <a href="{{ route('auctioneer.show', $auctioneer) }}" target="_blank" class="btn btn-outline">View public profile</a>
            </div>
        </form>

        {{-- ============= SCHEDULES ============= --}}
        <div class="card p-6 mt-8">
            <h2 class="text-lg font-bold mb-1">Auction Schedules</h2>
            <p class="text-sm text-gray-500 mb-4">Each schedule produces one recurring weekly auction under this region (e.g. "Used Cars" on Friday + "General" on Sunday).</p>

            @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp

            @if($region->schedules->isNotEmpty())
                <div class="space-y-3 mb-6">
                    @foreach($region->schedules as $schedule)
                        <div x-data="{ editing: false }" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div x-show="!editing">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold">{{ $schedule->name }}</span>
                                    <span class="px-2 py-0.5 text-xs {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }} rounded">
                                        {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 mb-3">
                                    {{ $schedule->cadenceLabel() }}
                                    &middot; Next: {{ $schedule->nextGoesLiveAt()->format('D d M Y H:i') }}
                                </div>
                                <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('admin.community-schedules.create-next', [$region, $schedule]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary text-xs">Create next now</button>
                                    </form>
                                    <button type="button" @click="editing = true" class="btn btn-outline text-xs">Edit</button>
                                    <form method="POST" action="{{ route('admin.community-schedules.toggle-active', [$region, $schedule]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline text-xs">
                                            {{ $schedule->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    @php $pastCount = $schedule->auctions()->whereIn('status', ['ended', 'draft'])->count(); @endphp
                                    <form method="POST" action="{{ route('admin.community-schedules.destroy', [$region, $schedule]) }}"
                                          onsubmit="return confirm('Delete this schedule?{{ $pastCount > 0 ? ' ' . $pastCount . ' past auction(s) will also be deleted.' : '' }}')"
                                          class="ml-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.community-schedules.update', [$region, $schedule]) }}"
                                  x-data="{ frequency: '{{ $schedule->frequency ?? 'weekly' }}' }"
                                  class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                @csrf
                                @method('PUT')
                                <div class="md:col-span-2">
                                    <label class="label text-xs">Name</label>
                                    <input type="text" name="name" value="{{ $schedule->name }}" required maxlength="120" class="input">
                                </div>
                                <div>
                                    <label class="label text-xs">Frequency</label>
                                    <select name="frequency" x-model="frequency" class="input">
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                <div x-show="frequency === 'monthly'" x-cloak>
                                    <label class="label text-xs">Week of month</label>
                                    <select name="monthly_week" class="input">
                                        @foreach([1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Last'] as $w => $label)
                                            <option value="{{ $w }}" {{ ($schedule->monthly_week ?? 1) == $w ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label text-xs">Day</label>
                                    <select name="goes_live_day" class="input">
                                        @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $dayName)
                                            <option value="{{ $i }}" {{ $schedule->goes_live_day == $i ? 'selected' : '' }}>{{ $dayName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label text-xs">Time</label>
                                    <input type="time" name="goes_live_time" value="{{ substr($schedule->goes_live_time, 0, 5) }}" required class="input">
                                </div>
                                <div class="md:col-span-4 flex items-center gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" {{ $schedule->is_active ? 'checked' : '' }} class="rounded">
                                        Active
                                    </label>
                                    <button type="submit" class="btn btn-primary text-sm">Save</button>
                                    <button type="button" @click="editing = false" class="btn btn-outline text-sm">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 italic mb-6">No schedules yet. Add one below to start creating weekly auctions for this region.</p>
            @endif

            <div x-data="{ open: false, frequency: 'weekly' }">
                <button type="button" @click="open = !open" class="btn btn-outline text-sm">
                    <span x-show="!open">+ Add schedule</span>
                    <span x-show="open" x-cloak>Cancel</span>
                </button>

                <form x-show="open" x-cloak method="POST" action="{{ route('admin.community-schedules.store', $region) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="label text-xs">Name</label>
                        <input type="text" name="name" placeholder="e.g. Monthly First Sunday" required maxlength="120" class="input">
                    </div>
                    <div>
                        <label class="label text-xs">Frequency</label>
                        <select name="frequency" x-model="frequency" class="input">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div x-show="frequency === 'monthly'" x-cloak>
                        <label class="label text-xs">Week of month</label>
                        <select name="monthly_week" class="input">
                            @foreach([1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Last'] as $w => $label)
                                <option value="{{ $w }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Day</label>
                        <select name="goes_live_day" class="input">
                            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $dayName)
                                <option value="{{ $i }}">{{ $dayName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Time</label>
                        <input type="time" name="goes_live_time" value="18:00" required class="input">
                    </div>
                    <div class="md:col-span-4 flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded">
                            Active
                        </label>
                        <button type="submit" class="btn btn-primary text-sm">Add schedule</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============= AUCTION MANAGEMENT ============= --}}
        @php
            $managedAuctions = \App\Models\Auction::where('community_region_id', $region->id)
                ->whereIn('status', ['draft', 'upcoming', 'live', 'ended'])
                ->orderByRaw("FIELD(status, 'live', 'upcoming', 'draft', 'ended')")
                ->orderByDesc('goes_live_at')
                ->limit(15)
                ->get();
        @endphp

        @if($managedAuctions->isNotEmpty())

            {{-- Upcoming: reschedule + lineup lock --}}
            @php $schedulableAuctions = $managedAuctions->whereIn('status', ['draft', 'upcoming']); @endphp
            @if($schedulableAuctions->isNotEmpty())
                <div class="card p-6 mt-8">
                    <h2 class="text-lg font-bold mb-1">Upcoming Auctions</h2>
                    <p class="text-sm text-gray-500 mb-4">Adjust go-live time and lineup lock for scheduled auctions.</p>
                    <div class="space-y-4">
                        @foreach($schedulableAuctions as $auction)
                            @php
                                $localIso = optional($auction->goes_live_at)->format('Y-m-d\TH:i');
                                $currentLockHours = ($auction->goes_live_at && $auction->lineup_locks_at)
                                    ? max(1, (int) round(($auction->goes_live_at->timestamp - $auction->lineup_locks_at->timestamp) / 3600))
                                    : (int) config('community.lineup_lock_hours_before_live', 6);
                            @endphp
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="font-semibold text-sm">{{ $auction->title }}</span>
                                    <span class="px-2 py-0.5 text-xs rounded {{ $auction->status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ strtoupper($auction->status) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mb-3">
                                    Goes live: {{ optional($auction->goes_live_at)->format('D d M Y H:i') ?? '—' }}
                                    &middot; Lineup locks: {{ optional($auction->lineup_locks_at)->format('D d M Y H:i') ?? '—' }}
                                    &middot; Lots: {{ $auction->lots()->count() }}
                                </div>
                                <form method="POST" action="{{ route('admin.community-auctions.reschedule', $auction) }}"
                                      class="flex flex-wrap items-end gap-3">
                                    @csrf
                                    <div>
                                        <label class="label text-xs">Go-live at</label>
                                        <input type="datetime-local" name="goes_live_at" value="{{ $localIso }}" required class="input text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs">Lineup locks (hours before go-live)</label>
                                        <input type="number" name="lineup_lock_hours" value="{{ $currentLockHours }}"
                                               min="1" max="72" required class="input text-sm w-24">
                                    </div>
                                    <button type="submit" class="btn btn-primary text-sm">Save</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Testing helpers: lifecycle shortcuts --}}
            <div class="card p-6 mt-8 border-dashed border-2 border-amber-300 bg-amber-50/30">
                <h2 class="text-lg font-bold mb-1">Testing Helpers</h2>
                <p class="text-sm text-gray-600 mb-4">Force auctions through their lifecycle without waiting for the scheduler. Use for QA only.</p>
                <div class="space-y-3">
                    @foreach($managedAuctions as $auction)
                        @php $canDelete = $auction->status !== 'live'; @endphp
                        <div class="p-3 bg-white dark:bg-gray-800 rounded border border-amber-200">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm min-w-0">
                                    <span class="font-medium">#{{ $auction->id }} &middot; {{ $auction->title }}</span>
                                    <span class="ml-2 px-2 py-0.5 text-xs rounded
                                        @if($auction->status === 'live') bg-green-100 text-green-800
                                        @elseif($auction->status === 'upcoming') bg-blue-100 text-blue-800
                                        @elseif($auction->status === 'ended') bg-gray-200 text-gray-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ strtoupper($auction->status) }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if(in_array($auction->status, ['draft', 'upcoming']))
                                        <form method="POST" action="{{ route('admin.community-auctions.go-live', $auction) }}" onsubmit="return confirm('Force this auction LIVE now?')">
                                            @csrf
                                            <button type="submit" class="btn btn-primary text-xs">Go live now</button>
                                        </form>
                                    @endif
                                    @if($auction->status === 'live')
                                        <form method="POST" action="{{ route('admin.community-auctions.end-now', $auction) }}" onsubmit="return confirm('End this live auction now?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline text-xs text-red-600">End now</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('auctions.show', $auction->slug) }}" target="_blank" class="btn btn-outline text-xs">View</a>
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('admin.community-auctions.destroy', $auction) }}" onsubmit="return confirm('Delete auction #{{ $auction->id }} and all its lots/images? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
