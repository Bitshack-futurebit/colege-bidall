<x-app-layout>
    <x-slot name="title">Edit Promo Code - Admin</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-outline">&larr; Back to Promo Codes</a>
        </div>

        <div class="card p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Edit: {{ $promoCode->code }}</h1>
                <div class="flex items-center space-x-3">
                    @if($promoCode->isValid())
                        <span class="inline-block px-3 py-1 text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">Active</span>
                    @else
                        <span class="inline-block px-3 py-1 text-sm bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full">
                            {{ !$promoCode->is_active ? 'Inactive' : 'Expired/Maxed' }}
                        </span>
                    @endif
                    <form method="POST" action="{{ route('admin.promo-codes.toggle', $promoCode) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn {{ $promoCode->is_active ? 'bg-red-600 hover:bg-red-700 text-white' : 'btn-success' }} btn-sm">
                            {{ $promoCode->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.promo-codes.update', $promoCode) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Code & Name -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium mb-1">Code</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $promoCode->code) }}" required
                               class="input w-full font-mono uppercase" style="text-transform: uppercase;">
                        @error('code')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $promoCode->name) }}" required
                               class="input w-full">
                        @error('name')<p class="error-message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium mb-1">Description</label>
                    <textarea name="description" id="description" rows="2" class="input w-full">{{ old('description', $promoCode->description) }}</textarea>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Pricing Settings -->
                <h2 class="text-lg font-semibold">Pricing Settings</h2>

                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_free_account" value="1"
                               {{ old('is_free_account', $promoCode->is_free_account) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium">Free Account (No Lot Fees)</span>
                    </label>
                </div>

                <div>
                    <label for="custom_lot_fee" class="block text-sm font-medium mb-1">Custom Lot Fee</label>
                    <input type="number" step="0.01" min="0" name="custom_lot_fee" id="custom_lot_fee"
                           value="{{ old('custom_lot_fee', $promoCode->custom_lot_fee) }}" class="input w-full max-w-xs"
                           placeholder="Leave empty for standard pricing">
                    @error('custom_lot_fee')<p class="error-message">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Custom Tier Prices</label>
                    <div class="grid grid-cols-3 gap-3 max-w-lg">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Basic (1 image)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_basic"
                                   value="{{ old('custom_tier_basic', $promoCode->custom_tier_basic) }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_basic.price', 1) }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Pro (2-5 images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_pro"
                                   value="{{ old('custom_tier_pro', $promoCode->custom_tier_pro) }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_pro.price', 5) }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Premium (6+ images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_premium"
                                   value="{{ old('custom_tier_premium', $promoCode->custom_tier_premium) }}" class="input w-full"
                                   placeholder="R{{ config('platform.pricing.tier_premium.price', 20) }}">
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <div>
                    <label for="free_relist_reset" class="block text-sm font-medium mb-1">Free Relist Reset</label>
                    <select name="free_relist_reset" id="free_relist_reset" class="input w-full max-w-xs">
                        <option value="" {{ !$promoCode->free_relist_reset ? 'selected' : '' }}>Not at all (never expires)</option>
                        <option value="weekly" {{ old('free_relist_reset', $promoCode->free_relist_reset) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="biweekly" {{ old('free_relist_reset', $promoCode->free_relist_reset) === 'biweekly' ? 'selected' : '' }}>Every two weeks</option>
                        <option value="monthly" {{ old('free_relist_reset', $promoCode->free_relist_reset) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <div>
                    <label for="bonus_credits" class="block text-sm font-medium mb-1">Bonus Credits (R)</label>
                    <input type="number" step="0.01" min="0" name="bonus_credits" id="bonus_credits"
                           value="{{ old('bonus_credits', $promoCode->bonus_credits) }}" class="input w-full max-w-xs">
                    <p class="text-xs text-gray-500 mt-1">Credits added on registration. Changing this does not affect existing users.</p>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <h2 class="text-lg font-semibold">Usage Limits</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="max_uses" class="block text-sm font-medium mb-1">Max Uses</label>
                        <input type="number" min="1" name="max_uses" id="max_uses"
                               value="{{ old('max_uses', $promoCode->max_uses) }}" class="input w-full" placeholder="Unlimited">
                        <p class="text-xs text-gray-500 mt-1">Used {{ $promoCode->times_used }} times so far</p>
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium mb-1">Expiry Date</label>
                        <input type="datetime-local" name="expires_at" id="expires_at"
                               value="{{ old('expires_at', $promoCode->expires_at ? $promoCode->expires_at->format('Y-m-d\TH:i') : '') }}"
                               class="input w-full">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn btn-primary">Update Promo Code</button>
                </div>
            </form>
        </div>

        <!-- Auctioneers who used this code -->
        @if($promoCode->auctioneers->count() > 0)
            <div class="card p-6">
                <h2 class="text-xl font-bold mb-4">Auctioneers Using This Code ({{ $promoCode->auctioneers->count() }})</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Business</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Registered</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Credits</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($promoCode->auctioneers as $auctioneer)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $auctioneer->business_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $auctioneer->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $auctioneer->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ formatCurrency($auctioneer->credit_balance) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('admin.auctioneers.settings', $auctioneer) }}" class="text-primary-600 hover:underline">Settings</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
