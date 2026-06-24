<x-app-layout>
    <x-slot name="title">Confirm Sale — {{ $lot->title }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 py-6">
        <div class="mb-3">
            <a href="{{ route('community.my-lots') }}" class="text-sm text-gray-500 hover:text-primary-600">&larr; My Lots</a>
        </div>

        <div class="flex items-center gap-2 mb-1">
            <h1 class="text-2xl font-bold">Confirm Sale</h1>
            <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded uppercase tracking-wide">Awaiting you</span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            Your lot closed. Accept the winning bid, or use lowball protection to decline.
        </p>

        {{-- Lot card with image + headline amount --}}
        <div class="card overflow-hidden mb-5">
            <div class="flex flex-col sm:flex-row gap-4 p-5">
                @if($lot->images?->isNotEmpty())
                    <img src="{{ Storage::url($lot->images->first()->thumbnail_path) }}"
                         alt="{{ $lot->title }}"
                         class="w-full sm:w-40 h-40 object-cover rounded shrink-0">
                @else
                    <div class="w-full sm:w-40 h-40 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center text-gray-400 shrink-0">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h2 class="font-semibold text-lg">{{ $lot->title }}</h2>
                    <div class="text-xs text-gray-500 mt-0.5">Lot #{{ $lot->lot_number ?? '—' }}</div>
                    @if($lot->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-3">{{ $lot->description }}</p>
                    @endif
                </div>
            </div>

            <div class="p-5 bg-green-50 dark:bg-green-900/20 border-t border-green-200 dark:border-green-800 text-center">
                <div class="text-xs uppercase tracking-wide text-green-800 dark:text-green-300">Winning bid</div>
                <div class="text-4xl font-bold text-green-700 dark:text-green-400 mt-1">R{{ number_format($winningBid, 0) }}</div>
                @if($lot->confirmation_expires_at)
                    <div class="mt-3 inline-flex items-center gap-1 px-3 py-1 bg-white/60 dark:bg-black/20 rounded text-xs text-amber-800 dark:text-amber-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Decide by {{ $lot->confirmation_expires_at->format('d M H:i') }}
                        ({{ $lot->confirmation_expires_at->diffForHumans() }})
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Auto-confirmed if no response.</div>
                @endif
            </div>
        </div>

        {{-- Decline status --}}
        <div class="card p-4 mb-5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Declines used (30d)</div>
                    <div class="mt-0.5">
                        <span class="text-2xl font-bold">{{ $declinesUsed }}</span>
                        <span class="text-gray-400">/ {{ $declineLimit }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Mode</div>
                    <div class="text-lg font-semibold mt-0.5">{{ $pilotMode ? 'Pilot (relaxed)' : 'Standard' }}</div>
                </div>
            </div>
            @if($firstLotProtected)
                <div class="mt-3 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded text-xs text-amber-800 dark:text-amber-300">
                    <strong>First lot protection active.</strong> If you decline this lot, no penalty will apply.
                </div>
            @elseif($declinesUsed >= $declineLimit)
                <div class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-700 dark:text-red-300">
                    You've used your decline allowance this month. Declining this lot will trigger a 30-day suspension.
                </div>
            @endif
        </div>

        {{-- Decision cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {{-- Accept --}}
            <form method="POST" action="{{ route('community.confirm-accept', $lot) }}" class="card p-5 flex flex-col">
                @csrf
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <h3 class="font-bold text-green-700 dark:text-green-400">Accept</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 flex-1">
                    Confirm the sale. The buyer will coordinate collection with you directly.
                </p>
                <button type="submit" class="btn btn-primary w-full">Accept R{{ number_format($winningBid, 0) }}</button>
            </form>

            {{-- Decline --}}
            <form method="POST" action="{{ route('community.confirm-decline', $lot) }}" class="card p-5"
                  onsubmit="return confirm('Are you sure? This uses your lowball protection and counts against your decline history.');">
                @csrf
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <h3 class="font-bold text-red-700 dark:text-red-400">Decline</h3>
                    <span class="text-[10px] bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 px-1.5 py-0.5 rounded uppercase tracking-wide">Lowball protection</span>
                </div>
                <label class="block text-xs text-gray-500 mb-1">Reason (required, shared with buyer)</label>
                <textarea name="reason" required minlength="10" maxlength="500" rows="3"
                          class="input text-sm mb-3"
                          placeholder="e.g. The final bid is significantly below what I could get at a pawnshop."></textarea>
                <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white w-full">Decline</button>
            </form>
        </div>
    </div>
</x-app-layout>
