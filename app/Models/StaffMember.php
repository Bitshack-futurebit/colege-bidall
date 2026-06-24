<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    protected $fillable = [
        'user_id',
        'auctioneer_id',
        'staff_role',
        'invited_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function canManageLots(): bool
    {
        return in_array($this->staff_role, ['lot_manager', 'auction_manager']);
    }

    public function canManageAuctions(): bool
    {
        return $this->staff_role === 'auction_manager';
    }

    public function canManageCollections(): bool
    {
        return $this->staff_role === 'collections_manager';
    }
}
