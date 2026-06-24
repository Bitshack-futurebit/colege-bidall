<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop any partial table left over from a previous failed run.
        Schema::dropIfExists('community_commission_ledger');

        Schema::create('community_commission_ledger', function (Blueprint $table) {
            $table->id();

            // What this commission relates to
            $table->foreignId('lot_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('community_region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Amounts (all rand, 2 dp)
            $table->decimal('hammer_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2); // 5% of hammer

            // Ladder accounting
            $table->string('period_key', 7); // YYYY-MM
            $table->decimal('tier1_portion', 10, 2)->default(0);
            $table->decimal('tier2_portion', 10, 2)->default(0);
            $table->decimal('platform_share', 10, 2);
            $table->decimal('agent_share', 10, 2)->default(0);

            // Agent attribution at moment of accrual (frozen — agent reassignment doesn't retro)
            $table->foreignId('agent_id_at_accrual')->nullable()
                  ->constrained('agents')->nullOnDelete();

            // Lifecycle
            $table->enum('status', ['accrued', 'seller_paid', 'agent_paid', 'voided'])
                  ->default('accrued');
            $table->timestamp('accrued_at');
            $table->timestamp('seller_paid_at')->nullable();
            $table->timestamp('agent_paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('seller_payment_transaction_id')->nullable();
            $table->foreignId('agent_payout_id')->nullable();

            $table->timestamps();

            $table->index(['community_region_id', 'period_key', 'status'], 'ccl_region_period_status_idx');
            $table->index(['seller_user_id', 'status'], 'ccl_seller_status_idx');
            $table->index(['agent_id_at_accrual', 'status'], 'ccl_agent_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_commission_ledger');
    }
};
