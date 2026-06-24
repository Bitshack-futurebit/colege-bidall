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
            // Allow admin to mark accounts as free (no lot fees)
            $table->boolean('is_free_account')->default(false)->after('is_activated');

            // Allow admin to set custom flat fee per lot (overrides tiered pricing)
            // If null, use standard tiered pricing (R1/R5/R20)
            $table->decimal('custom_lot_fee', 8, 2)->nullable()->after('is_free_account');

            // Track who set these (for audit trail)
            $table->text('pricing_notes')->nullable()->after('custom_lot_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn(['is_free_account', 'custom_lot_fee', 'pricing_notes']);
        });
    }
};
