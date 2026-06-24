<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('sender_type', ['auctioneer', 'admin']);
            $table->unsignedBigInteger('sender_id');
            $table->foreignId('auctioneer_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('audience', ['followers', 'all_users', 'all_bidders', 'all_auctioneers', 'all_admins']);
            $table->string('title', 100);
            $table->text('body');
            $table->string('url', 255)->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
