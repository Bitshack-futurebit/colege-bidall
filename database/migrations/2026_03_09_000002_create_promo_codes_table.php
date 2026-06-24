<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_free_account')->default(false);
            $table->decimal('custom_lot_fee', 8, 2)->nullable();
            $table->decimal('custom_tier_basic', 8, 2)->nullable();
            $table->decimal('custom_tier_pro', 8, 2)->nullable();
            $table->decimal('custom_tier_premium', 8, 2)->nullable();
            $table->string('free_relist_reset')->nullable();
            $table->decimal('bonus_credits', 10, 2)->default(0);
            $table->integer('max_uses')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
