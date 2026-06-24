<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Search this auctioneer's suppliers by name, UID, or ID number.
     *
     * Intentionally scoped to the authenticated user's auctioneer — an
     * auctioneer must never see another auctioneer's supplier list.
     * ID numbers are never returned in full; only the last 4 digits are
     * exposed to keep the ID document sensitive even if the search
     * endpoint is probed.
     */
    public function search(Request $request): JsonResponse
    {
        $auctioneer = $request->user()?->auctioneer;
        if (!$auctioneer) {
            return response()->json(['results' => []]);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . $q . '%';

        $suppliers = Supplier::where('auctioneer_id', $auctioneer->id)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('uid', 'like', $like)
                    ->orWhere('id_number', 'like', $like);
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'uid', 'name', 'id_number']);

        $results = $suppliers->map(fn ($s) => [
            'id' => $s->id,
            'uid' => $s->uid,
            'name' => $s->name,
            // Only expose the last 4 chars of the ID so bidders/attackers
            // probing the endpoint can never reconstruct a full ID number.
            'id_number_last4' => $s->id_number ? mb_substr($s->id_number, -4) : null,
        ])->values();

        return response()->json(['results' => $results]);
    }
}
