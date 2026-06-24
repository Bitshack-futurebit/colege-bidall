<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    /**
     * Get auctioneer credit balance (for real-time monitoring).
     */
    public function balance()
    {
        $user = auth()->user();

        if (!$user->isAuctioneer() || !$user->auctioneer) {
            return response()->json([
                'success' => false,
                'message' => 'Not an auctioneer',
            ], 403);
        }

        $auctioneer = $user->auctioneer;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $auctioneer->credit_balance,
                'formatted_balance' => formatCurrency($auctioneer->credit_balance),
                'is_low' => $auctioneer->credit_balance < 100,
                'can_create_basic' => $auctioneer->canCreateLot('basic'),
                'can_create_pro' => $auctioneer->canCreateLot('pro'),
                'can_create_premium' => $auctioneer->canCreateLot('premium'),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
