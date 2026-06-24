<x-app-layout>
    <x-slot name="title">List an Item — {{ $region->name }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 py-6">
        <div class="mb-3">
            <a href="{{ route('community.region', $region) }}" class="text-sm text-gray-500 hover:text-primary-600">
                &larr; Back to {{ $region->name }}
            </a>
        </div>

        <h1 class="text-2xl font-bold mb-1">List an Item</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            Goes into: <strong>{{ $auction->title }}</strong>
            @if($auction->goes_live_at)
                &middot; live {{ $auction->goes_live_at->format('D, d M \a\t H:i') }}
            @endif
        </p>

        {{-- Benchmark + quota (compact row) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div class="card p-3 md:col-span-2 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400">
                <div class="text-xs font-semibold text-blue-900 dark:text-blue-200 uppercase tracking-wide mb-1">How this works</div>
                <p class="text-sm text-blue-900 dark:text-blue-200">
                    Every lot starts at <strong>R{{ $startingBid }}</strong> — no reserves, the market sets the price.
                    Pawnshops offer 15–25%; community auctions tend to realise 40–60%.
                    Platform takes {{ number_format($commissionPercent, 0) }}% on confirmed sales only.
                </p>
            </div>
            <div class="card p-4 text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wide">Your quota</div>
                <div class="text-2xl font-bold mt-1">{{ $lotsThisWeek }}<span class="text-gray-400 text-base">/{{ $weeklyLimit }}</span></div>
                <div class="text-xs text-gray-500">
                    {{ max(0, $weeklyLimit - $lotsThisWeek) }} more this week
                </div>
            </div>
        </div>

        {{-- Viability gate (region-specific) --}}
        @php
            $minLots    = (int) ($region->min_lots_for_viability ?? config('community.min_lots_for_viability', 5));
            $minBidders = (int) ($region->min_bidders_for_viability ?? config('community.min_bidders_for_viability', 20));
            $currentLots    = $auction->lots->count();
            $currentBidders = $region->bidderCount();
            $lotsOk    = $currentLots >= $minLots;
            $biddersOk = $currentBidders >= $minBidders;
            $bothOk    = $lotsOk && $biddersOk;
        @endphp
        <div class="card p-4 mb-5 border-l-4 {{ $bothOk ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' }}">
            <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide mb-1 {{ $bothOk ? 'text-green-900 dark:text-green-200' : 'text-amber-900 dark:text-amber-200' }}">
                        {{ $bothOk ? 'On track to go live' : 'Auction needs critical mass to go live' }}
                    </div>
                    <p class="text-sm {{ $bothOk ? 'text-green-900 dark:text-green-200' : 'text-amber-900 dark:text-amber-200' }}">
                        Needs at least <strong>{{ $minLots }} lots</strong> and <strong>{{ $minBidders }} active bidders</strong> in {{ $region->name }}.
                        Below either, all lots roll to next week — no charge, no harm.
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <div class="text-center px-3 py-1.5 rounded bg-white/60 dark:bg-black/20">
                        <div class="text-[10px] uppercase text-gray-500">Lots</div>
                        <div class="font-bold {{ $lotsOk ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }}">
                            {{ $currentLots }}<span class="text-gray-400">/{{ $minLots }}</span>
                        </div>
                    </div>
                    <div class="text-center px-3 py-1.5 rounded bg-white/60 dark:bg-black/20">
                        <div class="text-[10px] uppercase text-gray-500">Bidders</div>
                        <div class="font-bold {{ $biddersOk ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }}">
                            {{ $currentBidders }}<span class="text-gray-400">/{{ $minBidders }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('community.store-lot') }}" enctype="multipart/form-data"
              class="card p-5 md:p-6 space-y-5"
              x-data="communityLotForm()">
            @csrf

            <div>
                <label class="label">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="120"
                       class="input w-full" placeholder="e.g. Bosch drill with case">
                <p class="text-xs text-gray-500 mt-1">Short, specific titles perform best.</p>
            </div>

            <div>
                <label class="label">Description <span class="text-xs text-gray-400 font-normal">optional</span></label>
                <textarea name="description" rows="3" maxlength="4000"
                          class="input w-full"
                          placeholder="Condition, size, any flaws... (optional but helps get higher bids)">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="label">Photos <span class="text-red-500">*</span></label>

                <input id="community-images-input"
                       type="file"
                       name="images[]"
                       multiple
                       accept="image/*"
                       class="hidden"
                       @change="onFiles($event)">

                {{-- Drop / select zone --}}
                <div x-show="files.length === 0"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-primary-400 transition cursor-pointer"
                     @click="document.getElementById('community-images-input').click()">
                    <svg class="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Tap to select photos</p>
                    <p class="text-xs text-gray-500">At least 1 photo, up to 10. Clear, well-lit photos dramatically affect final price.</p>
                </div>

                <div x-show="files.length > 0" x-cloak>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            <strong x-text="files.length"></strong> of 10 selected
                        </span>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-outline btn-sm"
                                    @click="document.getElementById('community-images-input').click()">+ Add more</button>
                            <button type="button" class="btn btn-sm bg-red-50 text-red-700 hover:bg-red-100"
                                    @click="clearAll()">Clear all</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <template x-for="(f, i) in files" :key="f.id">
                            <div class="relative aspect-square bg-gray-100 dark:bg-gray-800 rounded overflow-hidden border border-gray-200 dark:border-gray-700 group">
                                <img :src="f.preview" :alt="f.name" class="w-full h-full object-cover">
                                <button type="button"
                                        @click="removeAt(i)"
                                        class="absolute top-1 right-1 w-6 h-6 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity"
                                        title="Remove">×</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 p-4 rounded text-xs text-gray-700 dark:text-gray-300">
                <div class="font-semibold text-gray-800 dark:text-gray-200 mb-1.5">Before you list</div>
                <ul class="list-disc list-inside space-y-1">
                    <li>Starting bid is fixed at R{{ $startingBid }} (no reserves).</li>
                    <li>Winning bid comes to you for accept/decline within 24 hours of close.</li>
                    <li>Repeated declines lead to listing suspension — use your "lowball protection" carefully.</li>
                    <li>Buyer pays you directly on collection. No platform money-handling.</li>
                </ul>
            </div>

            <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-2 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('community.region', $region) }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"
                        :disabled="files.length === 0"
                        :class="{ 'opacity-50 cursor-not-allowed': files.length === 0 }">
                    List this item
                </button>
            </div>
        </form>
    </div>

    <script>
        function communityLotForm() {
            return {
                files: [],
                _nextId: 1,
                onFiles(event) {
                    const incoming = Array.from(event.target.files || []);
                    const room = 10 - this.files.length;
                    const accept = incoming.slice(0, Math.max(0, room));
                    accept.forEach(file => {
                        const reader = new FileReader();
                        const id = this._nextId++;
                        this.files.push({ id, file, name: file.name, preview: '' });
                        reader.onload = e => {
                            const entry = this.files.find(f => f.id === id);
                            if (entry) entry.preview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                    event.target.value = '';
                    this._syncInput();
                },
                removeAt(index) {
                    this.files.splice(index, 1);
                    this._syncInput();
                },
                clearAll() {
                    this.files = [];
                    this._syncInput();
                },
                _syncInput() {
                    const input = document.getElementById('community-images-input');
                    const dt = new DataTransfer();
                    this.files.forEach(f => dt.items.add(f.file));
                    input.files = dt.files;
                },
            };
        }
    </script>
</x-app-layout>
