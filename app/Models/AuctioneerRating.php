<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctioneerRating extends Model
{
    protected $fillable = ['user_id', 'auctioneer_id', 'rating'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auctioneer(): BelongsTo
    {
        return $this->belongsTo(Auctioneer::class);
    }
}
