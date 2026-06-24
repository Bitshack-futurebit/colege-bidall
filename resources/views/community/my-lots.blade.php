<x-app-layout>
    <x-slot name="title">My Community Lots</x-slot>

    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-5">
            <h1 class="text-2xl font-bold">My Community Lots</h1>
            <div class="flex gap-2 flex-wrap">
                @auth
                    @if(auth()->user()->community_region_id)
                        <a href="{{ route('community.create-lot') }}" class="btn btn-primary">+ List another</a>
                    @endif
                @endauth
                @php
                    $feeDebt = \App\Models\CommunityCommissionLedger::where('seller_user_id', auth()->id())
                        ->where('status', 'accrued')->sum('commission_amount');
                @endphp
                <a href="{{ route('community.fees') }}" class="btn btn-outline relative">
                    Fees
                    @if($feeDebt > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-blue-600 text-white rounded">R{{ number_format($feeDebt, 2) }}</span>
                    @endif
                </a>
                <a href="{{ route('community.index') }}" class="btn btn-outline">Back to region</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded flex flex-wrap items-center justify-between gap-2">
                <span>{{ session('success') }}</span>
                @auth
                    @if(auth()->user()->community_region_id)
                        <a href="{{ route('community.create-lot') }}" class="btn btn-primary btn-sm">List another item</a>
                    @endif
                @endauth
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        @if($lots->isEmpty())
            <div class="card p-6 text-center text-gray-500">
                You haven't listed anything yet.
            </div>
        @else
            <div class="space-y-4">
                @foreach($lots as $lot)
                    @php
                        $statusColors = [
                            'draft' => 'gray',
                            'pending' => 'blue',
                            'live' => 'red',
                            'sold' => 'green',
                            'unsold' => 'gray',
                            'pending_confirmation' => 'amber',
                        ];
                        $color = $statusColors[$lot->status] ?? 'gray';
                        $statusLabel = match($lot->status) {
                            'pending_confirmation' => 'Awaiting your decision',
                            'sold' => 'Sold',
                            'unsold' => 'Unsold',
                            'live' => 'Live now',
                            'pending' => 'Pending',
                            'draft' => 'Draft',
                            default => ucfirst(str_replace('_', ' ', $lot->status)),
                        };
                        $inDraft       = $lot->status === 'draft' && $lot->total_bids === 0 && ($lot->auction?->status ?? null) === 'draft';
                        $canRelist     = $lot->canBeRelisted();
                        $unsoldCleanup = $lot->status === 'unsold' && ($lot->auction?->status ?? null) === 'ended';
                        $isRemovable   = $inDraft || $unsoldCleanup;
                        // payment_status='voided' marks a buyer-non-payment void; status is 'unsold' so the
                        // lot can be relisted normally, but we still show the void breadcrumb.
                        $voided        = $lot->payment_status === 'voided';
                        $needsPayment  = $lot->status === 'sold' && !$lot->isPaidOffline() && !$voided;
                        $paidOffline   = $lot->status === 'sold' && $lot->isPaidOffline();
                    @endphp
                    <div class="card overflow-hidden">
                        <div class="flex flex-col sm:flex-row gap-4 p-4">
                            {{-- Image --}}
                            @if($lot->images->isNotEmpty())
                                <img src="{{ Storage::url($lot->images->first()->thumbnail_path) }}"
                                     alt="{{ $lot->title }}"
                                     class="w-full sm:w-36 h-36 object-cover rounded shrink-0">
                            @else
                                <div class="w-full sm:w-36 h-36 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center text-gray-400 shrink-0">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif

                            {{-- Main content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold truncate">{{ $lot->title }}</h3>
                                        <div class="text-xs text-gray-500 mt-0.5 truncate">
                                            {{ $lot->auction->title ?? 'Auction' }}
                                            @if($lot->auction?->communityRegion?->name)
                                                &middot; {{ $lot->auction->communityRegion->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-xs bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300 rounded whitespace-nowrap shrink-0">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                {{-- Stats row --}}
                                <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                                    <div>
                                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Starting</div>
                                        <div class="font-semibold">R{{ number_format($lot->starting_bid, 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] uppercase tracking-wide text-gray-500">
                                            {{ $lot->status === 'sold' ? 'Sold for' : 'Current' }}
                                        </div>
                                        <div class="font-semibold {{ $lot->status === 'sold' ? 'text-green-700 dark:text-green-400' : '' }}">
                                            {{ $lot->current_bid ? 'R' . number_format($lot->current_bid, 0) : '—' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Bids</div>
                                        <div class="font-semibold">{{ $lot->total_bids }}</div>
                                    </div>
                                </div>

                                {{-- Contextual info --}}
                                @if($lot->isAwaitingConfirmation() && $lot->confirmation_expires_at)
                                    <div class="mt-3 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded text-xs text-amber-800 dark:text-amber-300">
                                        <span class="font-semibold">Decide by:</span>
                                        {{ $lot->confirmation_expires_at->format('d M H:i') }}
                                        ({{ $lot->confirmation_expires_at->diffForHumans() }})
                                    </div>
                                @elseif($lot->status === 'sold' && $lot->winningBidder)
                                    <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                        <div><span class="text-gray-500">Winner:</span> <span class="font-medium">{{ $lot->winningBidder->name }}</span></div>
                                        @if($lot->winningBidder->phone)
                                            <div><span class="text-gray-500">Contact:</span> <a href="tel:{{ $lot->winningBidder->phone }}" class="text-primary-600 hover:underline">{{ $lot->winningBidder->phone }}</a></div>
                                        @endif
                                    </div>
                                @elseif($lot->status === 'live' && $lot->auction?->end_time)
                                    <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                                        Ends {{ $lot->auction->end_time->diffForHumans() }}
                                    </div>
                                @endif

                                {{-- Payment banner for sold lots --}}
                                @if($paidOffline)
                                    <div class="mt-3 px-3 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-xs text-green-800 dark:text-green-300 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Collected &amp; paid{{ $lot->payment_completed_at ? ' on ' . $lot->payment_completed_at->format('d M Y') : '' }}
                                    </div>
                                @elseif($voided)
                                    <div class="mt-3 px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-800 dark:text-red-300 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Sale voided — buyer never paid. Strike issued.
                                    </div>
                                @elseif($needsPayment)
                                    <div class="mt-3 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-xs text-blue-800 dark:text-blue-300">
                                        Awaiting collection &amp; payment from buyer.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Action footer --}}
                        @if($lot->isAwaitingConfirmation() || $lot->status === 'sold' || $canRelist || $isRemovable)
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2 justify-end">
                                @if($lot->isAwaitingConfirmation())
                                    <a href="{{ route('community.confirm', $lot) }}" class="btn btn-primary btn-sm">Decide on winner</a>
                                @elseif($lot->status === 'sold')
                                    <a href="{{ route('community.invoice', $lot) }}" class="btn btn-outline btn-sm">View invoice</a>
                                    @if($needsPayment)
                                        <form method="POST" action="{{ route('community.mark-paid', $lot) }}"
                                              onsubmit="return confirm('Mark this lot as collected and paid?');">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">Mark collected &amp; paid</button>
                                        </form>
                                        <form method="POST" action="{{ route('community.void-buyer-nonpayment', $lot) }}"
                                              x-data="{ open: false }">
                                            @csrf
                                            <button type="button" @click="open = true" x-show="!open"
                                                    class="btn btn-sm border border-red-200 text-red-600 hover:bg-red-50">
                                                Buyer didn't pay
                                            </button>
                                            <div x-show="open" x-cloak class="flex flex-col sm:flex-row gap-2 items-stretch">
                                                <input type="text" name="reason" maxlength="500"
                                                       class="input text-sm" placeholder="Reason (optional)">
                                                <button type="submit"
                                                        class="btn btn-sm bg-red-600 hover:bg-red-700 text-white whitespace-nowrap"
                                                        onclick="return confirm('This voids the sale, waives the platform fee, and issues a strike against the buyer. Continue?');">
                                                    Confirm void
                                                </button>
                                                <button type="button" @click="open = false" class="btn btn-outline btn-sm">Cancel</button>
                                            </div>
                                        </form>
                                    @endif
                                @endif
                                @if($canRelist)
                                    <form method="POST" action="{{ route('community.relist-lot', $lot) }}"
                                          onsubmit="return confirm('Relist this lot into the next community auction?');">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Relist</button>
                                    </form>
                                @endif
                                @if($isRemovable)
                                    <form method="POST" action="{{ route('community.delete-lot', $lot) }}"
                                          onsubmit="return confirm('Delete this lot? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm bg-red-600 hover:bg-red-700 text-white">
                                            {{ $inDraft ? 'Remove' : 'Delete' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $lots->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
