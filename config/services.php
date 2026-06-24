<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as PayFast, BTCPay, Mailgun, etc.
    |
    */

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'payfast' => [
        'merchant_id' => env('PAYFAST_MERCHANT_ID'),
        'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
        'passphrase' => env('PAYFAST_PASSPHRASE'),
        'sandbox' => env('PAYFAST_SANDBOX', true),
    ],

    'btcpay' => [
        'server_url' => env('BTCPAY_SERVER_URL'),
        'api_key' => env('BTCPAY_API_KEY'),
        'store_id' => env('BTCPAY_STORE_ID'),
        'sandbox' => env('BTCPAY_SANDBOX', true),
    ],

    'blink' => [
        // Mainnet: https://api.blink.sv/graphql · Staging: https://api.staging.blink.sv/graphql
        'api_url' => env('BLINK_API_URL', 'https://api.blink.sv/graphql'),
        'api_key' => env('BLINK_API_KEY'),
        'wallet_id' => env('BLINK_WALLET_ID'),         // USD (Stablesats) wallet id
        'webhook_secret' => env('BLINK_WEBHOOK_SECRET'), // Svix signing secret (whsec_...)
        'zar_usd_rate' => env('BLINK_ZAR_USD_RATE'),     // optional static USD-per-ZAR fallback if live FX is down
        'sandbox' => env('BLINK_SANDBOX', true),
    ],

    // FX rates (openexchangerates.org) — ZAR→USD for Blink Stablesats invoices.
    // USD base; free plan ~1,000 req/mo with hourly updates, so cache ~1h (see ttl).
    'openexchangerates' => [
        'app_id' => env('OPENEXCHANGERATES_APP_ID'),
        'base_url' => env('OPENEXCHANGERATES_URL', 'https://openexchangerates.org/api'),
        'ttl' => env('OPENEXCHANGERATES_TTL', 3600), // cache seconds (1h ≈ 720 calls/mo)
    ],

    'google' => [
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

];
