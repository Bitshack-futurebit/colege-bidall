<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            // Global human-readable identifier: SUP-XXXXXX (used in reports, QR codes, URLs).
            $table->string('uid', 20)->unique();
            $table->foreignId('auctioneer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255)->nullable();
            $table->string('id_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('id_document', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Scope supplier lookups per auctioneer — fast lookups + dedup by id_number.
            $table->index(['auctioneer_id', 'name']);
            $table->index(['auctioneer_id', 'id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
