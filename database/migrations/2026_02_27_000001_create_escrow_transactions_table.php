<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['auction', 'standalone']);
            $table->string('reference', 20)->unique(); // ESC-XXXXXX
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('seller_email')->nullable(); // for standalone invites before seller registers
            $table->foreignId('lot_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();

            // Amounts
            $table->decimal('amount', 10, 2); // principal deal amount
            $table->decimal('escrow_fee', 10, 2); // normal fee charged to buyer
            $table->decimal('dispute_fee_amount', 10, 2)->default(0); // higher fee if disputed
            $table->decimal('total_amount', 10, 2); // amount + escrow_fee (what buyer pays)
            $table->decimal('escrow_fee_percent', 5, 2); // rate at time of creation
            $table->decimal('dispute_fee_percent', 5, 2); // rate at time of creation

            // Status
            $table->enum('status', [
                'pending',           // created, awaiting acceptance (standalone)
                'accepted',          // other party accepted (standalone) / auto for auction
                'funded',            // buyer paid via PayFast
                'shipped',           // seller confirmed shipment
                'delivered',         // buyer confirmed receipt
                'released',          // funds released to seller
                'disputed',          // buyer raised dispute
                'refunded',          // admin refunded to buyer after dispute
                'cancelled',         // cancelled before funding
                'expired',           // invitation expired
            ])->default('pending');

            // Shipping
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Release
            $table->timestamp('release_due_at')->nullable(); // shipped_at + auto_release_days
            $table->timestamp('released_at')->nullable();

            // Dispute
            $table->text('dispute_reason')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('resolution', ['release_to_seller', 'refund_to_buyer'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Payment
            $table->foreignId('funding_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->timestamp('funded_at')->nullable();

            // Invitation (standalone)
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->enum('initiated_by', ['buyer', 'seller'])->nullable(); // who created the deal

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('lot_id');
            $table->index('status');
            $table->index('type');
            $table->index('reference');
            $table->index('invite_token');
            $table->index('release_due_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
