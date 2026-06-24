<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Auctioneer;
use App\Models\CommunityRegion;
use App\Models\CommunitySchedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateNextCommunityAuction extends Command
{
    protected $signature = 'community:create-next-auction';
    protected $description = 'Create the next scheduled community auction for every active schedule under an active region';

    public function handle(): int
    {
        $schedules = CommunitySchedule::active()
            ->whereHas('region', fn ($q) => $q->where('is_active', true))
            ->with('region')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No active community schedules.');
            return self::SUCCESS;
        }

        $created = 0;
        foreach ($schedules as $schedule) {
            if ($this->ensureNextAuction($schedule)) {
                $created++;
            }
        }

        $this->info("Done. {$created} auction(s) created.");
        return self::SUCCESS;
    }

    /**
     * Create the next draft auction for a schedule (if none already exists).
     * Returns true if an auction was created.
     */
    public function ensureNextAuction(CommunitySchedule $schedule): bool
    {
        $region = $schedule->region;
        $goesLiveAt = $schedule->nextGoesLiveAt();

        $exists = Auction::where('community_schedule_id', $schedule->id)
            ->where('goes_live_at', $goesLiveAt)
            ->exists();
        if ($exists) {
            $this->log("— {$region->name} / {$schedule->name}: next auction already exists for {$goesLiveAt}.");
            return false;
        }

        $safetyCapHours = (int) config('community.auction_safety_cap_hours', 12);
        $lineupLocksAt = $goesLiveAt->copy()->subHours(
            (int) config('community.lineup_lock_hours_before_live', 18)
        );

        $auctioneer = $this->systemAuctioneerFor($region);

        $title = "{$region->name} — {$schedule->name} " . $goesLiveAt->format('d M Y');
        $auction = Auction::create([
            'auctioneer_id' => $auctioneer->id,
            'title' => $title,
            'slug' => $this->uniqueSlug($title),
            'description' => $region->description,
            'status' => 'draft',
            'auction_type' => 'live',
            'start_time' => $goesLiveAt,
            'end_time' => $goesLiveAt->copy()->addHours($safetyCapHours),
            'is_community' => true,
            'community_region_id' => $region->id,
            'community_schedule_id' => $schedule->id,
            'pilot_mode' => $region->pilot_mode,
            'lineup_locks_at' => $lineupLocksAt,
            'goes_live_at' => $goesLiveAt,
            'requires_registration' => false,
            'allow_proxy_bidding' => false,
        ]);

        $this->log("+ {$region->name} / {$schedule->name}: created {$auction->slug} (goes live {$goesLiveAt->format('d M Y H:i')}).");
        return true;
    }

    /** Write only when we have a console output (no-op from web context). */
    private function log(string $message): void
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    public function systemAuctioneerFor(CommunityRegion $region): Auctioneer
    {
        $slug = 'community-' . $region->slug;
        $existing = Auctioneer::where('slug', $slug)->first();
        if ($existing) {
            return $existing;
        }

        $email = $slug . '@bidall.internal';
        $businessName = $region->name . ' Community';
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $businessName,
                'password' => bcrypt(Str::random(40)),
                'role' => 'auctioneer',
                'is_active' => true,
            ]
        );

        return Auctioneer::create([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'slug' => $slug,
            'phone' => '0000000000',
            'whatsapp_number' => '0000000000',
            'description' => $region->description,
            'is_activated' => true,
            'credit_balance' => 0,
            'is_free_account' => true,
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Auction::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
