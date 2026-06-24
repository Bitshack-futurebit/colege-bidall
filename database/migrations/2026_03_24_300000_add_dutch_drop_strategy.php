<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add strategy to lots (per-lot setting)
        Schema::table('lots', function (Blueprint $table) {
            $table->string('dutch_drop_strategy', 20)->default('constant')->after('dutch_drop_interval');
        });

        // Add default strategy to events (auction-level default for new lots)
        Schema::table('events', function (Blueprint $table) {
            $table->string('dutch_drop_strategy', 20)->default('constant')->after('dutch_drop_interval');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('dutch_drop_strategy');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('dutch_drop_strategy');
        });
    }
};
