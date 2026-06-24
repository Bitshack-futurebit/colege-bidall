<x-app-layout>
    <x-slot name="title">Buyer Strikes</x-slot>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Buyer non-payment strikes</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Auto-disabled buyers panel --}}
        @if($disabledBuyers->isNotEmpty())
            <div class="card p-5 mb-5 bg-red-50 dark:bg-red-900/10 border-l-4 border-red-500">
                <h2 class="text-lg font-bold text-red-900 dark:text-red-200 mb-2">
                    Currently auto-disabled ({{ $disabledBuyers->count() }})
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                    These buyers can't bid until enough strikes are reversed.
                </p>
                <div class="space-y-1.5">
                    @foreach($disabledBuyers as $buyer)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-semibold">{{ $buyer->name }}</span>
                                <span class="text-xs text-gray-500">· {{ $buyer->email }}</span>
                            </div>
                            <div class="text-xs text-red-700 dark:text-red-300">
                                {{ $buyer->bidding_disabled_reason }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Filter tabs --}}
        <div class="flex gap-1 mb-5 border-b border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.buyer-strikes.index', ['filter' => 'active']) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $filter === 'active' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-600 hover:text-red-600' }}">
                Active
                <span class="ml-1 text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ $counts['active'] }}</span>
            </a>
            <a href="{{ route('admin.buyer-strikes.index', ['filter' => 'reversed']) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $filter === 'reversed' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-600 hover:text-primary-600' }}">
                Reversed
                <span class="ml-1 text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ $counts['reversed'] }}</span>
            </a>
            <a href="{{ route('admin.buyer-strikes.index', ['filter' => 'all']) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $filter === 'all' ? 'border-gray-700 text-gray-700' : 'border-transparent text-gray-600 hover:text-gray-700' }}">
                All
            </a>
        </div>

        @if($strikes->count() === 0)
            <div class="card p-8 text-center text-gray-500">
                No {{ $filter === 'all' ? '' : $filter }} strikes.
            </div>
        @else
            <div class="space-y-3">
                @foreach($strikes as $strike)
                    @php $isActive = $strike->reversed_at === null; @endphp
                    <div class="card p-4">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    @if($isActive)
                                        <span class="px-2 py-0.5 text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded uppercase tracking-wide">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 rounded uppercase tracking-wide">Reversed</span>
                                    @endif
                                    <span class="font-semibold">{{ $strike->user?->name ?? 'Buyer #' . $strike->user_id }}</span>
                                    <span class="text-xs text-gray-500">on lot</span>
                                    <span class="font-medium">{{ $strike->lot?->title ?? '#' . $strike->lot_id }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mb-2">
                                    Reported by {{ $strike->seller?->name ?? '?' }} ·
                                    {{ $strike->reported_at->diffForHumans() }} ({{ $strike->reported_at->format('d M Y') }})
                                </div>
                                @if($strike->reason)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                        <span class="text-gray-500 text-xs">Reason:</span> {{ $strike->reason }}
                                    </div>
                                @endif
                                @if($strike->reversed_at)
                                    <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-800/50 rounded text-xs">
                                        <span class="text-gray-500">Reversed</span>
                                        {{ $strike->reversed_at->diffForHumans() }}
                                        @if($strike->reversal_note) — {{ $strike->reversal_note }}@endif
                                    </div>
                                @endif
                            </div>

                            @if($isActive)
                                <form method="POST" action="{{ route('admin.buyer-strikes.reverse', $strike) }}"
                                      x-data="{ open: false }" class="shrink-0">
                                    @csrf
                                    <button type="button" @click="open = true" x-show="!open"
                                            class="btn btn-outline text-sm">Reverse</button>
                                    <div x-show="open" x-cloak class="flex flex-col sm:flex-row gap-2 items-stretch">
                                        <input type="text" name="reversal_note" required minlength="5" maxlength="500"
                                               class="input text-sm" placeholder="Reversal reason (required)">
                                        <button type="submit" class="btn btn-primary text-sm whitespace-nowrap"
                                                onclick="return confirm('Reverse this strike? If the buyer drops below the threshold, their bidding will be auto-restored.');">Confirm</button>
                                        <button type="button" @click="open = false" class="btn btn-outline text-sm">Cancel</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $strikes->withQueryString()->links() }}</div>
        @endif
    </div>
</x-app-layout>
