<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'approved_at',
        'approved_by_user_id',
        'suspended_at',
        'suspension_reason',
        'whatsapp_group_name',
        'whatsapp_group_size_claim',
        'whatsapp_group_proof_path',
        'referral_code',
        'bio',
        'photo',
        'public_whatsapp_number',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function communities()
    {
        return $this->belongsToMany(CommunityRegion::class, 'agent_community')
            ->withPivot(['is_primary', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    /** Communities this agent is currently primary on. */
    public function activeCommunities()
    {
        return $this->communities()
            ->wherePivot('is_primary', true)
            ->wherePivotNull('ended_at');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CommunityCommissionLedger::class, 'agent_id_at_accrual');
    }

    public function payouts()
    {
        return $this->hasMany(AgentPayout::class);
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_agent_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Sum of agent_share on rows in seller_paid status (collected, claimable). */
    public function availableBalance(): float
    {
        return (float) $this->ledgerEntries()
            ->where('status', 'seller_paid')
            ->sum('agent_share');
    }

    /** Generate a unique short referral code. */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('referral_code', $code)->exists());
        return $code;
    }
}
