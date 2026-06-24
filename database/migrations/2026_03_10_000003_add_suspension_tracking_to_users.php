<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('suspended_by_auctioneer_id')->nullable()->after('is_active')
                ->constrained('auctioneers')->nullOnDelete();
            $table->string('suspension_reason')->nullable()->after('suspended_by_auctioneer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['suspended_by_auctioneer_id']);
            $table->dropColumn(['suspended_by_auctioneer_id', 'suspension_reason']);
        });
    }
};
