<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_regions', function (Blueprint $table) {
            $table->unsignedInteger('listing_limit_per_week')
                  ->nullable()
                  ->after('min_bidders_for_viability')
                  ->comment('Per-seller weekly listing cap. Null = use config default.');
        });
    }

    public function down(): void
    {
        Schema::table('community_regions', function (Blueprint $table) {
            $table->dropColumn('listing_limit_per_week');
        });
    }
};
