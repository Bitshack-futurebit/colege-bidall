<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->string('free_relist_reset')->nullable()->after('pricing_notes');
            $table->timestamp('free_relist_last_reset_at')->nullable()->after('free_relist_reset');
        });
    }

    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn(['free_relist_reset', 'free_relist_last_reset_at']);
        });
    }
};
