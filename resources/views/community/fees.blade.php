<x-app-layout>
    <x-slot name="title">Platform fees</x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-3">
            <a href="{{ route('community.my-lots') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; My Lots</a>
        </div>

        <h1 class="text-2xl font-bold mb-1">Platform fees</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            5% of each sold community lot. Settle the running balance to keep listing.
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded">{{ session('error') }}</div>
        @endif

        {{-- Block warning if fee gate has tripped --}}
        @if($blockReason)
            <div class="card p-5 mb-5 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500">
                <h3 class="font-bold text-red-900 dark:text-red-200 mb-1">Listings paused</h3>
                <p class="text-sm text-red-800 dark:text-red-300">{{ $blockReason }}</p>
            </div>
        @endif

        {{-- Headline balance --}}
        <div class="card p-6 mb-5 bg-gradient-to-br from-blue-500 to-blue-700 text-white">
            <div class="text-xs uppercase tracking-wide opacity-80">Outstanding balance</div>
            <div class="text-4xl font-bold mt-1">R{{ number_format($outstandingTotal, 2) }}</div>
            <div class="text-xs opacity-80 mt-2">
                {{ $outstandingRows->count() }} unpaid {{ Str::plural('lot', $outstandingRows->count()) }} ·
                Block at R{{ number_format($feeBlockThreshold, 0) }} or {{ $feeAgeDays }} days unpaid
            </div>

            @if($outstandingTotal > 0)
                @if($pendingTx)
                    <div class="mt-4 p-3 bg-amber-100 text-amber-900 rounded">
                        <div class="font-semibold mb-1">Payment in progress</div>
                        <div class="text-xs mb-2">
                            R{{ number_format($pendingTx->amount, 2) }} payment started {{ $pendingTx->created_at->diffForHumans() }}.
                            If you completed it, give the webhook a minute to confirm. If you didn't, you can cancel and start over.
                        </div>
                        <form method="POST" action="{{ route('community.fees.cancel-pending') }}" class="inline"
                              onsubmit="return confirm('Cancel the in-progress payment? Only do this if you didn\'t actually complete the PayFast checkout.');">
                            @csrf
                            <button type="submit" class="text-xs underline text-amber-900 hover:text-amber-700">Cancel pending payment</button>
                        </form>
                    </div>
                @else
                    @php($blinkAvailable = !empty(config('services.blink.api_key')) && !empty(config('services.blink.wallet_id')))
                    <form method="POST" action="{{ route('payment.create') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="type" value="community_fee_payment">
                        <input type="hidden" name="amount" value="{{ number_format($outstandingTotal, 2, '.', '') }}">

                        @if($blinkAvailable)
                            <div class="grid grid-cols-2 gap-2 mb-3 max-w-sm">
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="payfast" class="peer sr-only" checked>
                                    <div class="rounded-xl border border-white/40 bg-white/10 p-3 text-center text-sm font-semibold peer-checked:bg-white peer-checked:text-blue-700 transition">
                                        Card<span class="block text-[10px] font-normal opacity-80">PayFast · ZAR</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="blink" class="peer sr-only">
                                    <div class="rounded-xl border border-white/40 bg-white/10 p-3 text-center text-sm font-semibold peer-checked:bg-white peer-checked:text-blue-700 transition">
                                        Bitcoin<span class="block text-[10px] font-normal opacity-80">Lightning</span>
                                    </div>
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="payment_method" value="payfast">
                        @endif

                        <button type="submit" class="btn bg-white text-blue-700 hover:bg-blue-50 font-semibold w-full md:w-auto">
                            Pay R{{ number_format($outstandingTotal, 2) }}
                        </button>
                    </form>
                    <div class="mt-2 text-[11px] opacity-80">
                        @if($blinkAvailable)Secure payment via PayFast (card) or Bitcoin Lightning.@else Secure payment via PayFast.@endif Charges all unpaid line items in one go.
                    </div>
                @endif
            @endif
        </div>

        {{-- Outstanding line items --}}
        <div class="card p-5 mb-5">
            <h2 class="text-lg font-bold mb-3">Unpaid line items</h2>

            @if($outstandingRows->isEmpty())
                <p class="text-sm text-gray-500">Nothing outstanding. Keep listing.</p>
            @else
                <div class="space-y-2">
                    @foreach($outstandingRows as $row)
                        <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded border border-gray-200 dark:border-gray-700">
                            @if($row->lot && $row->lot->images->isNotEmpty())
                                <img src="{{ Storage::url($row->lot->images->first()->thumbnail_path) }}"
                                     class="w-12 h-12 rounded object-cover shrink-0">
                            @else
                                <div class="w-12 h-12 rounded bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold truncate">{{ $row->lot?->title ?? 'Lot #' . $row->lot_id }}</div>
                                <div class="text-xs text-gray-500">
                                    Sold for R{{ number_format($row->hammer_amount, 0) }}
                                    @if($row->region)· {{ $row->region->name }}@endif
                                    · {{ $row->accrued_at->format('d M Y') }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-bold text-blue-600">R{{ number_format($row->commission_amount, 2) }}</div>
                                <div class="text-[10px] text-gray-500 uppercase">5%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Paid history --}}
        @if($paidRows->isNotEmpty())
            <div class="card p-5">
                <h2 class="text-lg font-bold mb-3">Recently paid</h2>
                <div class="space-y-2">
                    @foreach($paidRows as $row)
                        <div class="flex items-center justify-between gap-3 p-3 bg-green-50/50 dark:bg-green-900/10 rounded border border-green-100 dark:border-green-900/30">
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold truncate">{{ $row->lot?->title ?? 'Lot #' . $row->lot_id }}</div>
                                <div class="text-xs text-gray-500">
                                    Paid {{ $row->seller_paid_at?->format('d M Y') }}
                                    @if($row->region)· {{ $row->region->name }}@endif
                                </div>
                            </div>
                            <div class="font-bold text-green-700 dark:text-green-400">R{{ number_format($row->commission_amount, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
