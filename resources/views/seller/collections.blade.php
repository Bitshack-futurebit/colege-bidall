<x-app-layout>
    <x-slot name="title">Collections</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Back to Dashboard -->
            <div class="mb-4">
                <a href="{{ route('seller.dashboard') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1 w-fit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
            </div>

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Collections</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Track and confirm offline payments from bidders</p>
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Awaiting Payment</div>
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['awaiting'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Confirmed Paid</div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['confirmed'] }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Outstanding</div>
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency($stats['total_outstanding']) }}</div>
                    </div>
                </div>
            </div>

            <!-- Bidder Summary -->
            @if($bidderSummary->isNotEmpty())
                <div class="card mb-8">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bidders Owing</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Bidder</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Contact</th>
                                        <th class="text-center py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Lots</th>
                                        <th class="text-right py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Total Owed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bidderSummary as $summary)
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <td class="py-2 px-3 text-gray-900 dark:text-gray-100">{{ $summary['bidder']->name }}</td>
                                            <td class="py-2 px-3 text-gray-600 dark:text-gray-400">
                                                {{ $summary['bidder']->email }}
                                                @if($summary['bidder']->phone)
                                                    <br><span class="text-xs">{{ $summary['bidder']->phone }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-center text-gray-900 dark:text-gray-100">{{ $summary['lot_count'] }}</td>
                                            <td class="py-2 px-3 text-right font-semibold text-red-600 dark:text-red-400">{{ formatCurrency($summary['total_owed']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filters -->
            <form method="GET" action="{{ route('seller.collections') }}" class="card mb-6">
                <div class="p-4 flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 w-full sm:w-auto">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All Statuses</option>
                            <option value="awaiting_collection" {{ request('status') === 'awaiting_collection' ? 'selected' : '' }}>Awaiting Payment</option>
                            <option value="paid_offline" {{ request('status') === 'paid_offline' ? 'selected' : '' }}>Confirmed Paid</option>
                        </select>
                    </div>
                    <div class="flex-1 w-full sm:w-auto">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Auction</label>
                        <select name="auction_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">All Auctions</option>
                            @foreach($auctions as $auction)
                                <option value="{{ $auction->id }}" {{ request('auction_id') == $auction->id ? 'selected' : '' }}>
                                    {{ $auction->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('seller.collections') }}" class="btn btn-outline">Clear</a>
                    </div>
                </div>
            </form>

            <!-- Grouped Collection Cards -->
            @if($grouped->isEmpty())
                <div class="card">
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">No collection items found.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($grouped as $group)
                        @php
                            $bidder = $group['bidder'];
                            $auction = $group['auction'];
                            $lots = $group['lots'];
                            $isAwaiting = $group['status'] === 'awaiting_collection';
                            $awaitingLots = $lots->filter(fn($l) => $l->payment_status === 'awaiting_collection' || $l->payment_status === null);
                            $awaitingLotIds = $awaitingLots->pluck('id')->join(',');
                            $awaitingTotal = $awaitingLots->sum(fn ($l) => $l->getTotalAmountDue());
                            $earliestArranged = $group['earliest_arranged'] ? \Carbon\Carbon::parse($group['earliest_arranged']) : null;
                            $daysAgo = $earliestArranged ? (int) $earliestArranged->diffInDays(now()) : 0;
                            $overdueClass = match(true) {
                                !$isAwaiting => 'text-green-600 dark:text-green-400',
                                $daysAgo > 7 => 'text-red-600 dark:text-red-400',
                                $daysAgo >= 3 => 'text-amber-600 dark:text-amber-400',
                                default => 'text-green-600 dark:text-green-400',
                            };
                        @endphp
                        <div class="card">
                            <div class="p-4 sm:p-5">
                                <!-- Header: Bidder + Auction + Status + Total -->
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $bidder->name }}</h3>
                                            @if($isAwaiting)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    Awaiting
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    Paid
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $auction->title }} &middot;
                                            {{ $bidder->email }}{{ $bidder->phone ? ' · ' . $bidder->phone : '' }}
                                            @if($isAwaiting && $earliestArranged)
                                                &middot; <span class="{{ $overdueClass }} font-medium">{{ $daysAgo === 0 ? 'today' : $daysAgo . 'd ago' }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold {{ $isAwaiting ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                            {{ formatCurrency($group['total_due']) }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $lots->count() }} lot{{ $lots->count() > 1 ? 's' : '' }}</div>
                                    </div>
                                </div>

                                <!-- Lot List (compact table) -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-500 dark:text-gray-400">
                                                <th class="text-left py-1 pr-2 font-medium">Lot</th>
                                                <th class="text-left py-1 px-2 font-medium hidden sm:table-cell">Title</th>
                                                <th class="text-right py-1 px-2 font-medium">Bid</th>
                                                <th class="text-right py-1 px-2 font-medium">Total Due</th>
                                                <th class="text-center py-1 pl-2 font-medium">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($lots as $lot)
                                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                                    <td class="py-1.5 pr-2 text-gray-900 dark:text-gray-100 font-medium">#{{ $lot->lot_number }}</td>
                                                    <td class="py-1.5 px-2 hidden sm:table-cell">
                                                        <a href="{{ route('lots.show', $lot) }}" class="text-primary-600 hover:underline truncate block max-w-[200px]" target="_blank">
                                                            {{ $lot->title }}
                                                        </a>
                                                    </td>
                                                    <td class="py-1.5 px-2 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency($lot->current_bid) }}</td>
                                                    <td class="py-1.5 px-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->getTotalAmountDue()) }}</td>
                                                    <td class="py-1.5 pl-2 text-center">
                                                        @if($lot->status === 'pending_confirmation')
                                                            <span class="text-amber-600 font-medium">Pending Confirmation</span>
                                                        @elseif($lot->payment_status === 'awaiting_collection')
                                                            <span class="text-amber-600">Awaiting</span>
                                                        @else
                                                            <span class="text-green-600">Paid</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Actions -->
                                @php
                                    $pendingConfirmationLots = $lots->filter(fn($l) => $l->status === 'pending_confirmation');
                                @endphp
                                @if($pendingConfirmationLots->isNotEmpty())
                                    <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                                        <p class="text-xs font-medium text-amber-700 dark:text-amber-400 mb-2">Subject to Confirmation — confirm or reject these lots:</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($pendingConfirmationLots as $pcLot)
                                                <div class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded px-2 py-1 border border-amber-200 dark:border-amber-700">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">#{{ $pcLot->lot_number }}</span>
                                                    <form action="{{ route('seller.lots.confirm-sale', $pcLot) }}" method="POST" class="inline" onsubmit="return confirm('Confirm sale of Lot #{{ $pcLot->lot_number }}?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-600 hover:bg-green-700 text-white">Confirm</button>
                                                    </form>
                                                    <form action="{{ route('seller.lots.reject-sale', $pcLot) }}" method="POST" class="inline" onsubmit="return confirm('Reject sale of Lot #{{ $pcLot->lot_number }}? This will mark it as unsold.')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-600 hover:bg-red-700 text-white">Reject</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($awaitingLots->isNotEmpty() || ($bidder && !$bidder->is_active && $bidder->suspended_by_auctioneer_id === $auctioneer->id))
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                                        @if($awaitingLots->isNotEmpty())
                                            <form action="{{ route('seller.collections.confirm') }}" method="POST"
                                                  onsubmit="return confirm('Confirm payment received for {{ $awaitingLots->count() }} lot{{ $awaitingLots->count() > 1 ? 's' : '' }} ({{ formatCurrency($awaitingTotal) }})?\n\nThis will create sales records and credit your account.')">
                                                @csrf
                                                <input type="hidden" name="lot_ids" value="{{ $awaitingLotIds }}">
                                                <button type="submit" class="btn btn-sm bg-green-600 hover:bg-green-700 text-white">
                                                    Confirm Payment ({{ formatCurrency($awaitingTotal) }})
                                                </button>
                                            </form>

                                            @if($bidder && $bidder->phone)
                                                @php
                                                    $whatsappNumber = preg_replace('/[^0-9]/', '', $bidder->phone);
                                                    if (str_starts_with($whatsappNumber, '0')) {
                                                        $whatsappNumber = '27' . substr($whatsappNumber, 1);
                                                    }
                                                    $premiumPct = (float) $auction->buyers_premium_percentage;
                                                    $hammerSubtotal = $awaitingLots->sum('current_bid');
                                                    $premiumSubtotal = $awaitingTotal - $hammerSubtotal;

                                                    $emojiWave    = "\u{1F44B}"; // waving hand
                                                    $emojiParty   = "\u{1F389}"; // party popper
                                                    $emojiLabel   = "\u{1F3F7}\u{FE0F}"; // label
                                                    $emojiMoney   = "\u{1F4B0}"; // money bag
                                                    $emojiPackage = "\u{1F4E6}"; // package
                                                    $emojiThanks  = "\u{1F64F}"; // folded hands

                                                    if ($premiumPct > 0) {
                                                        $lotList = $awaitingLots->map(function ($l) use ($premiumPct, $emojiLabel) {
                                                            $hammer = (float) $l->current_bid;
                                                            $premium = $hammer * $premiumPct / 100;
                                                            $total = $hammer + $premium;
                                                            return $emojiLabel . ' Lot #' . $l->lot_number . ' ' . $l->title . ': '
                                                                . formatCurrency($hammer) . ' + ' . formatCurrency($premium) . ' premium = '
                                                                . formatCurrency($total);
                                                        })->join("\n");
                                                        $totalsBlock = "Subtotal: " . formatCurrency($hammerSubtotal) . "\n"
                                                            . "Buyer's premium (" . rtrim(rtrim(number_format($premiumPct, 2), '0'), '.') . "%): " . formatCurrency($premiumSubtotal) . "\n"
                                                            . $emojiMoney . " Total due: " . formatCurrency($awaitingTotal);
                                                    } else {
                                                        $lotList = $awaitingLots->map(fn ($l) => $emojiLabel . ' Lot #' . $l->lot_number . ' ' . $l->title . ': ' . formatCurrency($l->getTotalAmountDue()))->join("\n");
                                                        $totalsBlock = $emojiMoney . " Total due: " . formatCurrency($awaitingTotal);
                                                    }

                                                    $businessName = $auction->auctioneer->business_name;
                                                    $whatsappRaw =
                                                        "Hi {$bidder->name} {$emojiWave}\n\n"
                                                        . "Thank you for participating in our auction \"{$auction->title}\" with {$businessName} - congratulations on your winning bids! {$emojiParty}\n\n"
                                                        . "Here's a friendly reminder of the lots you've won:\n\n"
                                                        . "{$lotList}\n\n"
                                                        . "{$totalsBlock}\n\n"
                                                        . "{$emojiPackage} Please arrange payment and collection at your earliest convenience.\n\n"
                                                        . "We really appreciate your support and look forward to seeing you at our next auction. {$emojiThanks}\n\n"
                                                        . "Kind regards,\n{$businessName}";
                                                @endphp
                                                <a href="https://api.whatsapp.com/send?phone={{ $whatsappNumber }}&text={{ rawurlencode($whatsappRaw) }}" target="_blank"
                                                   class="btn btn-sm bg-green-500 hover:bg-green-600 text-white">
                                                    WhatsApp Reminder
                                                </a>
                                            @endif

                                            @if($bidder && $bidder->is_active)
                                                <form action="{{ route('seller.collections.suspend', $bidder) }}" method="POST"
                                                      onsubmit="return confirm('Suspend {{ $bidder->name }}? They will be logged out and unable to bid.')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm bg-red-600 hover:bg-red-700 text-white">
                                                        Suspend Bidder
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        @if($bidder && !$bidder->is_active && $bidder->suspended_by_auctioneer_id === $auctioneer->id)
                                            <form action="{{ route('seller.collections.unsuspend', $bidder) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white">
                                                    Unsuspend Bidder
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
