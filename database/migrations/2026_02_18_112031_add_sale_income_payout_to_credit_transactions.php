<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL requires re-specifying all ENUM values when adding new ones
        DB::statement("ALTER TABLE credit_transactions MODIFY COLUMN type ENUM(
            'purchase',
            'lot_creation',
            'lot_live',
            'lot_close',
            'refund',
            'adjustment',
            'sale_income',
            'payout'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE credit_transactions MODIFY COLUMN type ENUM(
            'purchase',
            'lot_creation',
            'lot_live',
            'lot_close',
            'refund',
            'adjustment'
        ) NOT NULL");
    }
};
