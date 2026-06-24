<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // null = system message
            $table->enum('type', ['message', 'status_change', 'system'])->default('message');
            $table->text('content');
            $table->timestamps();

            $table->index('escrow_transaction_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_messages');
    }
};
