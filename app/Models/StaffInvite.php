<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffInvite extends Model
{
    protected $fillable = [
        'auctioneer_id',
        'staff_role',
        'token',
        'invited_by',
        'expires_at',
        'used_at',
        'used_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function auctioneer()
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
