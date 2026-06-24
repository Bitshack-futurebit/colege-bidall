<x-app-layout>
    <x-slot name="title">Unsold Lots</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-8">
                <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Unsold Lots</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Relist unsold lots from ended auctions into a new draft auction.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-6">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-6">{{ session('error') }}</div>
            @endif

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="card">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Unsold</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_unsold'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Free Relists Available</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['free_relist'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Reserve Not Met</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['paid_relist'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No draft auctions warning -->
            @if($draftAuctions->isEmpty())
                <div class="card mb-6 border-l-4 border-amber-400">
                    <div class="p-5 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-semibold text-amber-800 dark:text-amber-200">No Draft Auctions Available</p>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">You need to create a draft auction before you can relist lots.</p>
                            <a href="{{ route('seller.auctions.create') }}" class="btn btn-primary mt-3 text-sm">Create Draft Auction</a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filter -->
            <form method="GET" class="card mb-6">
                <div class="p-4 flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Relist Type</label>
                        <select name="type" class="input w-full">
                            <option value="">All Types</option>
                            <option value="free" @selected(request('type') === 'free')>Free Relist Only</option>
                            <option value="paid" @selected(request('type') === 'paid')>Reserve Not Met Only</option>
                        </select>
                    </div>
                    <div class="flex gap-2 sm:flex-shrink-0">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['type']))
                            <a href="{{ route('seller.unsold-lots') }}" class="btn btn-outline">Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Auction Groups -->
            @if(count($auctionGroups) > 0)
                @foreach($auctionGroups as $groupIndex => $group)
                    @php
                        $auction = $group['auction'];
                        $groupLots = $group['lots'];
                        $groupId = 'group-' . $auction->id;
                        $lotsJson = $groupLots->map(function ($l) {
                            return [
                                'id' => $l->id,
                                'isFree' => (bool) $l->free_relist_eligible,
                                'tier' => $l->image_tier ?? 'basic',
                            ];
                        })->values();
                    @endphp

                    <div class="mb-8" x-data="auctionGroup_{{ $auction->id }}()">
                        <!-- Auction Header -->
                        <div class="card mb-3">
                            <div class="p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $auction->title }}</h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                            Ended {{ $auction->end_time->format('d M Y') }}
                                            &middot; {{ $group['total'] }} unsold
                                            @if($group['free'] > 0)
                                                &middot; <span class="text-green-600 dark:text-green-400">{{ $group['free'] }} free</span>
                                            @endif
                                            @if($group['paid'] > 0)
                                                &middot; <span class="text-amber-600 dark:text-amber-400">{{ $group['paid'] }} reserve not met</span>
                                            @endif
                                        </p>
                                    </div>

                                    <!-- Bulk controls -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" @click="selectAllFree()" class="btn btn-outline text-xs text-green-700 dark:text-green-300 border-green-300 dark:border-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 py-1 px-2">
                                            Select Free
                                        </button>
                                        <button type="button" @click="selectAll()" class="btn btn-outline text-xs py-1 px-2">
                                            Select All
                                        </button>
                                        <button type="button" @click="deselectAll()" x-show="selected.length > 0" class="btn btn-outline text-xs py-1 px-2">
                                            Deselect
                                        </button>
                                    </div>
                                </div>

                                <!-- Bulk action bar (shows when items selected) -->
                                <div x-show="selected.length > 0" x-collapse class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                        <div class="text-sm text-gray-700 dark:text-gray-300 flex-shrink-0">
                                            <span x-text="selected.length"></span> selected
                                            (<span x-text="freeCount"></span> free<template x-if="paidCount > 0"><span>, <span x-text="paidCount"></span> paid &mdash; est. R<span x-text="estimatedCost.toFixed(2)"></span></span></template>)
                                        </div>
                                        @if($draftAuctions->isNotEmpty())
                                            <select x-model="bulkTargetAuction" class="input flex-1 text-sm">
                                                <option value="">Choose target auction...</option>
                                                @foreach($draftAuctions as $draftAuction)
                                                    <option value="{{ $draftAuction->id }}">{{ $draftAuction->title }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button"
                                                    @click="submitBulkRelist()"
                                                    :disabled="!bulkTargetAuction"
                                                    class="btn btn-primary whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                                                Relist Selected
                                            </button>
                                        @endif
                                        <button type="button"
                                                @click="submitBulkDelete()"
                                                class="btn btn-outline text-red-600 dark:text-red-400 border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 whitespace-nowrap">
                                            Delete Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lots in this auction -->
                        <div class="space-y-3">
                            @foreach($groupLots as $lot)
                                <div class="card overflow-hidden">
                                    <div class="flex flex-wrap sm:flex-nowrap sm:divide-x sm:divide-gray-100 sm:dark:divide-gray-700">

                                        <!-- Checkbox -->
                                        <div class="flex-shrink-0 flex items-center justify-center px-3 sm:px-4">
                                            <input type="checkbox"
                                                   class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                                   :checked="isSelected({{ $lot->id }})"
                                                   @change="toggleLot({{ $lot->id }}, {{ $lot->free_relist_eligible ? 'true' : 'false' }}, '{{ $lot->image_tier ?? 'basic' }}')">
                                        </div>

                                        <!-- Image -->
                                        <div class="flex-shrink-0 p-4 flex items-center justify-center sm:bg-gray-50 sm:dark:bg-gray-800/30">
                                            <div class="w-20 h-20 rounded-lg overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-100 dark:bg-gray-700">
                                                @if($lot->images->isNotEmpty())
                                                    <img src="{{ $lot->images->first()->thumbnail_url }}"
                                                         alt="{{ $lot->title }}"
                                                         class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Lot info -->
                                        <div class="flex-1 min-w-0 px-5 py-4">
                                            <div class="flex items-start justify-between gap-3 mb-1.5">
                                                <div class="min-w-0">
                                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 leading-snug truncate">{{ $lot->title }}</h3>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Lot #{{ $lot->lot_number }}</span>
                                                    @if($lot->relist_count > 0)
                                                        <span class="badge badge-secondary text-xs ml-1">Relisted {{ $lot->relist_count }}x</span>
                                                    @endif
                                                </div>
                                                @if($lot->free_relist_eligible)
                                                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-700 whitespace-nowrap">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        Free Relist
                                                    </span>
                                                @else
                                                    <span class="flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700 whitespace-nowrap">
                                                        Reserve Not Met
                                                    </span>
                                                @endif
                                            </div>

                                            @if(!$lot->free_relist_eligible)
                                                <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                    <span>Bid: <strong class="text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->current_bid ?? 0) }}</strong></span>
                                                    @if($lot->reserve_price)
                                                        <span>Reserve: <strong class="text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->reserve_price) }}</strong></span>
                                                    @endif
                                                    <span>Relist: <strong class="text-gray-900 dark:text-gray-100">{{ formatCurrency($tierCosts[$lot->image_tier ?? 'basic']) }}</strong> <span class="text-gray-400 dark:text-gray-500">({{ ucfirst($lot->image_tier ?? 'basic') }})</span></span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="w-full sm:w-auto sm:flex-shrink-0 sm:min-w-[220px] border-t border-gray-100 dark:border-gray-700 sm:border-t-0 px-5 py-4 sm:flex sm:flex-col sm:justify-center sm:gap-2">
                                            @if($draftAuctions->isNotEmpty())
                                                <form action="{{ route('seller.lots.relist', $lot) }}"
                                                      method="POST"
                                                      class="flex gap-2 sm:flex-col"
                                                      onsubmit="return confirmRelist(event, '{{ addslashes($lot->title) }}', {{ $lot->free_relist_eligible ? 'true' : 'false' }}, '{{ formatCurrency($tierCosts[$lot->image_tier ?? 'basic']) }}')">
                                                    @csrf
                                                    <select name="target_auction_id" required class="input flex-1 sm:flex-none sm:w-full text-sm">
                                                        <option value="">Choose auction...</option>
                                                        @foreach($draftAuctions as $draftAuction)
                                                            <option value="{{ $draftAuction->id }}">{{ $draftAuction->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn {{ $lot->free_relist_eligible ? 'btn-success' : 'btn-primary' }} whitespace-nowrap sm:w-full">
                                                        {{ $lot->free_relist_eligible ? 'Relist FREE' : 'Relist Lot' }}
                                                    </button>
                                                </form>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <a href="{{ route('seller.auctions.create') }}" class="text-primary-600 hover:underline">Create a draft auction</a> to relist.
                                                </p>
                                            @endif

                                            <form action="{{ route('seller.lots.destroy-unsold', $lot) }}"
                                                  method="POST"
                                                  class="sm:mt-1"
                                                  onsubmit="return confirm('Delete &quot;{{ addslashes($lot->title) }}&quot;?\n\nThis will remove the lot and all its images from the relist list.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline text-red-600 dark:text-red-400 border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 w-full sm:w-full text-sm">
                                                    Delete Lot
                                                </button>
                                            </form>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Hidden bulk relist form for this group -->
                        <form id="bulk-form-{{ $auction->id }}" action="{{ route('seller.lots.bulk-relist') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="target_auction_id" id="bulk-target-{{ $auction->id }}">
                        </form>

                        <!-- Hidden bulk delete form for this group -->
                        <form id="bulk-delete-form-{{ $auction->id }}" action="{{ route('seller.lots.bulk-delete-unsold') }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>

                    <script>
                    function auctionGroup_{{ $auction->id }}() {
                        const tierCosts = @json($tierCosts);
                        const allLots = @json($lotsJson);

                        return {
                            selected: [],
                            bulkTargetAuction: '',

                            get freeCount() {
                                return this.selected.filter(s => s.isFree).length;
                            },
                            get paidCount() {
                                return this.selected.filter(s => !s.isFree).length;
                            },
                            get estimatedCost() {
                                return this.selected
                                    .filter(s => !s.isFree)
                                    .reduce((sum, s) => sum + (tierCosts[s.tier] || 0), 0);
                            },
                            isSelected(id) {
                                return this.selected.some(s => s.id === id);
                            },
                            toggleLot(id, isFree, tier) {
                                const idx = this.selected.findIndex(s => s.id === id);
                                if (idx >= 0) {
                                    this.selected.splice(idx, 1);
                                } else {
                                    this.selected.push({ id, isFree, tier });
                                }
                            },
                            selectAllFree() {
                                allLots.filter(l => l.isFree).forEach(l => {
                                    if (!this.isSelected(l.id)) this.selected.push(l);
                                });
                            },
                            selectAll() {
                                allLots.forEach(l => {
                                    if (!this.isSelected(l.id)) this.selected.push(l);
                                });
                            },
                            deselectAll() {
                                this.selected = [];
                            },
                            submitBulkRelist() {
                                if (!this.bulkTargetAuction) {
                                    alert('Please select a target auction.');
                                    return;
                                }
                                if (this.selected.length === 0) return;

                                const costLine = this.paidCount > 0
                                    ? '\nPaid lots: ' + this.paidCount + ' (est. cost: R' + this.estimatedCost.toFixed(2) + ')'
                                    : '';

                                const msg = 'Relist ' + this.selected.length + ' lot(s)?\n\n' +
                                    'Free lots: ' + this.freeCount + costLine +
                                    '\n\nContinue?';

                                if (!confirm(msg)) return;

                                const form = document.getElementById('bulk-form-{{ $auction->id }}');
                                document.getElementById('bulk-target-{{ $auction->id }}').value = this.bulkTargetAuction;

                                form.querySelectorAll('input[name="lot_ids[]"]').forEach(el => el.remove());

                                this.selected.forEach(s => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'lot_ids[]';
                                    input.value = s.id;
                                    form.appendChild(input);
                                });

                                form.submit();
                            },
                            submitBulkDelete() {
                                if (this.selected.length === 0) return;

                                const msg = 'Delete ' + this.selected.length + ' unsold lot(s)?\n\n' +
                                    'This will permanently remove the lots and all their images.\n\nContinue?';

                                if (!confirm(msg)) return;

                                const form = document.getElementById('bulk-delete-form-{{ $auction->id }}');

                                form.querySelectorAll('input[name="lot_ids[]"]').forEach(el => el.remove());

                                this.selected.forEach(s => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'lot_ids[]';
                                    input.value = s.id;
                                    form.appendChild(input);
                                });

                                form.submit();
                            }
                        };
                    }
                    </script>
                @endforeach
            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        @if(request()->hasAny(['type']))
                            <p class="text-gray-600 dark:text-gray-400 mb-4">No lots match your current filter.</p>
                            <a href="{{ route('seller.unsold-lots') }}" class="btn btn-outline">Clear Filter</a>
                        @else
                            <p class="text-gray-600 dark:text-gray-400 mb-2">No unsold lots to relist.</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">Unsold lots from ended auctions will appear here, grouped by auction.</p>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
    function confirmRelist(event, lotTitle, isFree, relistCost) {
        const selectEl = event.target.querySelector('select[name="target_auction_id"]');
        const auctionName = selectEl.options[selectEl.selectedIndex]?.text ?? 'the selected auction';

        if (!selectEl.value) {
            alert('Please select a draft auction to relist to.');
            event.preventDefault();
            return false;
        }

        const costLine = isFree
            ? '\u2713 Cost: FREE (lot had no bids)'
            : '\u2713 Cost: ' + relistCost + ' (charged when auction goes live)';

        return confirm(
            'Relist "' + lotTitle + '" to ' + auctionName + '?\n\n' +
            '\u2713 Images will be copied\n' +
            '\u2713 You can edit this lot after relisting\n' +
            costLine + '\n\n' +
            'Continue?'
        );
    }
    </script>
</x-app-layout>
