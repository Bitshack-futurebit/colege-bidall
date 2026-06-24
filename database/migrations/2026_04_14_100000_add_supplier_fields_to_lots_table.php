<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            // Internal supplier record — auctioneer-only, never shown to bidders.
            // All fields optional; tracks who physically supplied the item for the auctioneer's records.
            $table->string('supplier_name', 255)->nullable()->after('withdrawal_reason');
            $table->string('supplier_id_number', 50)->nullable()->after('supplier_name');
            $table->text('supplier_address')->nullable()->after('supplier_id_number');
            $table->string('supplier_id_document', 255)->nullable()->after('supplier_address');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_name',
                'supplier_id_number',
                'supplier_address',
                'supplier_id_document',
            ]);
        });
    }
};
