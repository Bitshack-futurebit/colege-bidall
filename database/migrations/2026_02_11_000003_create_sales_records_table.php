<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auctioneer_id')->constrained()->onDelete('cascade');
            $table->foreignId('lot_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions'); // Link to payment

            // Sale breakdown
            $table->decimal('sale_price', 10, 2); // Winning bid amount
            $table->decimal('payment_gateway_fee', 10, 2)->default(0); // PayFast fee
            $table->decimal('platform_commission', 10, 2)->default(0); // 1% commission
            $table->decimal('net_to_auctioneer', 10, 2); // What auctioneer gets

            // Metadata
            $table->string('payment_gateway')->default('payfast'); // payfast, btcpay, etc
            $table->decimal('commission_rate', 5, 2)->default(1.00); // % rate at time of sale
            $table->timestamp('sale_date');
            $table->timestamp('paid_date')->nullable(); // When bidder paid

            $table->timestamps();

            $table->index('auctioneer_id');
            $table->index('lot_id');
            $table->index('sale_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_records');
    }
};
