<x-app-layout>
    <x-slot name="title">Edit Lot - {{ $lot->title }}</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('seller.auctions.show', $lot->auction) }}" class="text-primary-600 hover:text-primary-700 flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Auction
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Edit Lot #{{ $lot->lot_number }}</h1>
            </div>

            @if($auction->status === 'upcoming')
                <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <div>
                            <p class="font-semibold text-amber-800 dark:text-amber-200">Auction Scheduled — Lot Locked</p>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">This auction is scheduled to go live. Lot details can no longer be edited. You may only withdraw the lot.</p>
                        </div>
                    </div>
                </div>

                <!-- Read-only lot summary -->
                <div class="card mb-6">
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Title</p>
                            <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $lot->title }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Description</p>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $lot->description }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Starting Bid</p>
                                <p class="text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->starting_bid) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Reserve</p>
                                <p class="text-gray-900 dark:text-gray-100">{{ $lot->reserve_price ? formatCurrency($lot->reserve_price) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Increment</p>
                                <p class="text-gray-900 dark:text-gray-100">{{ formatCurrency($lot->increment) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!$lot->isWithdrawn() && $lot->canBeWithdrawn())
                    <div class="card border-amber-200 dark:border-amber-700">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Withdraw Lot</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Remove this lot from the auction.</p>
                                </div>
                                <button type="button" onclick="document.getElementById('withdrawSection').classList.toggle('hidden')" class="btn btn-warning">
                                    Withdraw Lot
                                </button>
                            </div>
                            <div id="withdrawSection" class="hidden mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <form action="{{ route('seller.auctions.lots.withdraw', [$lot->auction, $lot]) }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="withdrawal_reason" class="label">Reason for Withdrawal (Optional)</label>
                                        <textarea id="withdrawal_reason"
                                                  name="withdrawal_reason"
                                                  rows="3"
                                                  class="input"
                                                  placeholder="Why are you withdrawing this lot?"></textarea>
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="button" onclick="document.getElementById('withdrawSection').classList.add('hidden')" class="btn btn-outline flex-1">Cancel</button>
                                        <button type="submit" class="btn btn-warning flex-1" onclick="return confirm('Are you sure you want to withdraw this lot?');">Confirm Withdrawal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @elseif($lot->isWithdrawn())
                    <div class="card">
                        <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Lot Withdrawn</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Withdrawn on {{ $lot->withdrawn_at->format('M d, Y \a\t H:i') }}</p>
                            @if($lot->withdrawal_reason)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><strong>Reason:</strong> {{ $lot->withdrawal_reason }}</p>
                            @endif
                        </div>
                    </div>
                @endif

            @else

            @if($errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-4 mb-6">
                    <p class="font-semibold text-red-800 dark:text-red-200 mb-2">Please fix the following before saving:</p>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('seller.auctions.lots.update', [$lot->auction, $lot]) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Basic Information -->
                <div class="card">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Basic Information</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="title" class="label">Lot Title *</label>
                                <input id="title"
                                       type="text"
                                       name="title"
                                       value="{{ old('title', $lot->title) }}"
                                       required
                                       class="input @error('title') input-error @enderror">
                                @error('title')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="description" class="label">Description *</label>
                                <textarea id="description"
                                          name="description"
                                          rows="6"
                                          required
                                          class="input @error('description') input-error @enderror">{{ old('description', $lot->description) }}</textarea>
                                @error('description')<p class="error-message">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if($lot->status === 'draft')
                    <!-- Bidding Details -->
                    @if($auction->isDutch())
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Dutch Auction Pricing</h2>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="dutch_start_price" class="label">Start Price *</label>
                                        <input id="dutch_start_price"
                                               type="number"
                                               name="dutch_start_price"
                                               value="{{ old('dutch_start_price', $lot->dutch_start_price) }}"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               class="input @error('dutch_start_price') input-error @enderror">
                                        @error('dutch_start_price')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label for="dutch_floor_price" class="label">Floor Price *</label>
                                        <input id="dutch_floor_price"
                                               type="number"
                                               name="dutch_floor_price"
                                               value="{{ old('dutch_floor_price', $lot->dutch_floor_price) }}"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               class="input @error('dutch_floor_price') input-error @enderror">
                                        @error('dutch_floor_price')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="dutch_duration" class="label">Lot Duration *</label>
                                    <select id="dutch_duration" name="dutch_duration" required class="input @error('dutch_duration') input-error @enderror">
                                        @foreach([180 => '3 minutes', 300 => '5 minutes', 420 => '7 minutes', 600 => '10 minutes'] as $sec => $label)
                                            <option value="{{ $sec }}" {{ old('dutch_duration', $lot->dutch_duration ?? 300) == $sec ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('dutch_duration')<p class="error-message">{{ $message }}</p>@enderror
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">How long the lot runs before reaching floor price.</p>
                                </div>

                                {{-- Drop matrix preview --}}
                                <div id="drop-preview" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4" style="display: none;"
                                     x-data="dropPreview()" x-init="calculate()">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Calculated Drop Schedule</h4>
                                    <div class="grid grid-cols-2 gap-2 text-sm text-blue-700 dark:text-blue-300">
                                        <div>Drop amount: <span class="font-semibold" x-text="'R' + dropAmount"></span></div>
                                        <div>Drop interval: <span class="font-semibold" x-text="dropInterval + 's'"></span></div>
                                        <div>Total drops: <span class="font-semibold" x-text="totalDrops"></span></div>
                                        <div>Actual duration: <span class="font-semibold" x-text="actualDuration"></span></div>
                                    </div>
                                    <template x-if="phaseBreakdown.length > 1">
                                        <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                                            <h5 class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-2">Phase Breakdown</h5>
                                            <div class="space-y-1">
                                                <template x-for="(p, i) in phaseBreakdown" :key="i">
                                                    <div class="flex justify-between text-xs text-blue-600 dark:text-blue-400">
                                                        <span x-text="'Phase ' + (i+1) + ': ' + p.priceRange"></span>
                                                        <span x-text="'R' + p.dropAmount + ' every ' + p.interval + 's — ' + p.drops + ' drops, ' + p.duration"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <label class="label">Drop Strategy *</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                        @foreach(\App\Models\Lot::DROP_STRATEGIES as $key => $strat)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="dutch_drop_strategy" value="{{ $key }}" class="sr-only peer"
                                                   {{ old('dutch_drop_strategy', $lot->dutch_drop_strategy ?: 'constant') === $key ? 'checked' : '' }}>
                                            <div class="p-3 border-2 rounded-lg transition-all peer-checked:border-primary-600 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900 border-gray-300 dark:border-gray-600 hover:border-primary-400">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $strat['label'] }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $strat['description'] }}</div>
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                    @error('dutch_drop_strategy')<p class="error-message">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="quantity" class="label">Quantity Available</label>
                                    <input id="quantity"
                                           type="number"
                                           name="quantity"
                                           value="{{ old('quantity', $lot->quantity) }}"
                                           min="1"
                                           class="input @error('quantity') input-error @enderror">
                                    @error('quantity')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    @elseif($auction->isSealed())
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Sealed Bid Details</h2>

                            <div class="space-y-4">
                                <!-- Hidden starting_bid for validation (sealed doesn't use it) -->
                                <input type="hidden" name="starting_bid" value="{{ old('starting_bid', $lot->starting_bid ?? 0) }}">

                                @if(config('regional.features.reserve_prices'))
                                    <div>
                                        <label class="flex items-center gap-3">
                                            <input type="checkbox"
                                                   name="has_reserve"
                                                   value="1"
                                                   {{ old('has_reserve', $lot->reserve_price > 0) ? 'checked' : '' }}
                                                   onchange="document.getElementById('reserve_price_field').style.display = this.checked ? 'block' : 'none'">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                @if($auction->isSealedHighest()) Set reserve price (minimum to sell) @else Set maximum price @endif
                                            </span>
                                        </label>
                                    </div>

                                    <div id="reserve_price_field" style="display: {{ old('has_reserve', $lot->reserve_price > 0) ? 'block' : 'none' }}">
                                        <label for="reserve_price" class="label">
                                            @if($auction->isSealedHighest()) Reserve Price @else Maximum Price @endif
                                        </label>
                                        <input id="reserve_price"
                                               type="number"
                                               name="reserve_price"
                                               value="{{ old('reserve_price', $lot->reserve_price) }}"
                                               step="0.01"
                                               min="0"
                                               class="input @error('reserve_price') input-error @enderror">
                                        @error('reserve_price')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Bidding Details</h2>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="starting_bid" class="label">Starting Bid *</label>
                                        <input id="starting_bid"
                                               type="number"
                                               name="starting_bid"
                                               value="{{ old('starting_bid', $lot->starting_bid) }}"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="input @error('starting_bid') input-error @enderror">
                                        @error('starting_bid')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label for="increment" class="label">Bid Increment *</label>
                                        <input id="increment"
                                               type="number"
                                               name="increment"
                                               value="{{ old('increment', $lot->increment) }}"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               class="input @error('increment') input-error @enderror">
                                        @error('increment')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                @if(config('regional.features.reserve_prices'))
                                    <div>
                                        <label class="flex items-center gap-3">
                                            <input type="checkbox"
                                                   name="has_reserve"
                                                   value="1"
                                                   {{ old('has_reserve', $lot->reserve_price > 0) ? 'checked' : '' }}
                                                   onchange="document.getElementById('reserve_price_field').style.display = this.checked ? 'block' : 'none'">
                                            <span class="text-gray-700 dark:text-gray-300">Set reserve price</span>
                                        </label>
                                    </div>

                                    <div id="reserve_price_field" style="display: {{ old('has_reserve', $lot->reserve_price > 0) ? 'block' : 'none' }}">
                                        <label for="reserve_price" class="label">Reserve Price</label>
                                        <input id="reserve_price"
                                               type="number"
                                               name="reserve_price"
                                               value="{{ old('reserve_price', $lot->reserve_price) }}"
                                               step="0.01"
                                               min="0"
                                               class="input @error('reserve_price') input-error @enderror">
                                        @error('reserve_price')<p class="error-message">{{ $message }}</p>@enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    </div>

                    <!-- Subject to Confirmation -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Subject to Confirmation</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                If enabled, bidders will see your message after placing their first bid on this lot.
                            </p>

                            <div class="space-y-4">
                                <label class="flex items-center gap-3">
                                    <input type="checkbox"
                                           name="subject_to_confirmation"
                                           value="1"
                                           {{ old('subject_to_confirmation', $lot->subject_to_confirmation) ? 'checked' : '' }}
                                           onchange="document.getElementById('confirmation_message_field').style.display = this.checked ? 'block' : 'none'"
                                           class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">This lot is subject to confirmation</span>
                                </label>

                                <div id="confirmation_message_field" style="display: {{ old('subject_to_confirmation', $lot->subject_to_confirmation) ? 'block' : 'none' }}">
                                    <label for="confirmation_message" class="label">Confirmation Message *</label>
                                    <textarea id="confirmation_message"
                                              name="confirmation_message"
                                              rows="3"
                                              class="input @error('confirmation_message') input-error @enderror"
                                              placeholder="e.g., This lot is subject to seller confirmation.">{{ old('confirmation_message', $lot->confirmation_message) }}</textarea>
                                    @error('confirmation_message')<p class="error-message">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($auction->isSealedLowest())
                    <!-- Tender Document -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Tender Document</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Upload a PDF document for this lot. Bidders will be able to view and download this document before placing their bid.
                            </p>

                            @if($lot->tender_document)
                                <div class="mb-4 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <a href="{{ Storage::url($lot->tender_document) }}" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline font-medium">
                                            Current Document
                                        </a>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="remove_tender_document" value="1" class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        <span class="text-red-600 dark:text-red-400">Remove</span>
                                    </label>
                                </div>
                            @endif

                            <div>
                                <label for="tender_document" class="label">{{ $lot->tender_document ? 'Replace PDF Document' : 'PDF Document (optional)' }}</label>
                                <input id="tender_document"
                                       type="file"
                                       name="tender_document"
                                       accept=".pdf,application/pdf"
                                       class="input @error('tender_document') input-error @enderror">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max file size: 10MB. PDF format only.</p>
                                @error('tender_document')<p class="error-message">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Current Images -->
                    <div class="card">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Images</h2>

                            @if($lot->images->count() > 0)
                                <div class="mb-6">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Current Images ({{ $lot->images->count() }})</h3>
                                    <div class="grid grid-cols-4 gap-4">
                                        @foreach($lot->images as $image)
                                            <div class="relative">
                                                <img src="{{ $image->thumbnail_url }}" alt="Image" class="w-full h-24 object-cover rounded">
                                                @if($lot->images->count() > 1)
                                                    <button type="button"
                                                            onclick="deleteImage('{{ route('seller.lots.images.delete', [$lot, $image]) }}', this)"
                                                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 shadow">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($lot->images->count() === 1)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">At least 1 image required — upload a replacement before removing this one.</p>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <!-- Pricing Info -->
                                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="text-sm text-blue-800 dark:text-blue-200">
                                            <p class="font-semibold mb-1">Image Pricing</p>
                                            <p>Current: <strong>{{ $lot->images->count() }} {{ Str::plural('image', $lot->images->count()) }}</strong> = {{ ucfirst($lot->image_tier) }} tier ({{ formatCurrency(config('platform.pricing.tier_' . $lot->image_tier . '.price', 1)) }})</p>
                                            <p class="text-xs mt-1 opacity-75">
                                                • 1 image = Basic ({{ formatCurrency(config('platform.pricing.tier_basic.price', 1)) }})<br>
                                                • 2-5 images = Pro ({{ formatCurrency(config('platform.pricing.tier_pro.price', 5)) }})<br>
                                                • 6+ images = Premium ({{ formatCurrency(config('platform.pricing.tier_premium.price', 20)) }})
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add More Images -->
                                <label for="images" class="label">Add More Images</label>
                                <input id="images"
                                       type="file"
                                       name="images[]"
                                       multiple
                                       accept="image/*"
                                       class="input @error('images') input-error @enderror">
                                @error('images')<p class="error-message">{{ $message }}</p>@enderror
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Add as many images as needed. Tier will be recalculated based on total image count.
                                </p>
                                <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-800 dark:text-blue-300">
                                    <strong>Best image results:</strong><br>
                                    Ideal size: <strong>900 × 1200px</strong> (portrait) or <strong>1200 × 1200px</strong> (square)<br>
                                    Minimum: 600 × 600px &mdash; Maximum: 15MB per image<br>
                                    Portrait or square recommended (most buyers browse on phones) &mdash; any format accepted (auto-converted to WebP)
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @php $supplier = $lot->supplier; @endphp
                <!-- Supplier Information (Internal Only) -->
                <div class="card" x-data="{ open: {{ $supplier ? 'true' : 'false' }} }">
                    <div class="p-6">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Supplier Information</h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Internal record only — never shown to bidders. All fields optional.</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        @php
                            $supplierPickerInitial = $supplier ? [
                                'id' => $supplier->id,
                                'uid' => $supplier->uid,
                                'name' => $supplier->name,
                                'id_number_last4' => $supplier->id_number ? substr($supplier->id_number, -4) : null,
                            ] : null;
                        @endphp
                        <div x-show="open" x-cloak class="mt-6 space-y-4"
                             x-data='supplierPicker(@json($supplierPickerInitial))'>
                            <!-- Hidden field: holds the picked supplier_id when in 'selected' mode -->
                            <input type="hidden" name="supplier_id" :value="supplierId">

                            <!-- SEARCH mode: look up existing supplier by name, UID, or ID number -->
                            <div x-show="mode === 'search'" class="space-y-3">
                                <div>
                                    <label class="label">Find existing supplier</label>
                                    <div class="relative">
                                        <input type="text"
                                               x-model="query"
                                               @input="onQueryInput()"
                                               placeholder="Type supplier name, UID (SUP-...), or ID number"
                                               class="input pr-10"
                                               autocomplete="off">
                                        <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <svg class="w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"></path></svg>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start typing — matches your saved suppliers only.</p>
                                </div>

                                <div x-show="results.length > 0" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden divide-y divide-gray-200 dark:divide-gray-700">
                                    <template x-for="r in results" :key="r.id">
                                        <button type="button" @click="pickResult(r)" class="w-full text-left p-3 hover:bg-amber-50 dark:hover:bg-amber-900/20 flex items-center gap-3">
                                            <span class="font-mono text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded" x-text="r.uid"></span>
                                            <span class="flex-1 text-sm text-gray-900 dark:text-gray-100" x-text="r.name || '(no name)'"></span>
                                            <span x-show="r.id_number_last4" class="text-xs text-gray-500 dark:text-gray-400">ID ···<span x-text="r.id_number_last4"></span></span>
                                        </button>
                                    </template>
                                </div>

                                <div x-show="searchedOnce && results.length === 0 && !loading" class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                    No matching suppliers.
                                    <button type="button" @click="enterNew()" class="text-amber-600 dark:text-amber-400 font-medium hover:underline ml-1">
                                        Enter new supplier &rarr;
                                    </button>
                                </div>

                                <button type="button" @click="enterNew()" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">
                                    + Enter new supplier
                                </button>
                            </div>

                            <!-- SELECTED mode: linked to an existing supplier -->
                            <div x-show="mode === 'selected'" class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-3">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        Linked to <strong x-text="selected?.name || '(no name)'"></strong>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="selected?.uid"></div>
                                </div>
                                <button type="button" @click="unlink()" class="text-sm text-amber-700 dark:text-amber-400 hover:underline flex-shrink-0">
                                    Change
                                </button>
                            </div>

                            <!-- NEW mode: free-text fields for creating / editing-in-place -->
                            <div x-show="mode === 'new'" class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">New supplier details</span>
                                    <button type="button" @click="backToSearch()" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        &larr; Back to search
                                    </button>
                                </div>

                            <div>
                                <label for="supplier_name" class="label">Supplier Name</label>
                                <input id="supplier_name"
                                       type="text"
                                       name="supplier_name"
                                       value="{{ old('supplier_name', $supplier?->name) }}"
                                       maxlength="255"
                                       class="input @error('supplier_name') input-error @enderror"
                                       placeholder="Full name of person/entity who supplied the item">
                                @error('supplier_name')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="supplier_id_number" class="label">ID / Passport Number</label>
                                <input id="supplier_id_number"
                                       type="text"
                                       name="supplier_id_number"
                                       value="{{ old('supplier_id_number', $supplier?->id_number) }}"
                                       maxlength="50"
                                       class="input @error('supplier_id_number') input-error @enderror"
                                       placeholder="South African ID or passport number">
                                @error('supplier_id_number')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="supplier_address" class="label">Address</label>
                                <textarea id="supplier_address"
                                          name="supplier_address"
                                          rows="3"
                                          maxlength="1000"
                                          class="input @error('supplier_address') input-error @enderror"
                                          placeholder="Supplier's physical address">{{ old('supplier_address', $supplier?->address) }}</textarea>
                                @error('supplier_address')<p class="error-message">{{ $message }}</p>@enderror
                            </div>

                            <div x-data="idCaptureComponent">
                                <label class="label">ID Document Photo</label>
                                @if($supplier?->id_document)
                                    <div class="mb-3 flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <a href="{{ Storage::url($supplier->id_document) }}" target="_blank" class="flex items-center gap-3 flex-1 hover:underline">
                                            <img src="{{ Storage::url($supplier->id_document) }}" alt="ID Document" class="w-16 h-16 object-cover rounded">
                                            <span class="text-sm text-primary-600 dark:text-primary-400">View current document</span>
                                        </a>
                                        <label class="inline-flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                            <input type="checkbox" name="remove_supplier_id_document" value="1" class="rounded">
                                            Remove
                                        </label>
                                    </div>
                                @endif
                                <input id="supplier_id_document"
                                       type="file"
                                       name="supplier_id_document"
                                       accept="image/*"
                                       class="hidden @error('supplier_id_document') input-error @enderror"
                                       @change="handleFileChange($event)">
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button type="button" @click="takePhoto()" class="btn btn-outline flex-1 justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Take Photo
                                    </button>
                                    <button type="button" @click="pickFile()" class="btn btn-outline flex-1 justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Upload File
                                    </button>
                                </div>
                                <p x-show="filename" x-text="filename" class="text-sm text-primary-600 dark:text-primary-400 mt-2 font-medium"></p>
                                <p x-show="error" x-text="error" class="text-sm text-red-600 mt-2"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    "Take Photo" uses your camera (webcam on desktop, phone camera on mobile). "Upload File" picks from your device. Max 10MB.
                                    {{ $supplier?->id_document ? 'Selecting a new file will replace the current one.' : '' }}
                                    Stored securely — not visible to bidders.
                                </p>
                                @error('supplier_id_document')<p class="error-message">{{ $message }}</p>@enderror

                                <!-- Desktop webcam modal -->
                                <div x-show="cameraOpen" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center p-4" @keydown.escape.window="closeCamera()">
                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full" @click.stop>
                                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Capture ID Document</h3>
                                            <button type="button" @click="closeCamera()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="p-4">
                                            <video x-ref="video" autoplay playsinline muted class="w-full rounded-lg bg-black"></video>
                                            <canvas x-ref="canvas" class="hidden"></canvas>
                                        </div>
                                        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex gap-3 justify-end">
                                            <button type="button" @click="closeCamera()" class="btn btn-outline">Cancel</button>
                                            <button type="button" @click="capturePhoto()" class="btn btn-primary">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Capture
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div><!-- /mode === 'new' -->
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <a href="{{ route('seller.auctions.show', $lot->auction) }}" class="btn btn-outline flex-1">Cancel</a>
                    <button type="submit" class="btn btn-primary flex-1">
                        Update Lot
                    </button>
                </div>
            </form>

            @if(!$lot->isWithdrawn())
                @if($auction->status === 'draft' && !in_array($lot->status, ['live', 'sold']))
                    <!-- Delete Lot (Draft only) -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Delete Lot</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Permanently delete this lot and all its images. This action cannot be undone.</p>
                            </div>
                            <form action="{{ route('seller.auctions.lots.destroy', [$lot->auction, $lot]) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this lot? All images will be permanently deleted. This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete Lot</button>
                            </form>
                        </div>
                    </div>
                @endif
            @else
                <!-- Lot is withdrawn -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Lot Withdrawn</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            This lot was withdrawn on {{ $lot->withdrawn_at->format('M d, Y \a\t H:i') }}
                        </p>
                        @if($lot->withdrawal_reason)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <strong>Reason:</strong> {{ $lot->withdrawal_reason }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            @endif {{-- end draft-only edit form --}}
        </div>
    </div>

    @if($auction->isDutch())
    @push('scripts')
    <script>
    function deleteImage(url, btn) {
        if (!confirm('Delete this image?')) return;
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        }).then(r => {
            if (r.ok) {
                btn.closest('.relative').remove();
            } else {
                alert('Failed to delete image.');
            }
        }).catch(() => alert('Failed to delete image.'));
    }

    const DROP_STRATEGIES = {
        constant: { phases: [{ range: 1.0, drop_mult: 1.0, interval_mult: 1.0 }] },
        fast_sell: { phases: [
            { range: 0.30, drop_mult: 3.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.5, interval_mult: 2.0 },
        ]},
        max_value: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.5 },
            { range: 0.40, drop_mult: 1.0, interval_mult: 1.0 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
        high_drama: { phases: [
            { range: 0.30, drop_mult: 2.0, interval_mult: 0.75 },
            { range: 0.40, drop_mult: 0.5, interval_mult: 1.5 },
            { range: 0.30, drop_mult: 0.25, interval_mult: 3.0 },
        ]},
    };

    function jsCalculateDuration(startPrice, floorPrice, dropAmount, dropInterval, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0) return 0;
        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let totalTime = 0;
        for (const phase of phases) {
            const phaseRange = totalRange * phase.range;
            const effectiveDrop = dropAmount * phase.drop_mult;
            const effectiveInterval = Math.max(1, Math.round(dropInterval * phase.interval_mult));
            if (effectiveDrop <= 0) continue;
            totalTime += Math.ceil(phaseRange / effectiveDrop) * effectiveInterval;
        }
        return totalTime;
    }

    function jsRoundDropAmount(amount, totalRange) {
        if (totalRange >= 10000) return amount >= 50 ? Math.round(amount / 10) * 10 : Math.max(5, Math.round(amount / 5) * 5);
        if (totalRange >= 1000) return Math.max(1, Math.round(amount));
        if (totalRange >= 100) return Math.max(0.50, Math.round(amount * 2) / 2);
        if (totalRange >= 10) return Math.max(0.10, Math.round(amount * 10) / 10);
        return Math.round(amount * 100) / 100;
    }

    function jsCalculateDropMatrix(startPrice, floorPrice, targetDuration, strategy) {
        const totalRange = startPrice - floorPrice;
        if (totalRange <= 0 || targetDuration <= 0) return { drop_amount: 0, drop_interval: 10, actual_duration: 0, total_drops: 0 };

        const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
        let W = 0;
        for (const phase of phases) {
            if (phase.drop_mult > 0) W += phase.range * phase.interval_mult / phase.drop_mult;
        }
        if (W <= 0) W = 1.0;

        const candidates = [5, 8, 10, 12, 15, 20, 25, 30];
        const minDrop = Math.max(0.01, totalRange * 0.005);
        const maxDrop = totalRange * 0.15;
        let bestInterval = 10, bestDropAmount = totalRange * 10 * W / targetDuration;

        for (const interval of candidates) {
            const dropAmount = totalRange * interval * W / targetDuration;
            if (dropAmount >= minDrop && dropAmount <= maxDrop) {
                bestInterval = interval;
                bestDropAmount = dropAmount;
                break;
            }
            if (dropAmount > maxDrop) break;
            bestInterval = interval;
            bestDropAmount = dropAmount;
        }

        bestDropAmount = jsRoundDropAmount(bestDropAmount, totalRange);
        bestDropAmount = Math.max(0.01, bestDropAmount);

        const actualDuration = jsCalculateDuration(startPrice, floorPrice, bestDropAmount, bestInterval, strategy);
        const totalDrops = bestDropAmount > 0 ? Math.ceil(totalRange / bestDropAmount) : 0;

        return { drop_amount: bestDropAmount, drop_interval: bestInterval, actual_duration: actualDuration, total_drops: totalDrops };
    }

    function formatDuration(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m > 0 ? m + 'm ' + s + 's' : s + 's';
    }

    function dropPreview() {
        return {
            dropAmount: '—',
            dropInterval: '—',
            totalDrops: '—',
            actualDuration: '—',
            phaseBreakdown: [],
            calculate() {
                const startPrice = parseFloat(document.getElementById('dutch_start_price')?.value) || 0;
                const floorPrice = parseFloat(document.getElementById('dutch_floor_price')?.value) || 0;
                const duration = parseInt(document.getElementById('dutch_duration')?.value) || 300;
                const strategyEl = document.querySelector('input[name="dutch_drop_strategy"]:checked');
                const strategy = strategyEl ? strategyEl.value : 'constant';

                const preview = document.getElementById('drop-preview');
                if (startPrice <= 0 || floorPrice <= 0 || floorPrice >= startPrice) {
                    if (preview) preview.style.display = 'none';
                    return;
                }

                const matrix = jsCalculateDropMatrix(startPrice, floorPrice, duration, strategy);
                this.dropAmount = matrix.drop_amount.toFixed(2);
                this.dropInterval = matrix.drop_interval;
                this.totalDrops = matrix.total_drops;
                this.actualDuration = formatDuration(matrix.actual_duration);

                // Phase breakdown
                const totalRange = startPrice - floorPrice;
                const phases = (DROP_STRATEGIES[strategy] || DROP_STRATEGIES.constant).phases;
                this.phaseBreakdown = [];
                let priceAt = startPrice;
                if (phases.length > 1) {
                    for (const phase of phases) {
                        const phaseRange = totalRange * phase.range;
                        const effDrop = matrix.drop_amount * phase.drop_mult;
                        const effInterval = Math.max(1, Math.round(matrix.drop_interval * phase.interval_mult));
                        const drops = effDrop > 0 ? Math.ceil(phaseRange / effDrop) : 0;
                        const phaseSecs = drops * effInterval;
                        const priceEnd = Math.max(floorPrice, priceAt - drops * effDrop);
                        this.phaseBreakdown.push({
                            priceRange: 'R' + Math.round(priceAt) + ' → R' + Math.round(priceEnd),
                            dropAmount: effDrop.toFixed(2),
                            interval: effInterval,
                            drops: drops,
                            duration: formatDuration(phaseSecs),
                        });
                        priceAt -= phaseRange;
                    }
                }

                if (preview) preview.style.display = 'block';
            },
            init() {
                ['dutch_start_price', 'dutch_floor_price', 'dutch_duration'].forEach(id => {
                    document.getElementById(id)?.addEventListener('input', () => this.calculate());
                    document.getElementById(id)?.addEventListener('change', () => this.calculate());
                });
                document.querySelectorAll('input[name="dutch_drop_strategy"]').forEach(el => {
                    el.addEventListener('change', () => this.calculate());
                });
                this.calculate();
            }
        };
    }
    </script>
    @endpush
    @endif

</x-app-layout>
