<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            // Current payout balance (what platform owes auctioneer)
            $table->decimal('payout_balance', 10, 2)->default(0)->after('credit_balance');

            // Lifetime statistics for reporting
            $table->decimal('total_sales', 10, 2)->default(0)->after('payout_balance');
            $table->decimal('total_fees_paid', 10, 2)->default(0)->after('total_sales');
            $table->decimal('total_commissions_paid', 10, 2)->default(0)->after('total_fees_paid');
            $table->decimal('total_payouts_received', 10, 2)->default(0)->after('total_commissions_paid');
        });
    }

    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn([
                'payout_balance',
                'total_sales',
                'total_fees_paid',
                'total_commissions_paid',
                'total_payouts_received'
            ]);
        });
    }
};
