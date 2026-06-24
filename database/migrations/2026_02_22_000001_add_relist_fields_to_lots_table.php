<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            // Relist tracking
            $table->unsignedBigInteger('relisted_from_lot_id')->nullable()->after('withdrawal_reason')
                  ->comment('Direct parent lot ID (if this lot is a relist of another)');
            $table->unsignedBigInteger('original_lot_id')->nullable()->after('relisted_from_lot_id')
                  ->comment('First lot in the relist chain');
            $table->unsignedInteger('relist_count')->default(0)->after('original_lot_id')
                  ->comment('How many times this item has been relisted (chain depth)');

            // Fee eligibility
            $table->boolean('free_relist_eligible')->default(false)->after('relist_count')
                  ->comment('True when lot closes with 0 bids - qualifies for free relist');
            $table->boolean('is_free_relist')->default(false)->after('free_relist_eligible')
                  ->comment('True if this lot was created as a free relist (no fee charged at go-live)');

            $table->index('relisted_from_lot_id');
            $table->index('original_lot_id');
            $table->index('free_relist_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex(['relisted_from_lot_id']);
            $table->dropIndex(['original_lot_id']);
            $table->dropIndex(['free_relist_eligible']);
            $table->dropColumn([
                'relisted_from_lot_id',
                'original_lot_id',
                'relist_count',
                'free_relist_eligible',
                'is_free_relist',
            ]);
        });
    }
};
