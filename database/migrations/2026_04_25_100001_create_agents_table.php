<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Lifecycle
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated'])
                  ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            // Qualification (WhatsApp group proof)
            $table->string('whatsapp_group_name')->nullable();
            $table->unsignedInteger('whatsapp_group_size_claim')->nullable();
            $table->string('whatsapp_group_proof_path')->nullable();

            // Public profile
            $table->string('referral_code', 32)->unique();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('public_whatsapp_number')->nullable();

            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
