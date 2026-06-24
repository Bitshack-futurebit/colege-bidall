<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add Dutch auction columns to events (auctions) table
        Schema::table('events', function (Blueprint $table) {
            $table->string('auction_type', 10)->default('english')->after('status');
            $table->string('dutch_lot_mode', 15)->nullable()->after('auction_type'); // simultaneous, sequential
            $table->decimal('dutch_drop_amount', 10, 2)->nullable()->after('dutch_lot_mode');
            $table->integer('dutch_drop_interval')->nullable()->after('dutch_drop_amount'); // seconds
            $table->integer('dutch_lot_duration')->nullable()->after('dutch_drop_interval'); // seconds (sequential only)
            $table->integer('dutch_lot_gap')->nullable()->after('dutch_lot_duration'); // seconds (sequential only)
        });

        // Add Dutch lot columns to lots table
        Schema::table('lots', function (Blueprint $table) {
            $table->decimal('dutch_start_price', 10, 2)->nullable()->after('reserve_price');
            $table->decimal('dutch_floor_price', 10, 2)->nullable()->after('dutch_start_price');
            $table->integer('quantity')->default(1)->after('dutch_floor_price');
            $table->integer('quantity_sold')->default(0)->after('quantity');
            $table->datetime('dutch_start_time')->nullable()->after('quantity_sold');
            $table->datetime('dutch_end_time')->nullable()->after('dutch_start_time');
        });

        // Add Dutch buy columns to bids table
        Schema::table('bids', function (Blueprint $table) {
            $table->boolean('is_dutch_buy')->default(false)->after('is_proxy');
            $table->integer('quantity_bought')->default(1)->after('is_dutch_buy');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'auction_type', 'dutch_lot_mode', 'dutch_drop_amount',
                'dutch_drop_interval', 'dutch_lot_duration', 'dutch_lot_gap',
            ]);
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn([
                'dutch_start_price', 'dutch_floor_price', 'quantity',
                'quantity_sold', 'dutch_start_time', 'dutch_end_time',
            ]);
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['is_dutch_buy', 'quantity_bought']);
        });
    }
};
