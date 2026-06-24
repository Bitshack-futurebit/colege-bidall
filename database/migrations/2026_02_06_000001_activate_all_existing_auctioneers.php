<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Auctioneer;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Activate all existing auctioneers to support the new pay-as-you-go credit system.
     * No longer require separate activation - auctioneers are active from registration.
     */
    public function up(): void
    {
        // Activate all auctioneers and set activation date if not already set
        Auctioneer::whereNull('activated_at')
            ->orWhere('is_activated', false)
            ->update([
                'is_activated' => true,
                'activated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this is a one-way migration for business logic change
    }
};
