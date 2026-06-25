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
    protected $signature = 'college:seed-demo {--fresh : wipe all existing demo data first (DESTRUCTIVE)}';
    protected $description = 'Add draft demo auctions (one per format) with real images — additive by default, does NOT wipe existing data';

    private const MARKER = '@demo.college.test';
    private const IMG_DIR = 'demo/college-lots'; // under storage/app/public

    public function handle(): int
    {
        // ADDITIVE by default: never wipes existing auctions/data. Re-running only adds
        // catalogue auctions that don't already exist (matched by title) and won't
        // duplicate the owner/bidders. Use --fresh to deliberately wipe and reseed.
        if ($this->option('fresh')) {
            $this->cleanup();
            $this->warn('--fresh: wiped existing demo data.');
        }
        $this->copyImages();

        $owner = User::updateOrCreate(['email' => 'owner' . self::MARKER], [
            'name' => 'Auctioneering College of SA',
            'phone' => '0314000000',
            'password' => Hash::make('password'),
            'role' => 'auctioneer',
            'email_verified_at' => now(),
        ]);

        // firstOrCreate so re-runs reuse the single owner-auctioneer (no duplicates).
        $auctioneer = Auctioneer::firstOrCreate(
            ['user_id' => $owner->id],
            [
                'business_name' => 'Auctioneering College of SA',
                'slug' => 'auctioneering-college-of-sa-' . Str::random(5),
                'whatsapp_number' => '0314000000',
                'is_activated' => true,
                'credit_balance' => 0,
                'is_free_account' => true,
            ]
        );

        // ---- Auction catalogue (all DRAFT) ----
        // Non-Dutch lot: [title, description, image, starting_bid, increment, reserve]
        // Dutch lot:     [title, description, image, start_price, floor_price]
        $catalogue = [
            ['type' => 'english', 'title' => 'Monthly Estate & Antiques Auction',
             'desc' => 'Fine art, antique furniture and collectibles. Ascending bids with anti-snipe soft close.',
             'extra' => ['allow_proxy_bidding' => true], 'lots' => [
                ['Framed Oil Painting', 'Original framed oil on canvas.', 'painting', 2000, 250, 5000],
                ['Antique Oak Dresser', 'Solid oak Victorian dresser.', 'dresser', 1500, 100, 3000],
                ['Display Cabinet', 'Glass-front antique display cabinet.', 'cabinet', 1000, 100, 2200],
                ['Gold Jewellery Set', 'Estate gold jewellery set.', 'jewellery', 3000, 250, 6000],
                ['Luxury Wristwatch', 'Pre-owned luxury automatic wristwatch.', 'watch', 2500, 250, 5000],
             ]],
            ['type' => 'english', 'title' => 'Fine Art & Collectibles',
             'desc' => 'Original artworks, rare coins and collectible pieces.',
             'extra' => ['allow_proxy_bidding' => true], 'lots' => [
                ['Bronze Sculpture', 'Limited-edition bronze sculpture.', 'art2', 4000, 500, 9000],
                ['Signed Oil Painting', 'Signed original oil painting.', 'painting', 2500, 250, 6000],
                ['Rare Coin Collection', 'Cased collection of rare South African coins.', 'coins', 1500, 100, 4000],
                ['Diamond Pendant', 'White-gold diamond pendant.', 'jewellery', 5000, 500, 12000],
             ]],
            ['type' => 'english', 'title' => 'Household & Estate Clearance',
             'desc' => 'A full house lot — furniture, electronics and more.', 'lots' => [
                ['Leather Lounge Suite', '3-piece genuine leather lounge suite.', 'sofa', 1800, 100, 4000],
                ['Antique Oak Dresser', 'Solid oak dresser.', 'dresser', 1200, 100, 2800],
                ['Display Cabinet', 'Glass-front cabinet.', 'cabinet', 900, 50, 2000],
                ['Laptop Computer', 'Refurbished business laptop.', 'laptop', 2500, 250, 5000],
                ['Case of Vintage Wine', 'Cellared case of red wine.', 'wine', 1200, 100, 3000],
             ]],
            ['type' => 'english', 'title' => 'Classic & Collector Cars',
             'desc' => 'Classic, vintage and collector vehicles.',
             'extra' => ['allow_proxy_bidding' => true], 'lots' => [
                ['Classic Sports Car', 'Restored classic sports car.', 'car', 120000, 5000, 280000],
                ['2018 Toyota Hilux', 'Single-owner double cab.', 'hilux', 180000, 5000, 250000],
             ]],
            ['type' => 'live', 'title' => 'Livestock Auction (Live)',
             'desc' => 'Auctioneer-paced live sale ring — presented, opened, going once, going twice, sold.',
             'extra' => ['goes_live_at' => now()->addDay()], 'lots' => [
                ['Angus Bull', 'Registered Angus bull, excellent condition.', 'bull', 8000, 500, 15000],
                ['Flock of Merino Sheep', 'Flock of 10 Merino ewes.', 'sheep', 4000, 250, 7000],
                ['Boerperd Gelding', 'Trained Boerperd gelding.', 'horse', 12000, 1000, 25000],
             ]],
            ['type' => 'live', 'title' => 'Stud & Performance Horses (Live)',
             'desc' => 'Live ring sale of stud and performance horses.',
             'extra' => ['goes_live_at' => now()->addDays(2)], 'lots' => [
                ['Warmblood Mare', 'Performance warmblood mare.', 'horse', 18000, 1000, 40000],
                ['Stud Angus Bull', 'Stud-quality Angus bull.', 'bull', 10000, 500, 20000],
             ]],
            ['type' => 'sealed', 'title' => 'Property & Vehicle Tender',
             'desc' => 'Sealed-bid tender — bids stay secret until close. Highest bid wins.',
             'extra' => ['sealed_mode' => 'highest'], 'lots' => [
                ['3-Bedroom House, Margate', 'Freehold 3-bed family home near the coast.', 'house', 450000, 10000, 750000],
                ['2018 Toyota Hilux', 'Single-owner 2.4 GD-6 double cab.', 'hilux', 180000, 5000, 250000],
                ['John Deere Tractor', 'John Deere 5075E utility tractor.', 'tractor', 220000, 5000, 350000],
             ]],
            ['type' => 'sealed', 'title' => 'Commercial Property Tender',
             'desc' => 'Sealed tender for a commercial property. Highest bid wins.',
             'extra' => ['sealed_mode' => 'highest'], 'lots' => [
                ['Retail Premises', 'Ground-floor retail premises on a main road.', 'house', 1200000, 25000, 1800000],
             ]],
            ['type' => 'sealed', 'title' => 'Farm Equipment & Machinery',
             'desc' => 'Sealed tender on farm machinery and equipment.',
             'extra' => ['sealed_mode' => 'highest'], 'lots' => [
                ['John Deere Tractor', 'Utility tractor, low hours.', 'tractor', 200000, 5000, 320000],
                ['Diesel Generator', 'Standby diesel generator.', 'generator', 30000, 2000, 70000],
                ['Farm Bakkie', '2018 Toyota Hilux double cab.', 'hilux', 170000, 5000, 240000],
             ]],
            ['type' => 'dutch', 'title' => 'Farm Dispersal (Dutch Sale)',
             'desc' => 'Descending-price Dutch sale — price drops until the first buyer accepts.',
             'extra' => ['dutch_drop_strategy' => 'constant'], 'lots' => [
                ['Angus Bull', 'Registered Angus bull.', 'bull', 18000, 9000],
                ['John Deere Tractor', 'John Deere 5075E utility tractor.', 'tractor', 320000, 180000],
                ['Flock of Merino Sheep', 'Flock of 10 Merino ewes.', 'sheep', 7000, 3500],
             ]],
            ['type' => 'dutch', 'title' => 'Wholesale Stock Clearance (Dutch)',
             'desc' => 'Bulk stock clearance — Dutch descending price.',
             'extra' => ['dutch_drop_strategy' => 'fast_sell'], 'lots' => [
                ['Case of Vintage Wine', 'Cellared case of red wine.', 'wine', 3000, 1200],
                ['Laptop Computer', 'Refurbished business laptop.', 'laptop', 5000, 2200],
                ['Leather Lounge Suite', '3-piece leather lounge suite.', 'sofa', 4500, 1800],
             ]],
        ];

        $added = 0;
        $skipped = 0;
        foreach ($catalogue as $a) {
            // Additive + idempotent: skip an auction that already exists (matched by title)
            // so existing/edited/published auctions are never touched or duplicated.
            if (Auction::where('auctioneer_id', $auctioneer->id)->where('title', $a['title'])->exists()) {
                $skipped++;
                continue;
            }
            $auction = $this->auction($auctioneer, $a['type'], $a['title'], $a['desc'], $a['extra'] ?? []);
            $n = 1;
            foreach ($a['lots'] as $lotData) {
                if ($a['type'] === 'dutch') {
                    $this->dutchLot($auction, $n++, $lotData[0], $lotData[1], $lotData[2], $lotData[3], $lotData[4]);
                } else {
                    $this->lot($auction, $n++, $lotData[0], $lotData[1], $lotData[2], $lotData[3], $lotData[4], $lotData[5] ?? 0);
                }
            }
            $added++;
        }

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
        $this->info("🟢 Added {$added} new DRAFT auction(s); skipped {$skipped} already present (catalogue total " . count($catalogue) . ").");
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
