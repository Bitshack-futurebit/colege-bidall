<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxyBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'user_id',
        'max_amount',
        'is_active',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
