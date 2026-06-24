<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            // Profile image (personal photo/avatar of the auctioneer)
            $table->string('profile_image')->nullable()->after('logo');

            // Banner image (header/cover image for auctioneer page)
            $table->string('banner_image')->nullable()->after('profile_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn(['profile_image', 'banner_image']);
        });
    }
};
