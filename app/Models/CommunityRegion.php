<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'metro_area',
        'lat',
        'lng',
        'description',
        'is_active',
        'pilot_mode',
        'bidder_threshold',
        'min_lots_for_viability',
        'min_bidders_for_viability',
        'listing_limit_per_week',
        'goes_live_day',
        'goes_live_time',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'pilot_mode' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function auctions()
    {
        return $this->hasMany(Auction::class, 'community_region_id');
    }

    public function schedules()
    {
        return $this->hasMany(CommunitySchedule::class, 'community_region_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'community_region_id');
    }

    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'agent_community')
            ->withPivot(['is_primary', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    /** Currently-assigned primary agent for this community (if any). */
    public function primaryAgent(): ?Agent
    {
        return $this->agents()
            ->wherePivot('is_primary', true)
            ->wherePivotNull('ended_at')
            ->first();
    }

    public function commissionLedger()
    {
        return $this->hasMany(CommunityCommissionLedger::class, 'community_region_id');
    }

    public function bidderCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Returns the next scheduled go-live datetime for this region (based on
     * goes_live_day + goes_live_time), relative to the given reference time.
     */
    public function nextGoesLiveAt(?\Carbon\Carbon $from = null): \Carbon\Carbon
    {
        $from = $from ?: now();
        $targetDay = (int) $this->goes_live_day; // 0 = Sunday
        $targetTime = $this->goes_live_time ?: '18:00:00';

        $candidate = $from->copy()->startOfDay();
        while ((int) $candidate->dayOfWeek !== $targetDay) {
            $candidate->addDay();
        }

        [$h, $m, $s] = array_pad(explode(':', $targetTime), 3, 0);
        $candidate->setTime((int) $h, (int) $m, (int) $s);

        if ($candidate->lte($from)) {
            $candidate->addWeek();
        }

        return $candidate;
    }
}
