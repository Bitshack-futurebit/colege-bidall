<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Auction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'auctioneer_id',
        'title',
        'slug',
        'description',
        'status',
        'start_time',
        'end_time',
        'deposit_amount',
        'deposit_type',
        'requires_registration',
        'allow_proxy_bidding',
        'buyers_premium_percentage',
        'payment_deadline',
        'total_lots',
        'total_bids',
        'total_value',
        'winner_emails_sent',
        'facebook_post_id',
        'facebook_posted_status',
        'auction_type',
        'dutch_lot_mode',
        'dutch_drop_amount',
        'dutch_drop_interval',
        'dutch_drop_strategy',
        'dutch_lot_gap',
        'enable_online_payment',
        'sealed_mode',
        'is_community',
        'community_region_id',
        'community_schedule_id',
        'pilot_mode',
        'lineup_locks_at',
        'goes_live_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'payment_deadline' => 'datetime',
            'deposit_amount' => 'decimal:2',
            'buyers_premium_percentage' => 'decimal:2',
            'total_value' => 'decimal:2',
            'requires_registration' => 'boolean',
            'allow_proxy_bidding' => 'boolean',
            'winner_emails_sent' => 'boolean',
            'dutch_drop_amount' => 'decimal:2',
            'enable_online_payment' => 'boolean',
            'is_community' => 'boolean',
            'pilot_mode' => 'boolean',
            'lineup_locks_at' => 'datetime',
            'goes_live_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auction) {
            if (empty($auction->slug)) {
                $auction->slug = Str::slug($auction->title);

                // Ensure unique slug
                $originalSlug = $auction->slug;
                $count = 1;
                while (static::where('slug', $auction->slug)->exists()) {
                    $auction->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    // Relationships
    public function auctioneer()
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class, 'event_id')->orderBy('lot_number');
    }

    public function registrations()
    {
        return $this->hasMany(AuctionRegistration::class, 'event_id');
    }

    public function communityRegion()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function communitySchedule()
    {
        return $this->belongsTo(CommunitySchedule::class, 'community_schedule_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'event_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['upcoming', 'live']);
    }

    // Helper Methods
    public function requiresDeposit(): bool
    {
        return $this->deposit_type !== 'none' && $this->deposit_amount > 0;
    }

    public function requiresRegistration(): bool
    {
        return $this->requires_registration ?? false;
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming';
    }

    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canGoLive(): bool
    {
        // Can go live from draft (manual) or upcoming (automatic when time arrives)
        return in_array($this->status, ['draft', 'upcoming']) &&
               $this->start_time !== null &&
               $this->lots()->count() > 0;
    }

    public function goLive(): void
    {
        if (!$this->canGoLive()) {
            throw new \Exception('Auction cannot go live');
        }

        // Calculate lot schedules and end time before going live
        if ($this->isDutch()) {
            $this->calculateSequentialSchedule();
        }

        // If start time is in the future, schedule it (upcoming)
        // If start time is now or past, go live immediately
        if ($this->start_time > now()) {
            $this->update(['status' => 'upcoming']);
            // Don't set lots to live yet - will happen automatically when start_time arrives
        } else {
            $this->update(['status' => 'live']);

            if ($this->isDutch()) {
                // Dutch: only activate the first lot (sequential)
                $firstLot = $this->lots()->whereNull('withdrawn_at')->orderBy('lot_number')->first();
                if ($firstLot) {
                    $firstLot->update(['status' => 'live']);
                }
            } elseif ($this->isLiveFormat()) {
                // Live: only activate first lot, put it in presenting phase
                $this->scheduleLiveLots();
            } else {
                // English & Sealed: set all active (non-withdrawn) lots to live
                $this->lots()->whereNull('withdrawn_at')->update(['status' => 'live']);
            }
        }
    }

    /**
     * Start the first live-format lot in its presentation phase.
     * Remaining lots stay pending and advance one-by-one via Lot::closeLive().
     */
    public function scheduleLiveLots(): void
    {
        if (!$this->isLiveFormat()) {
            return;
        }

        $firstLot = $this->lots()->whereNull('withdrawn_at')->orderBy('lot_number')->first();
        if (!$firstLot) {
            return;
        }

        $now = now();

        // All live-format auctions start with a 5-minute warm-up countdown
        // before the first lot enters PRESENTING. Gives bidders time to settle
        // in and read the rules before bidding opens.
        $firstLot->update([
            'status' => 'live',
            'live_phase' => Lot::LIVE_PHASE_OPENING,
            'live_phase_ends_at' => $now->copy()->addSeconds(self::LIVE_OPENING_SECONDS),
            'live_opens_at' => $now->copy()->addSeconds(self::LIVE_OPENING_SECONDS + self::LIVE_PRESENTATION_SECONDS),
            'live_last_bid_at' => null,
        ]);
    }

    public function end(): void
    {
        $this->update(['status' => 'ended']);

        // Close all remaining live lots individually
        if ($this->isDutch()) {
            // Dutch: close live lots + any pending sequential lots that never started
            $openLots = $this->lots()->whereIn('status', ['live', 'draft', 'pending'])->get();
            foreach ($openLots as $lot) {
                $lot->closeDutch();
            }
        } elseif ($this->isSealed()) {
            // Sealed: determine winners based on sealed bids
            $liveLots = $this->lots()->where('status', 'live')->get();
            foreach ($liveLots as $lot) {
                $lot->closeSealed();
            }
        } elseif ($this->isLiveFormat()) {
            // Live: close the active lot + mark any remaining pending lots as unsold
            $openLots = $this->lots()->whereIn('status', ['live', 'pending', 'draft'])->get();
            foreach ($openLots as $lot) {
                $lot->closeLive(false);
            }
        } else {
            // English: Lot::close() handles sold/unsold status, commission deduction,
            // actual_end_time, free relist eligibility, and image cleanup scheduling
            $liveLots = $this->lots()->where('status', 'live')->get();
            foreach ($liveLots as $lot) {
                $lot->close();
            }
        }
    }

    public function calculateBuyerTotal(User $user): float
    {
        $wonLots = $this->lots()
            ->where('winning_bidder_id', $user->id)
            ->where('status', 'sold')
            ->get();

        $hammerTotal = $wonLots->sum('current_bid');
        $buyersPremium = $hammerTotal * ($this->buyers_premium_percentage / 100);

        return $hammerTotal + $buyersPremium;
    }

    // Live (Automated) Auction Phase Timing (seconds)
    public const LIVE_PRESENTATION_SECONDS = 10;
    public const LIVE_OPEN_CALL_SECONDS = 30;          // "Who'll open?" window — no bid in this time = lot closes as no-interest
    public const LIVE_ACTIVE_SILENCE_SECONDS = 8;      // silence after a bid before pulse fires (each bid resets it)
    public const LIVE_PULSE_ONCE_SECONDS = 15;         // "going once"
    public const LIVE_PULSE_TWICE_SECONDS = 10;        // "going twice" — shorter, more urgent
    public const LIVE_RESULT_SECONDS = 5;              // result display after gavel
    public const LIVE_INTERMISSION_SECONDS = 20;       // between lots
    public const LIVE_OPENING_SECONDS = 300;           // community auction warm-up countdown before the first lot's PRESENTING (5 min)

    // Backwards-compat alias for any external code still referencing the old name.
    public const LIVE_IDLE_SECONDS = self::LIVE_ACTIVE_SILENCE_SECONDS;

    // Dutch Auction Helpers
    public function isDutch(): bool
    {
        return $this->auction_type === 'dutch';
    }

    public function isEnglish(): bool
    {
        return $this->auction_type === 'english';
    }

    public function isSealed(): bool
    {
        return $this->auction_type === 'sealed';
    }

    public function isLiveFormat(): bool
    {
        return $this->auction_type === 'live';
    }

    public function isCommunity(): bool
    {
        return (bool) $this->is_community;
    }

    public function isInPilotMode(): bool
    {
        return $this->isCommunity() && (bool) $this->pilot_mode;
    }

    public function isSealedHighest(): bool
    {
        return $this->isSealed() && $this->sealed_mode === 'highest';
    }

    public function isSealedLowest(): bool
    {
        return $this->isSealed() && $this->sealed_mode === 'lowest';
    }

    public function isDutchSequential(): bool
    {
        return $this->isDutch();
    }

    /**
     * Whether this auction has online payment enabled and the auctioneer has PayFast configured.
     */
    public function hasOnlinePayment(): bool
    {
        // MONETIZATION PARKED — free standalone product: no real bidder settlement.
        // Returning false hides every "Pay Now" prompt across won-lot views; winners
        // are simply recorded at hammer price. Restore the line below for a paid deployment.
        return false;

        return $this->enable_online_payment && $this->auctioneer->hasPayfastConfigured();
    }

    /**
     * Calculate sequential lot schedule times based on lot order.
     */
    public function calculateSequentialSchedule(): void
    {
        if (!$this->isDutchSequential() || !$this->start_time) {
            return;
        }

        $lotGap = max($this->dutch_lot_gap ?? 0, Lot::DUTCH_COUNTDOWN_BUFFER);
        $lots = $this->lots()->whereNull('withdrawn_at')->orderBy('lot_number')->get();

        // First lot: drops start after gap (which serves as the countdown) from auction start
        $currentDropStart = $this->start_time->copy()->addSeconds($lotGap);

        foreach ($lots as $lot) {
            $floorReachedAt = Lot::calculateFloorReachedAt(
                (float) $lot->dutch_start_price,
                (float) $lot->dutch_floor_price,
                (float) $lot->dutch_drop_amount,
                (int) $lot->dutch_drop_interval,
                $lot->dutch_drop_strategy ?: 'constant'
            );

            $lotEndTime = $currentDropStart->copy()->addSeconds($floorReachedAt + Lot::DUTCH_FLOOR_BUFFER);
            $lot->update([
                'dutch_start_time' => $currentDropStart->copy(),
                'dutch_end_time' => $lotEndTime,
                'end_time' => $lotEndTime,
            ]);
            // Next lot's drops start after: this lot ends + gap
            $currentDropStart = $lotEndTime->copy()->addSeconds($lotGap);
        }

        // Update auction end time to match last lot end
        $lastLot = $lots->last();
        if ($lastLot) {
            $this->update(['end_time' => $lastLot->dutch_end_time]);
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
