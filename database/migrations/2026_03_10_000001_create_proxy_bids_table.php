<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add allow_proxy_bidding to events table
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('allow_proxy_bidding')->default(false)->after('requires_registration');
        });

        // Create proxy_bids table
        Schema::create('proxy_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('max_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_bids');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('allow_proxy_bidding');
        });
    }
};
