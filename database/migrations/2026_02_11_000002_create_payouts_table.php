<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auctioneer_id')->constrained()->onDelete('cascade');

            // Payout details
            $table->decimal('amount', 10, 2); // Amount paid out
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('method')->nullable(); // eft, paypal, bank_transfer, etc
            $table->string('reference')->nullable(); // Bank reference number

            // Bank details (copied at time of payout for audit trail)
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_code')->nullable();

            // Admin tracking
            $table->foreignId('processed_by')->nullable()->constrained('users'); // Admin who processed
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable(); // Admin notes

            $table->timestamps();

            $table->index('auctioneer_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
