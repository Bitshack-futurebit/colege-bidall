<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_free_account',
        'custom_lot_fee',
        'custom_tier_basic',
        'custom_tier_pro',
        'custom_tier_premium',
        'free_relist_reset',
        'bonus_credits',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_free_account' => 'boolean',
            'custom_lot_fee' => 'decimal:2',
            'custom_tier_basic' => 'decimal:2',
            'custom_tier_pro' => 'decimal:2',
            'custom_tier_premium' => 'decimal:2',
            'bonus_credits' => 'decimal:2',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function auctioneers()
    {
        return $this->hasMany(Auctioneer::class);
    }

    /**
     * Check if this promo code is valid for use.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
