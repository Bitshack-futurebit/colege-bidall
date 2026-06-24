<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunitySellerSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'community_region_id',
        'starts_at',
        'ends_at',
        'reason',
        'is_permanent',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_permanent' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function region()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
              ->orWhere('ends_at', '>', now());
        })->where('starts_at', '<=', now());
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->is_permanent) {
            return true;
        }
        return $this->ends_at && $this->ends_at->isFuture();
    }
}
