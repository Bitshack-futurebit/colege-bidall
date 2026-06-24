<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('is_paid')
                ->comment('null=awaiting_selection, paid_platform=paid via PayFast, awaiting_collection=arrange with auctioneer, paid_offline=auctioneer confirmed');
            $table->timestamp('payment_method_selected_at')->nullable()->after('payment_status');
            $table->timestamp('payment_completed_at')->nullable()->after('payment_method_selected_at');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method_selected_at', 'payment_completed_at']);
        });
    }
};
