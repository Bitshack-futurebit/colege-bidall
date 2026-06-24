<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('community_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_region_id')
                ->constrained('community_regions')
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedTinyInteger('goes_live_day');
            $table->time('goes_live_time');
            $table->unsignedSmallInteger('duration_hours')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['community_region_id', 'is_active']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('community_schedule_id')
                ->nullable()
                ->after('community_region_id')
                ->constrained('community_schedules')
                ->nullOnDelete();
        });

        // Backfill one schedule per region from existing cadence fields
        $regions = DB::table('community_regions')->get();
        foreach ($regions as $region) {
            $scheduleId = DB::table('community_schedules')->insertGetId([
                'community_region_id' => $region->id,
                'name' => 'Weekly auction',
                'goes_live_day' => (int) ($region->goes_live_day ?? 0),
                'goes_live_time' => $region->goes_live_time ?: '18:00:00',
                'duration_hours' => 3,
                'is_active' => (bool) ($region->is_active ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('events')
                ->where('community_region_id', $region->id)
                ->update(['community_schedule_id' => $scheduleId]);
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['community_schedule_id']);
            $table->dropColumn('community_schedule_id');
        });

        Schema::dropIfExists('community_schedules');
    }
};
