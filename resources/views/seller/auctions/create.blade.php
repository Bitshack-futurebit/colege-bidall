<x-app-layout>
    <x-slot name="title">Create Auction</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('seller.dashboard') }}" class="btn btn-outline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create New Auction</h1>
            </div>

            <form id="auction-create-form" method="POST" action="{{ route('seller.auctions.store') }}" class="space-y-6" x-data="auctionForm()">
                @csrf

                <!-- Auction Type -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Auction Type</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="auction_type" value="english" x-model="auctionType" class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">Standard Auction</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Price goes up. Highest bid wins at closing time.</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="auction_type" value="live" x-model="auctionType" class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-red-600 peer-checked:bg-red-50 dark:peer-checked:bg-red-900 border-gray-300 dark:border-gray-600 hover:border-red-400">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-bold uppercase tracking-wide">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            Live
                                        </span>
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100 mt-1">Live (Automated)</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Lots run one at a time. "Going once, going twice, sold" cadence.</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="auction_type" value="dutch" x-model="auctionType" class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">Dutch Auction</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Price drops over time. First to buy wins.</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="auction_type" value="sealed" x-model="auctionType" class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">Sealed Auction</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Secret bids. Winner revealed after close.</div>
                                </div>
                            </label>
                        </div>
                        @error('auction_type')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Dutch Auction Settings -->
                <div class="card" x-show="auctionType === 'dutch'" x-transition>
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Dutch Auction Settings</h2>

                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Lots will run one at a time in order. Each lot starts after the previous one ends.</p>

                            <!-- Gap Between Lots -->
                            <div>
                                <div>
                                    <label for="dutch_lot_gap" class="label">Gap Between Lots</label>
                                    <select id="dutch_lot_gap"
                                            name="dutch_lot_gap"
                                            class="input @error('dutch_lot_gap') input-error @enderror">
                                        <option value="30" {{ old('dutch_lot_gap', '60') == '30' ? 'selected' : '' }}>30 seconds</option>
                                        <option value="60" {{ old('dutch_lot_gap', '60') == '60' ? 'selected' : '' }}>1 minute</option>
                                        <option value="120" {{ old('dutch_lot_gap') == '120' ? 'selected' : '' }}>2 minutes</option>
                                        <option value="300" {{ old('dutch_lot_gap') == '300' ? 'selected' : '' }}>5 minutes</option>
                                    </select>
                                    @error('dutch_lot_gap')<p class="error-message">{{ $message }}</p>@enderror
                                    <p class="text-xs text-gray-500 mt-1">Pause between one lot closing and the next starting</p>
                                </div>

                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                                    <p><strong>How Dutch auctions work:</strong></p>
                                    <p>Each lot starts at a high price and drops by the drop amount at each interval. The first bidder to click "Buy Now" wins the lot at the current price. If nobody buys before the floor price, the lot remains unsold.</p>
                                    <p><strong>End time:</strong> Auto-calculated from lot pricing and drop settings when the auction goes live.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sealed Auction Settings -->
                <div class="card" x-show="auctionType === 'sealed'" x-transition>
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Sealed Auction Settings</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="label">Winner Selection *</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="sealed_mode" value="highest" x-model="sealedMode" class="sr-only peer">
                                        <div class="p-3 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Highest Bid Wins</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Traditional sealed bid. Highest bidder wins and pays their bid amount.</div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer">
                                        <input type="radio" name="sealed_mode" value="lowest" x-model="sealedMode" class="sr-only peer">
                                        <div class="p-3 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                            <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Lowest Bid Wins</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Reverse sealed bid. Lowest bidder wins. Great for procurement or tenders.</div>
                                        </div>
                                    </label>
                                </div>
                                @error('sealed_mode')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                                    <p><strong>How sealed auctions work:</strong></p>
                                    <p>Bidders place secret bids that no one else can see — not even the auctioneer. Each bidder can update their bid until the auction closes. After closing, the winner is revealed automatically.</p>
                                    <p><strong>Requirements:</strong> Each lot needs bids from at least 2 different bidders to be a valid auction. Lots with fewer than 2 bidders will be marked as unsold.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live (Automated) Auction Info -->
                <div class="card" x-show="auctionType === 'live'" x-transition>
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Live (Automated) Auction</h2>

                        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 space-y-3 text-sm text-red-900 dark:text-red-200">
                            <p><strong>Simulates a real live auction:</strong> lots run one after another, with the platform playing the role of the auctioneer.</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Each lot is <strong>presented</strong> for 10 seconds, then the floor opens with a "Who'll open?" call.</li>
                                <li>If no opening bid in <strong>30 seconds</strong>, the lot closes immediately as no-interest — skipping the going routine entirely.</li>
                                <li>Once bidding starts, each new bid keeps the floor open. <strong>8 seconds of silence</strong> triggers "Going once" (15s).</li>
                                <li>"Going twice" follows for 10s — then <strong>Sold</strong> or <strong>Gone</strong>.</li>
                                <li>Any bid during the going pulse drops back to active bidding with a fresh 8-second silence timer.</li>
                                <li>Validation rule: each lot needs <strong>at least 2 distinct bidders</strong>, unless a single bidder has already passed the reserve.</li>
                                <li>A 20-second intermission plays before the next lot starts.</li>
                            </ol>
                            <p class="pt-2"><strong>End time:</strong> calculated dynamically — depends on how long each lot actually runs. Proxy bidding is allowed.</p>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Basic Information</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="title" class="label">Auction Title *</label>
                                <input id="title"
                                       type="text"
                                       name="title"
                                       value="{{ old('title') }}"
                                       required
                                       class="input @error('title') input-error @enderror"
                                       placeholder="e.g., Monthly Antique Auction">
                                @error('title')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="description" class="label">Description</label>
                                <textarea id="description"
                                          name="description"
                                          rows="4"
                                          class="input @error('description') input-error @enderror"
                                          placeholder="Describe your auction...">{{ old('description') }}</textarea>
                                @error('description')<p class="error-message">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timing -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Timing</h2>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="start_time" class="label">Start Date & Time *</label>
                                    <input id="start_time"
                                           type="datetime-local"
                                           name="start_time"
                                           value="{{ old('start_time') }}"
                                           required
                                           class="input @error('start_time') input-error @enderror">
                                    @error('start_time')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                <div x-show="auctionType !== 'dutch' && auctionType !== 'live'">
                                    <label for="end_time" class="label">End Date & Time *</label>
                                    <input id="end_time"
                                           type="datetime-local"
                                           name="end_time"
                                           value="{{ old('end_time') }}"
                                           :required="auctionType !== 'dutch' && auctionType !== 'live'"
                                           class="input @error('end_time') input-error @enderror">
                                    @error('end_time')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                <div x-show="auctionType === 'dutch'">
                                    <p class="label">End Time</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Auto-calculated from lot pricing and drop settings when the auction goes live</p>
                                </div>

                                <div x-show="auctionType === 'live'">
                                    <p class="label">End Time</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Determined automatically — auction ends when the final lot closes</p>
                                </div>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4" x-show="auctionType === 'english'">
                                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                                    <p>
                                        <strong>Maximum Duration:</strong> Auctions cannot exceed 12 hours
                                    </p>
                                    <p>
                                        <strong>Lot Timing:</strong> All lots start bidding at the auction start time. Individual lots close {{ config('platform.auction.lot_gap_seconds') }} seconds apart, starting from the end time.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4" x-show="auctionType === 'sealed'">
                                <div class="text-sm text-purple-800 dark:text-purple-200 space-y-2">
                                    <p>
                                        <strong>Flexible Duration:</strong> Set any duration that suits your auction — no time limit
                                    </p>
                                    <p>
                                        <strong>Lot Timing:</strong> All lots open simultaneously at the start time and close together at the end time. No staggered closing.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4" x-show="auctionType === 'live'">
                                <div class="text-sm text-red-800 dark:text-red-200 space-y-2">
                                    <p>
                                        <strong>No End Time:</strong> Lots run one after another — the auction ends when the last lot closes.
                                    </p>
                                    <p>
                                        <strong>Lot Cadence:</strong> 10s presentation → 30s "Who'll open?" call → live bidding (8s silence triggers Going once) → "Going once/twice" → sold or unsold → 20s intermission before the next lot.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auction Settings -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Auction Settings</h2>

                        <div class="space-y-4">
                            <!-- Hidden deposit_type field (required by validation) -->
                            <input type="hidden" name="deposit_type" id="deposit_type" value="{{ old('deposit_type', 'none') }}">

                            @if(config('regional.features.deposits'))
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="requires_deposit"
                                               value="1"
                                               {{ old('requires_deposit') ? 'checked' : '' }}
                                               onchange="
                                                   document.getElementById('deposit_amount_field').style.display = this.checked ? 'block' : 'none';
                                                   document.getElementById('deposit_type').value = this.checked ? 'refundable' : 'none';
                                                   if (!this.checked) document.getElementById('deposit_amount').value = '';
                                               ">
                                        <span class="text-gray-700 dark:text-gray-300">Require deposit to register</span>
                                    </label>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">Bidders must pay a deposit before they can bid</p>
                                </div>

                                <div id="deposit_amount_field" style="display: {{ old('requires_deposit') ? 'block' : 'none' }}">
                                    <label for="deposit_amount" class="label">Deposit Amount</label>
                                    <input id="deposit_amount"
                                           type="number"
                                           name="deposit_amount"
                                           value="{{ old('deposit_amount') }}"
                                           step="0.01"
                                           min="0"
                                           class="input @error('deposit_amount') input-error @enderror">
                                    @error('deposit_amount')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            @else
                                <!-- If deposits feature is disabled, always set to none -->
                                <input type="hidden" name="deposit_type" value="none">
                            @endif

                            <!-- Registration Requirement -->
                            <div>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox"
                                           name="requires_registration"
                                           value="1"
                                           {{ old('requires_registration', false) ? 'checked' : '' }}>
                                    <span class="text-gray-700 dark:text-gray-300">Require registration to bid</span>
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                    If unchecked, any logged-in user can bid without registering for the auction first
                                </p>
                            </div>

                            <!-- Proxy Bidding (English + Live only) -->
                            <div x-show="auctionType === 'english' || auctionType === 'live'">
                                <label class="flex items-center gap-3">
                                    <input type="checkbox"
                                           name="allow_proxy_bidding"
                                           value="1"
                                           {{ old('allow_proxy_bidding', false) ? 'checked' : '' }}>
                                    <span class="text-gray-700 dark:text-gray-300">Allow proxy bidding (max bid)</span>
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                    Bidders can set a maximum bid and the platform will auto-bid on their behalf
                                </p>
                            </div>

                            @if(config('regional.features.buyers_premium'))
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="has_buyers_premium"
                                               value="1"
                                               {{ old('has_buyers_premium') ? 'checked' : '' }}
                                               onchange="
                                                   document.getElementById('buyers_premium_field').style.display = this.checked ? 'block' : 'none';
                                                   if (!this.checked) document.getElementById('buyers_premium_percentage').value = '';
                                               ">
                                        <span class="text-gray-700 dark:text-gray-300">Add buyer's premium</span>
                                    </label>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">Percentage added to winning bids</p>
                                </div>

                                <div id="buyers_premium_field" style="display: {{ old('has_buyers_premium') ? 'block' : 'none' }}">
                                    <label for="buyers_premium_percentage" class="label">Buyer's Premium (%)</label>
                                    <input id="buyers_premium_percentage"
                                           type="number"
                                           name="buyers_premium_percentage"
                                           value="{{ old('buyers_premium_percentage') }}"
                                           step="0.1"
                                           min="0"
                                           max="100"
                                           class="input @error('buyers_premium_percentage') input-error @enderror">
                                    @error('buyers_premium_percentage')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            @endif

                            @if(auth()->user()->auctioneer->hasPayfastConfigured())
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="enable_online_payment"
                                               value="1"
                                               {{ old('enable_online_payment') ? 'checked' : '' }}>
                                        <span class="text-gray-700 dark:text-gray-300">Enable online payment via PayFast</span>
                                    </label>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                        Winners can pay directly to your PayFast account
                                    </p>
                                </div>
                            @else
                                <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <a href="{{ route('seller.profile') }}" class="text-primary-600 hover:underline">Configure PayFast credentials</a>
                                    in your profile to enable online payments for your auctions.
                                </div>
                            @endif

                            <div>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="auto_start" value="1" {{ old('auto_start', true) ? 'checked' : '' }}>
                                    <span class="text-gray-700 dark:text-gray-300">Automatically start auction at scheduled time</span>
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">If unchecked, you'll need to manually start the auction</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button type="submit" name="status" value="draft" class="btn btn-primary flex-1">
                        Save as Draft
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                    After creating your auction, you'll be able to add lots and then publish
                </p>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function auctionForm() {
        return {
            auctionType: '{{ old('auction_type', 'english') }}',
            sealedMode: '{{ old('sealed_mode', 'highest') }}',
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.setupFormAutosave === 'function') {
            window.setupFormAutosave(document.getElementById('auction-create-form'), 'auction-create');
        }
    });
    </script>
    @endpush
</x-app-layout>
