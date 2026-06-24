<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_community')->default(false)->after('auctioneer_id');
            $table->foreignId('community_region_id')->nullable()->after('is_community')
                ->constrained('community_regions')->nullOnDelete();
            $table->boolean('pilot_mode')->default(false)->after('community_region_id');
            $table->timestamp('lineup_locks_at')->nullable()->after('end_time');
            $table->timestamp('goes_live_at')->nullable()->after('lineup_locks_at');

            $table->index(['is_community', 'community_region_id', 'status'], 'events_community_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_community_active_idx');
            $table->dropForeign(['community_region_id']);
            $table->dropColumn([
                'is_community',
                'community_region_id',
                'pilot_mode',
                'lineup_locks_at',
                'goes_live_at',
            ]);
        });
    }
};
