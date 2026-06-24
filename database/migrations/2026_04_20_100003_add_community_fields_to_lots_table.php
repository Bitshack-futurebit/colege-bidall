<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('seller_user_id')->nullable()->after('event_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('confirmation_expires_at')->nullable()->after('confirmation_message');
            $table->timestamp('declined_at')->nullable()->after('confirmation_expires_at');
            $table->string('decline_reason', 500)->nullable()->after('declined_at');
            $table->foreignId('rolled_from_lot_id')->nullable()->after('decline_reason')
                ->constrained('lots')->nullOnDelete();

            $table->index('seller_user_id');
            $table->index('confirmation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex(['seller_user_id']);
            $table->dropIndex(['confirmation_expires_at']);
            $table->dropForeign(['seller_user_id']);
            $table->dropForeign(['rolled_from_lot_id']);
            $table->dropColumn([
                'seller_user_id',
                'confirmation_expires_at',
                'declined_at',
                'decline_reason',
                'rolled_from_lot_id',
            ]);
        });
    }
};
