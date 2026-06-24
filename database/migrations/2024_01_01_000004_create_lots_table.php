<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->integer('lot_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('image_tier', ['basic', 'pro', 'premium'])->default('basic');
            $table->decimal('starting_bid', 10, 2);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('increment', 10, 2);
            $table->decimal('current_bid', 10, 2)->nullable();
            $table->foreignId('winning_bidder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('total_bids')->default(0);
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->timestamp('actual_end_time')->nullable();
            $table->enum('status', ['draft', 'pending', 'live', 'sold', 'unsold'])->default('draft');
            $table->boolean('reserve_met')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('event_id');
            $table->index('lot_number');
            $table->index('status');
            $table->index(['event_id', 'lot_number']);
            $table->index('end_time');
            $table->index('winning_bidder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
