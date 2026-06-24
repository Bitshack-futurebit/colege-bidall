<x-app-layout>
    <x-slot name="title">Create Promo Code - Admin</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-outline">&larr; Back to Promo Codes</a>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold mb-6">Create Promo Code</h1>

            <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="space-y-6">
                @csrf

                <!-- Code & Name -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium mb-1">Code</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}" required
                               class="input w-full font-mono uppercase" placeholder="LAUNCH2026"
                               style="text-transform: uppercase;">
                        <p class="text-xs text-gray-500 mt-1">The code auctioneers will enter during registration</p>
                        @error('code')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="input w-full" placeholder="Launch Special">
                        <p class="text-xs text-gray-500 mt-1">Admin-friendly label</p>
                        @error('name')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium mb-1">Description (optional)</label>
                    <textarea name="description" id="description" rows="2" class="input w-full"
                              placeholder="Internal notes about this promo code">{{ old('description') }}</textarea>
                    @error('description')<p class="error-message">{{ $message }}</p>@enderror
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Pricing Settings -->
                <h2 class="text-lg font-semibold">Pricing Settings</h2>

                <!-- Free Account Toggle -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_free_account" value="1"
                               {{ old('is_free_account') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                               id="free_account_check">
                        <span class="text-sm font-medium">Free Account (No Lot Fees)</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Auctioneers using this code pay R0 for all lots</p>
                </div>

                <!-- Custom Flat Fee -->
                <div>
                    <label for="custom_lot_fee" class="block text-sm font-medium mb-1">Custom Lot Fee (optional)</label>
                    <input type="number" step="0.01" min="0" name="custom_lot_fee" id="custom_lot_fee"
                           value="{{ old('custom_lot_fee') }}" class="input w-full max-w-xs"
                           placeholder="Leave empty for standard pricing">
                    <p class="text-xs text-gray-500 mt-1">Flat fee per lot instead of tiered pricing</p>
                    @error('custom_lot_fee')<p class="error-message">{{ $message }}</p>@enderror
                </div>

                <!-- Custom Per-Tier Prices -->
                <div>
                    <label class="block text-sm font-medium mb-1">Custom Tier Prices (optional)</label>
                    <div class="grid grid-cols-3 gap-3 max-w-lg">
                        <div>
                            <label for="custom_tier_basic" class="block text-xs text-gray-500 mb-1">Basic (1 image)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_basic" id="custom_tier_basic"
                                   value="{{ old('custom_tier_basic') }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_basic.price', 1) }}">
                        </div>
                        <div>
                            <label for="custom_tier_pro" class="block text-xs text-gray-500 mb-1">Pro (2-5 images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_pro" id="custom_tier_pro"
                                   value="{{ old('custom_tier_pro') }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_pro.price', 5) }}">
                        </div>
                        <div>
                            <label for="custom_tier_premium" class="block text-xs text-gray-500 mb-1">Premium (6+ images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_premium" id="custom_tier_premium"
                                   value="{{ old('custom_tier_premium') }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_premium.price', 20) }}">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Override individual tier prices. Ignored if Free Account or Custom Flat Fee is set.</p>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Relist Settings -->
                <div>
                    <label for="free_relist_reset" class="block text-sm font-medium mb-1">Free Relist Reset</label>
                    <select name="free_relist_reset" id="free_relist_reset" class="input w-full max-w-xs">
                        <option value="">Not at all (never expires)</option>
                        <option value="weekly" {{ old('free_relist_reset') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="biweekly" {{ old('free_relist_reset') === 'biweekly' ? 'selected' : '' }}>Every two weeks</option>
                        <option value="monthly" {{ old('free_relist_reset') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    @error('free_relist_reset')<p class="error-message">{{ $message }}</p>@enderror
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Bonus Credits -->
                <div>
                    <label for="bonus_credits" class="block text-sm font-medium mb-1">Bonus Credits (R)</label>
                    <input type="number" step="0.01" min="0" name="bonus_credits" id="bonus_credits"
                           value="{{ old('bonus_credits', '0') }}" class="input w-full max-w-xs" placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Credits automatically added to the auctioneer's balance on registration</p>
                    @error('bonus_credits')<p class="error-message">{{ $message }}</p>@enderror
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Usage Limits -->
                <h2 class="text-lg font-semibold">Usage Limits</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="max_uses" class="block text-sm font-medium mb-1">Max Uses (optional)</label>
                        <input type="number" min="1" name="max_uses" id="max_uses"
                               value="{{ old('max_uses') }}" class="input w-full" placeholder="Unlimited">
                        <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited uses</p>
                        @error('max_uses')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium mb-1">Expiry Date (optional)</label>
                        <input type="datetime-local" name="expires_at" id="expires_at"
                               value="{{ old('expires_at') }}" class="input w-full">
                        <p class="text-xs text-gray-500 mt-1">Leave empty for no expiry</p>
                        @error('expires_at')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn btn-primary">Create Promo Code</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
