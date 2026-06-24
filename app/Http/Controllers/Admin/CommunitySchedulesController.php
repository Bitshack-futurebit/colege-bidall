<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\CreateNextCommunityAuction;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\CommunityRegion;
use App\Models\CommunitySchedule;
use App\Models\Lot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunitySchedulesController extends Controller
{
    public function store(Request $request, CommunityRegion $region)
    {
        $validated = $this->validateSchedule($request);
        $validated['community_region_id'] = $region->id;
        CommunitySchedule::create($validated);

        return redirect()->route('admin.community-regions.edit', $region)
            ->with('success', 'Schedule added.');
    }

    public function update(Request $request, CommunityRegion $region, CommunitySchedule $schedule)
    {
        abort_if($schedule->community_region_id !== $region->id, 404);
        $validated = $this->validateSchedule($request);
        $schedule->update($validated);

        return redirect()->route('admin.community-regions.edit', $region)
            ->with('success', 'Schedule updated.');
    }

    public function toggleActive(CommunityRegion $region, CommunitySchedule $schedule)
    {
        abort_if($schedule->community_region_id !== $region->id, 404);
        $schedule->update(['is_active' => !$schedule->is_active]);

        return back()->with('success', 'Schedule ' . ($schedule->is_active ? 'activated.' : 'deactivated.'));
    }

    public function destroy(CommunityRegion $region, CommunitySchedule $schedule)
    {
        abort_if($schedule->community_region_id !== $region->id, 404);

        if ($schedule->auctions()->whereIn('status', ['live', 'upcoming'])->exists()) {
            return back()->with('error', 'Cannot delete a schedule with a live or upcoming auction. End it first.');
        }

        $auctionCount = $schedule->auctions()->delete();
        $schedule->delete();

        $msg = $auctionCount > 0
            ? "Schedule deleted along with {$auctionCount} past auction(s)."
            : 'Schedule deleted.';

        return back()->with('success', $msg);
    }

    /**
     * Testing helper: create the next scheduled auction immediately
     * (rather than waiting for the weekly cron).
     */
    public function createNext(CommunityRegion $region, CommunitySchedule $schedule)
    {
        abort_if($schedule->community_region_id !== $region->id, 404);
        $created = app(CreateNextCommunityAuction::class)->ensureNextAuction($schedule);

        return back()->with(
            $created ? 'success' : 'error',
            $created ? 'Next draft auction created.' : 'Next auction already exists.'
        );
    }

    /**
     * Testing helper: force a draft/upcoming community auction to live right now.
     */
    public function goLive(Auction $auction)
    {
        if (!$auction->is_community || !in_array($auction->status, ['draft', 'upcoming'])) {
            return back()->with('error', 'Only draft or upcoming community auctions can be forced live.');
        }

        $this->rescheduleAuction($auction, now());
        $auction->update(['status' => 'live']);

        if ($auction->isLiveFormat()) {
            $auction->scheduleLiveLots();
        } elseif ($auction->isDutch()) {
            $auction->calculateSequentialSchedule();
            $first = $auction->lots()->whereNull('withdrawn_at')->orderBy('lot_number')->first();
            if ($first) {
                $first->update(['status' => 'live']);
            }
        } else {
            $auction->lots()->whereNull('withdrawn_at')->update(['status' => 'live']);
        }

        return back()->with('success', "Auction #{$auction->id} is now LIVE.");
    }

    /**
     * Testing helper: end a live community auction right now.
     */
    public function endNow(Auction $auction)
    {
        if (!$auction->is_community || $auction->status !== 'live') {
            return back()->with('error', 'Only live community auctions can be ended.');
        }

        $auction->update(['end_time' => now()]);
        $auction->end();

        return back()->with('success', "Auction #{$auction->id} ended.");
    }

    /**
     * Testing helper: shift a draft/upcoming community auction to a specific go-live datetime.
     * Rewrites title + slug so history stays coherent.
     */
    public function reschedule(Request $request, Auction $auction)
    {
        if (!$auction->is_community || !in_array($auction->status, ['draft', 'upcoming'])) {
            return back()->with('error', 'Only draft or upcoming community auctions can be rescheduled.');
        }

        $validated = $request->validate([
            'goes_live_at' => ['required', 'date'],
            'lineup_lock_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ]);

        $lockHours = isset($validated['lineup_lock_hours'])
            ? (int) $validated['lineup_lock_hours']
            : (int) config('community.lineup_lock_hours_before_live', 6);

        $this->rescheduleAuction($auction, Carbon::parse($validated['goes_live_at']), $lockHours);

        return back()->with('success', "Auction #{$auction->id} rescheduled to {$auction->goes_live_at->format('D d M Y H:i')} (lineup locks {$lockHours}h before).");
    }

    /**
     * Testing helper: hard-delete a community auction and its lots/images.
     * Only allowed for non-live auctions so we don't yank a live test out from under bidders.
     */
    public function destroyAuction(Auction $auction)
    {
        if (!$auction->is_community) {
            return back()->with('error', 'Not a community auction.');
        }
        if ($auction->status === 'live') {
            return back()->with('error', 'End the auction before deleting it.');
        }

        $auction->loadMissing('lots.images');
        foreach ($auction->lots as $lot) {
            foreach ($lot->images as $img) {
                if ($img->optimized_path) Storage::disk('public')->delete($img->optimized_path);
                if ($img->thumbnail_path) Storage::disk('public')->delete($img->thumbnail_path);
                $img->delete();
            }
        }
        $auction->forceDelete();

        return back()->with('success', "Auction #{$auction->id} deleted.");
    }

    /**
     * Rewrite an auction's timing (goes_live_at, start_time, end_time, lineup_locks_at)
     * plus title/slug so the stored metadata matches the new go-live moment.
     */
    private function rescheduleAuction(Auction $auction, Carbon $goesLiveAt, ?int $lineupLockHours = null): void
    {
        $safetyCapHours = (int) config('community.auction_safety_cap_hours', 12);
        $lineupLockHours ??= (int) config('community.lineup_lock_hours_before_live', 6);
        $region = $auction->communityRegion;
        $schedule = $auction->communitySchedule;

        $title = $region && $schedule
            ? "{$region->name} — {$schedule->name} " . $goesLiveAt->format('d M Y')
            : ($auction->title ?? 'Community Auction');

        $auction->update([
            'goes_live_at' => $goesLiveAt,
            'start_time' => $goesLiveAt,
            'end_time' => $goesLiveAt->copy()->addHours($safetyCapHours),
            'lineup_locks_at' => $goesLiveAt->copy()->subHours($lineupLockHours),
            'title' => $title,
            'slug' => $this->uniqueSlug($title, $auction->id),
        ]);
    }

    private function uniqueSlug(string $title, int $ignoreAuctionId): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Auction::where('slug', $slug)->where('id', '!=', $ignoreAuctionId)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function validateSchedule(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'frequency' => ['required', 'in:weekly,monthly'],
            'goes_live_day' => ['required', 'integer', 'between:0,6'],
            'monthly_week' => ['nullable', 'integer', 'between:1,5', 'required_if:frequency,monthly'],
            'goes_live_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['goes_live_time'] = $validated['goes_live_time'] . ':00';
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        // Null out monthly_week when frequency is weekly to keep data clean.
        if ($validated['frequency'] === 'weekly') {
            $validated['monthly_week'] = null;
        }
        return $validated;
    }
}
