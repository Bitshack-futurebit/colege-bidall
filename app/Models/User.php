<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->paddle_number)) {
                $user->paddle_number = static::generateUniquePaddleNumber();
            }
        });
    }

    public static function generateUniquePaddleNumber(): int
    {
        do {
            $number = random_int(1000, 9999);
        } while (static::where('paddle_number', $number)->exists());

        return $number;
    }

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'paddle_number',
        'phone',
        'whatsapp',
        'address',
        'city',
        'province',
        'postal_code',
        'lat',
        'lng',
        'is_active',
        'suspended_by_auctioneer_id',
        'suspension_reason',
        'email_notifications',
        'event_reminders',
        'community_region_id',
        'community_region_changed_at',
        'referred_by_agent_id',
        'bidding_disabled',
        'bidding_disabled_reason',
        'id_document',
        'id_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'bidding_disabled' => 'boolean',
            'email_notifications' => 'boolean',
            'event_reminders' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'community_region_changed_at' => 'datetime',
            'id_verified_at' => 'datetime',
        ];
    }

    public function communityRegion()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function referrer()
    {
        return $this->belongsTo(Agent::class, 'referred_by_agent_id');
    }

    public function isAgent(): bool
    {
        return $this->agent !== null && $this->agent->isActive();
    }

    public function communitySuspensions()
    {
        return $this->hasMany(CommunitySellerSuspension::class);
    }

    public function communityConfirmations()
    {
        return $this->hasMany(LotConfirmation::class, 'seller_user_id');
    }

    public function communityListedLots()
    {
        return $this->hasMany(Lot::class, 'seller_user_id');
    }

    /**
     * Count seller's community declines inside a rolling window (days back from now).
     */
    public function communityDeclineCount(int $days = 30, ?int $regionId = null): int
    {
        $q = $this->communityConfirmations()
            ->where('action', LotConfirmation::ACTION_DECLINED)
            ->where('acted_at', '>=', now()->subDays($days));
        if ($regionId !== null) {
            $q->where('community_region_id', $regionId);
        }
        return $q->count();
    }

    /**
     * Number of confirmed community sales (first-lot protection lookup).
     */
    public function communityConfirmedSalesCount(?int $regionId = null): int
    {
        $q = $this->communityConfirmations()->whereIn('action', [
            LotConfirmation::ACTION_CONFIRMED,
            LotConfirmation::ACTION_AUTO_CONFIRMED,
        ]);
        if ($regionId !== null) {
            $q->where('community_region_id', $regionId);
        }
        return $q->count();
    }

    public function activeCommunitySuspension(?int $regionId = null): ?CommunitySellerSuspension
    {
        $q = $this->communitySuspensions()->active();
        if ($regionId !== null) {
            $q->where(function ($qq) use ($regionId) {
                $qq->where('community_region_id', $regionId)->orWhereNull('community_region_id');
            });
        }
        return $q->latest('id')->first();
    }

    public function isCommunityPermanentlyBanned(): bool
    {
        return $this->communitySuspensions()
            ->where('is_permanent', true)
            ->exists();
    }

    /**
     * Listing eligibility for a region — returns [allowed, reason].
     */
    public function canListInCommunity(CommunityRegion $region): array
    {
        if (!$this->is_active) {
            return [false, 'Your account is inactive.'];
        }
        if (!$region->is_active) {
            return [false, 'This community region is not accepting listings.'];
        }
        if ($this->isCommunityPermanentlyBanned()) {
            return [false, 'You are permanently banned from community auctions.'];
        }
        if ($this->activeCommunitySuspension($region->id)) {
            return [false, 'You are currently suspended from community auctions.'];
        }
        return [true, null];
    }

    // Relationships
    public function auctioneer()
    {
        return $this->hasOne(Auctioneer::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function hasBidOnAuctioneerAuctions(int $auctioneerId): bool
    {
        return $this->bids()
            ->whereHas('lot.auction', function ($q) use ($auctioneerId) {
                $q->where('auctioneer_id', $auctioneerId)
                  ->whereIn('status', ['live', 'ended']);
            })
            ->exists();
    }

    public function wonLots()
    {
        return $this->hasMany(Lot::class, 'winning_bidder_id');
    }

    public function auctionRegistrations()
    {
        return $this->hasMany(AuctionRegistration::class);
    }

    public function watchlist()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function followedAuctioneers()
    {
        return $this->hasMany(AuctioneerFollower::class);
    }

    public function readNotifications()
    {
        return $this->belongsToMany(PushNotification::class, 'notification_reads')
            ->withPivot('read_at');
    }

    public function suspendedByAuctioneer()
    {
        return $this->belongsTo(Auctioneer::class, 'suspended_by_auctioneer_id');
    }

    public function staffMembership()
    {
        return $this->hasOne(StaffMember::class);
    }

    public function termsAcceptances()
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function hasAcceptedCurrentTerms(): bool
    {
        $requiredTerms = TermsVersion::currentForUser($this);

        if (empty($requiredTerms)) {
            return true; // No published terms yet
        }

        $acceptedIds = $this->termsAcceptances()->pluck('terms_version_id')->toArray();

        foreach ($requiredTerms as $terms) {
            if (!in_array($terms->id, $acceptedIds)) {
                return false;
            }
        }

        return true;
    }

    public function unacceptedTerms(): array
    {
        $requiredTerms = TermsVersion::currentForUser($this);
        $acceptedIds = $this->termsAcceptances()->pluck('terms_version_id')->toArray();

        return array_filter($requiredTerms, fn($t) => !in_array($t->id, $acceptedIds));
    }

    /**
     * Profile completeness score out of 100.
     * ID document adds 15 when uploaded, 30 when verified (replacing the 15).
     */
    public function profileScore(): int
    {
        $score = 0;
        if ($this->name)        $score += 5;
        if ($this->email)       $score += 5;
        if ($this->phone)       $score += 15;
        if ($this->whatsapp)    $score += 10;
        if ($this->address)     $score += 10;
        if ($this->city)        $score += 10;
        if ($this->province)    $score += 10;
        if ($this->postal_code) $score += 5;
        if ($this->id_document) {
            $score += $this->id_verified_at ? 30 : 15;
        }
        return $score;
    }

    /** none | pending | verified */
    public function idStatus(): string
    {
        if (!$this->id_document) return 'none';
        return $this->id_verified_at ? 'verified' : 'pending';
    }

    // Role Checks
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAuctioneer(): bool
    {
        return $this->role === 'auctioneer';
    }

    public function isBidder(): bool
    {
        return $this->role === 'bidder';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Resolve the auctioneer for this user — works for both owners and staff.
     */
    public function resolveAuctioneer(): ?Auctioneer
    {
        if ($this->isAuctioneer() || $this->isAdmin()) {
            return $this->auctioneer;
        }

        if ($this->isStaff() && $this->staffMembership) {
            return $this->staffMembership->auctioneer;
        }

        return null;
    }

    /**
     * Check if user has a specific staff permission.
     * Owners and admins always pass. Staff checked by role.
     */
    public function hasStaffPermission(string $permission): bool
    {
        if ($this->isAuctioneer() || $this->isAdmin()) {
            return true;
        }

        if (!$this->isStaff() || !$this->staffMembership || !$this->staffMembership->is_active) {
            return false;
        }

        return match ($permission) {
            'lots' => $this->staffMembership->canManageLots(),
            'auctions' => $this->staffMembership->canManageAuctions(),
            'collections' => $this->staffMembership->canManageCollections(),
            default => false,
        };
    }

    public function hasActivatedAuctioneer(): bool
    {
        return $this->isAuctioneer() &&
               $this->auctioneer &&
               $this->auctioneer->credit_balance > 0;
    }

    // Helper Methods
    public function isRegisteredForAuction(int $eventId): bool
    {
        return $this->auctionRegistrations()
            ->where('event_id', $eventId)
            ->exists();
    }

    public function hasWatchlisted(int $lotId): bool
    {
        return $this->watchlist()
            ->where('lot_id', $lotId)
            ->exists();
    }

    public function isFollowingAuctioneer(int $auctioneerId): bool
    {
        return $this->followedAuctioneers()
            ->where('auctioneer_id', $auctioneerId)
            ->exists();
    }

    public function hasBidOnLot(int $lotId): bool
    {
        return $this->bids()
            ->where('lot_id', $lotId)
            ->exists();
    }

    public function highestBidForLot(int $lotId)
    {
        return $this->bids()
            ->where('lot_id', $lotId)
            ->orderBy('amount', 'desc')
            ->first();
    }

    public function hasTopBidOnLot(int $lotId): bool
    {
        $lot = Lot::find($lotId);
        if (!$lot) {
            return false;
        }

        return $lot->winning_bidder_id === $this->id;
    }
}
