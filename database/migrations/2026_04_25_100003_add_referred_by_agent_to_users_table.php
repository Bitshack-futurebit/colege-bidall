<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by_agent_id')
                  ->nullable()
                  ->after('community_region_id')
                  ->constrained('agents')
                  ->nullOnDelete();
            $table->boolean('bidding_disabled')->default(false)->after('is_active');
            $table->string('bidding_disabled_reason')->nullable()->after('bidding_disabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_agent_id']);
            $table->dropColumn(['referred_by_agent_id', 'bidding_disabled', 'bidding_disabled_reason']);
        });
    }
};
