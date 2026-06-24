<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'auctioneer_id',
        'amount',
        'status',
        'method',
        'reference',
        'bank_name',
        'account_holder',
        'account_number',
        'branch_code',
        'processed_by',
        'requested_at',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function auctioneer()
    {
        return $this->belongsTo(Auctioneer::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Status checks
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Process payout
     */
    public function markAsCompleted(User $admin, string $reference = null, string $notes = null)
    {
        $this->update([
            'status' => 'completed',
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'reference' => $reference ?? $this->reference,
            'notes' => $notes,
        ]);

        // Deduct from unified credit_balance and track lifetime payouts
        $this->auctioneer->deductCredits(
            $this->amount,
            'payout',
            null,
            'Payout to bank account: ' . $this->bank_name . ' ' . ($reference ?? '')
        );
        $this->auctioneer->increment('total_payouts_received', $this->amount);

        // Log activity
        ActivityLog::log(
            'payout_completed',
            "Payout of " . formatCurrency($this->amount) . " completed",
            $this->auctioneer
        );
    }
}
