<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance optimization: Add indexes to frequently queried columns
     */
    public function up(): void
    {
        // Auctions (events) table indexes
        if (!$this->indexExists('events', 'idx_auctioneer_status')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['auctioneer_id', 'status'], 'idx_auctioneer_status');
            });
        }

        if (!$this->indexExists('events', 'idx_status_dates')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['status', 'start_time', 'end_time'], 'idx_status_dates');
            });
        }

        // Lots table indexes
        if (!$this->indexExists('lots', 'idx_event_status')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->index(['event_id', 'status'], 'idx_event_status');
            });
        }

        if (!$this->indexExists('lots', 'idx_end_time')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->index('end_time', 'idx_end_time');
            });
        }

        if (!$this->indexExists('lots', 'idx_withdrawn')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->index('withdrawn_at', 'idx_withdrawn');
            });
        }

        // Bids table indexes
        if (!$this->indexExists('bids', 'idx_lot_amount')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->index(['lot_id', 'amount'], 'idx_lot_amount');
            });
        }

        if (!$this->indexExists('bids', 'idx_user_created')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_user_created');
            });
        }

        // Users table indexes
        if (!$this->indexExists('users', 'idx_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email', 'idx_email');
            });
        }

        if (!$this->indexExists('users', 'idx_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role', 'idx_role');
            });
        }

        // Sessions table indexes (if using database sessions)
        if (Schema::hasTable('sessions')) {
            if (!$this->indexExists('sessions', 'idx_last_activity')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index('last_activity', 'idx_last_activity');
                });
            }
        }

        // Transactions table indexes
        if (!$this->indexExists('transactions', 'idx_user_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_user_status');
            });
        }

        if (!$this->indexExists('transactions', 'idx_auctioneer_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['auctioneer_id', 'status'], 'idx_auctioneer_status');
            });
        }

        // Credit transactions indexes
        if (!$this->indexExists('credit_transactions', 'idx_auctioneer_type')) {
            Schema::table('credit_transactions', function (Blueprint $table) {
                $table->index(['auctioneer_id', 'type'], 'idx_auctioneer_type');
            });
        }

        // Auctioneer followers indexes
        if (!$this->indexExists('auctioneer_followers', 'idx_user_auctioneer')) {
            Schema::table('auctioneer_followers', function (Blueprint $table) {
                $table->index(['user_id', 'auctioneer_id'], 'idx_user_auctioneer');
            });
        }

        if (!$this->indexExists('auctioneer_followers', 'idx_auctioneer_created')) {
            Schema::table('auctioneer_followers', function (Blueprint $table) {
                $table->index(['auctioneer_id', 'created_at'], 'idx_auctioneer_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('auctioneer_followers', function (Blueprint $table) {
            $table->dropIndex('idx_user_auctioneer');
            $table->dropIndex('idx_auctioneer_created');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_auctioneer_type');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_auctioneer_status');
        });

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('idx_last_activity');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_email');
            $table->dropIndex('idx_role');
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->dropIndex('idx_lot_amount');
            $table->dropIndex('idx_user_created');
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->dropIndex('idx_event_status');
            $table->dropIndex('idx_end_time');
            $table->dropIndex('idx_withdrawn');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_auctioneer_status');
            $table->dropIndex('idx_status_dates');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
