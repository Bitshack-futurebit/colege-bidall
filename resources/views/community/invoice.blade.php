<x-app-layout>
    <x-slot name="title">Invoice — Lot #{{ $lot->lot_number }}</x-slot>

    @php
        $waIcon = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
        $sellerWa = waNumber($lot->seller?->phone);
        $buyerWa = waNumber($lot->winningBidder?->phone);
        $waMsg = rawurlencode(
            "👋 Hi, regarding Lot #{$lot->lot_number} — {$lot->title}\n\n"
            . "From the " . config('app.name', 'BidAll') . " community auction."
        );
        $isConfirmed = $lot->status === 'sold';
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-6 print:py-0 print:px-0 print:max-w-none">
        {{-- Action bar (hidden when printing) --}}
        <div class="print:hidden flex flex-wrap items-center justify-between gap-2 mb-4">
            <a href="{{ route('community.my-lots') }}" class="btn btn-outline btn-sm">&larr; Back</a>
            <div class="flex flex-wrap gap-2">
                @if($isSeller && $lot->status === 'sold' && $lot->winningBidder?->phone)
                    @php
                        $buyerWaNum = waNumber($lot->winningBidder->phone);
                        $invoiceMsg = rawurlencode(
                            "🧾 *Invoice — " . config('app.name', 'BidAll') . " Community*\n\n"
                            . "Hi " . ($lot->winningBidder->name ?? '') . ",\n\n"
                            . "Your winning bid on *Lot #{$lot->lot_number} — {$lot->title}* has been confirmed.\n\n"
                            . "💰 Amount: R" . number_format((float) $lot->current_bid, 0) . "\n"
                            . "📄 Full invoice: " . route('community.invoice', $lot) . "\n\n"
                            . "Let's arrange payment and collection. Thank you!"
                        );
                    @endphp
                    <a href="https://wa.me/{{ $buyerWaNum }}?text={{ $invoiceMsg }}" target="_blank" rel="noopener"
                       class="btn bg-green-500 hover:bg-green-600 text-white inline-flex items-center gap-1.5">
                        {!! $waIcon !!}
                        Send via WhatsApp
                    </a>
                @endif
                <button onclick="window.print()" class="btn btn-outline btn-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print / Save PDF
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="print:hidden mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        @if(!$isConfirmed)
            <div class="print:hidden mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded text-sm flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>This lot is still awaiting seller confirmation — invoice is a draft preview until confirmed.</span>
            </div>
        @endif

        <div class="card p-6 md:p-10 print:shadow-none print:border-0 print:p-0">
            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.2em] text-gray-500">Invoice</div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ config('app.name', 'BidAll') }} Community
                    </h1>
                    @if($lot->auction?->communityRegion)
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $lot->auction->communityRegion->name }}</div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="font-mono text-sm text-gray-700 dark:text-gray-300">#CL-{{ str_pad($lot->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="text-sm text-gray-500">{{ now()->format('d M Y') }}</div>
                    <div class="mt-2">
                        @if($isConfirmed)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                Confirmed Sale
                            </span>
                        @else
                            <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-800 rounded">Pending Confirmation</span>
                        @endif
                        @if($lot->isPaidOffline())
                            <span class="ml-1 inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">
                                Paid &amp; collected
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Parties --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 mb-1.5">From (Seller)</div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $lot->seller?->name ?? '—' }}</div>
                    @if($lot->seller?->email)
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $lot->seller->email }}</div>
                    @endif
                    @if($lot->seller?->phone)
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $lot->seller->phone }}</div>
                    @endif
                    @if($isBuyer && $sellerWa)
                        <a href="https://wa.me/{{ $sellerWa }}?text={{ $waMsg }}" target="_blank" rel="noopener"
                           class="print:hidden mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-sm rounded">
                            {!! $waIcon !!}
                            WhatsApp seller
                        </a>
                    @endif
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 mb-1.5">Billed To (Buyer)</div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $lot->winningBidder?->name ?? '—' }}</div>
                    @if($lot->winningBidder?->email)
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $lot->winningBidder->email }}</div>
                    @endif
                    @if($lot->winningBidder?->phone)
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $lot->winningBidder->phone }}</div>
                    @endif
                    @if($isSeller && $buyerWa)
                        <a href="https://wa.me/{{ $buyerWa }}?text={{ $waMsg }}" target="_blank" rel="noopener"
                           class="print:hidden mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-sm rounded">
                            {!! $waIcon !!}
                            WhatsApp buyer
                        </a>
                    @endif
                </div>
            </div>

            {{-- Item --}}
            <div class="py-6 border-b border-gray-200 dark:border-gray-700">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 mb-3">Item</div>
                <div class="flex gap-4">
                    @if($lot->images->isNotEmpty())
                        <img src="{{ $lot->images->first()->thumbnail_url }}" alt="{{ $lot->title }}"
                             class="w-24 h-24 object-cover rounded shrink-0">
                    @else
                        <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center text-gray-400 shrink-0">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $lot->title }}</div>
                        <div class="text-xs text-gray-500">Lot #{{ $lot->lot_number }} &middot; {{ $lot->auction->title ?? 'Community auction' }}</div>
                        @if($lot->description)
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-3">{{ $lot->description }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Amount --}}
            <div class="py-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Winning bid</span>
                    <span class="font-mono text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->current_bid) }}</span>
                </div>
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">Amount Due</span>
                    <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->current_bid) }}</span>
                </div>
            </div>

            {{-- Handover --}}
            <div class="py-6 text-sm text-gray-600 dark:text-gray-400">
                <div class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Handover</div>
                <ul class="space-y-1.5 list-disc list-inside">
                    <li>Community sale — the buyer pays the seller directly and arranges collection.</li>
                    <li>A {{ number_format((float) config('community.platform_commission_percent', 10), 0) }}% platform commission applies to the seller.</li>
                    <li>Coordinate payment method and collection using the contact details above.</li>
                </ul>
            </div>
        </div>
    </div>

    <style>
        @media print {
            nav, header, footer, .print\:hidden { display: none !important; }
            body { background: white !important; }
        }
    </style>
</x-app-layout>
