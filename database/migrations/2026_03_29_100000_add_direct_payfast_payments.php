<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->string('payfast_merchant_id', 50)->nullable()->after('pricing_notes');
            $table->string('payfast_merchant_key', 50)->nullable()->after('payfast_merchant_id');
            $table->text('payfast_passphrase')->nullable()->after('payfast_merchant_key');
            $table->boolean('payfast_sandbox')->default(false)->after('payfast_passphrase');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('enable_online_payment')->default(false)->after('dutch_lot_gap');
        });
    }

    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn(['payfast_merchant_id', 'payfast_merchant_key', 'payfast_passphrase', 'payfast_sandbox']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('enable_online_payment');
        });
    }
};
