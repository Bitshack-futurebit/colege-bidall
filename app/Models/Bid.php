<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'user_id',
        'amount',
        'is_winning',
        'is_proxy',
        'is_dutch_buy',
        'quantity_bought',
        'ip_address',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_winning' => 'boolean',
            'is_proxy' => 'boolean',
            'is_dutch_buy' => 'boolean',
            'placed_at' => 'datetime',
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
}
