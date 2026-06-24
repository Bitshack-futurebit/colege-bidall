<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Lot;
use App\Models\Auctioneer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DirectPaymentController extends Controller
{
    /**
     * Initiate a PayFast payment to the auctioneer's account.
     */
    public function payNow(Request $request)
    {
        $request->validate([
            'lot_ids' => ['required', 'string'],
        ]);

        $lotIds = explode(',', $request->lot_ids);

        $lots = Lot::with('auction.auctioneer')
            ->whereIn('id', $lotIds)
            ->where('winning_bidder_id', auth()->id())
            ->where('status', 'sold')
            ->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhere('payment_status', 'awaiting_collection');
            })
            ->get();

        if ($lots->isEmpty()) {
            return redirect()->back()->with('error', 'No valid lots found for payment.');
        }

        // Verify auction has online payment enabled
        $auction = $lots->first()->auction;
        if (!$auction->hasOnlinePayment()) {
            return redirect()->back()->with('error', 'Online payment is not available for this auction.');
        }

        $auctioneer = $auction->auctioneer;

        // Prevent duplicate pending payments
        $existingPending = Transaction::where('user_id', auth()->id())
            ->where('type', 'direct_lot_payment')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->get()
            ->first(function ($tx) use ($lotIds) {
                $txLotIds = $tx->payment_data['lot_ids'] ?? [];
                sort($txLotIds);
                $sorted = $lotIds;
                sort($sorted);
                return $txLotIds == $sorted;
            });

        if ($existingPending) {
            return redirect()->back()->with('info', 'A payment is already in progress for these lots. Please complete or cancel it before trying again.');
        }

        // Calculate total with buyer's premium
        $total = $lots->sum(fn($lot) => $lot->getTotalAmountDue());

        // Generate unique payment reference
        $paymentId = 'DPF-' . uniqid() . '-' . time();

        // Create transaction record
        Transaction::create([
            'user_id' => auth()->id(),
            'auctioneer_id' => $auctioneer->id,
            'type' => 'direct_lot_payment',
            'amount' => $total,
            'status' => 'pending',
            'payment_method' => 'payfast_direct',
            'payment_id' => $paymentId,
            'payment_data' => [
                'lot_ids' => $lotIds,
                'event_id' => $auction->id,
                'auctioneer_id' => $auctioneer->id,
            ],
        ]);

        // Determine PayFast URL
        $sandbox = $auctioneer->payfast_sandbox;
        $baseUrl = $sandbox
            ? 'https://sandbox.payfast.co.za/eng/process'
            : 'https://www.payfast.co.za/eng/process';

        // Build lot descriptions
        $lotDescriptions = $lots->map(fn($l) => "Lot #{$l->lot_number}: {$l->title}")->implode(', ');
        $itemName = "Payment to {$auctioneer->business_name}";
        $itemDescription = \Illuminate\Support\Str::limit($lotDescriptions, 250);

        // Build PayFast form data
        $data = [
            'merchant_id' => $auctioneer->payfast_merchant_id,
            'merchant_key' => $auctioneer->payfast_merchant_key,
            'return_url' => $this->absoluteUrl(route('direct-payment.return')),
            'cancel_url' => $this->absoluteUrl(route('direct-payment.cancel')),
            'notify_url' => $this->absoluteUrl(route('direct-payment.webhook')),
            'amount' => number_format($total, 2, '.', ''),
            'item_name' => $itemName,
            'item_description' => $itemDescription,
            'custom_int1' => auth()->id(),
            'custom_int2' => $auctioneer->id,
            'custom_str1' => $paymentId,
            'custom_str2' => 'direct_lot_payment',
        ];

        // Generate signature
        $data['signature'] = $this->generateSignature($data, $auctioneer->payfast_passphrase);

        Log::info('Direct PayFast payment created', [
            'payment_id' => $paymentId,
            'amount' => $total,
            'user_id' => auth()->id(),
            'auctioneer_id' => $auctioneer->id,
            'sandbox' => $sandbox,
        ]);

        // Redirect via auto-submitting form (reuse existing view)
        return view('payment.redirect', [
            'payment' => [
                'payment_id' => $paymentId,
                'redirect_url' => $baseUrl,
                'form_data' => $data,
                'method' => 'POST',
                'amount' => $total,
                'type' => 'direct_lot_payment',
            ],
        ]);
    }

    /**
     * Handle PayFast return (success page).
     */
    public function return(Request $request)
    {
        $paymentId = $request->query('custom_str1')
            ?? $request->query('payment_id')
            ?? session('pending_payment');

        if (!$paymentId) {
            return redirect()->route('dashboard.won')
                ->with('info', 'Payment processing. You will see the status update shortly.');
        }

        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            return redirect()->route('dashboard.won')
                ->with('error', 'Payment not found.');
        }

        $displayStatus = match($transaction->status) {
            'completed' => 'success',
            'failed' => 'cancelled',
            default => 'pending',
        };

        return view('payment.return', [
            'transaction' => $transaction,
            'status' => $displayStatus,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'reference' => $transaction->payment_id,
            'relatedId' => $transaction->payment_data['event_id'] ?? null,
        ]);
    }

    /**
     * Handle PayFast cancellation.
     */
    public function cancel(Request $request)
    {
        $paymentId = $request->query('payment_id')
            ?? $request->query('custom_str1')
            ?? session('pending_payment');

        if ($paymentId) {
            $transaction = Transaction::where('payment_id', $paymentId)->first();
            if ($transaction && $transaction->status === 'pending') {
                $transaction->update(['status' => 'failed']);
            }
        }

        return redirect()->route('dashboard.won')
            ->with('warning', 'Payment was cancelled. You can try again anytime.');
    }

    /**
     * Handle PayFast ITN webhook for direct payments.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Direct PayFast webhook received', $payload);

        // Get auctioneer ID from payload to look up their passphrase
        $auctioneerId = $payload['custom_int2'] ?? null;
        if (!$auctioneerId) {
            Log::error('Direct PayFast webhook missing auctioneer ID');
            return response()->json(['error' => 'Missing auctioneer ID'], 400);
        }

        $auctioneer = Auctioneer::find($auctioneerId);
        if (!$auctioneer) {
            Log::error('Direct PayFast webhook auctioneer not found', ['auctioneer_id' => $auctioneerId]);
            return response()->json(['error' => 'Auctioneer not found'], 400);
        }

        // Validate signature (skip in sandbox)
        if (!$auctioneer->payfast_sandbox && !$this->validateSignature($payload, $auctioneer->payfast_passphrase)) {
            Log::error('Direct PayFast webhook signature validation failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Get transaction
        $paymentId = $payload['custom_str1'] ?? null;
        if (!$paymentId) {
            Log::error('Direct PayFast webhook missing payment ID');
            return response()->json(['error' => 'Missing payment ID'], 400);
        }

        $transaction = Transaction::where('payment_id', $paymentId)->first();
        if (!$transaction) {
            Log::error('Direct PayFast webhook transaction not found', ['payment_id' => $paymentId]);
            return response()->json(['error' => 'Transaction not found'], 400);
        }

        // Already processed
        if ($transaction->status === 'completed') {
            Log::info('Direct PayFast webhook skipped — already completed', ['payment_id' => $paymentId]);
            return response()->json(['status' => 'success']);
        }

        $paymentStatus = $payload['payment_status'] ?? 'PENDING';

        if ($paymentStatus === 'COMPLETE') {
            $gatewayFee = abs((float) ($payload['amount_fee'] ?? 0));

            $transaction->update([
                'status' => 'completed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'payfast_payment_id' => $payload['pf_payment_id'] ?? null,
                    'gateway_fee' => $gatewayFee,
                    'amount_gross' => (float) ($payload['amount_gross'] ?? 0),
                    'amount_net' => (float) ($payload['amount_net'] ?? 0),
                    'completed_at' => now()->toISOString(),
                ]),
            ]);

            // Mark lots as paid
            $lotIds = $transaction->payment_data['lot_ids'] ?? [];
            $lots = Lot::whereIn('id', $lotIds)->get();

            foreach ($lots as $lot) {
                $lot->update([
                    'is_paid' => true,
                    'payment_status' => 'paid_platform',
                    'payment_method_selected_at' => $lot->payment_method_selected_at ?? now(),
                    'payment_completed_at' => now(),
                ]);
            }

            // Log activity
            ActivityLog::create([
                'user_id' => $auctioneer->user_id,
                'type' => 'direct_payment_received',
                'description' => 'Online payment received for ' . $lots->count() . ' lot(s) — ' . formatCurrency($transaction->amount),
                'subject_type' => 'App\\Models\\Transaction',
                'subject_id' => $transaction->id,
            ]);

            Log::info('Direct PayFast payment completed', [
                'payment_id' => $paymentId,
                'lot_count' => $lots->count(),
                'auctioneer_id' => $auctioneerId,
            ]);

            return response()->json(['status' => 'success']);
        }

        if ($paymentStatus === 'FAILED' || $paymentStatus === 'CANCELLED') {
            $transaction->update(['status' => 'failed']);

            Log::warning('Direct PayFast payment failed', [
                'payment_id' => $paymentId,
                'status' => $paymentStatus,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Generate PayFast signature for form submission (predefined field order).
     */
    protected function generateSignature(array $data, ?string $passphrase = null): string
    {
        unset($data['signature']);

        $fieldOrder = [
            'merchant_id', 'merchant_key',
            'return_url', 'cancel_url', 'notify_url',
            'name_first', 'name_last', 'email_address', 'cell_number',
            'm_payment_id',
            'amount', 'item_name', 'item_description',
            'custom_int1', 'custom_int2', 'custom_int3', 'custom_int4', 'custom_int5',
            'custom_str1', 'custom_str2', 'custom_str3', 'custom_str4', 'custom_str5',
            'email_confirmation', 'confirmation_address',
            'payment_method',
            'subscription_type', 'billing_date', 'recurring_amount', 'frequency', 'cycles',
        ];

        $pfOutput = '';
        foreach ($fieldOrder as $key) {
            if (array_key_exists($key, $data)) {
                $val = trim((string) $data[$key]);
                $pfOutput .= $key . '=' . urlencode($val) . '&';
            }
        }
        $pfOutput = rtrim($pfOutput, '&');

        if (!empty($passphrase)) {
            $pfOutput .= '&passphrase=' . urlencode($passphrase);
        }

        return md5($pfOutput);
    }

    /**
     * Validate PayFast webhook signature (alphabetical field order).
     */
    protected function validateSignature(array $data, ?string $passphrase = null): bool
    {
        $signature = $data['signature'] ?? '';
        unset($data['signature']);

        ksort($data);

        $pfOutput = '';
        foreach ($data as $key => $val) {
            $val = trim((string) $val);
            $pfOutput .= $key . '=' . urlencode($val) . '&';
        }
        $pfOutput = rtrim($pfOutput, '&');

        if (!empty($passphrase)) {
            $pfOutput .= '&passphrase=' . urlencode($passphrase);
        }

        $generated = md5($pfOutput);

        Log::info('Direct PayFast signature validation', [
            'received' => $signature,
            'generated' => $generated,
            'match' => $signature === $generated,
        ]);

        return $signature === $generated;
    }

    /**
     * Ensure URL uses the scheme from APP_URL.
     */
    protected function absoluteUrl(string $url): string
    {
        $appUrl = config('app.url');
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME);

        if ($appScheme && parse_url($url, PHP_URL_SCHEME) !== $appScheme) {
            $url = preg_replace('/^https?/', $appScheme, $url);
        }

        return $url;
    }
}
