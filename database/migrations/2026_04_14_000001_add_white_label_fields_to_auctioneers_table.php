<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->boolean('white_label_enabled')->default(false)->after('payfast_sandbox');
            $table->string('brand_primary_color', 7)->nullable()->after('white_label_enabled');
            $table->string('brand_secondary_color', 7)->nullable()->after('brand_primary_color');
            $table->string('brand_favicon')->nullable()->after('brand_secondary_color');
            $table->string('brand_hero_text', 500)->nullable()->after('brand_favicon');
        });
    }

    public function down(): void
    {
        Schema::table('auctioneers', function (Blueprint $table) {
            $table->dropColumn([
                'white_label_enabled',
                'brand_primary_color',
                'brand_secondary_color',
                'brand_favicon',
                'brand_hero_text',
            ]);
        });
    }
};
