<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // notification_reads: speed up "unread for user" subquery (polled every 30s)
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->index('user_id', 'notification_reads_user_id_index');
        });

        // push_notifications: speed up audience-based lookups (bell polling)
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->index(['audience', 'auctioneer_id'], 'push_notifications_audience_auctioneer_index');
        });

        // auctioneer_followers: speed up follower lookups in notification scope
        if (!$this->hasIndex('auctioneer_followers', 'auctioneer_followers_user_id_index')) {
            Schema::table('auctioneer_followers', function (Blueprint $table) {
                $table->index('user_id', 'auctioneer_followers_user_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->dropIndex('notification_reads_user_id_index');
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropIndex('push_notifications_audience_auctioneer_index');
        });

        if ($this->hasIndex('auctioneer_followers', 'auctioneer_followers_user_id_index')) {
            Schema::table('auctioneer_followers', function (Blueprint $table) {
                $table->dropIndex('auctioneer_followers_user_id_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
