<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Auctioneer;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One-shot demo housekeeping:
 *  - removes the SpikeLiveTest data (spike.*@college.test + its auctioneer/auctions/lots/bids)
 *  - ensures a super-admin login exists (role=admin sees everything for support)
 */
class CollegeDemoAdmin extends Command
{
    protected $signature = 'college:demo-admin
        {--email=admin@auctioncollege.test : super-admin email}
        {--password=Admin@College2026 : super-admin password}';
    protected $description = 'Remove spike data and ensure a super-admin login';

    public function handle(): int
    {
        // --- 1. Purge SpikeLiveTest data ---
        $ids = User::where('email', 'like', 'spike.%@college.test')->pluck('id');
        if ($ids->isNotEmpty()) {
            $aids = Auctioneer::whereIn('user_id', $ids)->pluck('id');
            $eids = Auction::whereIn('auctioneer_id', $aids)->pluck('id');
            $lids = Lot::whereIn('event_id', $eids)->pluck('id');
            DB::table('bids')->whereIn('lot_id', $lids)->delete();
            DB::table('bids')->whereIn('user_id', $ids)->delete();
            DB::table('lot_images')->whereIn('lot_id', $lids)->delete();
            Lot::whereIn('id', $lids)->forceDelete();
            Auction::whereIn('id', $eids)->forceDelete();
            Auctioneer::whereIn('id', $aids)->forceDelete();
            User::whereIn('id', $ids)->forceDelete();
            $this->info("Removed spike data ({$ids->count()} users, {$aids->count()} auctioneer, {$eids->count()} auctions).");
        } else {
            $this->line('No spike data found.');
        }

        // --- 2. Ensure super-admin ---
        $email = $this->option('email');
        $password = $this->option('password');
        $admin = User::updateOrCreate(['email' => $email], [
            'name' => 'Super Admin',
            'phone' => '0310000000',
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info('🔑 Super-admin ready (full visibility / support):');
        $this->line("   Email:    {$email}");
        $this->line("   Password: {$password}");
        $this->line("   Login at /login → redirects to the admin dashboard.");
        $this->line('   (Change the password after first login for a real deployment.)');
        return Command::SUCCESS;
    }
}
