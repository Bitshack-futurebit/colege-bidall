<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Regional Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable features based on region. For example, WhatsApp
    | integration may be relevant for South Africa but not globally.
    |
    */

    'features' => [
        'whatsapp_integration' => env('FEATURE_WHATSAPP', true),
        'map_discovery' => env('FEATURE_MAP', true),
        'deposits' => env('FEATURE_DEPOSITS', true),
        'buyers_premium' => env('FEATURE_BUYERS_PREMIUM', true),
        'reserve_prices' => env('FEATURE_RESERVE_PRICES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | WhatsApp integration settings for direct customer support.
    |
    */

    'whatsapp' => [
        'enabled' => env('FEATURE_WHATSAPP', true),
        'platform_number' => env('WHATSAPP_PLATFORM_NUMBER', '27123456789'),
        'support_group_url' => env('WHATSAPP_SUPPORT_GROUP', 'https://chat.whatsapp.com/DEDvpUo83KN8vqSuwvbqG4?mode=gi_t'),
        'message_template' => env('WHATSAPP_MESSAGE_TEMPLATE', 'Hi, I need help with {platform_name}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Map Configuration
    |--------------------------------------------------------------------------
    |
    | Default map settings for auctioneer discovery.
    |
    */

    'map' => [
        'enabled' => env('FEATURE_MAP', true),
        'provider' => env('MAP_PROVIDER', 'leaflet'), // leaflet, google, mapbox
        'default_lat' => env('MAP_DEFAULT_LAT', -25.7479), // South Africa center
        'default_lng' => env('MAP_DEFAULT_LNG', 28.2293),
        'default_zoom' => env('MAP_DEFAULT_ZOOM', 6),
        'tile_layer' => env('MAP_TILE_LAYER', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Regional localization settings.
    |
    */

    'locale' => [
        'default' => env('APP_LOCALE', 'en_ZA'),
        'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
        'available' => ['en_ZA', 'en_US', 'af_ZA'], // Available locales
    ],

    'timezone' => env('APP_TIMEZONE', 'Africa/Johannesburg'),

    /*
    |--------------------------------------------------------------------------
    | Regional Regulations
    |--------------------------------------------------------------------------
    |
    | Country-specific regulatory requirements.
    |
    */

    'regulations' => [
        'require_id_verification' => env('REQUIRE_ID_VERIFICATION', false),
        'require_tax_number' => env('REQUIRE_TAX_NUMBER', false),
        'data_residency' => env('DATA_RESIDENCY', 'south-africa'), // Where data must be stored
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    |
    | Regional contact details.
    |
    */

    'contact' => [
        'email' => env('CONTACT_EMAIL', 'support@basicbidall.com'),
        'phone' => env('CONTACT_PHONE', '+27 12 345 6789'),
        'address' => env('CONTACT_ADDRESS', 'Johannesburg, South Africa'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Regional Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for region-specific settings.
    |
    */

    'defaults' => [
        'phone_country_code' => env('DEFAULT_COUNTRY_CODE', '+27'),
        'date_format' => env('DATE_FORMAT', 'd/m/Y'),
        'time_format' => env('TIME_FORMAT', 'H:i'),
        'datetime_format' => env('DATETIME_FORMAT', 'd/m/Y H:i'),
    ],

];
