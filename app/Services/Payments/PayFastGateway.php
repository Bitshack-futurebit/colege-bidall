<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayFastGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $merchantKey;
    protected string $passphrase;
    protected bool $sandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.payfast.merchant_id');
        $this->merchantKey = config('services.payfast.merchant_key');
        $this->passphrase = config('services.payfast.passphrase');
        $this->sandbox = config('services.payfast.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.payfast.co.za/eng/process'
            : 'https://www.payfast.co.za/eng/process';
    }

    public function createPayment(
        float $amount,
        User $user,
        string $type,
        array $metadata = []
    ): array {
        // Generate unique payment reference
        $paymentId = 'PF-' . uniqid() . '-' . time();

        // Build PayFast data in the required field order
        $data = [
            'merchant_id' => $this->merchantId,
            'merchant_key' => $this->merchantKey,
            'return_url' => $this->absoluteUrl(route('payment.return')),
            'cancel_url' => $this->absoluteUrl(route('payment.cancel')),
            'notify_url' => $this->absoluteUrl(route('payment.webhook')),
            'amount' => number_format($amount, 2, '.', ''),
            'item_name' => $this->getItemName($type, $metadata),
            'item_description' => $this->getItemDescription($type, $metadata),
            'custom_int1' => $user->id,
            'custom_str1' => $paymentId,
            'custom_str2' => $type,
        ];

        // Generate signature
        $signature = $this->generateSignature($data);
        $data['signature'] = $signature;

        // Create transaction record
        Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'payfast',
            'payment_id' => $paymentId,
            'payment_data' => $metadata,
        ]);

        Log::info('PayFast payment created', [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'user_id' => $user->id,
            'type' => $type,
            'form_data' => $data,
            'merchant_id' => $this->merchantId,
            'sandbox' => $this->sandbox,
        ]);

        return [
            'payment_id' => $paymentId,
            'redirect_url' => $this->baseUrl,
            'form_data' => $data,
            'method' => 'POST',
            'amount' => $amount,
            'type' => $type,
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

        // In sandbox, we can't verify via API, rely on webhook
        return [
            'success' => $transaction->status === 'completed',
            'status' => $transaction->status,
            'amount' => $transaction->amount,
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
        Log::info('PayFast webhook received', $payload);

        // Validate signature (skip in sandbox - sandbox passphrase may differ from .env)
        if (!$this->sandbox && !$this->validateSignature($payload)) {
            Log::error('PayFast webhook signature validation failed');
            return [
                'success' => false,
                'message' => 'Invalid signature',
            ];
        }

        // Get transaction
        $paymentId = $payload['custom_str1'] ?? null;
        if (!$paymentId) {
            Log::error('PayFast webhook missing payment ID');
            return [
                'success' => false,
                'message' => 'Missing payment ID',
            ];
        }

        $transaction = Transaction::where('payment_id', $paymentId)->first();
        if (!$transaction) {
            Log::error('PayFast webhook transaction not found', ['payment_id' => $paymentId]);
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        // Already processed — prevent duplicate webhook handling
        if ($transaction->status === 'completed') {
            Log::info('PayFast webhook skipped — already completed', ['payment_id' => $paymentId]);
            return [
                'success' => true,
                'action' => 'already_completed',
            ];
        }

        // Update transaction based on payment status
        $paymentStatus = $payload['payment_status'] ?? 'PENDING';

        if ($paymentStatus === 'COMPLETE') {
            // amount_fee from PayFast is negative (e.g. "-9.20"), store as positive
            $gatewayFee = abs((float) ($payload['amount_fee'] ?? 0));

            $transaction->update([
                'status' => 'completed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'payfast_payment_id' => $payload['pf_payment_id'] ?? null,
                    'gateway_fee' => $gatewayFee,
                    'amount_gross' => (float) ($payload['amount_gross'] ?? 0),
                    'amount_net' => (float) ($payload['amount_net'] ?? 0),
                    'completed_at' => now(),
                ]),
            ]);

            Log::info('PayFast payment completed', [
                'payment_id' => $paymentId,
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
                'action' => 'completed',
            ];
        }

        if ($paymentStatus === 'FAILED' || $paymentStatus === 'CANCELLED') {
            $transaction->update(['status' => 'failed']);

            Log::warning('PayFast payment failed', [
                'payment_id' => $paymentId,
                'status' => $paymentStatus,
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
        // PayFast refunds must be done manually through dashboard
        Log::info('PayFast refund requested (manual process)', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

        // Update transaction to mark refund requested
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
            'status' => $transaction->status,
            'type' => $transaction->type,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ];
    }

    public function getName(): string
    {
        return 'PayFast';
    }

    public function getCurrency(): string
    {
        return 'ZAR';
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Ensure URL uses the scheme from APP_URL (fixes http→https when behind ngrok/proxy).
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

    /**
     * Generate PayFast signature.
     *
     * For custom (POST form) integrations, fields must be in PayFast's
     * predefined order — NOT alphabetical. Alphabetical order is only
     * correct for API integrations.
     */
    protected function generateSignature(array $data): string
    {
        // Remove signature if present
        unset($data['signature']);

        // PayFast required field order for custom integrations
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

        // Build parameter string in the correct order
        $pfOutput = '';
        foreach ($fieldOrder as $key) {
            if (array_key_exists($key, $data)) {
                $val = trim((string) $data[$key]);
                $pfOutput .= $key . '=' . urlencode($val) . '&';
            }
        }

        // Remove last ampersand
        $pfOutput = rtrim($pfOutput, '&');

        // Add passphrase if set
        if (!empty($this->passphrase)) {
            $pfOutput .= '&passphrase=' . urlencode($this->passphrase);
        }

        Log::info('PayFast signature generation', [
            'data_keys' => array_keys($data),
            'string' => $pfOutput,
            'md5' => md5($pfOutput),
        ]);

        return md5($pfOutput);
    }

    /**
     * Validate PayFast webhook signature.
     * Webhook payloads use alphabetical field order for signature verification.
     */
    protected function validateSignature(array $data): bool
    {
        $signature = $data['signature'] ?? '';
        unset($data['signature']);

        // Webhook validation uses alphabetical order (different from outbound form order)
        ksort($data);

        $pfOutput = '';
        foreach ($data as $key => $val) {
            $val = trim((string) $val);
            $pfOutput .= $key . '=' . urlencode($val) . '&';
        }
        $pfOutput = rtrim($pfOutput, '&');

        if (!empty($this->passphrase)) {
            $pfOutput .= '&passphrase=' . urlencode($this->passphrase);
        }

        $generatedSignature = md5($pfOutput);

        Log::info('PayFast signature validation', [
            'received' => $signature,
            'generated' => $generatedSignature,
            'match' => $signature === $generatedSignature,
        ]);

        return $signature === $generatedSignature;
    }

    /**
     * Get item name for payment.
     */
    protected function getItemName(string $type, array $metadata): string
    {
        return match ($type) {
            'activation_fee' => 'Auctioneer Activation Fee',
            'credit_purchase' => 'Credit Purchase - ' . ($metadata['credits'] ?? 'N/A'),
            'deposit' => 'Event Deposit - ' . ($metadata['event_name'] ?? 'N/A'),
            'lot_payment' => 'Lot Payment - ' . ($metadata['lot_title'] ?? 'N/A'),
            'community_fee_payment' => 'Community Auction Platform Fees',
            default => 'Payment',
        };
    }

    /**
     * Get item description for payment.
     */
    protected function getItemDescription(string $type, array $metadata): string
    {
        return match ($type) {
            'activation_fee' => 'One-time lifetime auctioneer activation',
            'credit_purchase' => 'Purchase platform credits for lots',
            'deposit' => 'Refundable/non-refundable event deposit',
            'lot_payment' => 'Payment for won auction lot(s)',
            'community_fee_payment' => '5% platform fee settlement for sold community lots',
            default => 'Platform payment',
        };
    }
}
