<x-app-layout>
    <x-slot name="title">Pay with Lightning</x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6 sm:p-8 text-center">
                    {{-- Header --}}
                    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 2 4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">Pay with Lightning</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Scan the QR code or open it in your Lightning wallet.</p>

                    {{-- Amount --}}
                    <div class="mb-6">
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency($amountZar) }}</div>
                        @if($amountSats)
                            <div class="text-sm text-gray-500 dark:text-gray-400">≈ {{ number_format($amountSats) }} sats</div>
                        @endif
                    </div>

                    @if($invoice)
                        {{-- Server-rendered SVG QR of the BOLT11 invoice (simple-qrcode).
                             White box keeps it scannable in dark mode; the invoice never
                             leaves our server. --}}
                        <div class="flex justify-center mb-6">
                            <div id="ln-qr"
                                 class="w-60 h-60 bg-white rounded-xl flex items-center justify-center border border-gray-200 dark:border-gray-700 p-3">
                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(216)->margin(1)->errorCorrection('M')->generate('lightning:' . $invoice) !!}
                            </div>
                        </div>

                        {{-- Primary action: open in wallet --}}
                        <a href="lightning:{{ $invoice }}"
                           class="btn btn-primary btn-lg w-full flex items-center justify-center mb-3">
                            Open in Wallet
                        </a>

                        {{-- Secondary action: copy the raw invoice --}}
                        <button type="button"
                                onclick="copyInvoice()"
                                class="btn btn-outline w-full flex items-center justify-center mb-6">
                            <span id="copy-label">Copy Invoice</span>
                        </button>

                        {{-- Raw BOLT11 (selectable) --}}
                        <p id="ln-invoice" class="text-xs font-mono text-gray-500 dark:text-gray-400 break-all select-all mb-6">{{ $invoice }}</p>
                    @else
                        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-6 text-sm text-red-800 dark:text-red-200">
                            No Lightning invoice was generated. Please go back and try again.
                        </div>
                    @endif

                    {{-- Live status --}}
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 animate-pulse"></span>
                        <span id="ln-status">Waiting for payment…</span>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('payment.cancel', ['payment_id' => $paymentId]) }}"
                           class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Cancel payment</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyInvoice() {
            const inv = @json($invoice);
            if (!inv || !navigator.clipboard) return;
            navigator.clipboard.writeText(inv).then(function () {
                const label = document.getElementById('copy-label');
                const prev = label.textContent;
                label.textContent = 'Copied!';
                setTimeout(function () { label.textContent = prev; }, 1500);
            });
        }

        // Poll for payment confirmation (status flips when the Blink webhook lands),
        // then redirect to the standard result page.
        (function () {
            const statusUrl = @json(route('payment.lightning.status', ['paymentId' => $paymentId]));
            const poll = setInterval(function () {
                fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.paid) {
                            clearInterval(poll);
                            document.getElementById('ln-status').textContent = 'Payment received! Redirecting…';
                            window.location.href = data.redirect;
                        }
                    })
                    .catch(function () { /* transient error — keep polling */ });
            }, 4000);
        })();
    </script>
    @endpush
</x-app-layout>
