<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'auctioneer_id',
        'event_id',
        'lot_id',
        'type',
        'amount',
        'platform_fee',
        'status',
        'payment_method',
        'payment_id',
        'payment_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'payment_data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auctioneer()
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class, 'event_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
