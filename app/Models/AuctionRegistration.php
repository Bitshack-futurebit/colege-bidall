<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionRegistration extends Model
{
    use HasFactory;

    protected $table = 'event_registrations';

    protected $fillable = [
        'event_id',
        'user_id',
        'deposit_paid',
        'deposit_refunded',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'deposit_paid' => 'decimal:2',
            'deposit_refunded' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
