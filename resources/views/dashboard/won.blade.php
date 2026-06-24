<x-app-layout>
    <x-slot name="title">My Won Lots</x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Won Lots</h1>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="card p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalWon }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Won</div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl font-bold {{ $totalUnpaid > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ $totalUnpaid }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Awaiting Collection</div>
                </div>
                <div class="card p-4 text-center">
                    <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ formatCurrency($totalValue) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Value</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="p-4">
                    <form method="GET" action="{{ route('dashboard.won') }}" class="flex flex-wrap gap-3 items-end">
                        <!-- Payment Status -->
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Payment Status</label>
                            <select name="payment" class="input input-sm w-full">
                                <option value="">All</option>
                                <option value="awaiting" {{ request('payment') === 'awaiting' ? 'selected' : '' }}>Awaiting Collection</option>
                                <option value="paid" {{ request('payment') === 'paid' ? 'selected' : '' }}>Collected / Paid</option>
                            </select>
                        </div>

                        <!-- Auctioneer -->
                        @if($auctioneers->count() > 1)
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Auctioneer</label>
                            <select name="auctioneer" class="input input-sm w-full">
                                <option value="">All Auctioneers</option>
                                @foreach($auctioneers as $auctioneer)
                                    <option value="{{ $auctioneer->id }}" {{ request('auctioneer') == $auctioneer->id ? 'selected' : '' }}>
                                        {{ $auctioneer->business_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Date From -->
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">From</label>
                            <input type="date" name="from" value="{{ request('from') }}" class="input input-sm w-full">
                        </div>

                        <!-- Date To -->
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">To</label>
                            <input type="date" name="to" value="{{ request('to') }}" class="input input-sm w-full">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            @if(request()->hasAny(['payment', 'auctioneer', 'from', 'to']))
                                <a href="{{ route('dashboard.won') }}" class="btn btn-outline btn-sm">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lot List -->
            @if($wonLots->count() > 0)
                <div class="card overflow-hidden">
                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-14"></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lot</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Auction</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Auctioneer</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hammer</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($wonLots as $lot)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <!-- Thumbnail -->
                                        <td class="px-4 py-3">
                                            @if($lot->images->count() > 0)
                                                <img src="{{ $lot->images->first()->thumbnail_url }}"
                                                     alt="{{ $lot->title }}"
                                                     class="w-12 h-12 rounded object-cover">
                                            @else
                                                <div class="w-12 h-12 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Lot Title -->
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm leading-tight">
                                                {{ $lot->title }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                Lot #{{ $lot->lot_number }}
                                                @if($lot->actual_end_time)
                                                    · {{ $lot->actual_end_time->format('d M Y') }}
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Auction -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-700 dark:text-gray-300 leading-tight">
                                                {{ Str::limit($lot->auction->title, 30) }}
                                            </div>
                                        </td>

                                        <!-- Auctioneer -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $lot->auction->auctioneer->business_name }}
                                            </div>
                                        </td>

                                        <!-- Hammer Price -->
                                        <td class="px-4 py-3 text-right">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                                {{ formatCurrency($lot->current_bid) }}
                                            </div>
                                            @if($lot->auction->buyers_premium_percentage > 0)
                                                <div class="text-xs text-gray-500">
                                                    + {{ formatCurrency($lot->getTotalAmountDue() - $lot->current_bid) }} prem.
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Payment Status -->
                                        <td class="px-4 py-3 text-center">
                                            @if($lot->status === 'pending_confirmation')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Pending Confirmation
                                                </span>
                                            @elseif($lot->payment_status === 'paid_platform')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    Paid
                                                </span>
                                            @elseif($lot->payment_status === 'paid_offline')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    Paid (Offline)
                                                </span>
                                            @elseif($lot->payment_status === 'awaiting_collection')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Awaiting Collection
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Awaiting Collection
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Action -->
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($lot->status === 'pending_confirmation')
                                                    <span class="text-xs text-amber-600 dark:text-amber-400">Awaiting auctioneer</span>
                                                @elseif(($lot->payment_status === null || $lot->payment_status === 'awaiting_collection') && $lot->auction->hasOnlinePayment())
                                                    <form action="{{ route('direct-payment.lots') }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="lot_ids" value="{{ $lot->id }}">
                                                        <button type="submit" class="btn btn-sm bg-green-600 hover:bg-green-700 text-white">
                                                            Pay Now
                                                        </button>
                                                    </form>
                                                @elseif(($lot->payment_status === null || $lot->payment_status === 'awaiting_collection') && $lot->auction->auctioneer->whatsapp_number)
                                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lot->auction->auctioneer->whatsapp_number) }}?text=Hi, I won Lot %23{{ $lot->lot_number }} ({{ urlencode($lot->title) }}) in {{ urlencode($lot->auction->title) }}. I would like to arrange collection."
                                                       target="_blank"
                                                       class="btn btn-sm btn-primary">
                                                        WhatsApp
                                                    </a>
                                                @endif
                                                <a href="{{ route('lots.show', $lot) . '?ref=won' }}" class="btn btn-sm btn-outline">
                                                    Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($wonLots as $lot)
                            <div class="p-4 flex gap-3">
                                <!-- Thumbnail -->
                                @if($lot->images->count() > 0)
                                    <img src="{{ $lot->images->first()->thumbnail_url }}"
                                         alt="{{ $lot->title }}"
                                         class="w-16 h-16 rounded object-cover flex-shrink-0">
                                @else
                                    <div class="w-16 h-16 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate">{{ $lot->title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                Lot #{{ $lot->lot_number }} · {{ $lot->auction->auctioneer->business_name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $lot->auction->title }}</p>
                                        </div>
                                        <!-- Status Badge -->
                                        @if($lot->status === 'pending_confirmation')
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Pending Confirmation</span>
                                        @elseif($lot->payment_status === 'paid_platform')
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Paid</span>
                                        @elseif($lot->payment_status === 'paid_offline')
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Paid (Offline)</span>
                                        @elseif($lot->payment_status === 'awaiting_collection')
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Awaiting Collection</span>
                                        @else
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Awaiting Collection</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between mt-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ formatCurrency($lot->current_bid) }}</span>
                                        <div class="flex gap-2 flex-wrap">
                                            @if($lot->status === 'pending_confirmation')
                                                <span class="text-xs text-amber-600 dark:text-amber-400">Awaiting auctioneer</span>
                                            @elseif(($lot->payment_status === null || $lot->payment_status === 'awaiting_collection') && $lot->auction->hasOnlinePayment())
                                                <form action="{{ route('direct-payment.lots') }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="lot_ids" value="{{ $lot->id }}">
                                                    <button type="submit" class="btn btn-sm bg-green-600 hover:bg-green-700 text-white">Pay Now</button>
                                                </form>
                                            @elseif(($lot->payment_status === null || $lot->payment_status === 'awaiting_collection') && $lot->auction->auctioneer->whatsapp_number)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lot->auction->auctioneer->whatsapp_number) }}?text=Hi, I won Lot %23{{ $lot->lot_number }} ({{ urlencode($lot->title) }}) in {{ urlencode($lot->auction->title) }}. I would like to arrange collection."
                                                   target="_blank"
                                                   class="btn btn-sm btn-primary">
                                                    WhatsApp
                                                </a>
                                            @endif
                                            @if($lot->auction?->is_community && $lot->status === 'sold')
                                                <a href="{{ route('community.invoice', $lot) }}" class="btn btn-sm btn-outline">Invoice</a>
                                            @endif
                                            <a href="{{ route('lots.show', $lot) . '?ref=won' }}" class="btn btn-sm btn-outline">
                                                Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $wonLots->links() }}
                </div>

            @else
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        @if(request()->hasAny(['payment', 'auctioneer', 'from', 'to']))
                            <p class="text-gray-600 dark:text-gray-400 mb-4">No lots match your filters.</p>
                            <a href="{{ route('dashboard.won') }}" class="btn btn-outline">Clear Filters</a>
                        @else
                            <p class="text-gray-600 dark:text-gray-400 mb-4">You haven't won any lots yet.</p>
                            <a href="{{ route('auctions.index') }}" class="btn btn-primary">Browse Auctions</a>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Prevent double-click on payment forms --}}
    <script>
        document.querySelectorAll('form[action*="payment/lots"]').forEach(function(form) {
            form.addEventListener('submit', function() {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                }
            });
        });
    </script>
</x-app-layout>
