<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->boolean('subject_to_confirmation')->default(false)->after('withdrawal_reason');
            $table->text('confirmation_message')->nullable()->after('subject_to_confirmation');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['subject_to_confirmation', 'confirmation_message']);
        });
    }
};
