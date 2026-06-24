<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lots MODIFY COLUMN status ENUM('draft', 'pending', 'live', 'sold', 'unsold', 'pending_confirmation') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Move any pending_confirmation lots back to sold before reverting enum
        DB::table('lots')->where('status', 'pending_confirmation')->update(['status' => 'sold']);
        DB::statement("ALTER TABLE lots MODIFY COLUMN status ENUM('draft', 'pending', 'live', 'sold', 'unsold') NOT NULL DEFAULT 'draft'");
    }
};
