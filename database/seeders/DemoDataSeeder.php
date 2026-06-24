<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Auctioneer;
use App\Models\Auction;
use App\Models\Lot;
use App\Models\LotImage;
use App\Models\Bid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting demo data seeding...');

        // Always clean up existing demo data first
        $this->command->info('Cleaning up existing demo data...');
        $this->cleanup();

        // Create bidders
        $this->command->info('Creating bidders...');
        $bidders = $this->createBidders();

        // Create auctioneers with businesses
        $this->command->info('Creating auctioneers...');
        $auctioneers = $this->createAuctioneers();

        // Create auctions for each auctioneer
        $this->command->info('Creating auctions...');
        foreach ($auctioneers as $auctioneer) {
            $this->createAuctionsForAuctioneer($auctioneer);
        }

        // Add bids from bidders
        $this->command->info('Adding bids...');
        $this->createBids($bidders);

        $this->command->info('✅ Demo data seeding completed!');
    }

    private function cleanup(): void
    {
        try {
            // Get all demo user IDs and auctioneer IDs
            $demoUserIds = \DB::table('users')->where('email', 'like', '%@example.com')->pluck('id');

            if ($demoUserIds->isEmpty()) {
                $this->command->info('No demo users found to clean up');
                return;
            }

            $this->command->info("Found {$demoUserIds->count()} demo users to clean up");

            $demoAuctioneerIds = \DB::table('auctioneers')->whereIn('user_id', $demoUserIds)->pluck('id');
            $this->command->info("Found {$demoAuctioneerIds->count()} demo auctioneers");

            if ($demoAuctioneerIds->isNotEmpty()) {
                $demoEventIds = \DB::table('events')->whereIn('auctioneer_id', $demoAuctioneerIds)->pluck('id');
                $this->command->info("Found {$demoEventIds->count()} demo auctions");

                if ($demoEventIds->isNotEmpty()) {
                    $demoLotIds = \DB::table('lots')->whereIn('event_id', $demoEventIds)->pluck('id');
                    $this->command->info("Found {$demoLotIds->count()} demo lots");

                    // Delete in correct order to avoid foreign key constraints

                    // 1. Delete all bids
                    if ($demoLotIds->isNotEmpty()) {
                        $deleted = \DB::table('bids')->whereIn('lot_id', $demoLotIds)->delete();
                        $this->command->info("Deleted {$deleted} bids on lots");
                    }

                    // 2. Delete lot images
                    if ($demoLotIds->isNotEmpty()) {
                        $deleted = \DB::table('lot_images')->whereIn('lot_id', $demoLotIds)->delete();
                        $this->command->info("Deleted {$deleted} lot images");
                    }

                    // 3. Delete lots
                    $deleted = \DB::table('lots')->whereIn('event_id', $demoEventIds)->delete();
                    $this->command->info("Deleted {$deleted} lots");
                }

                // 4. Delete auctions
                $deleted = \DB::table('events')->whereIn('auctioneer_id', $demoAuctioneerIds)->delete();
                $this->command->info("Deleted {$deleted} auctions");

                // 5. Delete auctioneers
                $deleted = \DB::table('auctioneers')->whereIn('user_id', $demoUserIds)->delete();
                $this->command->info("Deleted {$deleted} auctioneers");
            }

            // Delete user bids
            $deleted = \DB::table('bids')->whereIn('user_id', $demoUserIds)->delete();
            $this->command->info("Deleted {$deleted} user bids");

            // 6. Delete users
            $deleted = \DB::table('users')->where('email', 'like', '%@example.com')->delete();
            $this->command->info("Deleted {$deleted} users");

            // Clean up demo images
            if (Storage::disk('public')->exists('demo')) {
                Storage::disk('public')->deleteDirectory('demo');
                $this->command->info("Deleted demo images directory");
            }

            $this->command->info('✓ Cleanup completed successfully');
        } catch (\Exception $e) {
            $this->command->error('Cleanup error: ' . $e->getMessage());
            $this->command->error('Error on line: ' . $e->getLine());
            throw $e;
        }
    }

    private function createBidders(): array
    {
        $bidders = [];
        $names = ['John Smith', 'Sarah Johnson', 'Michael Brown', 'Emma Wilson', 'David Lee'];

        foreach ($names as $name) {
            $bidders[] = User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $name)) . '@example.com'],
                [
                'name' => $name,
                'phone' => '071' . rand(1000000, 9999999),
                'address' => rand(1, 999) . ' Main Street',
                'city' => collect(['Johannesburg', 'Cape Town', 'Durban', 'Pretoria'])->random(),
                'province' => 'Gauteng',
                'lat' => -26.2041 + (rand(-100, 100) / 1000),
                'lng' => 28.0473 + (rand(-100, 100) / 1000),
                'password' => Hash::make('password'),
                'role' => 'bidder',
                'email_verified_at' => now(),
            ]);
        }

        return $bidders;
    }

    private function createAuctioneers(): array
    {
        $businesses = [
            [
                'name' => 'Heritage Antiques',
                'owner' => 'Robert Anderson',
                'description' => 'Specializing in fine antiques, vintage furniture, and rare collectibles since 1985.',
                'city' => 'Johannesburg',
                'category' => 'antiques',
            ],
            [
                'name' => 'Cape Art Gallery',
                'owner' => 'Lisa van der Merwe',
                'description' => 'Contemporary and classical art pieces from renowned South African artists.',
                'city' => 'Cape Town',
                'category' => 'art',
            ],
            [
                'name' => 'Classic Cars SA',
                'owner' => 'James Peterson',
                'description' => 'Vintage and classic automobiles, motorcycle memorabilia, and automotive collectibles.',
                'city' => 'Pretoria',
                'category' => 'vehicles',
            ],
            [
                'name' => 'Estate Liquidators',
                'owner' => 'Amanda Nkosi',
                'description' => 'Complete estate sales, household goods, jewelry, and general merchandise.',
                'city' => 'Durban',
                'category' => 'general',
            ],
        ];

        $auctioneers = [];

        foreach ($businesses as $business) {
            $user = User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $business['owner'])) . '@example.com'],
                [
                'name' => $business['owner'],
                'phone' => '082' . rand(1000000, 9999999),
                'address' => rand(1, 99) . ' Business Avenue',
                'city' => $business['city'],
                'province' => 'Gauteng',
                'lat' => -26.2041 + (rand(-200, 200) / 1000),
                'lng' => 28.0473 + (rand(-200, 200) / 1000),
                'password' => Hash::make('password'),
                'role' => 'auctioneer',
                'email_verified_at' => now(),
            ]);

            $auctioneerData = [
                'business_name' => $business['name'],
                'slug' => \Str::slug($business['name']),
                'description' => $business['description'],
                'whatsapp_number' => '082' . rand(1000000, 9999999),
                'website' => 'https://www.' . \Str::slug($business['name']) . '.co.za',
                'is_activated' => true,
                'activated_at' => now()->subDays(rand(30, 365)),
                'credit_balance' => 1000,
            ];

            // Try to download images, but don't fail if they don't work
            $logo = $this->downloadPlaceholderImage('logo', 200, 200, $business['category']);
            $profile = $this->downloadPlaceholderImage('profile', 200, 200, 'people');
            $banner = $this->downloadPlaceholderImage('banner', 1920, 400, $business['category']);

            if ($logo) $auctioneerData['logo'] = $logo;
            if ($profile) $auctioneerData['profile_image'] = $profile;
            if ($banner) $auctioneerData['banner_image'] = $banner;

            $auctioneer = Auctioneer::updateOrCreate(
                ['user_id' => $user->id],
                $auctioneerData
            );

            $auctioneers[] = $auctioneer;
        }

        return $auctioneers;
    }

    private function createAuctionsForAuctioneer(Auctioneer $auctioneer): void
    {
        $auctionTypes = [
            ['title' => 'Monthly Estate Auction', 'status' => 'live', 'lots' => 50],
            ['title' => 'Spring Clearance Sale', 'status' => 'upcoming', 'lots' => 30],
            ['title' => 'Collectors Special', 'status' => 'ended', 'lots' => 25],
        ];

        foreach ($auctionTypes as $auctionData) {
            $startTime = match($auctionData['status']) {
                'live' => now()->subHours(2),
                'upcoming' => now()->addDays(rand(3, 10)),
                'ended' => now()->subDays(rand(5, 30)),
            };

            $auction = Auction::create([
                'auctioneer_id' => $auctioneer->id,
                'title' => $auctioneer->business_name . ' - ' . $auctionData['title'],
                'slug' => \Str::slug($auctioneer->business_name . ' ' . $auctionData['title'] . ' ' . rand(1000, 9999)),
                'description' => 'A curated selection of quality items from ' . $auctioneer->business_name . '. Located in ' . $auctioneer->user->city . '. Standard auction terms apply.',
                'status' => $auctionData['status'],
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHours(8),
                'deposit_amount' => rand(0, 1) ? rand(100, 500) : 0,
                'buyers_premium_percentage' => 10,
            ]);

            $this->createLotsForAuction($auction, $auctionData['lots']);
        }
    }

    private function createLotsForAuction(Auction $auction, int $count): void
    {
        $categories = ['Furniture', 'Electronics', 'Jewelry', 'Art', 'Collectibles', 'Tools', 'Antiques'];

        for ($i = 1; $i <= $count; $i++) {
            $category = $categories[array_rand($categories)];
            $startPrice = rand(10, 500);
            $reservePrice = $startPrice * 1.5;
            $imageTier = match(rand(1, 3)) {
                1 => 'basic',
                2 => 'pro',
                default => 'premium',
            };

            $lot = Lot::create([
                'event_id' => $auction->id,
                'lot_number' => $i,
                'title' => $category . ' Item #' . $i,
                'description' => 'Quality ' . strtolower($category) . ' in excellent condition. Perfect for collectors and enthusiasts. Condition: ' . collect(['New', 'Excellent', 'Good', 'Fair'])->random(),
                'starting_bid' => $startPrice,
                'reserve_price' => $reservePrice,
                'increment' => 10, // R10 increment
                'current_bid' => $auction->status === 'live' ? $startPrice + rand(0, 200) : null,
                'total_bids' => $auction->status === 'live' ? rand(0, 15) : 0,
                'status' => match($auction->status) {
                    'live' => 'live',
                    'ended' => rand(0, 1) ? 'sold' : 'unsold',
                    default => 'pending',
                },
                'image_tier' => $imageTier,
            ]);

            // Add images based on tier
            $imageCount = match($imageTier) {
                'basic' => 1,
                'pro' => 5,
                'premium' => rand(10, 20),
            };

            for ($j = 1; $j <= $imageCount; $j++) {
                $optimizedPath = $this->downloadPlaceholderImage('lot', 800, 600, strtolower($category));
                $thumbnailPath = $this->downloadPlaceholderImage('thumb', 300, 225, strtolower($category));

                // Only create image record if download succeeded
                if ($optimizedPath && $thumbnailPath) {
                    LotImage::create([
                        'lot_id' => $lot->id,
                        'optimized_path' => $optimizedPath,
                        'thumbnail_path' => $thumbnailPath,
                        'order' => $j,
                        'is_primary' => $j === 1,
                    ]);
                }
            }
        }
    }

    private function createBids(array $bidders): void
    {
        $activeLots = Lot::whereHas('auction', function($q) {
            $q->where('status', 'live');
        })->where('status', 'live')->get();

        foreach ($activeLots as $lot) {
            // Random number of bids
            $bidCount = rand(0, 8);
            $currentBid = $lot->starting_bid;

            for ($i = 0; $i < $bidCount; $i++) {
                $bidder = $bidders[array_rand($bidders)];
                $currentBid += rand(5, 50);

                Bid::create([
                    'lot_id' => $lot->id,
                    'user_id' => $bidder->id,
                    'amount' => $currentBid,
                    'created_at' => now()->subMinutes(rand(1, 120)),
                ]);
            }

            // Update lot current bid
            if ($bidCount > 0) {
                $lot->update([
                    'current_bid' => $currentBid,
                ]);
            }
        }
    }

    private function downloadPlaceholderImage(string $type, int $width, int $height, string $category): ?string
    {
        try {
            // Color mappings for different categories
            $colors = [
                'antiques' => [139, 115, 85],
                'art' => [255, 107, 107],
                'vehicles' => [78, 205, 196],
                'general' => [149, 225, 211],
                'people' => [108, 92, 231],
                'furniture' => [168, 230, 207],
                'electronics' => [116, 185, 255],
                'jewelry' => [250, 177, 160],
                'collectibles' => [255, 217, 61],
                'tools' => [108, 117, 125],
            ];

            $color = $colors[$category] ?? [204, 204, 204];

            // Create image using GD
            $image = imagecreatetruecolor($width, $height);
            $bgColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
            $textColor = imagecolorallocate($image, 255, 255, 255);

            imagefill($image, 0, 0, $bgColor);

            // Add text
            $text = ucfirst($type);
            $fontSize = min($width, $height) / 10;
            $fontFile = 'C:\\Windows\\Fonts\\arial.ttf'; // Windows default font

            if (file_exists($fontFile)) {
                // Calculate text position to center it
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
                $x = ($width - ($bbox[2] - $bbox[0])) / 2;
                $y = ($height - ($bbox[7] - $bbox[1])) / 2;
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
            }

            // Save to storage
            $path = "demo/{$type}s/" . uniqid() . '.png';
            $fullPath = storage_path('app/public/' . $path);

            // Create directory if it doesn't exist
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            imagepng($image, $fullPath);
            imagedestroy($image);

            return $path;
        } catch (\Exception $e) {
            $this->command->error("Failed to create image: {$e->getMessage()}");
            return null;
        }
    }
}
