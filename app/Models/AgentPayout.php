<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPayout extends Model
{
    protected $fillable = [
        'agent_id',
        'amount',
        'status',
        'requested_at',
        'approved_by_user_id',
        'approved_at',
        'paid_at',
        'paid_via',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CommunityCommissionLedger::class);
    }
}
