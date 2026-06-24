<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->decimal('dutch_drop_amount', 10, 2)->nullable()->after('dutch_floor_price');
            $table->integer('dutch_drop_interval')->nullable()->after('dutch_drop_amount'); // seconds
        });

        // Copy auction-level defaults to existing lots
        DB::statement("
            UPDATE lots
            JOIN events ON lots.event_id = events.id
            SET lots.dutch_drop_amount = events.dutch_drop_amount,
                lots.dutch_drop_interval = events.dutch_drop_interval
            WHERE events.auction_type = 'dutch'
              AND lots.dutch_start_price IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['dutch_drop_amount', 'dutch_drop_interval']);
        });
    }
};
