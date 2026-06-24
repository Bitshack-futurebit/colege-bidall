<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK + column from lots FIRST (before dropping the referenced table)
        if (Schema::hasColumn('lots', 'escrow_transaction_id')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->dropForeign(['escrow_transaction_id']);
                $table->dropColumn('escrow_transaction_id');
            });
        }

        // Now safe to drop escrow tables (child tables first)
        Schema::dropIfExists('escrow_messages');
        Schema::dropIfExists('escrow_payouts');
        Schema::dropIfExists('escrow_settings');
        Schema::dropIfExists('escrow_transactions');
    }

    public function down(): void
    {
        // Not reversible — escrow has been permanently removed
    }
};
