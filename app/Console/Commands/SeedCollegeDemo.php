<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Auctioneer;
use App\Models\Lot;
use App\Models\LotImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds a self-contained demo for the standalone (college) product:
 * one owner-auctioneer + four DRAFT auctions (english/dutch/sealed/live),
 * each with image-backed lots. Nothing is published/gone-live and no bids
 * are placed — the owner reviews and publishes manually.
 *
 * Idempotent: re-running wipes the prior demo (by the demo email marker) first.
 */
class SeedCollegeDemo extends Command
{
    protected $signature = 'college:seed-demo';
    protected $description = 'Seed owner-auctioneer + 4 draft demo auctions (one per format) with real images';

    private const MARKER = '@demo.college.test';
    private const IMG_DIR = 'demo/college-lots'; // under storage/app/public

    public function handle(): int
    {
        $this->cleanup();
        $this->copyImages();

        $owner = User::updateOrCreate(['email' => 'owner' . self::MARKER], [
            'name' => 'Auctioneering College of SA',
            'phone' => '0314000000',
            'password' => Hash::make('password'),
            'role' => 'auctioneer',
            'email_verified_at' => now(),
        ]);

        $auctioneer = Auctioneer::create([
            'user_id' => $owner->id,
            'business_name' => 'Auctioneering College of SA',
            'slug' => 'auctioneering-college-of-sa-' . Str::random(5),
            'whatsapp_number' => '0314000000',
            'is_activated' => true,
            'credit_balance' => 0,
            'is_free_account' => true,
        ]);

        // ---- English: estate & antiques (ascending, soft-close) ----
        $english = $this->auction($auctioneer, 'english', 'Monthly Estate & Antiques Auction',
            'Fine art, antique furniture and collectibles. Ascending bids with anti-snipe soft close.', [
                'allow_proxy_bidding' => true,
            ]);
        $this->lot($english, 1, 'Framed Oil Painting', 'Original framed oil on canvas.', 'painting', 2000, 250, 5000);
        $this->lot($english, 2, 'Antique Oak Dresser', 'Solid oak Victorian dresser.', 'dresser', 1500, 100, 3000);
        $this->lot($english, 3, 'Display Cabinet', 'Glass-front antique display cabinet.', 'cabinet', 1000, 100, 2200);
        $this->lot($english, 4, 'Gold Jewellery Set', 'Estate gold jewellery set.', 'jewellery', 3000, 250, 6000);
        $this->lot($english, 5, 'Luxury Wristwatch', 'Pre-owned luxury automatic wristwatch.', 'watch', 2500, 250, 5000);

        // ---- Live: livestock (auctioneer-paced) ----
        $live = $this->auction($auctioneer, 'live', 'Livestock Auction (Live)',
            'Auctioneer-paced live sale ring — presented, opened, going once, going twice, sold.', [
                'goes_live_at' => now()->addDay(),
            ]);
        $this->lot($live, 1, 'Angus Bull', 'Registered Angus bull, excellent condition.', 'bull', 8000, 500, 15000);
        $this->lot($live, 2, 'Flock of Merino Sheep', 'Flock of 10 Merino ewes.', 'sheep', 4000, 250, 7000);

        // ---- Sealed: property & vehicles (tender, secret bids) ----
        $sealed = $this->auction($auctioneer, 'sealed', 'Property & Vehicle Tender',
            'Sealed-bid tender — bids stay secret until close. Highest bid wins.', [
                'sealed_mode' => 'highest',
            ]);
        $this->lot($sealed, 1, '3-Bedroom House, Margate', 'Freehold 3-bed family home near the coast.', 'house', 450000, 10000, 750000);
        $this->lot($sealed, 2, '2018 Toyota Hilux', 'Single-owner 2.4 GD-6 double cab.', 'hilux', 180000, 5000, 250000);
        $this->lot($sealed, 3, 'John Deere Tractor', 'John Deere 5075E utility tractor.', 'tractor', 220000, 5000, 350000);

        // ---- Dutch: farm dispersal (descending) ----
        $dutch = $this->auction($auctioneer, 'dutch', 'Farm Dispersal (Dutch Sale)',
            'Descending-price Dutch sale — price drops until the first buyer accepts.', [
                'dutch_drop_strategy' => 'constant',
            ]);
        $this->dutchLot($dutch, 1, 'Angus Bull', 'Registered Angus bull.', 'bull', 18000, 9000);
        $this->dutchLot($dutch, 2, 'John Deere Tractor', 'John Deere 5075E utility tractor.', 'tractor', 320000, 180000);
        $this->dutchLot($dutch, 3, 'Flock of Merino Sheep', 'Flock of 10 Merino ewes.', 'sheep', 7000, 3500);

        // ---- Two demo bidder logins (so the site can be tested as a student) ----
        foreach (['student1', 'student2'] as $i => $s) {
            User::updateOrCreate(['email' => $s . self::MARKER], [
                'name' => 'Demo Student ' . ($i + 1),
                'phone' => '08200000' . ($i + 10),
                'password' => Hash::make('password'),
                'role' => 'bidder',
                'email_verified_at' => now(),
            ]);
        }

        $this->newLine();
        $this->info('🟢 Seeded 4 DRAFT auctions (english/dutch/sealed/live) for "Auctioneering College of SA".');
        $this->line('  Owner auctioneer login: owner' . self::MARKER . ' / password');
        $this->line('  Demo bidder logins:     student1' . self::MARKER . ', student2' . self::MARKER . ' / password');
        $this->line('  All auctions are DRAFT — publish them from the dashboard when ready.');
        return Command::SUCCESS;
    }

    /** Create a draft auction of the given type. */
    private function auction(Auctioneer $a, string $type, string $title, string $desc, array $extra = []): Auction
    {
        return Auction::create(array_merge([
            'auctioneer_id' => $a->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'description' => $desc,
            'status' => 'draft',
            'auction_type' => $type,
            'is_community' => false,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(3),
            'buyers_premium_percentage' => 10,
            'requires_registration' => false,
            'allow_proxy_bidding' => false,
            'enable_online_payment' => false,
        ], $extra));
    }

    /** Standard (english/sealed/live) lot + image. */
    private function lot(Auction $auction, int $n, string $title, string $desc, string $image, float $start, float $inc, float $reserve = 0): void
    {
        $lot = Lot::create([
            'event_id' => $auction->id,
            'lot_number' => $n,
            'title' => $title,
            'description' => $desc,
            'image_tier' => 'basic',
            'starting_bid' => $start,
            'increment' => $inc,
            'reserve_price' => $reserve,
            'status' => 'pending',
        ]);
        $this->attachImage($lot, $image);
    }

    /** Dutch lot (descending: start price -> floor price). */
    private function dutchLot(Auction $auction, int $n, string $title, string $desc, string $image, float $startPrice, float $floorPrice): void
    {
        $lot = Lot::create([
            'event_id' => $auction->id,
            'lot_number' => $n,
            'title' => $title,
            'description' => $desc,
            'image_tier' => 'basic',
            'starting_bid' => $startPrice,
            'increment' => max(1, round(($startPrice - $floorPrice) / 20)),
            'reserve_price' => $floorPrice,
            'dutch_start_price' => $startPrice,
            'dutch_floor_price' => $floorPrice,
            'dutch_drop_strategy' => 'constant',
            'status' => 'pending',
        ]);
        $this->attachImage($lot, $image);
    }

    /** Copy bundled demo photos from tracked seeder assets into the public disk (idempotent). */
    private function copyImages(): void
    {
        $disk = Storage::disk('public');
        foreach (glob(database_path('seeders/assets/lots') . '/*.jpg') as $file) {
            $target = self::IMG_DIR . '/' . basename($file);
            if (!$disk->exists($target)) {
                $disk->put($target, file_get_contents($file));
            }
        }
    }

    /** Point all three image paths at the pre-downloaded demo photo (public disk). */
    private function attachImage(Lot $lot, string $name): void
    {
        $path = self::IMG_DIR . "/{$name}.jpg";
        LotImage::create([
            'lot_id' => $lot->id,
            'original_path' => $path,
            'optimized_path' => $path,
            'thumbnail_path' => $path,
            'order' => 0,
            'is_primary' => true,
        ]);
    }

    private function cleanup(): void
    {
        $ids = User::where('email', 'like', '%' . self::MARKER)->pluck('id');
        if ($ids->isEmpty()) return;
        $aids = Auctioneer::whereIn('user_id', $ids)->pluck('id');
        $eids = Auction::whereIn('auctioneer_id', $aids)->pluck('id');
        $lids = Lot::whereIn('event_id', $eids)->pluck('id');
        DB::table('lot_images')->whereIn('lot_id', $lids)->delete();
        DB::table('bids')->whereIn('lot_id', $lids)->delete();
        Lot::whereIn('id', $lids)->forceDelete();
        Auction::whereIn('id', $eids)->forceDelete();
        Auctioneer::whereIn('id', $aids)->forceDelete();
        User::whereIn('id', $ids)->forceDelete();
    }
}
