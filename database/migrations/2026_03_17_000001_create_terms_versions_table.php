<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('title')->default('Terms & Conditions');
            $table->string('role')->nullable(); // null = all users, 'bidder', 'auctioneer'
            $table->longText('content');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('published_at');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_versions');
    }
};
