<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->decimal('custom_tier_basic', 8, 2)->nullable()->after('custom_lot_fee');
            $table->decimal('custom_tier_pro', 8, 2)->nullable()->after('custom_tier_basic');
            $table->decimal('custom_tier_premium', 8, 2)->nullable()->after('custom_tier_pro');
        });
    }

    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn(['custom_tier_basic', 'custom_tier_pro', 'custom_tier_premium']);
        });
    }
};
