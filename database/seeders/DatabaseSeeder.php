<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Auctioneer;
use App\Models\Auction;
use App\Models\Lot;
use App\Models\Bid;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@bidall.co.za',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '0123456789',
            'email_verified_at' => now(),
        ]);

        echo "✓ Admin created: admin@bidall.co.za / password\n";

        // Create Test Bidders
        $bidders = [];
        for ($i = 1; $i <= 5; $i++) {
            $bidders[] = User::create([
                'name' => "Test Bidder $i",
                'email' => "bidder$i@test.com",
                'password' => Hash::make('password'),
                'role' => 'bidder',
                'phone' => "012345678$i",
                'email_verified_at' => now(),
            ]);
        }

        echo "✓ Created 5 test bidders (bidder1@test.com - bidder5@test.com / password)\n";

        // Create Activated Auctioneer
        $activatedUser = User::create([
            'name' => 'John Smith',
            'email' => 'auctioneer@test.com',
            'password' => Hash::make('password'),
            'role' => 'auctioneer',
            'phone' => '0821234567',
            'email_verified_at' => now(),
            'city' => 'Johannesburg',
            'province' => 'Gauteng',
            'lat' => -26.2041,
            'lng' => 28.0473,
        ]);

        $activatedAuctioneer = Auctioneer::create([
            'user_id' => $activatedUser->id,
            'business_name' => 'Smith Auctions',
            'slug' => 'smith-auctions',
            'phone' => '0821234567',
            'whatsapp_number' => '27821234567',
            'bio' => 'Professional auctioneer with 20 years experience in antiques and collectibles.',
            'address' => '123 Main Street',
            'city' => 'Johannesburg',
            'province' => 'Gauteng',
            'is_activated' => true,
            'activated_at' => now(),
            'credit_balance' => 1000.00, // R1000 starting balance
        ]);

        echo "✓ Activated auctioneer created: auctioneer@test.com / password\n";

        // Create more activated auctioneers in different cities
        $capeTownUser = User::create([
            'name' => 'Sarah Williams',
            'email' => 'cape-auctions@test.com',
            'password' => Hash::make('password'),
            'role' => 'auctioneer',
            'phone' => '0829876543',
            'email_verified_at' => now(),
            'city' => 'Cape Town',
            'province' => 'Western Cape',
            'lat' => -33.9249,
            'lng' => 18.4241,
        ]);

        Auctioneer::create([
            'user_id' => $capeTownUser->id,
            'business_name' => 'Cape Town Auctions',
            'slug' => 'cape-town-auctions',
            'phone' => '0829876543',
            'whatsapp_number' => '27829876543',
            'bio' => 'Specializing in fine art and antiques in the Western Cape.',
            'address' => '45 Waterfront Drive',
            'city' => 'Cape Town',
            'province' => 'Western Cape',
            'is_activated' => true,
            'activated_at' => now(),
            'credit_balance' => 500.00,
        ]);

        $durbanUser = User::create([
            'name' => 'Michael Brown',
            'email' => 'durban-auctions@test.com',
            'password' => Hash::make('password'),
            'role' => 'auctioneer',
            'phone' => '0835551234',
            'email_verified_at' => now(),
            'city' => 'Durban',
            'province' => 'KwaZulu-Natal',
            'lat' => -29.8587,
            'lng' => 31.0218,
        ]);

        Auctioneer::create([
            'user_id' => $durbanUser->id,
            'business_name' => 'Durban Auction House',
            'slug' => 'durban-auction-house',
            'phone' => '0835551234',
            'whatsapp_number' => '27835551234',
            'bio' => 'Premier auction house in KwaZulu-Natal for estate sales.',
            'address' => '78 Marine Parade',
            'city' => 'Durban',
            'province' => 'KwaZulu-Natal',
            'is_activated' => true,
            'activated_at' => now(),
            'credit_balance' => 750.00,
        ]);

        echo "✓ Created additional activated auctioneers in Cape Town and Durban\n";

        // Create Pending Auctioneers
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'name' => "Auctioneer $i",
                'email' => "pending$i@test.com",
                'password' => Hash::make('password'),
                'role' => 'auctioneer',
                'phone' => "083123456$i",
                'email_verified_at' => now(),
            ]);

            Auctioneer::create([
                'user_id' => $user->id,
                'business_name' => "Test Auctions $i",
                'slug' => "test-auctions-$i",
                'phone' => "083123456$i",
                'whatsapp_number' => "2783123456$i",
                'city' => $i == 1 ? 'Cape Town' : 'Durban',
                'province' => $i == 1 ? 'Western Cape' : 'KwaZulu-Natal',
                'is_activated' => false,
                'credit_balance' => 0,
            ]);
        }

        echo "✓ Created 2 pending auctioneers (pending1@test.com - pending2@test.com / password)\n";

        // Create Live Auction
        $liveAuction = Auction::create([
            'auctioneer_id' => $activatedAuctioneer->id,
            'title' => 'Live Antique Auction',
            'slug' => 'live-antique-auction',
            'description' => 'Featuring rare antiques, collectibles, and vintage items from estate collections.',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => 'live',
            'deposit_type' => 'none',
        ]);

        echo "✓ Created live auction: {$liveAuction->title}\n";

        // Create lots for live auction
        $lotTitles = [
            'Victorian Pocket Watch',
            'Antique Oak Desk',
            'Vintage Wine Collection',
            'Persian Rug',
            'Art Deco Lamp',
        ];

        foreach ($lotTitles as $index => $title) {
            $lot = Lot::create([
                'event_id' => $liveAuction->id,
                'lot_number' => $index + 1,
                'title' => $title,
                'description' => "Beautiful $title in excellent condition. A rare find for collectors.",
                'starting_bid' => 100 + ($index * 50),
                'current_bid' => 100 + ($index * 50) + (rand(1, 5) * 10),
                'increment' => 10,
                'image_tier' => ['basic', 'pro', 'premium'][rand(0, 2)],
                'status' => 'live',
                'start_time' => now(),
                'end_time' => now()->addMinutes(30 + ($index * 0.5)), // 30 seconds apart
                'total_bids' => rand(2, 8),
            ]);

            // Create some bids for this lot
            $numBids = rand(2, 5);
            $currentBid = $lot->starting_bid;

            for ($b = 0; $b < $numBids; $b++) {
                $currentBid += $lot->increment;
                Bid::create([
                    'lot_id' => $lot->id,
                    'user_id' => $bidders[rand(0, 4)]->id,
                    'amount' => $currentBid,
                    'placed_at' => now()->subMinutes(rand(1, 30)),
                ]);
            }

            // Update lot with final bid
            $lot->update([
                'current_bid' => $currentBid,
                'winning_bidder_id' => $bidders[rand(0, 4)]->id,
            ]);
        }

        echo "✓ Created 5 live lots with bids\n";

        // Create Upcoming Auction
        $upcomingAuction = Auction::create([
            'auctioneer_id' => $activatedAuctioneer->id,
            'title' => 'Monthly Art Auction',
            'slug' => 'monthly-art-auction',
            'description' => 'Original paintings, sculptures, and prints from local and international artists.',
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(4),
            'status' => 'upcoming',
            'deposit_type' => 'refundable',
            'deposit_amount' => 500,
        ]);

        echo "✓ Created upcoming auction: {$upcomingAuction->title}\n";

        // Create lots for upcoming auction
        $artTitles = [
            'Abstract Oil Painting',
            'Bronze Sculpture',
            'Watercolor Landscape',
        ];

        foreach ($artTitles as $index => $title) {
            Lot::create([
                'event_id' => $upcomingAuction->id,
                'lot_number' => $index + 1,
                'title' => $title,
                'description' => "Stunning $title by renowned artist.",
                'starting_bid' => 500 + ($index * 200),
                'increment' => 50,
                'image_tier' => 'pro',
                'status' => 'pending',
                'start_time' => $upcomingAuction->start_time,
                'end_time' => $upcomingAuction->end_time->copy()->addSeconds($index * 30),
                'reserve_price' => 800 + ($index * 200),
            ]);
        }

        echo "✓ Created 3 upcoming lots\n";

        echo "\n";
        // Seed Terms & Conditions
        $this->call(TermsVersionSeeder::class);
        echo "✓ Seeded Terms & Conditions v1.0\n";

        echo "═══════════════════════════════════════════════════════\n";
        echo "  Database seeded successfully! 🎉\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "\n";
        echo "Test Login Credentials:\n";
        echo "───────────────────────────────────────────────────────\n";
        echo "Admin:      admin@bidall.co.za     / password\n";
        echo "Auctioneer: auctioneer@test.com    / password\n";
        echo "Bidder:     bidder1@test.com       / password\n";
        echo "            (bidder2-5@test.com also available)\n";
        echo "\n";
        echo "Auctions Created:\n";
        echo "───────────────────────────────────────────────────────\n";
        echo "- Live Antique Auction (5 lots with active bidding)\n";
        echo "- Monthly Art Auction (3 lots, upcoming)\n";
        echo "\n";
        echo "Auctioneer has R1000 credit balance for testing.\n";
        echo "═══════════════════════════════════════════════════════\n";
    }
}
