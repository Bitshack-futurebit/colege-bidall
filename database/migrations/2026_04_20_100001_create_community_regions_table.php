<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('metro_area')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('pilot_mode')->default(true);
            $table->unsignedInteger('bidder_threshold')->default(50);
            $table->unsignedInteger('min_lots_for_viability')->default(5);
            $table->unsignedInteger('min_bidders_for_viability')->default(20);
            $table->unsignedTinyInteger('goes_live_day')->default(0); // 0 = Sunday, 6 = Saturday
            $table->time('goes_live_time')->default('18:00:00');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('slug');
        });

        DB::table('community_regions')->insert([
            'name' => 'Lower South Coast',
            'slug' => 'lower-south-coast',
            'metro_area' => 'KZN South',
            'description' => 'Port Shepstone, Margate, Shelly Beach, Hibberdene and surrounds',
            'is_active' => true,
            'pilot_mode' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('community_regions');
    }
};
