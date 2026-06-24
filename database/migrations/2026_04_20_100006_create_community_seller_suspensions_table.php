<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_seller_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('community_region_id')->nullable()->constrained('community_regions')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable(); // null = permanent
            $table->string('reason', 500);
            $table->boolean('is_permanent')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
            $table->index(['user_id', 'is_permanent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_seller_suspensions');
    }
};
