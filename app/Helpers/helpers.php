<?php

if (!function_exists('formatPrice')) {
    /**
     * Format price with currency symbol
     */
    function formatPrice($configKey, $amount = null)
    {
        // If config key is passed, get the value from config
        // Only treat as config key if it contains a dot (e.g., "platform.pricing.tier_basic.price")
        if (is_string($configKey) && $amount === null && strpos($configKey, '.') !== false && !is_numeric($configKey)) {
            $amount = config($configKey);
        } elseif ($amount === null) {
            $amount = $configKey;
        }

        $currency = config('regional.currency', 'ZAR');
        $symbol = config('regional.currency_symbol', 'R');

        return $symbol . number_format((float)$amount, 2);
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency amount
     */
    function formatCurrency($amount)
    {
        return formatPrice($amount);
    }
}

if (!function_exists('getPriceValue')) {
    /**
     * Get price value from config without formatting
     */
    function getPriceValue($configKey)
    {
        return config($configKey) ?? 0;
    }
}

if (!function_exists('minBid')) {
    /**
     * Get minimum bid amount
     */
    function minBid()
    {
        return config('platform.auction.minimum_bid', 1);
    }
}

if (!function_exists('minIncrement')) {
    /**
     * Get minimum bid increment
     */
    function minIncrement()
    {
        return config('platform.auction.minimum_increment', 1);
    }
}

if (!function_exists('waNumber')) {
    /**
     * Normalize a phone number for use in wa.me / WhatsApp deep-links.
     * Returns digits only, with SA local format (0XXXXXXXXX) converted to
     * international (27XXXXXXXXX). WhatsApp silently drops the text param
     * when the number is invalid.
     */
    function waNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '27' . substr($digits, 1);
        }
        return $digits;
    }
}
