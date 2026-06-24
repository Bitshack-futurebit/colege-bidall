<x-app-layout>
    <x-slot name="title">Auctioneer Settings - Admin</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="{{ route('admin.auctioneers.show', $auctioneer) }}" class="btn btn-outline">&larr; Back to Financial Report</a>
        </div>

        <!-- Basic Info -->
        <div class="card p-6 mb-6">
            <h1 class="text-2xl font-bold mb-6">{{ $auctioneer->business_name }} &mdash; Settings</h1>

            <div class="grid grid-cols-2 gap-4">
                <div><strong>Owner:</strong> {{ $auctioneer->user->name }}</div>
                <div><strong>Email:</strong> {{ $auctioneer->user->email }}</div>
                <div><strong>Phone:</strong> {{ $auctioneer->user->phone ?? 'N/A' }}</div>
                <div><strong>WhatsApp:</strong> {{ $auctioneer->whatsapp_number ?? 'N/A' }}</div>
                <div><strong>Status:</strong> {{ $auctioneer->is_activated ? 'Activated' : 'Pending' }}</div>
                <div><strong>Credits:</strong> {{ formatCurrency($auctioneer->credit_balance) }}</div>
                <div class="col-span-2"><strong>Business:</strong> {{ $auctioneer->business_name }}</div>
                <div class="col-span-2"><strong>Address:</strong> {{ $auctioneer->user->address ?? 'Not provided' }}</div>
                <div><strong>City:</strong> {{ $auctioneer->user->city ?? 'N/A' }}</div>
                <div><strong>Province:</strong> {{ $auctioneer->user->province ?? 'N/A' }}</div>
                <div><strong>Member Since:</strong> {{ $auctioneer->user->created_at->format('M d, Y') }}</div>
                <div><strong>Activated:</strong> {{ $auctioneer->activated_at ? $auctioneer->activated_at->format('M d, Y') : 'Not activated' }}</div>
            </div>
        </div>

        <!-- Pricing Controls -->
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Pricing Controls</h2>

            <form method="POST" action="{{ route('admin.auctioneers.update-pricing', $auctioneer) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <!-- Free Account Toggle -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_free_account" value="1"
                               {{ $auctioneer->is_free_account ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                               onchange="document.getElementById('custom_fee').disabled = this.checked">
                        <span class="text-sm font-medium">Free Account (No Lot Fees)</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">When enabled, this auctioneer pays R0 for all lots</p>
                </div>

                <!-- Custom Flat Fee -->
                <div>
                    <label for="custom_lot_fee" class="block text-sm font-medium mb-1">
                        Custom Lot Fee (Optional)
                    </label>
                    <input type="number" step="0.01" min="0" name="custom_lot_fee" id="custom_fee"
                           value="{{ $auctioneer->custom_lot_fee }}"
                           {{ $auctioneer->is_free_account ? 'disabled' : '' }}
                           class="input w-full max-w-xs"
                           placeholder="Leave empty for standard pricing">
                    <p class="text-xs text-gray-500 mt-1">
                        If set, charges this amount per lot instead of tiered pricing (R1/R5/R20).
                        Leave empty for standard tiered pricing.
                    </p>
                </div>

                <!-- Custom Per-Tier Prices -->
                <div id="custom_tiers_section">
                    <label class="block text-sm font-medium mb-1">Custom Tier Prices (Optional)</label>
                    <div class="grid grid-cols-3 gap-3 max-w-lg">
                        <div>
                            <label for="custom_tier_basic" class="block text-xs text-gray-500 mb-1">Basic (1 image)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_basic" id="custom_tier_basic"
                                   value="{{ $auctioneer->custom_tier_basic }}"
                                   class="input w-full"
                                   placeholder="Default: R{{ config('platform.pricing.tier_basic.price', 1) }}">
                        </div>
                        <div>
                            <label for="custom_tier_pro" class="block text-xs text-gray-500 mb-1">Pro (2-5 images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_pro" id="custom_tier_pro"
                                   value="{{ $auctioneer->custom_tier_pro }}"
                                   class="input w-full"
                                   placeholder="Default: R{{ config('platform.pricing.tier_pro.price', 5) }}">
                        </div>
                        <div>
                            <label for="custom_tier_premium" class="block text-xs text-gray-500 mb-1">Premium (6+ images)</label>
                            <input type="number" step="0.01" min="0" name="custom_tier_premium" id="custom_tier_premium"
                                   value="{{ $auctioneer->custom_tier_premium }}"
                                   class="input w-full"
                                   placeholder="Default: R{{ config('platform.pricing.tier_premium.price', 20) }}">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Override individual tier prices. Leave empty to use standard pricing. Ignored if Free Account or Custom Flat Fee is set.
                    </p>
                </div>

                <!-- Pricing Notes -->
                <div>
                    <label for="pricing_notes" class="block text-sm font-medium mb-1">
                        Notes (Optional)
                    </label>
                    <textarea name="pricing_notes" id="pricing_notes" rows="2"
                              class="input w-full"
                              placeholder="Reason for custom pricing (e.g., 'Test account', 'Partnership deal')">{{ $auctioneer->pricing_notes }}</textarea>
                </div>

                <div class="flex items-center space-x-3">
                    <button type="submit" class="btn btn-primary">
                        Update Pricing
                    </button>

                    <!-- Current Pricing Display -->
                    <div class="text-sm text-gray-600">
                        <strong>Current:</strong>
                        @if($auctioneer->is_free_account)
                            <span class="text-green-600 font-semibold">Free Account (R0)</span>
                        @elseif($auctioneer->custom_lot_fee)
                            <span class="text-blue-600 font-semibold">Custom Fee: {{ formatCurrency($auctioneer->custom_lot_fee) }}/lot</span>
                        @elseif($auctioneer->custom_tier_basic !== null || $auctioneer->custom_tier_pro !== null || $auctioneer->custom_tier_premium !== null)
                            <span class="text-purple-600 font-semibold">Custom Tiers:
                                {{ $auctioneer->custom_tier_basic !== null ? 'R' . $auctioneer->custom_tier_basic : 'R' . config('platform.pricing.tier_basic.price', 1) }} /
                                {{ $auctioneer->custom_tier_pro !== null ? 'R' . $auctioneer->custom_tier_pro : 'R' . config('platform.pricing.tier_pro.price', 5) }} /
                                {{ $auctioneer->custom_tier_premium !== null ? 'R' . $auctioneer->custom_tier_premium : 'R' . config('platform.pricing.tier_premium.price', 20) }}
                            </span>
                        @else
                            <span>Standard Pricing (R1/R5/R20)</span>
                        @endif
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const freeCheckbox = document.querySelector('input[name="is_free_account"]');
                        const flatFee = document.getElementById('custom_fee');
                        const tierInputs = ['custom_tier_basic', 'custom_tier_pro', 'custom_tier_premium']
                            .map(id => document.getElementById(id));

                        function updateTierState() {
                            const disabled = freeCheckbox.checked || (flatFee.value !== '' && flatFee.value !== null);
                            tierInputs.forEach(input => {
                                input.disabled = disabled;
                                if (disabled) input.classList.add('opacity-50');
                                else input.classList.remove('opacity-50');
                            });
                        }

                        freeCheckbox.addEventListener('change', updateTierState);
                        flatFee.addEventListener('input', updateTierState);
                        updateTierState();
                    });
                </script>
            </form>
        </div>

        <!-- Relist Controls -->
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Free Relist Reset</h2>
            <p class="text-sm text-gray-500 mb-4">
                Unsold lots with 0 bids get free relist eligibility. Use this setting to periodically clear that eligibility so the auctioneer must pay for relisting.
            </p>

            <form method="POST" action="{{ route('admin.auctioneers.update-relist-settings', $auctioneer) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="free_relist_reset" class="block text-sm font-medium mb-1">Reset Free Relists</label>
                    <select name="free_relist_reset" id="free_relist_reset" class="input w-full max-w-xs">
                        <option value="" {{ !$auctioneer->free_relist_reset ? 'selected' : '' }}>Not at all (never expires)</option>
                        <option value="weekly" {{ $auctioneer->free_relist_reset === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="biweekly" {{ $auctioneer->free_relist_reset === 'biweekly' ? 'selected' : '' }}>Every two weeks</option>
                        <option value="monthly" {{ $auctioneer->free_relist_reset === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                </div>

                @if($auctioneer->free_relist_last_reset_at)
                    <p class="text-sm text-gray-500">
                        Last reset: {{ $auctioneer->free_relist_last_reset_at->format('M d, Y H:i') }}
                    </p>
                @endif

                <button type="submit" class="btn btn-primary">Update Relist Settings</button>
            </form>
        </div>

        <!-- Credit Management -->
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Credit Management</h2>

            <form method="POST" action="{{ route('admin.auctioneers.add-credits', $auctioneer) }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="credit_amount" class="block text-sm font-medium mb-1">
                            Credit Amount (R)
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="credit_amount"
                               required class="input w-full" placeholder="100.00">
                        <p class="text-xs text-gray-500 mt-1">Amount to add to auctioneer's balance</p>
                    </div>

                    <div>
                        <label for="credit_description" class="block text-sm font-medium mb-1">
                            Description
                        </label>
                        <input type="text" name="description" id="credit_description"
                               required class="input w-full" placeholder="e.g., Marketing credit, Test account">
                        <p class="text-xs text-gray-500 mt-1">Reason for adding credits</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    Add Credits
                </button>
            </form>
        </div>

        <!-- Credit Ledger Link -->
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-2">Credit Ledger</h2>
            <p class="text-sm text-gray-500 mb-4">View all credit purchases, lot fees, commissions, sale income, and payouts.</p>
            <a href="{{ route('admin.auctioneers.credit-ledger', $auctioneer) }}" class="btn btn-primary">
                View Credit Ledger &rarr;
            </a>
        </div>

        <!-- Danger Zone -->
        <div class="card p-6 border-2 border-red-500 dark:border-red-700" x-data="{ confirmName: '', showForm: false }">
            <h2 class="text-xl font-bold mb-2 text-red-600 dark:text-red-400">Danger Zone</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Permanently delete this auctioneer and all associated data (auctions, lots, bids, images, credit history).
                The user account will be demoted to bidder.
            </p>

            @php
                $hasBlockingAuctions = $auctioneer->auctions->whereIn('status', ['live', 'upcoming', 'pending'])->count() > 0;
                $hasPendingPayouts = $auctioneer->payouts->where('status', 'pending')->count() > 0;
                $isBlocked = $hasBlockingAuctions || $hasPendingPayouts;
            @endphp

            @if($isBlocked)
                <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 rounded-lg p-4 mb-4">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Deletion is blocked:</p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1">
                        @if($hasBlockingAuctions)
                            <li>Has live or upcoming auctions — end or delete them first</li>
                        @endif
                        @if($hasPendingPayouts)
                            <li>Has pending payout requests — process or reject them first</li>
                        @endif
                    </ul>
                </div>
            @endif

            @if(!$isBlocked)
                <button @click="showForm = !showForm" class="btn bg-red-600 hover:bg-red-700 text-white" x-show="!showForm">
                    Delete Auctioneer Permanently
                </button>

                <div x-show="showForm" x-cloak>
                    <form method="POST" action="{{ route('admin.auctioneers.hard-delete', $auctioneer) }}">
                        @csrf
                        @method('DELETE')

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 text-red-600 dark:text-red-400">
                                Type "<strong>{{ $auctioneer->business_name }}</strong>" to confirm:
                            </label>
                            <input type="text" name="confirm_name" x-model="confirmName"
                                   class="input w-full max-w-md" autocomplete="off"
                                   placeholder="{{ $auctioneer->business_name }}">
                        </div>

                        <div class="flex items-center space-x-3">
                            <button type="submit"
                                    class="btn bg-red-600 hover:bg-red-700 text-white disabled:opacity-50"
                                    :disabled="confirmName !== '{{ addslashes($auctioneer->business_name) }}'">
                                Permanently Delete
                            </button>
                            <button type="button" @click="showForm = false; confirmName = ''" class="btn btn-outline">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
