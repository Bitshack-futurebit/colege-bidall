<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add supplier_id FK (nullable — not every lot has a supplier)
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('event_id')
                ->constrained('suppliers')->nullOnDelete();
        });

        // Step 2: backfill — for each lot with denormalized supplier data,
        // find-or-create a supplier row for its auctioneer and link.
        if (Schema::hasColumn('lots', 'supplier_name')) {
            $lots = DB::table('lots')
                ->join('events', 'events.id', '=', 'lots.event_id')
                ->whereNotNull('lots.supplier_name')
                ->select(
                    'lots.id as lot_id',
                    'lots.supplier_name',
                    'lots.supplier_id_number',
                    'lots.supplier_address',
                    'lots.supplier_id_document',
                    'events.auctioneer_id'
                )
                ->get();

            foreach ($lots as $row) {
                // Try to match an existing supplier for this auctioneer by id_number (strongest signal),
                // otherwise by exact name. If neither matches, create fresh.
                $supplier = null;
                if ($row->supplier_id_number) {
                    $supplier = DB::table('suppliers')
                        ->where('auctioneer_id', $row->auctioneer_id)
                        ->where('id_number', $row->supplier_id_number)
                        ->first();
                }
                if (!$supplier && $row->supplier_name) {
                    $supplier = DB::table('suppliers')
                        ->where('auctioneer_id', $row->auctioneer_id)
                        ->where('name', $row->supplier_name)
                        ->first();
                }

                if (!$supplier) {
                    $supplierId = DB::table('suppliers')->insertGetId([
                        'uid' => self::generateUid(),
                        'auctioneer_id' => $row->auctioneer_id,
                        'name' => $row->supplier_name,
                        'id_number' => $row->supplier_id_number,
                        'address' => $row->supplier_address,
                        'id_document' => $row->supplier_id_document,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $supplierId = $supplier->id;
                }

                DB::table('lots')->where('id', $row->lot_id)->update(['supplier_id' => $supplierId]);
            }
        }

        // Step 3: drop the denormalized columns — suppliers table is now the source of truth.
        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'supplier_name')) {
                $table->dropColumn([
                    'supplier_name',
                    'supplier_id_number',
                    'supplier_address',
                    'supplier_id_document',
                ]);
            }
        });
    }

    public function down(): void
    {
        // Re-add denormalized columns and copy data back before dropping the FK.
        Schema::table('lots', function (Blueprint $table) {
            $table->string('supplier_name', 255)->nullable();
            $table->string('supplier_id_number', 50)->nullable();
            $table->text('supplier_address')->nullable();
            $table->string('supplier_id_document', 255)->nullable();
        });

        $lots = DB::table('lots')
            ->join('suppliers', 'suppliers.id', '=', 'lots.supplier_id')
            ->whereNotNull('lots.supplier_id')
            ->select(
                'lots.id as lot_id',
                'suppliers.name',
                'suppliers.id_number',
                'suppliers.address',
                'suppliers.id_document'
            )
            ->get();

        foreach ($lots as $row) {
            DB::table('lots')->where('id', $row->lot_id)->update([
                'supplier_name' => $row->name,
                'supplier_id_number' => $row->id_number,
                'supplier_address' => $row->address,
                'supplier_id_document' => $row->id_document,
            ]);
        }

        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }

    /**
     * Generate a short, readable, collision-resistant UID. Matches Supplier::generateUid().
     */
    private static function generateUid(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous I,O,0,1
        do {
            $random = '';
            for ($i = 0; $i < 6; $i++) {
                $random .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $uid = 'SUP-' . $random;
        } while (DB::table('suppliers')->where('uid', $uid)->exists());

        return $uid;
    }
};
