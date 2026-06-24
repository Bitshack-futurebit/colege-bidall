<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctioneerFollower extends Model
{
    protected $fillable = [
        'user_id',
        'auctioneer_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auctioneer(): BelongsTo
    {
        return $this->belongsTo(Auctioneer::class);
    }
}
