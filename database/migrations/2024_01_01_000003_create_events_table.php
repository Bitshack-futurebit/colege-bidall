<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auctioneer_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'upcoming', 'live', 'ended'])->default('draft');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->enum('deposit_type', ['none', 'refundable', 'non_refundable'])->default('none');
            $table->decimal('buyers_premium_percentage', 5, 2)->default(0);
            $table->timestamp('payment_deadline')->nullable();
            $table->integer('total_lots')->default(0);
            $table->integer('total_bids')->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('auctioneer_id');
            $table->index('slug');
            $table->index('status');
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
