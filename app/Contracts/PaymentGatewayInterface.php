<?php

namespace App\Contracts;

use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * Create a payment request.
     *
     * @param float $amount Amount in the platform's currency
     * @param User $user The user making the payment
     * @param string $type Payment type (activation, credits, deposit, lot_payment)
     * @param array $metadata Additional payment metadata
     * @return array Payment details including redirect URL
     */
    public function createPayment(
        float $amount,
        User $user,
        string $type,
        array $metadata = []
    ): array;

    /**
     * Verify a payment using the payment ID.
     *
     * @param string $paymentId The payment identifier from the gateway
     * @return array Payment verification details
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Check if a payment is completed/successful.
     *
     * @param string $paymentId The payment identifier
     * @return bool
     */
    public function isPaymentCompleted(string $paymentId): bool;

    /**
     * Handle webhook/callback from payment gateway.
     *
     * @param array $payload The webhook payload
     * @return array Processed webhook data
     */
    public function handleWebhook(array $payload): array;

    /**
     * Refund a payment.
     *
     * @param string $paymentId The payment identifier
     * @param float $amount Amount to refund (full or partial)
     * @return bool Success status
     */
    public function refund(string $paymentId, float $amount): bool;

    /**
     * Get payment details.
     *
     * @param string $paymentId The payment identifier
     * @return array Payment details
     */
    public function getPaymentDetails(string $paymentId): array;

    /**
     * Get the gateway name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the gateway currency.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Check if gateway is in sandbox/test mode.
     *
     * @return bool
     */
    public function isSandbox(): bool;
}
