<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('activation_fee', 'credit_purchase', 'deposit', 'lot_payment', 'deposit_refund', 'platform_fee', 'escrow_payment') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('activation_fee', 'credit_purchase', 'deposit', 'lot_payment', 'deposit_refund', 'platform_fee') NOT NULL");
    }
};
