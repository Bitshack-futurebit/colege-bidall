<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('escrow_transaction_id')->nullable()->after('payment_completed_at')
                ->constrained('escrow_transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['escrow_transaction_id']);
            $table->dropColumn('escrow_transaction_id');
        });
    }
};
