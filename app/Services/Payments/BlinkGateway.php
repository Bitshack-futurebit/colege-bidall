<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Blink (blink.sv / Galoy) Lightning Payment Gateway — SCAFFOLD.
 *
 * Custodial Lightning service talked to over a GraphQL API. No node, no host.
 * Pilot path for BidAll (see F:\bitshack\PAYMENTS_OPTIONS.md).
 *
 * Key differences vs PayFast/BTCPay handled here:
 *   - Currency: platform prices in ZAR, Blink has no ZAR. We convert ZAR→USD and
 *     create a USD-denominated (Stablesats) invoice, so the rand value is locked.
 *   - Notifications: Blink webhooks are signed with Svix over the RAW request body.
 *     The PaymentGatewayInterface::handleWebhook() only receives a parsed array, so
 *     signature verification lives in verifyWebhookSignature() and is called from a
 *     dedicated route (PaymentController::blinkWebhook) BEFORE delegating here.
 *   - No hosted checkout: Blink is API-only. createPayment() returns the BOLT11
 *     invoice so a Lightning QR view can render it (see redirect_url TODO below).
 *
 * Docs: https://dev.blink.sv/  ·  Webhooks: https://dev.blink.sv/api/webhooks
 *
 * TODOs are marked inline — every external call is stubbed against the documented
 * API shape but must be validated against a live Blink account before production.
 */
class BlinkGateway implements PaymentGatewayInterface
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $walletId;
    protected bool $sandbox;

    public function __construct()
    {
        $this->apiUrl = config('services.blink.api_url');
        $this->apiKey = config('services.blink.api_key');
        $this->walletId = config('services.blink.wallet_id'); // USD (Stablesats) wallet
        $this->sandbox = config('services.blink.sandbox', true);
    }

    public function createPayment(
        float $amount,
        User $user,
        string $type,
        array $metadata = []
    ): array {
        // Unique reference we control end-to-end (used as our transaction key).
        $paymentId = 'BLINK-' . uniqid() . '-' . time();

        // Platform prices in ZAR → Blink invoices are USD (Stablesats), in cents.
        $usd = $this->convertZarToUsd($amount);
        $cents = (int) round($usd * 100);

        $memo = $this->getItemDescription($type, $metadata) . ' (' . $paymentId . ')';

        // Ask Blink to mint a USD-denominated Lightning invoice.
        $invoice = $this->createBlinkInvoice($cents, $memo);

        // Persist BEFORE returning so the webhook can always find us by payment_hash.
        Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount, // store the ZAR amount the user actually owes
            'status' => 'pending',
            'payment_method' => 'blink',
            'payment_id' => $paymentId,
            'payment_data' => array_merge($metadata, [
                'payment_request' => $invoice['paymentRequest'] ?? null,
                'payment_hash' => $invoice['paymentHash'] ?? null,
                'amount_usd_cents' => $cents,
                'amount_sats' => $invoice['satoshis'] ?? null,
            ]),
        ]);

        Log::info('Blink payment created', [
            'payment_id' => $paymentId,
            'amount_zar' => $amount,
            'amount_usd_cents' => $cents,
            'payment_hash' => $invoice['paymentHash'] ?? null,
            'user_id' => $user->id,
            'type' => $type,
        ]);

        return [
            'payment_id' => $paymentId,
            // Lightning QR checkout page (renders the BOLT11 + polls for confirmation).
            'redirect_url' => route('payment.lightning', ['paymentId' => $paymentId]),
            'invoice_id' => $invoice['paymentHash'] ?? $paymentId,
            'lightning_invoice' => $invoice['paymentRequest'] ?? null, // BOLT11 — render as QR
            'amount_sats' => $invoice['satoshis'] ?? null,
            'amount' => $amount,
            'type' => $type,
        ];
    }

    public function verifyPayment(string $paymentId): array
    {
        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        // Authoritative check straight from Blink (don't trust local state alone).
        $paid = $this->isPaymentCompleted($paymentId);

        if ($paid && $transaction->status !== 'completed') {
            $transaction->update([
                'status' => 'completed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'completed_at' => now(),
                ]),
            ]);
        }

        return [
            'success' => $transaction->fresh()->status === 'completed',
            'status' => $transaction->fresh()->status,
            'amount' => $transaction->amount,
            'transaction' => $transaction->fresh(),
        ];
    }

    public function isPaymentCompleted(string $paymentId): bool
    {
        $transaction = Transaction::where('payment_id', $paymentId)->first();
        if (!$transaction) {
            return false;
        }

        $paymentRequest = $transaction->payment_data['payment_request'] ?? null;
        if (!$paymentRequest) {
            // No BOLT11 stored — fall back to local status.
            return $transaction->status === 'completed';
        }

        // Query Blink for the live invoice status.
        // TODO: confirm field/enum names against the live schema (api.blink.sv/graphql).
        $query = <<<'GQL'
        query InvoiceStatus($input: LnInvoicePaymentStatusInput!) {
          lnInvoicePaymentStatus(input: $input) {
            status
            errors { message }
          }
        }
        GQL;

        $response = $this->graphql($query, [
            'input' => ['paymentRequest' => $paymentRequest],
        ]);

        $status = data_get($response, 'data.lnInvoicePaymentStatus.status');

        return $status === 'PAID';
    }

    public function handleWebhook(array $payload): array
    {
        // NOTE: signature is verified by the caller (PaymentController::blinkWebhook)
        // over the RAW body before we get here — do NOT trust this payload otherwise.
        Log::info('Blink webhook received', $payload);

        // TODO: confirm the exact Blink event shape against dev.blink.sv/api/webhooks.
        // Blink fires on incoming Lightning receipt; we match back to our transaction
        // via the invoice's payment hash.
        $paymentHash = data_get($payload, 'paymentHash')
            ?? data_get($payload, 'transaction.initiationVia.paymentHash')
            ?? data_get($payload, 'data.transaction.initiationVia.paymentHash');

        if (!$paymentHash) {
            Log::error('Blink webhook missing payment hash', $payload);
            return ['success' => false, 'message' => 'Missing payment hash'];
        }

        $transaction = Transaction::where('payment_method', 'blink')
            ->whereJsonContains('payment_data->payment_hash', $paymentHash)
            ->first();

        if (!$transaction) {
            Log::error('Blink webhook transaction not found', ['payment_hash' => $paymentHash]);
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        // Idempotency — Svix retries, so the same event can arrive more than once.
        if ($transaction->status === 'completed') {
            Log::info('Blink webhook skipped — already completed', ['payment_id' => $transaction->payment_id]);
            return ['success' => true, 'action' => 'already_completed'];
        }

        // Optional belt-and-braces: re-confirm against Blink before crediting.
        // if (!$this->isPaymentCompleted($transaction->payment_id)) {
        //     return ['success' => true, 'action' => 'pending'];
        // }

        $transaction->update([
            'status' => 'completed',
            'payment_data' => array_merge($transaction->payment_data ?? [], [
                'completed_at' => now(),
                'blink_event' => data_get($payload, 'eventType') ?? data_get($payload, 'type'),
            ]),
        ]);

        Log::info('Blink payment completed', [
            'payment_id' => $transaction->payment_id,
            'transaction_id' => $transaction->id,
            'payment_hash' => $paymentHash,
        ]);

        return [
            'success' => true,
            'transaction' => $transaction,
            'action' => 'completed',
        ];
    }

    public function refund(string $paymentId, float $amount): bool
    {
        // Lightning has no native refund — a refund means PAYING the user a new invoice.
        // Out of scope for the scaffold; mark the record and handle the payout manually
        // (or implement lnInvoicePaymentSend to a buyer-supplied invoice later).
        Log::info('Blink refund requested (manual payout required)', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

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
            return ['found' => false];
        }

        return [
            'found' => true,
            'payment_id' => $paymentId,
            'amount' => $transaction->amount,
            'amount_sats' => $transaction->payment_data['amount_sats'] ?? null,
            'amount_usd_cents' => $transaction->payment_data['amount_usd_cents'] ?? null,
            'status' => $transaction->status,
            'type' => $transaction->type,
            'payment_hash' => $transaction->payment_data['payment_hash'] ?? null,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ];
    }

    public function getName(): string
    {
        return 'Blink';
    }

    public function getCurrency(): string
    {
        // Invoices settle in USD (Stablesats); the platform converts ZAR→USD on the way in.
        return 'USD';
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Verify a Blink (Svix) webhook signature over the RAW request body.
     *
     * Svix signs `${svix-id}.${svix-timestamp}.${body}` with HMAC-SHA256 using the
     * base64 secret (the part after the `whsec_` prefix), then base64-encodes it.
     * The `svix-signature` header is a space-separated list of `v1,<sig>` entries.
     *
     * @param string $payload Raw request body (exactly as received)
     * @param array  $headers ['svix-id' => ..., 'svix-timestamp' => ..., 'svix-signature' => ...]
     */
    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        $secret = config('services.blink.webhook_secret');
        if (empty($secret)) {
            Log::error('Blink webhook secret not configured (BLINK_WEBHOOK_SECRET)');
            return false;
        }

        $msgId = $headers['svix-id'] ?? null;
        $timestamp = $headers['svix-timestamp'] ?? null;
        $sigHeader = $headers['svix-signature'] ?? null;

        if (!$msgId || !$timestamp || !$sigHeader) {
            Log::warning('Blink webhook missing Svix headers');
            return false;
        }

        // Reject stale timestamps (replay protection) — 5 minute tolerance.
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('Blink webhook timestamp outside tolerance', ['timestamp' => $timestamp]);
            return false;
        }

        $secretBytes = base64_decode(
            str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret
        );

        $signedContent = "{$msgId}.{$timestamp}.{$payload}";
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        // Header may carry multiple signatures (key rotation) — accept any match.
        foreach (explode(' ', $sigHeader) as $entry) {
            [$version, $sig] = array_pad(explode(',', $entry, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $sig)) {
                return true;
            }
        }

        Log::error('Blink webhook signature mismatch');
        return false;
    }

    /**
     * Create a USD-denominated Lightning invoice via Blink's GraphQL API.
     *
     * @param int    $cents USD amount in cents (Stablesats)
     * @param string $memo  Human-readable description
     * @return array{paymentRequest:?string,paymentHash:?string,satoshis:?int}
     */
    protected function createBlinkInvoice(int $cents, string $memo): array
    {
        // TODO: validate mutation/field names against the live schema before production.
        $mutation = <<<'GQL'
        mutation LnUsdInvoiceCreate($input: LnUsdInvoiceCreateInput!) {
          lnUsdInvoiceCreate(input: $input) {
            invoice {
              paymentRequest
              paymentHash
              satoshis
            }
            errors { message }
          }
        }
        GQL;

        $response = $this->graphql($mutation, [
            'input' => [
                'walletId' => $this->walletId,
                'amount' => $cents,
                'memo' => $memo,
            ],
        ]);

        $errors = data_get($response, 'data.lnUsdInvoiceCreate.errors', []);
        if (!empty($errors)) {
            Log::error('Blink invoice creation returned errors', ['errors' => $errors]);
            throw new \RuntimeException('Blink invoice creation failed: ' . json_encode($errors));
        }

        $invoice = data_get($response, 'data.lnUsdInvoiceCreate.invoice');
        if (!$invoice || empty($invoice['paymentRequest'])) {
            Log::error('Blink invoice creation returned no invoice', ['response' => $response]);
            throw new \RuntimeException('Blink invoice creation returned no invoice');
        }

        return [
            'paymentRequest' => $invoice['paymentRequest'] ?? null,
            'paymentHash' => $invoice['paymentHash'] ?? null,
            'satoshis' => $invoice['satoshis'] ?? null,
        ];
    }

    /**
     * POST a GraphQL operation to Blink and return the decoded JSON.
     */
    protected function graphql(string $query, array $variables = []): array
    {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            Log::error('Blink GraphQL request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Blink GraphQL request failed (HTTP ' . $response->status() . ')');
        }

        return $response->json() ?? [];
    }

    /**
     * Convert a ZAR amount to USD for the Stablesats invoice.
     *
     * Uses the live (cached) exchangeratesapi.io feed. If that fails, falls back to
     * the static BLINK_ZAR_USD_RATE if one is configured; otherwise fails closed
     * rather than mis-pricing the invoice.
     *
     * @param float $zar Amount in ZAR
     * @return float Amount in USD
     */
    protected function convertZarToUsd(float $zar): float
    {
        try {
            $rate = app(\App\Services\FxRateService::class)->usdPerZar();
        } catch (\Throwable $e) {
            $rate = (float) config('services.blink.zar_usd_rate', 0);
            if ($rate <= 0) {
                Log::error('Blink ZAR→USD rate unavailable — live FX failed and no BLINK_ZAR_USD_RATE fallback', [
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException('ZAR→USD rate unavailable for Blink invoice');
            }
            Log::warning('Blink ZAR→USD using static fallback rate — live FX feed failed', [
                'error' => $e->getMessage(),
                'fallback_rate' => $rate,
            ]);
        }

        return round($zar * $rate, 2);
    }

    /**
     * Get item description for the invoice memo.
     */
    protected function getItemDescription(string $type, array $metadata): string
    {
        return match ($type) {
            'activation_fee' => 'Auctioneer Activation Fee (Lifetime)',
            'credit_purchase' => 'Platform Credits Purchase',
            'deposit' => 'Event Deposit - ' . ($metadata['event_name'] ?? 'N/A'),
            'lot_payment' => 'Auction Lot Payment - ' . ($metadata['lot_title'] ?? 'N/A'),
            'community_fee_payment' => 'Community Auction Platform Fees',
            default => 'BidAll Payment',
        };
    }
}
