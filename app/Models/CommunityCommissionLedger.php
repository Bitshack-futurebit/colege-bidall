<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityCommissionLedger extends Model
{
    protected $table = 'community_commission_ledger';

    protected $fillable = [
        'lot_id',
        'community_region_id',
        'seller_user_id',
        'buyer_user_id',
        'hammer_amount',
        'commission_amount',
        'period_key',
        'tier1_portion',
        'tier2_portion',
        'platform_share',
        'agent_share',
        'agent_id_at_accrual',
        'status',
        'accrued_at',
        'seller_paid_at',
        'agent_paid_at',
        'voided_at',
        'void_reason',
        'seller_payment_transaction_id',
        'agent_payout_id',
    ];

    protected function casts(): array
    {
        return [
            'hammer_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tier1_portion' => 'decimal:2',
            'tier2_portion' => 'decimal:2',
            'platform_share' => 'decimal:2',
            'agent_share' => 'decimal:2',
            'accrued_at' => 'datetime',
            'seller_paid_at' => 'datetime',
            'agent_paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function region()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id_at_accrual');
    }

    public function payout()
    {
        return $this->belongsTo(AgentPayout::class);
    }

    public function scopeAccrued($q)        { return $q->where('status', 'accrued'); }
    public function scopeSellerPaid($q)     { return $q->where('status', 'seller_paid'); }
    public function scopeAgentPaid($q)      { return $q->where('status', 'agent_paid'); }
    public function scopeVoided($q)         { return $q->where('status', 'voided'); }
    public function scopeNotVoided($q)      { return $q->where('status', '!=', 'voided'); }
}
