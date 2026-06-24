<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Auctioneer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'bio',
        'description',
        'rules',
        'logo',
        'profile_image',
        'banner_image',
        'website',
        'facebook',
        'instagram',
        'tiktok',
        'twitter',
        'linkedin',
        'whatsapp_number',
        'whatsapp_group_link',
        'is_activated',
        'activated_at',
        'credit_balance',
        'payout_balance',
        'total_sales',
        'total_fees_paid',
        'total_commissions_paid',
        'total_payouts_received',
        'is_free_account',
        'custom_lot_fee',
        'custom_tier_basic',
        'custom_tier_pro',
        'custom_tier_premium',
        'pricing_notes',
        'free_relist_reset',
        'free_relist_last_reset_at',
        'promo_code_id',
        'payfast_merchant_id',
        'payfast_merchant_key',
        'payfast_passphrase',
        'payfast_sandbox',
        'white_label_enabled',
        'brand_primary_color',
        'brand_secondary_color',
        'brand_favicon',
        'brand_hero_text',
    ];

    // Default rules text
    const DEFAULT_RULES = <<<'RULES'
What You Agree to When Bidding

Registration & Eligibility
You must register with accurate information (name, email, phone, etc.). You confirm you are at least 18 years old (or the legal age in your jurisdiction) and have the legal capacity to enter binding contracts.

Agreement to Terms
By registering, placing any bid, or purchasing, you agree to these rules and any auction-specific terms listed on the lot or auction page.

"As Is, Where Is" – No Warranties
All items are sold strictly "AS IS, WHERE IS" with all faults. You understand there are no warranties of any kind (including condition, authenticity, description accuracy, or fitness for purpose). You bid based solely on your own inspection, research, and judgment.

Inspection & No Returns
You are responsible for inspecting items (in person or via photos/videos) before bidding. All sales are final — no returns, refunds, exchanges, or cancellations (except in rare cases of major auction error).

Bidding Is Binding
Every bid you place is a serious, legally binding offer to purchase the item at that price (plus fees/taxes). Bids cannot be retracted once submitted.

Bid Increments & Closing
Follow the displayed bid increments. For timed online auctions, bids in the final minutes may extend the closing time (soft close/auto-extend rules apply as shown).

Buyer's Premium & Total Cost
You agree to pay the buyer's premium (usually 10–25%, as stated) added to your winning bid, plus any applicable sales tax or fees.

Payment Obligation & Deadline
If you win, you must pay the full amount (hammer price + buyer's premium + tax/fees) within the stated timeframe (typically 1–3 business days / 48 hours). Accepted payment methods are listed per auction. Late or non-payment may result in loss of the item and account restrictions.

Pickup / Removal
You must arrange to pick up and remove your items within the posted deadline (usually 3–7 days after payment). Items not removed may incur storage fees or be considered abandoned.

Shipping (If Applicable)
Many auctions are pickup-only. If shipping is offered, it is at your expense and risk (often via third-party services you arrange).

Risk & Title
Risk of loss or damage passes to you immediately when you become the winning bidder. Title transfers only after full payment is received.

Prohibited Actions
You agree not to engage in bid rigging, shill bidding, using fake accounts, collusion, harassment, or any abusive conduct. Violations may result in account suspension or ban.

Your Responsibility for Funds & Account
You are fully responsible for all bids placed from your account. Ensure you have sufficient funds before bidding. A winning bid creates a binding commitment to complete the purchase.
RULES;

    protected function casts(): array
    {
        return [
            'is_activated' => 'boolean',
            'activated_at' => 'datetime',
            'credit_balance' => 'decimal:2',
            'payout_balance' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'total_fees_paid' => 'decimal:2',
            'total_commissions_paid' => 'decimal:2',
            'total_payouts_received' => 'decimal:2',
            'is_free_account' => 'boolean',
            'custom_lot_fee' => 'decimal:2',
            'custom_tier_basic' => 'decimal:2',
            'custom_tier_pro' => 'decimal:2',
            'custom_tier_premium' => 'decimal:2',
            'free_relist_last_reset_at' => 'datetime',
            'payfast_passphrase' => 'encrypted',
            'payfast_sandbox' => 'boolean',
            'white_label_enabled' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auctioneer) {
            if (empty($auctioneer->slug)) {
                $auctioneer->slug = Str::slug($auctioneer->business_name);

                // Ensure unique slug
                $originalSlug = $auctioneer->slug;
                $count = 1;
                while (static::where('slug', $auctioneer->slug)->exists()) {
                    $auctioneer->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auctions()
    {
        return $this->hasMany(Auction::class, 'auctioneer_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function followers()
    {
        return $this->hasMany(AuctioneerFollower::class);
    }

    public function ratings()
    {
        return $this->hasMany(AuctioneerRating::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function salesRecords()
    {
        return $this->hasMany(SalesRecord::class);
    }

    public function staffMembers()
    {
        return $this->hasMany(StaffMember::class);
    }

    public function staffInvites()
    {
        return $this->hasMany(StaffInvite::class);
    }

    // Scopes
    public function scopeActivated($query)
    {
        return $query->where('is_activated', true);
    }

    public function scopeWithLocation($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereNotNull('lat')->whereNotNull('lng');
        });
    }

    public function scopeCommunity($query)
    {
        return $query->where('slug', 'like', 'community-%');
    }

    public function scopeNotCommunity($query)
    {
        return $query->where('slug', 'not like', 'community-%');
    }

    public function isCommunity(): bool
    {
        return str_starts_with($this->slug ?? '', 'community-');
    }

    /**
     * Resolve the CommunityRegion this auctioneer represents (by slug convention),
     * or null if this isn't a community-system auctioneer.
     */
    public function communityRegion(): ?CommunityRegion
    {
        if (!$this->isCommunity()) return null;
        $regionSlug = substr($this->slug, strlen('community-'));
        return CommunityRegion::where('slug', $regionSlug)->first();
    }

    // Helper Methods
    public function activate(): void
    {
        $this->update([
            'is_activated' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Check if auctioneer is in good standing (not too deeply negative).
     * Good standing = can still operate, just needs top-up eventually.
     */
    public function isInGoodStanding(): bool
    {
        // Allow operations even with negative balance (from commissions)
        // But not if they're severely in debt (more than -R1000)
        return $this->credit_balance > -1000;
    }

    /**
     * Check if auctioneer can make an auction go live.
     * Requires enough credits to cover all lot fees.
     */
    public function canGoLiveWithAuction(Auction $auction): bool
    {
        // MONETIZATION PARKED — free product: no credit gate on going live.
        return true;

        $totalCost = $auction->lots
            ->where('is_free_relist', false) // free relists cost nothing
            ->whereNull('withdrawn_at')
            ->sum(function ($lot) {
                return $this->calculateLotCost($lot->image_tier ?? 'basic');
            });

        return $this->credit_balance >= $totalCost;
    }

    /**
     * Calculate total cost for an auction's lots (excluding free relists and withdrawn lots).
     */
    public function calculateAuctionCost(Auction $auction): float
    {
        return $auction->lots
            ->where('is_free_relist', false)
            ->whereNull('withdrawn_at')
            ->sum(function ($lot) {
                return $this->calculateLotCost($lot->image_tier ?? 'basic');
            });
    }

    public function hasCredits(float $amount): bool
    {
        return $this->credit_balance >= $amount;
    }

    public function deductCredits(float $amount, string $type, ?int $lotId = null, ?string $description = null): void
    {
        // Commission and payout types can bypass positive-balance check
        // (lot_close can go negative from commission debt; payout is validated upstream)
        $bypassTypes = ['lot_close', 'adjustment', 'payout'];
        if (!in_array($type, $bypassTypes) && !$this->hasCredits($amount)) {
            throw new \Exception('Insufficient credits');
        }

        $this->decrement('credit_balance', $amount);

        CreditTransaction::create([
            'auctioneer_id' => $this->id,
            'lot_id' => $lotId,
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->fresh()->credit_balance,
            'description' => $description,
        ]);
    }

    public function addCredits(float $amount, string $type, ?string $description = null): void
    {
        $this->increment('credit_balance', $amount);

        CreditTransaction::create([
            'auctioneer_id' => $this->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->fresh()->credit_balance,
            'description' => $description,
        ]);
    }

    /**
     * Get available payout balance.
     *
     * = credit_balance - pending_clearance (48hr hold) - minimum_balance reserve (R100).
     * The R100 minimum must always remain in the account after any withdrawal.
     */
    public function getAvailablePayoutBalance(): float
    {
        $pendingClearance = $this->getPendingClearance();
        $minimumBalance = (float) config('platform.payout.minimum_balance', 100);
        return max(0, (float) $this->credit_balance - $pendingClearance - $minimumBalance);
    }

    /**
     * Get pending clearance amount (funds in 48-hour hold)
     */
    public function getPendingClearance(): float
    {
        return $this->salesRecords()
            ->where('funds_available_at', '>', now())
            ->sum('net_to_auctioneer');
    }

    /**
     * Request a payout
     */
    public function requestPayout(float $amount, array $bankDetails = []): Payout
    {
        $minimumPayout   = (float) config('platform.payout.minimum_payout', 500);
        $minimumBalance  = (float) config('platform.payout.minimum_balance', 100);
        $availableBalance = $this->getAvailablePayoutBalance();

        if ($amount < $minimumPayout) {
            throw new \Exception('Minimum payout amount is ' . formatCurrency($minimumPayout));
        }

        if ($amount > $availableBalance) {
            $pendingClearance = $this->getPendingClearance();
            $msg = 'Payout amount exceeds available balance. '
                . 'A minimum balance of ' . formatCurrency($minimumBalance) . ' must remain in your account after withdrawal.';
            if ($pendingClearance > 0) {
                $msg .= ' You also have ' . formatCurrency($pendingClearance) . ' pending clearance (available within 48 hours).';
            }
            throw new \Exception($msg);
        }

        return Payout::create([
            'auctioneer_id' => $this->id,
            'amount' => $amount,
            'status' => 'pending',
            'bank_name' => $bankDetails['bank_name'] ?? null,
            'account_holder' => $bankDetails['account_holder'] ?? null,
            'account_number' => $bankDetails['account_number'] ?? null,
            'branch_code' => $bankDetails['branch_code'] ?? null,
            'requested_at' => now(),
        ]);
    }

    /**
     * Check if auctioneer can request payout
     */
    public function canRequestPayout(): bool
    {
        $minimumPayout = config('platform.payout.minimum_payout', 500);
        return $this->getAvailablePayoutBalance() >= $minimumPayout;
    }

    /**
     * Get pending payout requests
     */
    public function pendingPayouts()
    {
        return $this->payouts()->where('status', 'pending');
    }

    public function calculateLotCost(string $imageTier): float
    {
        // MONETIZATION PARKED — the standalone product is free to use.
        // Listing a lot never costs credits, regardless of account flags or config.
        return 0;

        // Free accounts pay nothing
        if ($this->is_free_account) {
            return 0;
        }

        // Custom flat fee overrides tiered pricing
        if ($this->custom_lot_fee !== null) {
            return (float) $this->custom_lot_fee;
        }

        // Custom per-tier pricing
        $customTierField = "custom_tier_{$imageTier}";
        if ($this->$customTierField !== null) {
            return (float) $this->$customTierField;
        }

        // Standard tiered pricing
        return match ($imageTier) {
            'basic' => (float) config('platform.pricing.tier_basic.price', 1),
            'pro' => (float) config('platform.pricing.tier_pro.price', 5),
            'premium' => (float) config('platform.pricing.tier_premium.price', 20),
            default => 1,
        };
    }

    public function canCreateLot(string $imageTier): bool
    {
        $cost = $this->calculateLotCost($imageTier);
        return $this->hasCredits($cost);
    }

    /**
     * Check if auctioneer has PayFast credentials configured for direct payments.
     */
    public function hasPayfastConfigured(): bool
    {
        return !empty($this->payfast_merchant_id) && !empty($this->payfast_merchant_key);
    }

    public function isWhiteLabel(): bool
    {
        return $this->white_label_enabled && $this->brand_primary_color !== null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
