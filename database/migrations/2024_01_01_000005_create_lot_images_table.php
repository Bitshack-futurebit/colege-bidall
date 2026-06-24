<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->onDelete('cascade');
            $table->string('original_path')->nullable();
            $table->string('optimized_path');
            $table->string('thumbnail_path');
            $table->integer('order')->default(0);
            $table->integer('original_size')->nullable(); // bytes
            $table->integer('optimized_size')->nullable(); // bytes
            $table->integer('thumbnail_size')->nullable(); // bytes
            $table->boolean('is_primary')->default(false);
            $table->timestamp('scheduled_deletion_at')->nullable();
            $table->timestamps();

            $table->index('lot_id');
            $table->index('scheduled_deletion_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_images');
    }
};
