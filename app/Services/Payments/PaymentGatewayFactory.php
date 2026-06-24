<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;

/**
 * Resolves a payment gateway by name so a single transaction can choose its
 * gateway at runtime (e.g. buyer picks Card vs Bitcoin), instead of the whole
 * app being locked to one global gateway.
 */
class PaymentGatewayFactory
{
    /** Gateways a user may pick at checkout (btcpay is a stub, not user-selectable). */
    public const SELECTABLE = ['payfast', 'blink'];

    /**
     * @param string|null $name Gateway id; falls back to the platform default.
     */
    public function make(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: config('platform.payment_gateway', 'payfast');

        return match ($name) {
            'payfast' => new PayFastGateway(),
            'btcpay' => new BTCPayGateway(),
            'blink' => new BlinkGateway(),
            default => throw new \InvalidArgumentException("Unsupported payment gateway: {$name}"),
        };
    }

    /**
     * Is Blink fully configured (so the Bitcoin option can be offered)?
     */
    public function blinkAvailable(): bool
    {
        return !empty(config('services.blink.api_key'))
            && !empty(config('services.blink.wallet_id'));
    }
}
