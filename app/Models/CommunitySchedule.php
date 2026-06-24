<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunitySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_region_id',
        'name',
        'frequency',
        'goes_live_day',
        'monthly_week',
        'goes_live_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'goes_live_day' => 'integer',
            'monthly_week' => 'integer',
        ];
    }

    public function region()
    {
        return $this->belongsTo(CommunityRegion::class, 'community_region_id');
    }

    public function auctions()
    {
        return $this->hasMany(Auction::class, 'community_schedule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Returns the next scheduled go-live datetime for this schedule.
     *
     * Handles two frequencies:
     *  - weekly  → next occurrence of `goes_live_day` (0-6) at `goes_live_time`
     *  - monthly → Nth occurrence of `goes_live_day` in the month at `goes_live_time`,
     *              where N is `monthly_week` (1-4) or 5 for "last".
     */
    public function nextGoesLiveAt(?Carbon $from = null): Carbon
    {
        $from = $from ?: now();
        $targetDay = (int) $this->goes_live_day;
        $targetTime = $this->goes_live_time ?: '18:00:00';
        [$h, $m, $s] = array_pad(explode(':', $targetTime), 3, 0);

        if ($this->frequency === 'monthly') {
            // Try this month first; if the resolved date has already passed, roll to next month.
            $candidate = $this->resolveMonthlyOccurrence($from->copy()->startOfMonth(), $targetDay);
            $candidate->setTime((int) $h, (int) $m, (int) $s);

            if ($candidate->lte($from)) {
                $next = $from->copy()->startOfMonth()->addMonth();
                $candidate = $this->resolveMonthlyOccurrence($next, $targetDay);
                $candidate->setTime((int) $h, (int) $m, (int) $s);
            }
            return $candidate;
        }

        // Weekly (default)
        $candidate = $from->copy()->startOfDay();
        while ((int) $candidate->dayOfWeek !== $targetDay) {
            $candidate->addDay();
        }
        $candidate->setTime((int) $h, (int) $m, (int) $s);
        if ($candidate->lte($from)) {
            $candidate->addWeek();
        }
        return $candidate;
    }

    /**
     * Find the Nth occurrence of $dayOfWeek in the month containing $monthAnchor.
     * If monthly_week is 5, returns the LAST occurrence in the month.
     */
    private function resolveMonthlyOccurrence(Carbon $monthAnchor, int $dayOfWeek): Carbon
    {
        $week = (int) ($this->monthly_week ?: 1);

        if ($week === 5) {
            // "Last <day> of the month"
            $d = $monthAnchor->copy()->endOfMonth();
            while ((int) $d->dayOfWeek !== $dayOfWeek) {
                $d->subDay();
            }
            return $d;
        }

        // 1st-4th occurrence
        $d = $monthAnchor->copy()->startOfMonth();
        while ((int) $d->dayOfWeek !== $dayOfWeek) {
            $d->addDay();
        }
        $d->addWeeks($week - 1);

        // Sanity: the result must still be within the same month. If we asked for
        // the 5th week (extremely rare — only Februaries with no 5th occurrence)
        // fall back to the last occurrence in this month.
        if ($d->month !== $monthAnchor->month) {
            return $this->resolveMonthlyOccurrence($monthAnchor->copy(), $dayOfWeek);
        }
        return $d;
    }

    public function dayName(): string
    {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$this->goes_live_day] ?? '?';
    }

    /**
     * Human-readable cadence: "Mondays at 18:00" or "First Sunday of the month at 18:00".
     */
    public function cadenceLabel(): string
    {
        $time = substr($this->goes_live_time ?: '18:00:00', 0, 5);
        $day = $this->dayName();

        if ($this->frequency === 'monthly') {
            $nthLabels = [1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Last'];
            $nth = $nthLabels[(int) $this->monthly_week] ?? 'First';
            $fullDay = ['Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'][$day] ?? $day;
            return "{$nth} {$fullDay} of the month at {$time}";
        }

        // Weekly
        $plural = ['Sun' => 'Sundays', 'Mon' => 'Mondays', 'Tue' => 'Tuesdays', 'Wed' => 'Wednesdays', 'Thu' => 'Thursdays', 'Fri' => 'Fridays', 'Sat' => 'Saturdays'][$day] ?? $day;
        return "{$plural} at {$time}";
    }
}
