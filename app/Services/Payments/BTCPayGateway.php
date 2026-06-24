<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BTCPay Server Payment Gateway
 *
 * This is a placeholder implementation for the Bitcoin version.
 * Implement using BTCPay Server API for Lightning and on-chain payments.
 *
 * Documentation: https://docs.btcpayserver.org/
 */
class BTCPayGateway implements PaymentGatewayInterface
{
    protected string $serverUrl;
    protected string $apiKey;
    protected string $storeId;
    protected bool $sandbox;

    public function __construct()
    {
        $this->serverUrl = config('services.btcpay.server_url');
        $this->apiKey = config('services.btcpay.api_key');
        $this->storeId = config('services.btcpay.store_id');
        $this->sandbox = config('services.btcpay.sandbox', true);
    }

    public function createPayment(
        float $amount,
        User $user,
        string $type,
        array $metadata = []
    ): array {
        // Generate unique payment reference
        $paymentId = 'BTC-' . uniqid() . '-' . time();

        // Convert amount to satoshis if needed
        $amountSats = $this->convertToSats($amount);

        // Create BTCPay invoice
        // TODO: Implement BTCPay Server invoice creation
        // API Endpoint: POST /api/v1/stores/{storeId}/invoices

        $invoiceData = [
            'amount' => $amountSats,
            'currency' => 'BTC',
            'orderId' => $paymentId,
            'itemDesc' => $this->getItemDescription($type, $metadata),
            'notificationUrl' => route('payment.webhook'),
            'redirectUrl' => route('payment.return'),
            'buyer' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'metadata' => [
                'orderId' => $paymentId,
                'type' => $type,
                'userId' => $user->id,
            ],
        ];

        // Create transaction record
        Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'btcpay',
            'payment_id' => $paymentId,
            'payment_data' => array_merge($metadata, [
                'amount_sats' => $amountSats,
            ]),
        ]);

        Log::info('BTCPay payment created', [
            'payment_id' => $paymentId,
            'amount_sats' => $amountSats,
            'user_id' => $user->id,
            'type' => $type,
        ]);

        // TODO: Make API call to BTCPay Server
        // $response = Http::withHeaders([
        //     'Authorization' => 'token ' . $this->apiKey,
        // ])->post($this->serverUrl . "/api/v1/stores/{$this->storeId}/invoices", $invoiceData);

        return [
            'payment_id' => $paymentId,
            'redirect_url' => 'https://btcpay.example.com/invoice/' . $paymentId, // TODO: Use actual invoice URL
            'invoice_id' => $paymentId, // TODO: Use actual BTCPay invoice ID
            'amount_sats' => $amountSats,
            'lightning_invoice' => null, // TODO: Get from BTCPay response
            'onchain_address' => null, // TODO: Get from BTCPay response
        ];
    }

    public function verifyPayment(string $paymentId): array
    {
        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        // TODO: Check invoice status via BTCPay API
        // GET /api/v1/stores/{storeId}/invoices/{invoiceId}

        return [
            'success' => $transaction->status === 'completed',
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'confirmations' => 0, // TODO: Get from BTCPay
            'transaction' => $transaction,
        ];
    }

    public function isPaymentCompleted(string $paymentId): bool
    {
        $transaction = Transaction::where('payment_id', $paymentId)->first();
        return $transaction && $transaction->status === 'completed';
    }

    public function handleWebhook(array $payload): array
    {
        Log::info('BTCPay webhook received', $payload);

        // TODO: Validate webhook signature
        // BTCPay sends webhooks for invoice status changes

        $invoiceId = $payload['invoiceId'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$invoiceId) {
            return [
                'success' => false,
                'message' => 'Missing invoice ID',
            ];
        }

        // Find transaction by invoice ID (stored in payment_data)
        $transaction = Transaction::where('payment_id', $invoiceId)
            ->orWhereJsonContains('payment_data->invoice_id', $invoiceId)
            ->first();

        if (!$transaction) {
            Log::error('BTCPay webhook transaction not found', ['invoice_id' => $invoiceId]);
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        // Update transaction based on BTCPay status
        // BTCPay statuses: New, Paid, Confirmed, Complete, Expired, Invalid
        if (in_array($status, ['Paid', 'Confirmed', 'Complete'])) {
            $transaction->update([
                'status' => 'completed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'btcpay_status' => $status,
                    'completed_at' => now(),
                    'tx_hash' => $payload['transactionHash'] ?? null,
                ]),
            ]);

            Log::info('BTCPay payment completed', [
                'invoice_id' => $invoiceId,
                'transaction_id' => $transaction->id,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
                'action' => 'completed',
            ];
        }

        if (in_array($status, ['Expired', 'Invalid'])) {
            $transaction->update(['status' => 'failed']);

            Log::warning('BTCPay payment failed', [
                'invoice_id' => $invoiceId,
                'status' => $status,
            ]);

            return [
                'success' => false,
                'transaction' => $transaction,
                'action' => 'failed',
            ];
        }

        return [
            'success' => true,
            'action' => 'pending',
        ];
    }

    public function refund(string $paymentId, float $amount): bool
    {
        // BTCPay refunds require generating a new payment to the user
        Log::info('BTCPay refund requested', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

        // TODO: Implement BTCPay refund (create reverse payment)

        $transaction = Transaction::where('payment_id', $paymentId)->first();
        if ($transaction) {
            $transaction->update(['status' => 'refunded']);
            return true;
        }

        return false;
    }

    public function getPaymentDetails(string $paymentId): array
    {
        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            return [
                'found' => false,
            ];
        }

        return [
            'found' => true,
            'payment_id' => $paymentId,
            'amount' => $transaction->amount,
            'amount_sats' => $transaction->payment_data['amount_sats'] ?? null,
            'status' => $transaction->status,
            'type' => $transaction->type,
            'tx_hash' => $transaction->payment_data['tx_hash'] ?? null,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ];
    }

    public function getName(): string
    {
        return 'BTCPay Server';
    }

    public function getCurrency(): string
    {
        return 'BTC';
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Convert platform amount to satoshis.
     */
    protected function convertToSats(float $amount): int
    {
        // If platform is already using sats, return as-is
        if (config('platform.currency.btc_denomination') === 'sats') {
            return (int) $amount;
        }

        // Convert BTC to sats (1 BTC = 100,000,000 sats)
        return (int) ($amount * 100000000);
    }

    /**
     * Get item description for invoice.
     */
    protected function getItemDescription(string $type, array $metadata): string
    {
        return match ($type) {
            'activation_fee' => 'Auctioneer Activation Fee (Lifetime)',
            'credit_purchase' => 'Platform Credits Purchase',
            'deposit' => 'Event Deposit - ' . ($metadata['event_name'] ?? 'N/A'),
            'lot_payment' => 'Auction Lot Payment - ' . ($metadata['lot_title'] ?? 'N/A'),
            default => 'Platform Payment',
        };
    }
}
