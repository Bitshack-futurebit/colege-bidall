<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->unsignedInteger('dutch_duration')->nullable()->after('dutch_drop_strategy')
                ->comment('Target lot duration in seconds, set by auctioneer');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('dutch_duration');
        });
    }
};
