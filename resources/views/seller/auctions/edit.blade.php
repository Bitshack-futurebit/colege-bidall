<x-app-layout>
    <x-slot name="title">Edit Auction - {{ $auction->title }}</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('seller.auctions.show', $auction) }}" class="text-primary-600 hover:text-primary-700 flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Auction
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Edit Auction
                    @if($auction->isDutch())
                        <span class="text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-1 rounded ml-2">Dutch</span>
                    @elseif($auction->isSealed())
                        <span class="text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-2 py-1 rounded ml-2">Sealed ({{ ucfirst($auction->sealed_mode) }} Wins)</span>
                    @elseif($auction->isLiveFormat())
                        <span class="text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-2 py-1 rounded ml-2">Live (Automated)</span>
                    @endif
                </h1>
            </div>

            @if($auction->status !== 'draft' && $auction->status !== 'upcoming')
                <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                    <p class="text-yellow-800 dark:text-yellow-200">
                        <strong>Note:</strong> This auction is {{ $auction->status }}. Some fields cannot be edited.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('seller.auctions.update', $auction) }}" class="space-y-6">
                @csrf
                @method('PUT')

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
                                       value="{{ old('title', $auction->title) }}"
                                       required
                                       class="input @error('title') input-error @enderror">
                                @error('title')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="description" class="label">Description</label>
                                <textarea id="description"
                                          name="description"
                                          rows="4"
                                          class="input @error('description') input-error @enderror">{{ old('description', $auction->description) }}</textarea>
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
                                           value="{{ old('start_time', $auction->start_time->format('Y-m-d\TH:i')) }}"
                                           required
                                           {{ $auction->status === 'live' || $auction->status === 'ended' ? 'disabled' : '' }}
                                           class="input @error('start_time') input-error @enderror">
                                    @error('start_time')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                @if(!$auction->isDutch() && !$auction->isLiveFormat())
                                <div>
                                    <label for="end_time" class="label">End Date & Time *</label>
                                    <input id="end_time"
                                           type="datetime-local"
                                           name="end_time"
                                           value="{{ old('end_time', $auction->end_time ? $auction->end_time->format('Y-m-d\TH:i') : '') }}"
                                           required
                                           {{ $auction->status === 'ended' ? 'disabled' : '' }}
                                           class="input @error('end_time') input-error @enderror">
                                    @error('end_time')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                                @elseif($auction->isDutch())
                                <div>
                                    <p class="label">End Time</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Auto-calculated from lot pricing and drop settings</p>
                                    @if($auction->end_time)
                                        <p class="text-sm text-gray-900 dark:text-gray-100 mt-1 font-medium">{{ $auction->end_time->format('d M Y H:i') }}</p>
                                    @endif
                                </div>
                                @elseif($auction->isLiveFormat())
                                <div>
                                    <p class="label">End Time</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Calculated as the auction plays — depends on actual lot cadence</p>
                                </div>
                                @endif
                            </div>

                            @if($auction->status === 'live' || $auction->status === 'ended')
                                <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        Auction timing cannot be changed after the auction has started.
                                    </p>
                                </div>
                            @elseif($auction->isDutch())
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                                    <div class="text-sm text-amber-800 dark:text-amber-200 space-y-2">
                                        <p>Lots run one after another. Each lot's duration is auto-calculated from its drop settings, with a {{ $auction->dutch_lot_gap }}s gap between lots.</p>
                                    </div>
                                </div>
                            @elseif($auction->isSealed())
                                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
                                    <div class="text-sm text-purple-800 dark:text-purple-200 space-y-2">
                                        <p><strong>Sealed auction ({{ ucfirst($auction->sealed_mode) }} bid wins):</strong> All lots open simultaneously and close together. Bids are secret until the auction ends.</p>
                                    </div>
                                </div>
                            @else
                                <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                                        <p>
                                            <strong>Maximum Duration:</strong> Auctions cannot exceed 12 hours
                                        </p>
                                        <p>
                                            <strong>Lot Timing:</strong> All lots start bidding at the auction start time. Individual lots close {{ config('platform.auction.lot_gap_seconds') }} seconds apart, starting from the end time.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Auction Settings -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Auction Settings</h2>

                        <div class="space-y-4">
                            <!-- Hidden deposit_type field (required by validation) -->
                            <input type="hidden" name="deposit_type" id="deposit_type" value="{{ old('deposit_type', $auction->deposit_type ?? 'none') }}">

                            @if(config('regional.features.deposits'))
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="requires_deposit"
                                               value="1"
                                               {{ old('requires_deposit', $auction->requiresDeposit()) ? 'checked' : '' }}
                                               onchange="
                                                   document.getElementById('deposit_amount_field').style.display = this.checked ? 'block' : 'none';
                                                   document.getElementById('deposit_type').value = this.checked ? 'refundable' : 'none';
                                                   if (!this.checked) document.getElementById('deposit_amount').value = '';
                                               ">
                                        <span class="text-gray-700 dark:text-gray-300">Require deposit to register</span>
                                    </label>
                                </div>

                                <div id="deposit_amount_field" style="display: {{ old('requires_deposit', $auction->requiresDeposit()) ? 'block' : 'none' }}">
                                    <label for="deposit_amount" class="label">Deposit Amount</label>
                                    <input id="deposit_amount"
                                           type="number"
                                           name="deposit_amount"
                                           value="{{ old('deposit_amount', $auction->deposit_amount) }}"
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
                                           {{ old('requires_registration', $auction->requires_registration ?? true) ? 'checked' : '' }}>
                                    <span class="text-gray-700 dark:text-gray-300">Require registration to bid</span>
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                    If unchecked, any logged-in user can bid without registering for the auction first
                                </p>
                            </div>

                            <!-- Proxy Bidding (English + Live only) -->
                            @if($auction->isEnglish() || $auction->isLiveFormat())
                            <div>
                                <label class="flex items-center gap-3">
                                    <input type="checkbox"
                                           name="allow_proxy_bidding"
                                           value="1"
                                           {{ old('allow_proxy_bidding', $auction->allow_proxy_bidding ?? false) ? 'checked' : '' }}>
                                    <span class="text-gray-700 dark:text-gray-300">Allow proxy bidding (max bid)</span>
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-8">
                                    Bidders can set a maximum bid and the platform will auto-bid on their behalf
                                </p>
                            </div>
                            @endif

                            @if(config('regional.features.buyers_premium'))
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="has_buyers_premium"
                                               value="1"
                                               {{ old('has_buyers_premium', $auction->buyers_premium_percentage > 0) ? 'checked' : '' }}
                                               onchange="
                                                   document.getElementById('buyers_premium_field').style.display = this.checked ? 'block' : 'none';
                                                   if (!this.checked) document.getElementById('buyers_premium_percentage').value = '';
                                               ">
                                        <span class="text-gray-700 dark:text-gray-300">Add buyer's premium</span>
                                    </label>
                                </div>

                                <div id="buyers_premium_field" style="display: {{ old('has_buyers_premium', $auction->buyers_premium_percentage > 0) ? 'block' : 'none' }}">
                                    <label for="buyers_premium_percentage" class="label">Buyer's Premium (%)</label>
                                    <input id="buyers_premium_percentage"
                                           type="number"
                                           name="buyers_premium_percentage"
                                           value="{{ old('buyers_premium_percentage', $auction->buyers_premium_percentage) }}"
                                           step="0.1"
                                           min="0"
                                           max="100"
                                           class="input @error('buyers_premium_percentage') input-error @enderror">
                                    @error('buyers_premium_percentage')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            @endif

                            @if($auction->auctioneer->hasPayfastConfigured())
                                <div>
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox"
                                               name="enable_online_payment"
                                               value="1"
                                               {{ old('enable_online_payment', $auction->enable_online_payment) ? 'checked' : '' }}>
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
                        </div>
                    </div>
                </div>

                @if($auction->isDutch())
                <!-- Dutch Auction Settings -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Dutch Auction Settings</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="dutch_lot_gap" class="label">Gap Between Lots (seconds) *</label>
                                <input id="dutch_lot_gap"
                                       type="number"
                                       name="dutch_lot_gap"
                                       value="{{ old('dutch_lot_gap', $auction->dutch_lot_gap) }}"
                                       min="0"
                                       required
                                       class="input @error('dutch_lot_gap') input-error @enderror">
                                @error('dutch_lot_gap')<p class="error-message">{{ $message }}</p>@enderror
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Pause between sequential lots</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <a href="{{ route('seller.auctions.show', $auction) }}" class="btn btn-outline flex-1">Cancel</a>
                    <button type="submit" class="btn btn-primary flex-1">
                        Update Auction
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
