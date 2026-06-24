<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotConfirmation extends Model
{
    use HasFactory;

    public const ACTION_CONFIRMED = 'confirmed';
    public const ACTION_DECLINED = 'declined';
    public const ACTION_AUTO_CONFIRMED = 'auto_confirmed';

    protected $fillable = [
        'lot_id',
        'seller_user_id',
        'community_region_id',
        'winning_bid',
        'action',
        'reason',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'winning_bid' => 'decimal:2',
            'acted_at' => 'datetime',
        ];
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function region()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function scopeDeclined($query)
    {
        return $query->where('action', self::ACTION_DECLINED);
    }

    public function scopeForSeller($query, int $userId)
    {
        return $query->where('seller_user_id', $userId);
    }
}
