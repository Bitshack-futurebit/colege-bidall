<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'auctioneer_id',
        'lot_id',
        'transaction_id',
        'sale_price',
        'payment_gateway_fee',
        'platform_commission',
        'net_to_auctioneer',
        'payment_gateway',
        'commission_rate',
        'sale_date',
        'paid_date',
        'funds_available_at',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'payment_gateway_fee' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'net_to_auctioneer' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'sale_date' => 'datetime',
        'paid_date' => 'datetime',
        'funds_available_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function auctioneer()
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Create sales record from lot payment
     */
    public static function createFromLotPayment(Lot $lot, Transaction $transaction)
    {
        // Get PayFast fee from transaction data
        $paymentData = $transaction->payment_data ?? [];
        $gatewayFee = $paymentData['gateway_fee'] ?? 0;

        // Calculate breakdown
        $salePrice = $lot->current_bid;
        $platformCommission = $salePrice * 0.01; // 1%
        $netToAuctioneer = $salePrice - $gatewayFee - $platformCommission;

        // Calculate when funds will be available (PayFast 48-hour hold)
        $fundsHoldHours = (int) config('platform.payout.funds_hold_hours', 48);
        $fundsAvailableAt = now()->addHours($fundsHoldHours);

        // Create record
        $salesRecord = self::create([
            'auctioneer_id' => $lot->auction->auctioneer_id,
            'lot_id' => $lot->id,
            'transaction_id' => $transaction->id,
            'sale_price' => $salePrice,
            'payment_gateway_fee' => $gatewayFee,
            'platform_commission' => $platformCommission,
            'net_to_auctioneer' => $netToAuctioneer,
            'payment_gateway' => $transaction->payment_method,
            'commission_rate' => 1.00,
            'sale_date' => $lot->updated_at,
            'paid_date' => now(),
            'funds_available_at' => $fundsAvailableAt,
        ]);

        // Update auctioneer balances — sale income flows into unified credit_balance
        $auctioneer = $lot->auction->auctioneer;
        $auctioneer->addCredits(
            $netToAuctioneer,
            'sale_income',
            'Sale income: ' . $lot->title . ' (after gateway fee & 1% commission)'
        );
        $auctioneer->increment('total_sales', $salePrice);
        $auctioneer->increment('total_fees_paid', $gatewayFee);
        $auctioneer->increment('total_commissions_paid', $platformCommission);

        return $salesRecord;
    }

}
