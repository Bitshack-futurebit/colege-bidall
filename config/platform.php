<?php

return [

    'cron_secret' => env('CRON_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Platform Region
    |--------------------------------------------------------------------------
    |
    | The region this platform instance serves. Used to determine regional
    | features, payment gateways, and localization settings.
    |
    | Supported: 'south-africa', 'international'
    |
    */

    'region' => env('PLATFORM_REGION', 'south-africa'),

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the currency used by this platform instance.
    | For Bitcoin, set code to 'BTC' and adjust decimals to 8.
    |
    */

    'currency' => [
        'code' => env('CURRENCY_CODE', 'ZAR'),
        'symbol' => env('CURRENCY_SYMBOL', 'R'),
        'name' => env('CURRENCY_NAME', 'South African Rand'),
        'decimals' => env('CURRENCY_DECIMALS', 2),

        // Bitcoin-specific settings
        'btc_denomination' => env('BTC_DENOMINATION', 'sats'), // 'sats' or 'btc'
        'sats_per_btc' => 100000000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | Platform pricing in the configured currency.
    | For Bitcoin version, set these to satoshi values.
    |
    */

    'pricing' => [
        // Minimum credit deposit to unlock auctioneer account
        'minimum_deposit' => env('MINIMUM_DEPOSIT', 100),

        // Credit packages for purchase
        'credit_packages' => [
            [
                'name' => 'Starter',
                'amount' => 100,
                'credits' => 100,
                'description' => 'Perfect for small auctions',
                'popular' => false,
                'features' => [
                    '100 credits',
                    'Create up to 100 lots',
                    'Basic support',
                ],
            ],
            [
                'name' => 'Professional',
                'amount' => 500,
                'credits' => 500,
                'description' => 'Best for regular auctioneers',
                'popular' => true,
                'features' => [
                    '500 credits',
                    'Create up to 500 lots',
                    'Priority support',
                    'Best value',
                ],
            ],
            [
                'name' => 'Business',
                'amount' => 1000,
                'credits' => 1000,
                'description' => 'For high-volume auctions',
                'popular' => false,
                'features' => [
                    '1000 credits',
                    'Create up to 1000 lots',
                    'Priority support',
                    'Volume discount',
                ],
            ],
            [
                'name' => 'Enterprise',
                'amount' => 5000,
                'credits' => 5000,
                'description' => 'Maximum value package',
                'popular' => false,
                'features' => [
                    '5000 credits',
                    'Unlimited lots',
                    'Dedicated support',
                    'Maximum savings',
                ],
            ],
        ],

        // Image tier pricing (per lot)
        'tier_basic' => [
            'price' => env('TIER_BASIC_PRICE', 1),
            'images' => env('TIER_BASIC_LIMIT', 1),
        ],
        'tier_pro' => [
            'price' => env('TIER_PRO_PRICE', 5),
            'images' => env('TIER_PRO_LIMIT', 5),
        ],
        'tier_premium' => [
            'price' => env('TIER_PREMIUM_PRICE', 20),
            'images' => env('TIER_PREMIUM_LIMIT', 20),
        ],

        // Lot fees (alias for compatibility)
        'lot_fees' => [
            'basic' => env('TIER_BASIC_PRICE', 1),
            'pro' => env('TIER_PRO_PRICE', 5),
            'premium' => env('TIER_PREMIUM_PRICE', 20),
        ],

        // Platform fees
        'platform_percentage' => env('PLATFORM_PERCENTAGE_FEE', 1), // percentage
        'platform_fee_percent' => env('PLATFORM_PERCENTAGE_FEE', 1), // alias
        'platform_lot_fee' => env('PLATFORM_LOT_FEE', 1), // flat fee when lot goes live

        // Minimum values
        'minimum_bid' => env('MINIMUM_BID', 1),
        'minimum_increment' => env('MINIMUM_INCREMENT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auction Configuration
    |--------------------------------------------------------------------------
    |
    | Core auction timing and behavior settings.
    |
    */

    'auction' => [
        // Soft close timing (seconds)
        'soft_close_time' => env('AUCTION_SOFT_CLOSE_TIME', 120), // 2 minutes
        'soft_close_extension' => env('AUCTION_SOFT_CLOSE_EXTENSION', 120), // 2 minutes

        // Lot timing
        'lot_gap_seconds' => env('AUCTION_LOT_GAP_SECONDS', 30), // Time between lot endings

        // Polling interval for real-time updates (milliseconds)
        'polling_interval' => env('AUCTION_POLLING_INTERVAL', 3000), // 3 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Image upload and optimization settings.
    |
    */

    'images' => [
        'max_upload_size' => env('IMAGE_MAX_UPLOAD_SIZE', 15360), // KB (15MB - supports modern phone cameras)
        'max_upload_size_mb' => env('IMAGE_MAX_UPLOAD_SIZE_MB', 15), // MB display value
        'standard_width' => env('IMAGE_STANDARD_WIDTH', 1200),
        'thumbnail_width' => env('IMAGE_THUMBNAIL_WIDTH', 300),
        'quality' => env('IMAGE_QUALITY', 85),
        'format' => env('IMAGE_FORMAT', 'webp'),
        'auto_delete_days' => env('IMAGE_AUTO_DELETE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auction Management
    |--------------------------------------------------------------------------
    |
    | Auction lifecycle and cleanup settings.
    |
    */

    'auctions' => [
        'auto_archive_days' => env('AUCTION_AUTO_ARCHIVE_DAYS', 7), // Days after ending to archive (soft delete)
    ],

    /*
    |--------------------------------------------------------------------------
    | Payout Configuration
    |--------------------------------------------------------------------------
    |
    | Auctioneer payout and accounting settings.
    |
    */

    'payout' => [
        'minimum_payout' => env('MINIMUM_PAYOUT', 500),           // Minimum single payout request
        'minimum_balance' => env('MINIMUM_BALANCE', 100),          // Must remain in account after payout
        'payout_methods' => ['eft', 'bank_transfer'],
        'payout_processing_days' => env('PAYOUT_PROCESSING_DAYS', 5),
        'funds_hold_hours' => env('FUNDS_HOLD_HOURS', 48),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The payment gateway to use for this platform instance.
    |
    | Supported: 'payfast', 'btcpay', 'blink', 'test'
    |
    */

    'payment_gateway' => env('PAYMENT_GATEWAY', 'payfast'),

];
