<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PushNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sender_type',
        'sender_id',
        'auctioneer_id',
        'target_user_id',
        'audience',
        'title',
        'body',
        'url',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function auctioneer(): BelongsTo
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function readBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_reads')
            ->withPivot('read_at');
    }

    /**
     * Scope: notifications visible to this user based on audience targeting.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // all_users targets everyone
            $q->where('audience', 'all_users');

            // Role-based audiences
            if ($user->role === 'bidder') {
                $q->orWhere('audience', 'all_bidders');
            } elseif ($user->role === 'auctioneer') {
                $q->orWhere('audience', 'all_auctioneers');
            } elseif ($user->role === 'admin') {
                $q->orWhere('audience', 'all_admins');
            }

            // Follower-targeted: user must follow that auctioneer
            $followedAuctioneerIds = AuctioneerFollower::where('user_id', $user->id)->pluck('auctioneer_id');
            if ($followedAuctioneerIds->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($followedAuctioneerIds) {
                    $sub->where('audience', 'followers')
                        ->whereIn('auctioneer_id', $followedAuctioneerIds);
                });
            }

            // Specific user targeting (e.g. confirm/reject notifications)
            $q->orWhere(function ($sub) use ($user) {
                $sub->where('audience', 'specific_user')
                    ->where('target_user_id', $user->id);
            });
        });
    }

    public function getTargetUserIds(): \Illuminate\Support\Collection
    {
        return match ($this->audience) {
            'followers' => AuctioneerFollower::where('auctioneer_id', $this->auctioneer_id)
                ->pluck('user_id'),
            'all_users' => User::pluck('id'),
            'all_bidders' => User::where('role', 'bidder')->pluck('id'),
            'all_auctioneers' => User::where('role', 'auctioneer')->pluck('id'),
            'all_admins' => User::where('role', 'admin')->pluck('id'),
            'specific_user' => $this->target_user_id ? collect([$this->target_user_id]) : collect(),
            default => collect(),
        };
    }
}
