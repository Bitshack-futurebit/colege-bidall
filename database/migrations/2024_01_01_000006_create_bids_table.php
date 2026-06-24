<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_winning')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index('lot_id');
            $table->index('user_id');
            $table->index(['lot_id', 'amount']);
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
