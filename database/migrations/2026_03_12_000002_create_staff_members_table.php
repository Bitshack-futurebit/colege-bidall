<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('auctioneer_id')->constrained()->onDelete('cascade');
            $table->enum('staff_role', ['lot_manager', 'auction_manager', 'collections_manager']);
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('auctioneer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
