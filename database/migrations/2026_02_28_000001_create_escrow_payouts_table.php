<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_transaction_id')->constrained('escrow_transactions')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->enum('recipient_type', ['buyer', 'seller']);

            // Fee breakdown
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('net_payout', 12, 2);

            // Banking details
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('account_type')->nullable();

            // Status workflow
            $table->enum('status', ['pending_details', 'pending_approval', 'processing', 'completed'])->default('pending_details');
            $table->string('reference')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('escrow_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_payouts');
    }
};
