<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand Identity
    |--------------------------------------------------------------------------
    |
    | Core branding information for this platform instance.
    |
    */

    'name' => env('BRAND_NAME', 'Bidall'),
    'short_name' => env('BRAND_SHORT_NAME', 'Bidall'),
    'tagline' => env('BRAND_TAGLINE', 'South African Online Auctions'),
    'description' => env('BRAND_DESCRIPTION', 'Fast, simple online auction platform for South African auctioneers and bidders.'),

    /*
    |--------------------------------------------------------------------------
    | Visual Identity
    |--------------------------------------------------------------------------
    |
    | Logo and visual branding assets.
    |
    */

    'logo' => [
        'default' => env('BRAND_LOGO', '/images/logo.svg'),
        'dark' => env('BRAND_LOGO_DARK', '/images/logo-dark.svg'),
        'icon' => env('BRAND_ICON', '/images/icon.svg'),
        'favicon' => env('BRAND_FAVICON', '/favicon.ico'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Colors
    |--------------------------------------------------------------------------
    |
    | Primary color scheme for the platform.
    |
    */

    'colors' => [
        'primary' => env('BRAND_COLOR_PRIMARY', '#22c55e'), // Green
        'secondary' => env('BRAND_COLOR_SECONDARY', '#ef4444'), // Red
        'accent' => env('BRAND_COLOR_ACCENT', '#3b82f6'), // Blue
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    |
    | Social media accounts for the platform.
    |
    */

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK', ''),
        'instagram' => env('SOCIAL_INSTAGRAM', ''),
        'twitter' => env('SOCIAL_TWITTER', ''),
        'linkedin' => env('SOCIAL_LINKEDIN', ''),
        'youtube' => env('SOCIAL_YOUTUBE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal
    |--------------------------------------------------------------------------
    |
    | Legal entity and documentation.
    |
    */

    'legal' => [
        'company_name' => env('LEGAL_COMPANY_NAME', 'Bidall (Pty) Ltd'),
        'registration_number' => env('LEGAL_REGISTRATION_NUMBER', ''),
        'vat_number' => env('LEGAL_VAT_NUMBER', ''),
        'terms_url' => env('LEGAL_TERMS_URL', '/terms'),
        'privacy_url' => env('LEGAL_PRIVACY_URL', '/privacy'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PWA Configuration
    |--------------------------------------------------------------------------
    |
    | Progressive Web App manifest settings.
    |
    */

    'pwa' => [
        'name' => env('PWA_NAME', 'Bidall - Online Auctions'),
        'short_name' => env('PWA_SHORT_NAME', 'Bidall'),
        'theme_color' => env('PWA_THEME_COLOR', '#22c55e'),
        'background_color' => env('PWA_BACKGROUND_COLOR', '#0a0a0f'),
        'display' => env('PWA_DISPLAY', 'standalone'),
    ],

];
