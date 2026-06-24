<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Lot extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        $invalidate = function (Lot $lot) {
            Cache::forget("lot-status:{$lot->id}");
            Cache::forget("lot-distinct-bidders:{$lot->id}");
            if ($lot->event_id) {
                Cache::forget("auction-status:{$lot->event_id}");
            }
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** Seconds to keep a Dutch lot at floor price before closing. */
    const DUTCH_FLOOR_BUFFER = 15;

    /** Seconds of "Get Ready" countdown before drops begin on each sequential lot. */
    const DUTCH_COUNTDOWN_BUFFER = 30;

    /**
     * Drop strategy phase definitions.
     * Each strategy has 3 phases covering the price range (top→bottom).
     * 'range' = fraction of total price range this phase covers.
     * 'drop_mult' = multiplier on base drop amount.
     * 'interval_mult' = multiplier on base drop interval.
     */
    const DROP_STRATEGIES = [
        'constant' => [
            'label' => 'Constant',
            'description' => 'Same drop rate throughout',
            'phases' => [
                ['range' => 1.0, 'drop_mult' => 1.0, 'interval_mult' => 1.0],
            ],
        ],
        'fast_sell' => [
            'label' => 'Fast Sell',
            'description' => 'Fast drops early, slows near floor',
            'phases' => [
                ['range' => 0.30, 'drop_mult' => 3.0, 'interval_mult' => 0.5],
                ['range' => 0.40, 'drop_mult' => 1.0, 'interval_mult' => 1.0],
                ['range' => 0.30, 'drop_mult' => 0.5, 'interval_mult' => 2.0],
            ],
        ],
        'max_value' => [
            'label' => 'Max Value',
            'description' => 'Rush to decision zone, crawl at the bottom',
            'phases' => [
                ['range' => 0.30, 'drop_mult' => 2.0, 'interval_mult' => 0.5],
                ['range' => 0.40, 'drop_mult' => 1.0, 'interval_mult' => 1.0],
                ['range' => 0.30, 'drop_mult' => 0.25, 'interval_mult' => 3.0],
            ],
        ],
        'high_drama' => [
            'label' => 'High Drama',
            'description' => 'Builds tension throughout, very slow finish',
            'phases' => [
                ['range' => 0.30, 'drop_mult' => 2.0, 'interval_mult' => 0.75],
                ['range' => 0.40, 'drop_mult' => 0.5, 'interval_mult' => 1.5],
                ['range' => 0.30, 'drop_mult' => 0.25, 'interval_mult' => 3.0],
            ],
        ],
    ];

    protected $fillable = [
        'event_id',
        'lot_number',
        'title',
        'description',
        'image_tier',
        'starting_bid',
        'reserve_price',
        'increment',
        'current_bid',
        'winning_bidder_id',
        'total_bids',
        'start_time',
        'end_time',
        'actual_end_time',
        'status',
        'reserve_met',
        'is_paid',
        'payment_status',
        'payment_method_selected_at',
        'payment_completed_at',
        'withdrawn_at',
        'withdrawal_reason',
        'subject_to_confirmation',
        'confirmation_message',
        'tender_document',
        'relisted_from_lot_id',
        'original_lot_id',
        'relist_count',
        'free_relist_eligible',
        'is_free_relist',
        'dutch_start_price',
        'dutch_floor_price',
        'dutch_drop_amount',
        'dutch_drop_interval',
        'dutch_drop_strategy',
        'quantity',
        'quantity_sold',
        'dutch_start_time',
        'dutch_end_time',
        'dutch_duration',
        'supplier_id',
        'live_phase',
        'live_phase_ends_at',
        'live_opens_at',
        'live_last_bid_at',
        'seller_user_id',
        'confirmation_expires_at',
        'declined_at',
        'decline_reason',
        'rolled_from_lot_id',
    ];

    protected function casts(): array
    {
        return [
            'starting_bid' => 'decimal:2',
            'reserve_price' => 'decimal:2',
            'increment' => 'decimal:2',
            'current_bid' => 'decimal:2',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'actual_end_time' => 'datetime',
            'payment_method_selected_at' => 'datetime',
            'payment_completed_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'dutch_start_price' => 'decimal:2',
            'dutch_floor_price' => 'decimal:2',
            'dutch_drop_amount' => 'decimal:2',
            'dutch_start_time' => 'datetime',
            'dutch_end_time' => 'datetime',
            'reserve_met' => 'boolean',
            'is_paid' => 'boolean',
            'subject_to_confirmation' => 'boolean',
            'free_relist_eligible' => 'boolean',
            'is_free_relist' => 'boolean',
            'live_phase_ends_at' => 'datetime',
            'live_opens_at' => 'datetime',
            'live_last_bid_at' => 'datetime',
            'confirmation_expires_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lot) {
            if (is_null($lot->lot_number)) {
                $lot->lot_number = static::where('event_id', $lot->event_id)->max('lot_number') + 1;
            }

            // Calculate lot timing based on auction and lot number
            $auction = Auction::find($lot->event_id);
            if ($auction) {
                if ($auction->isDutch()) {
                    // Dutch auctions: timing set at go-live (sequential)
                    $lot->start_time = $auction->start_time;
                    $lot->end_time = $auction->end_time;
                } elseif ($auction->isSealed()) {
                    // Sealed auctions: all lots share the same end time (no staggering)
                    $lot->start_time = $auction->start_time;
                    $lot->end_time = $auction->end_time;
                } else {
                    // English auctions: staggered lot closing
                    $lot->start_time = $auction->start_time;

                    $gapSeconds = config('auction.lot_gap_seconds', 30);
                    $secondsToAdd = ($lot->lot_number - 1) * $gapSeconds;

                    $lot->end_time = $auction->end_time->copy()->addSeconds($secondsToAdd);
                }
            }
        });
    }

    // Relationships
    public function auction()
    {
        return $this->belongsTo(Auction::class, 'event_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function winningBidder()
    {
        return $this->belongsTo(User::class, 'winning_bidder_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class)->orderBy('amount', 'desc');
    }

    public function images()
    {
        return $this->hasMany(LotImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(LotImage::class)->where('is_primary', true);
    }

    public function watchlistedBy()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function relistedFrom()
    {
        return $this->belongsTo(Lot::class, 'relisted_from_lot_id');
    }

    public function relistedTo()
    {
        return $this->hasMany(Lot::class, 'relisted_from_lot_id');
    }

    public function originalLot()
    {
        return $this->belongsTo(Lot::class, 'original_lot_id');
    }

    // Scopes
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeUnsold($query)
    {
        return $query->where('status', 'unsold');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('withdrawn_at');
    }

    public function scopeWithdrawn($query)
    {
        return $query->whereNotNull('withdrawn_at');
    }

    // Helper Methods
    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    public function isUnsold(): bool
    {
        return $this->status === 'unsold';
    }

    public function isPendingConfirmation(): bool
    {
        return $this->status === 'pending_confirmation';
    }

    /**
     * Confirm a pending_confirmation lot as sold.
     * Charges platform fee and sets payment status.
     */
    public function confirmSale(): void
    {
        if ($this->status !== 'pending_confirmation') {
            throw new \Exception('Only pending confirmation lots can be confirmed.');
        }

        $auction = $this->auction;

        $updateData = [
            'status' => 'sold',
        ];

        if (!$auction->enable_online_payment) {
            $updateData['payment_status'] = 'awaiting_collection';
            $updateData['payment_method_selected_at'] = now();
        }

        $this->update($updateData);

        // Deduct 1% platform fee (deferred from close)
        if ($this->current_bid) {
            $hammerPrice = $this->current_bid;
            $buyersPremium = $hammerPrice * ($auction->buyers_premium_percentage / 100);
            $total = $hammerPrice + $buyersPremium;
            $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

            $auction->auctioneer->deductCredits(
                $platformFee,
                'lot_close',
                $this->id,
                "1% fee for Lot #{$this->lot_number} - {$this->title}"
            );
        }
    }

    /**
     * Reject a pending_confirmation lot — mark as unsold.
     */
    public function rejectSale(): void
    {
        if ($this->status !== 'pending_confirmation') {
            throw new \Exception('Only pending confirmation lots can be rejected.');
        }

        $this->update([
            'status' => 'unsold',
            'winning_bidder_id' => null,
            'free_relist_eligible' => false,
        ]);
    }

    public function hasReserve(): bool
    {
        return $this->reserve_price && $this->reserve_price > 0;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    public function canBeWithdrawn(): bool
    {
        // Can only withdraw when auction is upcoming (draft lots are deleted, not withdrawn)
        $auction = $this->auction;
        return !$this->isWithdrawn() &&
               $auction &&
               $auction->status === 'upcoming';
    }

    public function withdraw(string $reason = null): void
    {
        if (!$this->canBeWithdrawn()) {
            throw new \Exception('This lot cannot be withdrawn');
        }

        $this->update([
            'withdrawn_at' => now(),
            'withdrawal_reason' => $reason,
        ]);
    }

    public function isReserveMet(): bool
    {
        if (!$this->hasReserve()) {
            return true;
        }

        return $this->current_bid >= $this->reserve_price;
    }

    public function timeRemaining(): int
    {
        if (!$this->isLive()) {
            return 0;
        }

        return max(0, (int) (now()->diffInSeconds($this->end_time)));
    }

    public function isInSoftClose(): bool
    {
        $softCloseTime = (int) config('auction.soft_close_time', 120);
        return $this->timeRemaining() <= $softCloseTime;
    }

    public function proxyBids()
    {
        return $this->hasMany(ProxyBid::class);
    }

    public function placeBid(User $user, float $amount, bool $isProxy = false): Bid
    {
        if (!$this->isLive()) {
            throw new \Exception('Lot is not live');
        }

        if ($this->isWithdrawn()) {
            throw new \Exception('This lot has been withdrawn and is no longer available for bidding');
        }

        // Live (Automated) auctions: advance phase first so stale presenting/pulse
        // state doesn't block a bid that should be valid, then gate on phase.
        if ($this->auction->isLiveFormat()) {
            $this->advanceLivePhase();
            $this->refresh();

            if (!$this->acceptsLiveBids()) {
                $msg = match ($this->live_phase) {
                    self::LIVE_PHASE_OPENING => 'The auction has not started yet — bidding opens after the countdown.',
                    self::LIVE_PHASE_PRESENTING => 'The auctioneer is presenting this lot — bidding opens shortly.',
                    self::LIVE_PHASE_INTERMISSION => 'The next lot is about to start.',
                    self::LIVE_PHASE_CLOSED => 'Bidding on this lot has closed.',
                    default => 'Bidding is not open on this lot.',
                };
                throw new \Exception($msg);
            }
        }

        // Check if user is registered for auction (only if auction requires registration)
        if ($this->auction->requiresRegistration() && !$user->isRegisteredForAuction($this->event_id)) {
            throw new \Exception('You must register for this auction to bid');
        }

        if ($this->isOwnedBy($user)) {
            throw new \Exception('You cannot bid on your own lot');
        }

        // Prevent manual bidders from outbidding themselves.
        // Proxy bids bypass this — a winning proxy may need to raise its own bid
        // to outbid a competing proxy from another user.
        if (!$isProxy && $this->winning_bidder_id === $user->id) {
            throw new \Exception('You already have the highest bid on this lot');
        }

        // Validate bid amount
        // Community lots use the dynamic BidLadder; all others use the stored increment.
        if ($this->isCommunityLot()) {
            $base = (float) ($this->current_bid ?? $this->starting_bid);
            $increment = \App\Helpers\BidLadder::nextIncrement($base);
            $minimumBid = $this->current_bid
                ? $base + $increment
                : (float) $this->starting_bid;
        } else {
            $minimumBid = $this->current_bid
                ? $this->current_bid + $this->increment
                : $this->starting_bid;
        }

        if ($amount < $minimumBid) {
            throw new \Exception("Minimum bid is R" . number_format($minimumBid, 2));
        }

        // Mark previous bids as not winning
        $this->bids()->update(['is_winning' => false]);

        // Create new bid
        $bid = $this->bids()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'is_winning' => true,
            'is_proxy' => $isProxy,
            'ip_address' => request()->ip(),
            'placed_at' => now(),
        ]);

        // Update lot
        $updatePayload = [
            'current_bid' => $amount,
            'winning_bidder_id' => $user->id,
            'reserve_met' => $this->isReserveMet(),
        ];
        if ($this->isCommunityLot()) {
            $updatePayload['increment'] = \App\Helpers\BidLadder::nextIncrement($amount);
        }
        $this->update($updatePayload);

        $this->increment('total_bids');

        // Live format: reset pulse/idle. English format: extend end_time if in soft close.
        if ($this->auction->isLiveFormat()) {
            $this->onLiveBidPlaced();
        } elseif ($this->isInSoftClose()) {
            $extension = (int) config('auction.soft_close_extension', 120);
            $this->end_time = $this->end_time->addSeconds($extension);
            $this->save();
        }

        // Trigger proxy resolution after manual bids (not proxy bids, to prevent recursion)
        if (!$isProxy) {
            app(\App\Services\ProxyBiddingService::class)->resolveAfterBid($this->fresh(), $user);
        }

        return $bid;
    }

    public function close(): void
    {
        // Unsold if no bidder OR reserve not met
        $hasBidder = (bool) $this->winning_bidder_id;
        $reserveMet = $this->isReserveMet();
        $isSold = $hasBidder && $reserveMet;

        // Check if any bids were placed (use DB count to avoid stale in-memory value)
        $hadBids = $this->bids()->exists();

        // Subject to confirmation: hold as pending_confirmation instead of sold
        $needsConfirmation = $isSold && $this->subject_to_confirmation;
        $finalStatus = $isSold
            ? ($needsConfirmation ? 'pending_confirmation' : 'sold')
            : 'unsold';

        $updateData = [
            'actual_end_time' => now(),
            'status' => $finalStatus,
            // No bids at all = eligible for free relist; bids placed but reserve not met = paid relist
            'free_relist_eligible' => !$isSold && !$hadBids,
        ];

        // Sold lots: if online payment enabled, leave payment_status null so bidder sees Pay Now
        // Otherwise auto-set to awaiting collection (skip for pending_confirmation — set on confirm)
        if ($isSold && !$needsConfirmation && !$this->auction->enable_online_payment) {
            $updateData['payment_status'] = 'awaiting_collection';
            $updateData['payment_method_selected_at'] = now();
        }

        // Clear winning bidder if reserve not met (lot didn't actually sell)
        if ($hasBidder && !$reserveMet) {
            $updateData['winning_bidder_id'] = null;
        }

        $this->update($updateData);

        // Schedule image deletion
        $deletionDate = now()->addDays(config('auction.image_auto_delete_days', 30));
        $this->images()->update(['scheduled_deletion_at' => $deletionDate]);

        // Deduct 1% platform fee (defer for pending_confirmation — charged on confirm)
        if ($isSold && !$needsConfirmation && $this->current_bid) {
            $auction = $this->auction;
            $hammerPrice = $this->current_bid;
            $buyersPremium = $hammerPrice * ($auction->buyers_premium_percentage / 100);
            $total = $hammerPrice + $buyersPremium;
            $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

            $auction->auctioneer->deductCredits(
                $platformFee,
                'lot_close',
                $this->id,
                "1% fee for Lot #{$this->lot_number} - {$this->title}"
            );
        }
    }

    // Sealed Auction Methods

    /**
     * Place or update a sealed bid on this lot.
     * One bid per user per lot — upsert pattern.
     */
    public function placeSealed(User $user, float $amount): Bid
    {
        if (!$this->isLive()) {
            throw new \Exception('This lot is not currently accepting bids.');
        }

        if ($this->isWithdrawn()) {
            throw new \Exception('This lot has been withdrawn.');
        }

        $auction = $this->auction;

        if (!$auction->isSealed()) {
            throw new \Exception('This is not a sealed auction.');
        }

        // Check auction registration
        if ($auction->requiresRegistration() && !$user->isRegisteredForAuction($this->event_id)) {
            throw new \Exception('You must register for this auction to bid.');
        }

        if ($this->isOwnedBy($user)) {
            throw new \Exception('You cannot bid on your own lot.');
        }

        // Validate against reserve
        if ($this->hasReserve()) {
            if ($auction->isSealedHighest() && $amount < $this->reserve_price) {
                throw new \Exception('Your bid must be at least ' . formatCurrency($this->reserve_price) . '.');
            }
            if ($auction->isSealedLowest() && $amount > $this->reserve_price) {
                throw new \Exception('Your bid must be at or below ' . formatCurrency($this->reserve_price) . '.');
            }
        }

        // Upsert: one bid per user per lot
        $existingBid = $this->bids()->where('user_id', $user->id)->first();

        if ($existingBid) {
            $existingBid->update([
                'amount' => $amount,
                'placed_at' => now(),
                'ip_address' => request()->ip(),
            ]);
            return $existingBid->fresh();
        }

        return $this->bids()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'is_winning' => false,
            'ip_address' => request()->ip(),
            'placed_at' => now(),
        ]);
    }

    /**
     * Close a sealed lot: determine winner from sealed bids.
     * Requires 2+ distinct bidders. First-price: winner pays their bid.
     */
    public function closeSealed(): void
    {
        $auction = $this->auction;

        // Get all bids (one per user due to upsert)
        $bids = $this->bids()->get();
        $distinctBidders = $bids->pluck('user_id')->unique()->count();
        $hadBids = $bids->isNotEmpty();

        $updateData = [
            'actual_end_time' => now(),
        ];

        // Need 2+ distinct bidders for a valid sealed auction
        if ($distinctBidders < 2) {
            $updateData['status'] = 'unsold';
            $updateData['winning_bidder_id'] = null;
            $updateData['free_relist_eligible'] = !$hadBids;
            $this->update($updateData);
            $this->images()->update(['scheduled_deletion_at' => now()->addDays(config('auction.image_auto_delete_days', 30))]);
            return;
        }

        // Determine winner
        $winningBid = $auction->isSealedHighest()
            ? $bids->sortByDesc('amount')->first()
            : $bids->sortBy('amount')->first();

        // Check reserve
        $reserveMet = true;
        if ($this->hasReserve()) {
            if ($auction->isSealedHighest()) {
                $reserveMet = $winningBid->amount >= $this->reserve_price;
            } else {
                $reserveMet = $winningBid->amount <= $this->reserve_price;
            }
        }

        if (!$reserveMet) {
            $updateData['status'] = 'unsold';
            $updateData['winning_bidder_id'] = null;
            $updateData['free_relist_eligible'] = false;
            $this->update($updateData);
            $this->images()->update(['scheduled_deletion_at' => now()->addDays(config('auction.image_auto_delete_days', 30))]);
            return;
        }

        // Valid sale — check subject to confirmation
        $needsConfirmation = $this->subject_to_confirmation;
        $updateData['status'] = $needsConfirmation ? 'pending_confirmation' : 'sold';
        $updateData['current_bid'] = $winningBid->amount;
        $updateData['winning_bidder_id'] = $winningBid->user_id;
        $updateData['reserve_met'] = true;
        $updateData['total_bids'] = $distinctBidders;
        $updateData['free_relist_eligible'] = false;

        if (!$needsConfirmation && !$auction->enable_online_payment) {
            $updateData['payment_status'] = 'awaiting_collection';
            $updateData['payment_method_selected_at'] = now();
        }

        $this->update($updateData);

        // Mark winning bid
        $winningBid->update(['is_winning' => true]);

        // Schedule image deletion
        $this->images()->update(['scheduled_deletion_at' => now()->addDays(config('auction.image_auto_delete_days', 30))]);

        // Deduct platform fee (defer for pending_confirmation)
        if ($needsConfirmation) {
            return;
        }

        $hammerPrice = $winningBid->amount;
        $buyersPremium = $hammerPrice * ($auction->buyers_premium_percentage / 100);
        $total = $hammerPrice + $buyersPremium;
        $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

        if ($platformFee > 0) {
            $auction->auctioneer->deductCredits(
                $platformFee,
                'lot_close',
                $this->id,
                "1% fee for Sealed Lot #{$this->lot_number} - {$this->title}"
            );
        }
    }

    /**
     * Get the authenticated user's current sealed bid on this lot.
     */
    public function getUserSealedBid(int $userId): ?Bid
    {
        return $this->bids()->where('user_id', $userId)->first();
    }

    // Live (Automated) Auction Methods

    const LIVE_PHASE_OPENING = 'opening';          // Auction-wide warm-up window before the first lot's PRESENTING (community auctions only)
    const LIVE_PHASE_INTERMISSION = 'intermission';
    const LIVE_PHASE_PRESENTING = 'presenting';
    const LIVE_PHASE_OPEN_CALL = 'open_call';      // "Who'll open?" — no bid yet, 30s timer, silence = no-interest close
    const LIVE_PHASE_ACTIVE = 'active';            // bidding underway, 8s silence timer reset by every bid
    const LIVE_PHASE_GOING_ONCE = 'going_once';
    const LIVE_PHASE_GOING_TWICE = 'going_twice';
    const LIVE_PHASE_CLOSED = 'closed';

    /**
     * Count distinct bidders on this lot (for live validation window).
     * Cached 2s — called on every lot/auction page render.
     */
    public function distinctLiveBidderCount(): int
    {
        return Cache::remember("lot-distinct-bidders:{$this->id}", 2, function () {
            return (int) $this->bids()->distinct('user_id')->count('user_id');
        });
    }

    /**
     * Whether a new bid may be placed in the current live phase.
     * Presenting/closed/intermission lock out bids; active and pulse phases accept them.
     */
    public function acceptsLiveBids(): bool
    {
        return in_array($this->live_phase, [
            self::LIVE_PHASE_OPEN_CALL,
            self::LIVE_PHASE_ACTIVE,
            self::LIVE_PHASE_GOING_ONCE,
            self::LIVE_PHASE_GOING_TWICE,
        ], true);
    }

    /**
     * Hook called after a successful live bid. Drives the silence-driven flow:
     *
     *  - OPEN_CALL → first bid arrived, transition to ACTIVE with 8s silence timer
     *  - ACTIVE    → reset the 8s silence timer (rapid bidding extends the active window)
     *  - GOING_ONCE / GOING_TWICE → bidder reclaimed the lot, revert to ACTIVE with 8s silence
     *
     * The going phases exist to nudge a quiet room; any bid in that window
     * means there's still real demand, so we drop back into active bidding.
     */
    public function onLiveBidPlaced(): void
    {
        if (!$this->auction->isLiveFormat()) {
            return;
        }

        $now = now();
        $silence = Auction::LIVE_ACTIVE_SILENCE_SECONDS;

        $shouldEnterActive = in_array($this->live_phase, [
            self::LIVE_PHASE_OPEN_CALL,
            self::LIVE_PHASE_ACTIVE,
            self::LIVE_PHASE_GOING_ONCE,
            self::LIVE_PHASE_GOING_TWICE,
        ], true);

        if ($shouldEnterActive) {
            $this->update([
                'live_phase' => self::LIVE_PHASE_ACTIVE,
                'live_phase_ends_at' => $now->copy()->addSeconds($silence),
                'live_last_bid_at' => $now,
            ]);
        } else {
            // Phase doesn't accept bids in the model — just stamp the bid time.
            $this->update(['live_last_bid_at' => $now]);
        }
    }

    /**
     * Advance the live phase state machine based on timers + bid state.
     * Called on every poll and by the scheduler backstop.
     */
    public function advanceLivePhase(): void
    {
        if (!$this->auction->isLiveFormat()) {
            return;
        }
        // Only the CLOSED phase is allowed when status has moved past 'live'
        // (i.e., after closeLive set status to sold/unsold). All other phase
        // transitions require the lot to still be actively in the 'live' status.
        if ($this->status !== 'live' && $this->live_phase !== self::LIVE_PHASE_CLOSED) {
            return;
        }

        $now = now();
        $phaseEnds = $this->live_phase_ends_at;

        // Nothing to do until the current phase timer elapses
        if ($phaseEnds === null || $now->lessThan($phaseEnds)) {
            return;
        }

        switch ($this->live_phase) {
            case self::LIVE_PHASE_OPENING:
                // Auction-wide warm-up window done → begin presenting the first lot
                $this->update([
                    'live_phase' => self::LIVE_PHASE_PRESENTING,
                    'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_PRESENTATION_SECONDS),
                    'live_opens_at' => $now->copy()->addSeconds(Auction::LIVE_PRESENTATION_SECONDS),
                ]);
                break;

            case self::LIVE_PHASE_INTERMISSION:
                // Gap between lots → begin presenting the next lot
                $this->update([
                    'live_phase' => self::LIVE_PHASE_PRESENTING,
                    'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_PRESENTATION_SECONDS),
                    'live_opens_at' => $now->copy()->addSeconds(Auction::LIVE_PRESENTATION_SECONDS),
                ]);
                break;

            case self::LIVE_PHASE_PRESENTING:
                // Presentation done → enter the 30s "Who'll open?" window.
                $this->update([
                    'live_phase' => self::LIVE_PHASE_OPEN_CALL,
                    'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_OPEN_CALL_SECONDS),
                    'live_opens_at' => $this->live_opens_at ?? $now,
                ]);
                break;

            case self::LIVE_PHASE_OPEN_CALL:
                // Nobody opened in 30s → close as no-interest, skip the going routine entirely.
                $this->closeLive();
                break;

            case self::LIVE_PHASE_ACTIVE:
                // Silence timer elapsed → begin pulse (going once).
                $this->update([
                    'live_phase' => self::LIVE_PHASE_GOING_ONCE,
                    'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_PULSE_ONCE_SECONDS),
                ]);
                break;

            case self::LIVE_PHASE_GOING_ONCE:
                $this->update([
                    'live_phase' => self::LIVE_PHASE_GOING_TWICE,
                    'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_PULSE_TWICE_SECONDS),
                ]);
                break;

            case self::LIVE_PHASE_GOING_TWICE:
                // Pulse complete → close (enters 5s result window)
                $this->closeLive();
                break;

            case self::LIVE_PHASE_CLOSED:
                // Result window elapsed → advance the next lot into intermission
                $this->update(['live_phase_ends_at' => null]);
                $this->startNextLiveLot();
                break;
        }
    }

    /**
     * Close a live lot: mark sold/unsold, then advance the next pending lot
     * into its intermission phase so the sequential chain continues.
     *
     * Pass $advanceNext=false when the whole auction is ending (prevents
     * cascading calls through every remaining pending lot).
     */
    public function closeLive(bool $advanceNext = true): void
    {
        if ($this->live_phase === self::LIVE_PHASE_CLOSED) {
            return;
        }

        $hasBidder = (bool) $this->winning_bidder_id;
        $reserveMet = $this->isReserveMet();
        $distinctBidders = $this->distinctLiveBidderCount();
        // Sale valid if reserve is met AND (2+ distinct bidders OR a reserve is set).
        // Single bidder over reserve is accepted; single bidder on a no-reserve lot is not.
        $twoBidderRuleMet = $distinctBidders >= 2 || $this->hasReserve();
        $isSold = $hasBidder && $reserveMet && $twoBidderRuleMet;
        $hadBids = $this->bids()->exists();
        $needsConfirmation = $isSold && $this->subject_to_confirmation;

        $finalStatus = $isSold
            ? ($needsConfirmation ? 'pending_confirmation' : 'sold')
            : 'unsold';

        $updateData = [
            'actual_end_time' => now(),
            'status' => $finalStatus,
            'live_phase' => self::LIVE_PHASE_CLOSED,
            'live_phase_ends_at' => $advanceNext ? now()->addSeconds(Auction::LIVE_RESULT_SECONDS) : null,
            'free_relist_eligible' => !$isSold && !$hadBids,
        ];

        if ($needsConfirmation && $this->isCommunityLot()) {
            $updateData['confirmation_expires_at'] = now()->addHours(
                (int) config('community.confirmation_window_hours', 24)
            );
        }

        if ($isSold && !$needsConfirmation && !$this->auction->enable_online_payment) {
            $updateData['payment_status'] = 'awaiting_collection';
            $updateData['payment_method_selected_at'] = now();
        }

        if ($hasBidder && !$isSold) {
            $updateData['winning_bidder_id'] = null;
        }

        $this->update($updateData);

        $this->images()->update([
            'scheduled_deletion_at' => now()->addDays(config('auction.image_auto_delete_days', 30)),
        ]);

        // Platform fee (deferred for pending_confirmation)
        if ($isSold && !$needsConfirmation && $this->current_bid) {
            $auction = $this->auction;
            $hammerPrice = $this->current_bid;
            $buyersPremium = $hammerPrice * ($auction->buyers_premium_percentage / 100);
            $total = $hammerPrice + $buyersPremium;
            $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

            if ($platformFee > 0) {
                $auction->auctioneer->deductCredits(
                    $platformFee,
                    'lot_close',
                    $this->id,
                    "1% fee for Live Lot #{$this->lot_number} - {$this->title}"
                );
            }
        }

        // Next lot advances via advanceLivePhase() after LIVE_RESULT_SECONDS.
        // When $advanceNext is false (auction ending), live_phase_ends_at is null
        // so the scheduler won't tick — lot stays closed as final state.
    }

    /**
     * Move the next pending lot (by lot_number) into intermission, or end the auction.
     */
    protected function startNextLiveLot(): void
    {
        $next = $this->auction->lots()
            ->whereNull('withdrawn_at')
            ->whereIn('status', ['pending', 'draft'])
            ->where('lot_number', '>', $this->lot_number)
            ->orderBy('lot_number')
            ->first();

        if (!$next) {
            // No more lots — end the auction
            $this->auction->update(['status' => 'ended']);
            return;
        }

        $now = now();
        $next->update([
            'status' => 'live',
            'live_phase' => self::LIVE_PHASE_INTERMISSION,
            'live_phase_ends_at' => $now->copy()->addSeconds(Auction::LIVE_INTERMISSION_SECONDS),
            'live_opens_at' => null,
            'live_last_bid_at' => null,
        ]);
    }

    /**
     * Check if this unsold lot qualifies for a free relist (no bids received).
     */
    public function isFreeRelistEligible(): bool
    {
        return $this->status === 'unsold' && $this->free_relist_eligible;
    }

    /**
     * Check if this lot can be relisted (unsold from an ended auction, not already relisted).
     */
    public function canBeRelisted(): bool
    {
        return $this->status === 'unsold'
            && !$this->isWithdrawn()
            && $this->auction
            && $this->auction->status === 'ended'
            && !$this->relistedTo()->exists();
    }

    /**
     * Relist this lot into a target draft auction.
     * Clones the lot data and copies all images to new files.
     * Returns the new lot so the caller can redirect to its edit page.
     */
    public function relistTo(Auction $targetAuction): Lot
    {
        if (!$this->canBeRelisted()) {
            throw new \Exception('This lot cannot be relisted.');
        }

        if ($targetAuction->status !== 'draft') {
            throw new \Exception('Lots can only be relisted to draft auctions.');
        }

        $this->loadMissing('images');

        $newLot = $this->replicate();

        // Point to new auction
        $newLot->event_id = $targetAuction->id;
        $newLot->status = 'draft';

        // Relist metadata
        $newLot->relisted_from_lot_id = $this->id;
        $newLot->original_lot_id     = $this->original_lot_id ?? $this->id;
        $newLot->relist_count        = ($this->relist_count ?? 0) + 1;
        $newLot->is_free_relist      = $this->free_relist_eligible; // free if parent had 0 bids

        // Clear timing & lot number — boot() will recalculate from target auction
        $newLot->lot_number    = null;
        $newLot->start_time    = null;
        $newLot->end_time      = null;
        $newLot->actual_end_time = null;

        // Clear bidding data
        $newLot->current_bid              = null;
        $newLot->winning_bidder_id        = null;
        $newLot->total_bids               = 0;
        $newLot->reserve_met              = false;

        // Clear withdrawal & payment data
        $newLot->withdrawn_at             = null;
        $newLot->withdrawal_reason        = null;
        $newLot->payment_status           = null;
        $newLot->payment_method_selected_at = null;
        $newLot->payment_completed_at     = null;
        $newLot->is_paid                  = false;

        // New lot has not yet earned free relist eligibility
        $newLot->free_relist_eligible = false;

        // Dutch partial sales: reduce quantity to unsold amount
        if ($this->dutch_start_price && $this->quantity_sold > 0) {
            $newLot->quantity = max(1, $this->quantity - $this->quantity_sold);
        }
        $newLot->quantity_sold = 0;
        $newLot->dutch_start_time = null;
        $newLot->dutch_end_time = null;

        // Reset Live-format phase state so the relisted lot doesn't inherit
        // "closed / unsold" banner from the source.
        $newLot->live_phase = null;
        $newLot->live_phase_ends_at = null;
        $newLot->live_opens_at = null;
        $newLot->live_last_bid_at = null;

        $newLot->save();

        // Copy images to independent files
        $this->copyImagesToLot($newLot);

        return $newLot;
    }

    /**
     * Copy all images from this lot to another lot with new independent files.
     */
    protected function copyImagesToLot(Lot $targetLot): void
    {
        foreach ($this->images as $index => $image) {
            $newFilename = uniqid('lot_' . $targetLot->id . '_') . '.webp';

            $newOptimizedPath  = 'images/lots/optimized/' . $newFilename;
            $newThumbnailPath  = 'images/lots/thumbnails/' . $newFilename;

            if (Storage::disk('public')->exists($image->optimized_path)) {
                Storage::disk('public')->copy($image->optimized_path, $newOptimizedPath);
            }

            if (Storage::disk('public')->exists($image->thumbnail_path)) {
                Storage::disk('public')->copy($image->thumbnail_path, $newThumbnailPath);
            }

            LotImage::create([
                'lot_id'         => $targetLot->id,
                'original_path'  => null,
                'optimized_path' => $newOptimizedPath,
                'thumbnail_path' => $newThumbnailPath,
                'order'          => $image->order,
                'original_size'  => $image->original_size,
                'optimized_size' => $image->optimized_size,
                'thumbnail_size' => $image->thumbnail_size,
                'is_primary'     => $index === 0,
            ]);
        }
    }

    public function getMaxImages(): int
    {
        return match ($this->image_tier) {
            'basic' => config('auction.image_tier_basic_limit', 1),
            'pro' => config('auction.image_tier_pro_limit', 5),
            'premium' => config('auction.image_tier_premium_limit', 20),
            default => 1,
        };
    }

    public function canAddMoreImages(): bool
    {
        return $this->images()->count() < $this->getMaxImages();
    }

    /**
     * Payment status helpers
     */
    public function isPaidViaPayFast(): bool
    {
        return $this->payment_status === 'paid_platform';
    }

    public function isAwaitingCollection(): bool
    {
        return $this->payment_status === 'awaiting_collection';
    }

    public function isPaidOffline(): bool
    {
        return $this->payment_status === 'paid_offline';
    }

    public function needsPaymentSelection(): bool
    {
        return $this->isSold() && empty($this->payment_status);
    }

    public function getTotalAmountDue(): float
    {
        $hammerPrice = $this->current_bid;
        $buyersPremium = $hammerPrice * ($this->auction->buyers_premium_percentage / 100);
        return $hammerPrice + $buyersPremium;
    }

    // Dutch Auction Helpers

    /**
     * Whether this lot is live but still in the "Get Ready" countdown (drops haven't started yet).
     */
    public function isInDutchCountdown(): bool
    {
        if (!$this->isLive() || !$this->dutch_start_time) {
            return false;
        }

        return $this->dutch_start_time->isFuture();
    }

    /**
     * Calculate the current Dutch auction price based on elapsed time and drop strategy.
     */
    public function getCurrentDutchPrice(): float
    {
        $auction = $this->auction;

        if (!$auction->isDutch()) {
            return (float) $this->current_bid;
        }

        $startTime = $this->dutch_start_time ?: $auction->start_time;

        if (!$startTime || $startTime->isFuture()) {
            return (float) $this->dutch_start_price;
        }

        $elapsed = (int) abs(now()->diffInSeconds($startTime));

        return self::calculateDutchPriceAtTime(
            $elapsed,
            (float) $this->dutch_start_price,
            (float) $this->dutch_floor_price,
            (float) $this->dutch_drop_amount,
            (int) $this->dutch_drop_interval,
            $this->dutch_drop_strategy ?: 'constant'
        );
    }

    /**
     * Pure calculation: Dutch price at a given elapsed time.
     * Used by both PHP and mirrored in JavaScript.
     */
    public static function calculateDutchPriceAtTime(
        int $elapsedSeconds,
        float $startPrice,
        float $floorPrice,
        float $baseDropAmount,
        int $baseDropInterval,
        string $strategy = 'constant'
    ): float {
        $totalRange = $startPrice - $floorPrice;
        if ($totalRange <= 0) {
            return $floorPrice;
        }

        $phases = self::DROP_STRATEGIES[$strategy]['phases'] ?? self::DROP_STRATEGIES['constant']['phases'];

        $timeConsumed = 0;
        $priceDropped = 0;

        foreach ($phases as $phase) {
            $phaseRange = $totalRange * $phase['range'];
            $effectiveDrop = $baseDropAmount * $phase['drop_mult'];
            $effectiveInterval = max(1, (int) round($baseDropInterval * $phase['interval_mult']));

            if ($effectiveDrop <= 0) {
                continue;
            }

            $dropsInPhase = ceil($phaseRange / $effectiveDrop);
            $phaseTime = (int) ($dropsInPhase * $effectiveInterval);

            $timeIntoPhase = $elapsedSeconds - $timeConsumed;

            if ($timeIntoPhase < $phaseTime) {
                // We're in this phase
                $dropsCompleted = floor($timeIntoPhase / $effectiveInterval);
                $priceDropped += $dropsCompleted * $effectiveDrop;
                $price = $startPrice - $priceDropped;
                return max($price, $floorPrice);
            }

            // Completed this phase
            $priceDropped += $dropsInPhase * $effectiveDrop;
            $timeConsumed += $phaseTime;
        }

        // Past all phases = at floor
        return $floorPrice;
    }

    /**
     * Calculate drop amount and interval from a target duration.
     * Works backwards: auctioneer sets duration + strategy, platform computes the drop matrix.
     *
     * @return array{drop_amount: float, drop_interval: int, actual_duration: int}
     */
    public static function calculateDropMatrix(
        float $startPrice,
        float $floorPrice,
        int $targetDuration,
        string $strategy = 'constant'
    ): array {
        $totalRange = $startPrice - $floorPrice;

        if ($totalRange <= 0 || $targetDuration <= 0) {
            return ['drop_amount' => 1.0, 'drop_interval' => 10, 'actual_duration' => 0];
        }

        $phases = self::DROP_STRATEGIES[$strategy]['phases'] ?? self::DROP_STRATEGIES['constant']['phases'];

        // W = sum of (range × interval_mult / drop_mult) across phases
        // This captures how the strategy distributes time relative to price
        $W = 0;
        foreach ($phases as $phase) {
            if ($phase['drop_mult'] > 0) {
                $W += $phase['range'] * $phase['interval_mult'] / $phase['drop_mult'];
            }
        }

        if ($W <= 0) {
            $W = 1.0;
        }

        // Try intervals from shortest to longest, pick first that gives a clean drop amount
        // Target: drops feel visible (>= 0.5% of range) but not too jumpy (<= 15% of range)
        $candidateIntervals = [5, 8, 10, 12, 15, 20, 25, 30];
        $minDrop = max(0.01, $totalRange * 0.005);
        $maxDrop = $totalRange * 0.15;

        $bestInterval = 10;
        $bestDropAmount = $totalRange * 10 * $W / $targetDuration;

        foreach ($candidateIntervals as $interval) {
            $dropAmount = $totalRange * $interval * $W / $targetDuration;

            if ($dropAmount >= $minDrop && $dropAmount <= $maxDrop) {
                $bestInterval = $interval;
                $bestDropAmount = $dropAmount;
                break;
            }

            // If drop is too large, this interval is too long — use the previous (shorter) one
            if ($dropAmount > $maxDrop) {
                break;
            }

            // Track as fallback (keeps advancing to longer intervals if drop too small)
            $bestInterval = $interval;
            $bestDropAmount = $dropAmount;
        }

        // Round drop amount to clean currency values
        $bestDropAmount = self::roundDropAmount($bestDropAmount, $totalRange);

        // Ensure minimum
        $bestDropAmount = max(0.01, $bestDropAmount);

        // Verify with forward calculation
        $actualDuration = self::calculateDutchDuration(
            $startPrice, $floorPrice, $bestDropAmount, $bestInterval, $strategy
        );

        return [
            'drop_amount' => $bestDropAmount,
            'drop_interval' => $bestInterval,
            'actual_duration' => $actualDuration,
        ];
    }

    /**
     * Round a drop amount to visually clean currency values.
     */
    private static function roundDropAmount(float $amount, float $totalRange): float
    {
        if ($totalRange >= 10000) {
            // Large range: round to nearest R5 or R10
            if ($amount >= 50) {
                return round($amount / 10) * 10;
            }
            return round($amount / 5) * 5;
        }

        if ($totalRange >= 1000) {
            // Medium range: round to nearest R1
            return max(1, round($amount));
        }

        if ($totalRange >= 100) {
            // Smaller range: round to nearest R0.50
            return max(0.50, round($amount * 2) / 2);
        }

        if ($totalRange >= 10) {
            // Small range: round to nearest R0.10
            return max(0.10, round($amount, 1));
        }

        // Tiny range: keep 2 decimal places
        return round($amount, 2);
    }

    /**
     * Calculate total duration in seconds for a Dutch lot to reach floor price.
     */
    public static function calculateDutchDuration(
        float $startPrice,
        float $floorPrice,
        float $baseDropAmount,
        int $baseDropInterval,
        string $strategy = 'constant'
    ): int {
        $totalRange = $startPrice - $floorPrice;
        if ($totalRange <= 0) {
            return 0;
        }

        $phases = self::DROP_STRATEGIES[$strategy]['phases'] ?? self::DROP_STRATEGIES['constant']['phases'];
        $totalTime = 0;

        foreach ($phases as $phase) {
            $phaseRange = $totalRange * $phase['range'];
            $effectiveDrop = $baseDropAmount * $phase['drop_mult'];
            $effectiveInterval = max(1, (int) round($baseDropInterval * $phase['interval_mult']));

            if ($effectiveDrop <= 0) {
                continue;
            }

            $dropsInPhase = ceil($phaseRange / $effectiveDrop);
            $totalTime += (int) ($dropsInPhase * $effectiveInterval);
        }

        return $totalTime;
    }

    /**
     * Calculate the exact elapsed seconds when the floor price is reached.
     * This may be less than calculateDutchDuration() due to ceil() overshoot in phase calculations.
     */
    public static function calculateFloorReachedAt(
        float $startPrice,
        float $floorPrice,
        float $baseDropAmount,
        int $baseDropInterval,
        string $strategy = 'constant'
    ): int {
        $totalRange = $startPrice - $floorPrice;
        if ($totalRange <= 0) {
            return 0;
        }

        $phases = self::DROP_STRATEGIES[$strategy]['phases'] ?? self::DROP_STRATEGIES['constant']['phases'];
        $timeConsumed = 0;
        $priceDropped = 0;

        foreach ($phases as $phase) {
            $phaseRange = $totalRange * $phase['range'];
            $effectiveDrop = $baseDropAmount * $phase['drop_mult'];
            $effectiveInterval = max(1, (int) round($baseDropInterval * $phase['interval_mult']));

            if ($effectiveDrop <= 0) {
                continue;
            }

            $dropsInPhase = (int) ceil($phaseRange / $effectiveDrop);

            for ($d = 1; $d <= $dropsInPhase; $d++) {
                $priceDropped += $effectiveDrop;
                if ($startPrice - $priceDropped <= $floorPrice) {
                    return $timeConsumed + $d * $effectiveInterval;
                }
            }

            $timeConsumed += $dropsInPhase * $effectiveInterval;
        }

        return $timeConsumed;
    }

    /**
     * Seconds until the next price drop (strategy-aware).
     */
    public function getDutchNextDropIn(): int
    {
        $auction = $this->auction;

        if (!$auction->isDutch() || $this->isAtDutchFloor()) {
            return 0;
        }

        $startTime = $this->dutch_start_time ?: $auction->start_time;

        if (!$startTime || $startTime->isFuture()) {
            return 0;
        }

        $elapsed = (int) abs(now()->diffInSeconds($startTime));
        $strategy = $this->dutch_drop_strategy ?: 'constant';
        $baseDropAmount = (float) $this->dutch_drop_amount;
        $baseDropInterval = (int) $this->dutch_drop_interval;
        $totalRange = (float) $this->dutch_start_price - (float) $this->dutch_floor_price;

        $phases = self::DROP_STRATEGIES[$strategy]['phases'] ?? self::DROP_STRATEGIES['constant']['phases'];

        $timeConsumed = 0;

        foreach ($phases as $phase) {
            $phaseRange = $totalRange * $phase['range'];
            $effectiveDrop = $baseDropAmount * $phase['drop_mult'];
            $effectiveInterval = max(1, (int) round($baseDropInterval * $phase['interval_mult']));

            if ($effectiveDrop <= 0) {
                continue;
            }

            $dropsInPhase = ceil($phaseRange / $effectiveDrop);
            $phaseTime = (int) ($dropsInPhase * $effectiveInterval);

            $timeIntoPhase = $elapsed - $timeConsumed;

            if ($timeIntoPhase < $phaseTime) {
                $secondsIntoCurrentDrop = $timeIntoPhase % $effectiveInterval;
                return $effectiveInterval - $secondsIntoCurrentDrop;
            }

            $timeConsumed += $phaseTime;
        }

        return 0;
    }

    /**
     * Whether the price has reached the floor.
     */
    public function isAtDutchFloor(): bool
    {
        return $this->getCurrentDutchPrice() <= (float) $this->dutch_floor_price;
    }

    /**
     * Quantity remaining for purchase.
     */
    public function quantityRemaining(): int
    {
        return max(0, $this->quantity - $this->quantity_sold);
    }

    /**
     * Whether this Dutch lot is fully sold out.
     */
    public function isDutchSoldOut(): bool
    {
        return $this->quantity_sold >= $this->quantity;
    }

    /**
     * Close a Dutch lot at auction end.
     */
    public function closeDutch(): void
    {
        $isSold = $this->quantity_sold > 0;

        // Calculate total sales value from all Dutch buys
        $totalSalesValue = 0;
        if ($isSold) {
            $totalSalesValue = (float) ($this->bids()
                ->where('is_dutch_buy', true)
                ->selectRaw('SUM(amount * quantity_bought) as total')
                ->value('total') ?? 0);
        }

        $updateData = [
            'actual_end_time' => now(),
            'status' => $isSold ? 'sold' : 'unsold',
            'free_relist_eligible' => !$isSold,
            'current_bid' => $totalSalesValue,
        ];

        // Dutch sold lots: if online payment enabled, leave null so bidder sees Pay Now
        if ($isSold && empty($this->payment_status) && !$this->auction->enable_online_payment) {
            $updateData['payment_status'] = 'awaiting_collection';
            $updateData['payment_method_selected_at'] = now();
        }

        $this->update($updateData);

        // Schedule image deletion
        $deletionDate = now()->addDays(config('auction.image_auto_delete_days', 30));
        $this->images()->update(['scheduled_deletion_at' => $deletionDate]);

        // Deduct 1% platform fee on total Dutch sales for this lot
        if ($isSold) {
            $auction = $this->auction;
            $buyersPremium = $totalSalesValue * ($auction->buyers_premium_percentage / 100);
            $total = $totalSalesValue + $buyersPremium;
            $platformFee = $total * (config('auction.platform_percentage_fee', 1) / 100);

            if ($platformFee > 0) {
                $auction->auctioneer->deductCredits(
                    $platformFee,
                    'lot_close',
                    $this->id,
                    "1% fee for Dutch Lot #{$this->lot_number} - {$this->title}"
                );
            }
        }

        $this->activateNextDutchLot();
    }

    /**
     * Activate the next Dutch lot in sequence, recalculating timing from now.
     */
    public function activateNextDutchLot(): void
    {
        $auction = $this->auction;
        if (!$auction->isDutch()) {
            return;
        }

        $nextLot = self::where('event_id', $this->event_id)
            ->where('status', 'draft')
            ->whereNull('withdrawn_at')
            ->orderBy('lot_number')
            ->first();

        if (!$nextLot) {
            return;
        }

        // Recalculate timing: next lot drops start after the gap
        $lotGap = max($auction->dutch_lot_gap ?? 0, self::DUTCH_COUNTDOWN_BUFFER);
        $newDropStart = now()->addSeconds($lotGap);

        $floorReachedAt = self::calculateFloorReachedAt(
            (float) $nextLot->dutch_start_price,
            (float) $nextLot->dutch_floor_price,
            (float) $nextLot->dutch_drop_amount,
            (int) $nextLot->dutch_drop_interval,
            $nextLot->dutch_drop_strategy ?: 'constant'
        );
        $newEndTime = $newDropStart->copy()->addSeconds($floorReachedAt + self::DUTCH_FLOOR_BUFFER);

        $nextLot->update([
            'status' => 'live',
            'dutch_start_time' => $newDropStart,
            'dutch_end_time' => $newEndTime,
            'end_time' => $newEndTime,
        ]);

        // Recalculate all subsequent lots from this new timing
        $currentDropStart = $newEndTime->copy()->addSeconds($lotGap);
        $remainingLots = self::where('event_id', $this->event_id)
            ->where('status', 'draft')
            ->whereNull('withdrawn_at')
            ->where('lot_number', '>', $nextLot->lot_number)
            ->orderBy('lot_number')
            ->get();

        foreach ($remainingLots as $futureLot) {
            $futureFloorAt = self::calculateFloorReachedAt(
                (float) $futureLot->dutch_start_price,
                (float) $futureLot->dutch_floor_price,
                (float) $futureLot->dutch_drop_amount,
                (int) $futureLot->dutch_drop_interval,
                $futureLot->dutch_drop_strategy ?: 'constant'
            );
            $futureEndTime = $currentDropStart->copy()->addSeconds($futureFloorAt + self::DUTCH_FLOOR_BUFFER);

            $futureLot->update([
                'dutch_start_time' => $currentDropStart->copy(),
                'dutch_end_time' => $futureEndTime,
                'end_time' => $futureEndTime,
            ]);
            $currentDropStart = $futureEndTime->copy()->addSeconds($lotGap);
        }

        // Update auction end time
        $lastEndTime = $remainingLots->count() > 0
            ? $remainingLots->last()->fresh()->dutch_end_time
            : $newEndTime;
        $auction->update(['end_time' => $lastEndTime]);
    }

    // ===== Community Auction Support =====

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function rolledFromLot()
    {
        return $this->belongsTo(Lot::class, 'rolled_from_lot_id');
    }

    public function isCommunityLot(): bool
    {
        return !is_null($this->seller_user_id);
    }

    /**
     * Is the given user the seller/owner of this lot?
     * Community lots: owner is seller_user_id.
     * Auctioneer lots: owner is the auctioneer's linked user account.
     */
    public function isOwnedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($this->isCommunityLot()) {
            return (int) $this->seller_user_id === (int) $user->id;
        }
        $ownerId = $this->auction?->auctioneer?->user_id;
        return $ownerId && (int) $ownerId === (int) $user->id;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->status === 'pending_confirmation';
    }

    /**
     * Community seller confirms the winning bid — promote lot to sold.
     * Returns the LotConfirmation record created.
     */
    public function communityConfirm(bool $auto = false, ?string $reason = null): LotConfirmation
    {
        if (!$this->isCommunityLot()) {
            throw new \LogicException('Lot is not a community lot.');
        }
        if (!$this->isAwaitingConfirmation()) {
            throw new \LogicException('Lot is not awaiting confirmation.');
        }

        $regionId = $this->auction?->community_region_id;
        $action = $auto ? LotConfirmation::ACTION_AUTO_CONFIRMED : LotConfirmation::ACTION_CONFIRMED;

        $confirmation = LotConfirmation::create([
            'lot_id' => $this->id,
            'seller_user_id' => $this->seller_user_id,
            'community_region_id' => $regionId,
            'winning_bid' => $this->current_bid ?? 0,
            'action' => $action,
            'reason' => $reason,
            'acted_at' => now(),
        ]);

        $this->update([
            'status' => 'sold',
            'actual_end_time' => $this->actual_end_time ?? now(),
            'confirmation_expires_at' => null,
        ]);

        // Accrue platform commission + agent share into the community ledger.
        app(\App\Services\CommunityCommissionService::class)->accrue($this->fresh());

        return $confirmation;
    }

    /**
     * Community seller declines the winning bid — lot is unsold, decline logged.
     * Caller is responsible for checking/applying penalties (suspension, ban).
     */
    public function communityDecline(string $reason): LotConfirmation
    {
        if (!$this->isCommunityLot()) {
            throw new \LogicException('Lot is not a community lot.');
        }
        if (!$this->isAwaitingConfirmation()) {
            throw new \LogicException('Lot is not awaiting confirmation.');
        }

        $regionId = $this->auction?->community_region_id;

        $confirmation = LotConfirmation::create([
            'lot_id' => $this->id,
            'seller_user_id' => $this->seller_user_id,
            'community_region_id' => $regionId,
            'winning_bid' => $this->current_bid ?? 0,
            'action' => LotConfirmation::ACTION_DECLINED,
            'reason' => $reason,
            'acted_at' => now(),
        ]);

        $this->update([
            'status' => 'unsold',
            'declined_at' => now(),
            'decline_reason' => $reason,
            'confirmation_expires_at' => null,
        ]);

        return $confirmation;
    }
}
