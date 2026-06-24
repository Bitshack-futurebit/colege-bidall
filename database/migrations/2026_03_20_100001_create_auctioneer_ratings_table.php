<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctioneer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('auctioneer_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned();
            $table->timestamps();
            $table->unique(['user_id', 'auctioneer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctioneer_ratings');
    }
};
