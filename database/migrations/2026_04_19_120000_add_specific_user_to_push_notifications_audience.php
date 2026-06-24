<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE push_notifications MODIFY COLUMN audience ENUM('followers','all_users','all_bidders','all_auctioneers','all_admins','specific_user') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE push_notifications MODIFY COLUMN audience ENUM('followers','all_users','all_bidders','all_auctioneers','all_admins') NOT NULL");
    }
};
