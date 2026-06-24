<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_schedules', function (Blueprint $table) {
            $table->enum('frequency', ['weekly', 'monthly'])->default('weekly')->after('name');
            // 1 = first, 2 = second, 3 = third, 4 = fourth, 5 = last. Only used when frequency='monthly'.
            $table->unsignedTinyInteger('monthly_week')->nullable()->after('goes_live_day');
        });
    }

    public function down(): void
    {
        Schema::table('community_schedules', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'monthly_week']);
        });
    }
};
