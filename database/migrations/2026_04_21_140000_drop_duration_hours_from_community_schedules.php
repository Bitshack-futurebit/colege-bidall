<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_schedules', function (Blueprint $table) {
            $table->dropColumn('duration_hours');
        });
    }

    public function down(): void
    {
        Schema::table('community_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_hours')->default(3)->after('goes_live_time');
        });
    }
};
