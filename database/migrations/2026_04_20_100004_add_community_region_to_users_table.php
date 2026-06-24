<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('community_region_id')->nullable()->after('is_active')
                ->constrained('community_regions')->nullOnDelete();
            $table->timestamp('community_region_changed_at')->nullable()->after('community_region_id');

            $table->index('community_region_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['community_region_id']);
            $table->dropForeign(['community_region_id']);
            $table->dropColumn(['community_region_id', 'community_region_changed_at']);
        });
    }
};
