@php
    // Default: link to auctioneer profile (for discovery)
    // Set $linkTo = 'auction' on auctioneer profile pages to view lots
    $linkTo = $linkTo ?? 'auctioneer';

    // Ensure auctioneer is loaded
    if (!$auction->relationLoaded('auctioneer')) {
        $auction->load('auctioneer');
    }

    $url = $linkTo === 'auction' ? route('auctions.show', $auction) : route('auctioneer.show', $auction->auctioneer);
    $buttonText = $linkTo === 'auction' ? ($auction->status === 'live' ? 'View Lots & Bid' : 'View Lots') : 'View Auctioneer';
@endphp

<div class="card card-hover">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            @if($auction->status === 'live')
                <span class="badge badge-danger animate-pulse">Live Now</span>
            @elseif($auction->status === 'upcoming')
                <span class="badge badge-info">{{ $auction->start_time->format('M d, Y') }}</span>
            @else
                <span class="badge badge-secondary">{{ ucfirst($auction->status) }}</span>
            @endif
            <span class="text-sm text-gray-600 dark:text-gray-400">
                @if($auction->isDutch())
                    <span class="text-amber-600 dark:text-amber-400 font-medium">Dutch</span> &middot;
                @elseif($auction->isSealed())
                    <span class="text-purple-600 dark:text-purple-400 font-medium">Sealed</span> &middot;
                @elseif($auction->isLiveFormat())
                    <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 {{ $auction->status === 'live' ? 'animate-pulse' : '' }}"></span>
                        Live
                    </span> &middot;
                @endif
                {{ $auction->lots_count ?? $auction->lots->count() }} lots
            </span>
        </div>

        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
            <a href="{{ $url }}" class="hover:text-primary-600">
                {{ $auction->title }}
            </a>
        </h3>

        @if($auction->auctioneer)
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                by <span class="font-medium text-primary-600 dark:text-primary-400">{{ $auction->auctioneer->business_name }}</span>
            </p>
        @endif

        @if($auction->description)
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                {{ $auction->description }}
            </p>
        @endif

        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <div>Starts: {{ $auction->start_time->format('M d, H:i') }}</div>
                @if($auction->end_time && !$auction->isCommunity())
                    <div>Ends: {{ $auction->end_time->format('M d, H:i') }}</div>
                @endif
            </div>
        </div>

        @if($auction->requiresDeposit())
            <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded px-3 py-2 mb-4">
                <p class="text-xs text-yellow-800 dark:text-yellow-200">
                    Deposit required: {{ formatCurrency($auction->deposit_amount) }}
                </p>
            </div>
        @endif

        <a href="{{ $url }}" class="btn {{ $auction->status === 'live' ? 'btn-accent' : 'btn-primary' }} w-full">
            {{ $buttonText }}
        </a>
    </div>
</div>
