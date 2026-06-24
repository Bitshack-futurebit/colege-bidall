<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('live_phase', 20)->nullable()->after('status');
            $table->timestamp('live_phase_ends_at')->nullable()->after('live_phase');
            $table->timestamp('live_opens_at')->nullable()->after('live_phase_ends_at');
            $table->timestamp('live_last_bid_at')->nullable()->after('live_opens_at');

            $table->index(['live_phase', 'live_phase_ends_at'], 'lots_live_phase_tick_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex('lots_live_phase_tick_idx');
            $table->dropColumn(['live_phase', 'live_phase_ends_at', 'live_opens_at', 'live_last_bid_at']);
        });
    }
};
