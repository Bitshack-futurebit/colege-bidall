<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'auctioneer_id',
        'name',
        'id_number',
        'address',
        'id_document',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (empty($supplier->uid)) {
                $supplier->uid = self::generateUid();
            }
        });
    }

    public function auctioneer(): BelongsTo
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    /**
     * Generate a short, readable UID: SUP-XXXXXX (6 base-32 chars, no ambiguous I/O/0/1).
     * Collisions virtually impossible at ~1B combinations, but we still retry on clash.
     */
    public static function generateUid(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $random = '';
            for ($i = 0; $i < 6; $i++) {
                $random .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $uid = 'SUP-' . $random;
        } while (self::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Find or create a supplier for the given auctioneer based on best-match signal:
     * prefer id_number match, fall back to exact name match, otherwise create new.
     * Updates the matched record with any newly-provided fields.
     */
    public static function findOrCreateForAuctioneer(
        int $auctioneerId,
        ?string $name,
        ?string $idNumber,
        ?string $address,
        ?string $idDocument
    ): ?self {
        // If every field is empty, no supplier to record.
        if (!$name && !$idNumber && !$address && !$idDocument) {
            return null;
        }

        $query = self::where('auctioneer_id', $auctioneerId);

        $supplier = null;
        if ($idNumber) {
            $supplier = (clone $query)->where('id_number', $idNumber)->first();
        }
        if (!$supplier && $name) {
            $supplier = (clone $query)->where('name', $name)->first();
        }

        if ($supplier) {
            // Update only fields the caller explicitly provided (non-null), so partial
            // edits don't wipe existing supplier data.
            $updates = array_filter([
                'name' => $name,
                'id_number' => $idNumber,
                'address' => $address,
                'id_document' => $idDocument,
            ], fn ($v) => $v !== null);

            if (!empty($updates)) {
                $supplier->update($updates);
            }
            return $supplier;
        }

        return self::create([
            'auctioneer_id' => $auctioneerId,
            'name' => $name,
            'id_number' => $idNumber,
            'address' => $address,
            'id_document' => $idDocument,
        ]);
    }
}
