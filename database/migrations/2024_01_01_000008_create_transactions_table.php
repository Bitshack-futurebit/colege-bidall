<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('auctioneer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lot_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', [
                'activation_fee',
                'credit_purchase',
                'deposit',
                'lot_payment',
                'deposit_refund',
                'platform_fee'
            ]);
            $table->decimal('amount', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->default('payfast');
            $table->string('payment_id')->nullable();
            $table->text('payment_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('auctioneer_id');
            $table->index('event_id');
            $table->index('lot_id');
            $table->index('type');
            $table->index('status');
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
