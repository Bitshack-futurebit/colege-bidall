<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerStrike extends Model
{
    protected $fillable = [
        'user_id',
        'lot_id',
        'seller_user_id',
        'reason',
        'reported_at',
        'reversed_at',
        'reversed_by_user_id',
        'reversal_note',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function user()      { return $this->belongsTo(User::class); }
    public function lot()       { return $this->belongsTo(Lot::class); }
    public function seller()    { return $this->belongsTo(User::class, 'seller_user_id'); }
    public function reverser()  { return $this->belongsTo(User::class, 'reversed_by_user_id'); }

    public function scopeActive($q)
    {
        return $q->whereNull('reversed_at');
    }

    public function scopeWithinMonths($q, int $months)
    {
        return $q->where('reported_at', '>=', now()->subMonths($months));
    }
}
