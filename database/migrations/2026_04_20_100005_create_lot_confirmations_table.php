<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('community_region_id')->nullable()->constrained('community_regions')->nullOnDelete();
            $table->decimal('winning_bid', 10, 2);
            $table->enum('action', ['confirmed', 'declined', 'auto_confirmed'])->index();
            $table->string('reason', 500)->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['seller_user_id', 'acted_at']);
            $table->index(['community_region_id', 'action', 'acted_at'], 'lot_conf_region_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_confirmations');
    }
};
