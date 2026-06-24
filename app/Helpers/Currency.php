<?php

namespace App\Helpers;

/**
 * Currency Helper Functions
 *
 * These functions format currency based on platform configuration.
 * Works for both fiat (ZAR) and Bitcoin (BTC/sats).
 */

if (!function_exists('formatCurrency')) {
    /**
     * Format amount in platform currency.
     *
     * @param float|int|null $amount The amount to format
     * @param bool $includeSymbol Include currency symbol
     * @return string Formatted currency string
     */
    function formatCurrency($amount, bool $includeSymbol = true): string
    {
        if (is_null($amount)) {
            return $includeSymbol ? currencySymbol() . '0' : '0';
        }

        $code = config('platform.currency.code');
        $symbol = config('platform.currency.symbol');
        $decimals = config('platform.currency.decimals');

        // Bitcoin formatting
        if ($code === 'BTC') {
            $denomination = config('platform.currency.btc_denomination', 'sats');

            if ($denomination === 'sats') {
                // Display in satoshis (no decimals)
                $formatted = number_format($amount, 0, '.', ',');
                return $includeSymbol ? $formatted . ' sats' : $formatted;
            }

            // Display in BTC (8 decimals)
            $formatted = number_format($amount, 8, '.', ',');
            return $includeSymbol ? $symbol . $formatted : $formatted;
        }

        // Fiat formatting (ZAR, USD, etc.)
        $formatted = number_format($amount, $decimals, '.', ',');
        return $includeSymbol ? $symbol . $formatted : $formatted;
    }
}

if (!function_exists('currencySymbol')) {
    /**
     * Get the platform currency symbol.
     *
     * @return string Currency symbol (R, $, ₿, etc.)
     */
    function currencySymbol(): string
    {
        return config('platform.currency.symbol', 'R');
    }
}

if (!function_exists('currencyCode')) {
    /**
     * Get the platform currency code.
     *
     * @return string Currency code (ZAR, BTC, USD, etc.)
     */
    function currencyCode(): string
    {
        return config('platform.currency.code', 'ZAR');
    }
}

if (!function_exists('currencyName')) {
    /**
     * Get the platform currency name.
     *
     * @return string Currency name
     */
    function currencyName(): string
    {
        return config('platform.currency.name', 'South African Rand');
    }
}

if (!function_exists('isBitcoin')) {
    /**
     * Check if platform uses Bitcoin.
     *
     * @return bool
     */
    function isBitcoin(): bool
    {
        return currencyCode() === 'BTC';
    }
}

if (!function_exists('convertToSats')) {
    /**
     * Convert BTC amount to satoshis.
     *
     * @param float $btc Amount in BTC
     * @return int Amount in satoshis
     */
    function convertToSats(float $btc): int
    {
        return (int) ($btc * 100000000);
    }
}

if (!function_exists('convertToBTC')) {
    /**
     * Convert satoshis to BTC.
     *
     * @param int $sats Amount in satoshis
     * @return float Amount in BTC
     */
    function convertToBTC(int $sats): float
    {
        return $sats / 100000000;
    }
}

if (!function_exists('formatPrice')) {
    /**
     * Format a price from config (handles both fiat and sats).
     *
     * @param string $configKey Config key (e.g., 'platform.pricing.activation_fee')
     * @return string Formatted price
     */
    function formatPrice(string $configKey): string
    {
        $amount = config($configKey, 0);
        return formatCurrency($amount);
    }
}

if (!function_exists('parseCurrency')) {
    /**
     * Parse currency input string to float/int.
     *
     * @param string $input User input (e.g., "R1,250.50" or "50000 sats")
     * @return float|int Parsed amount
     */
    function parseCurrency(string $input)
    {
        // Remove currency symbols and text
        $cleaned = preg_replace('/[^0-9.,]/', '', $input);

        // Remove thousands separators
        $cleaned = str_replace(',', '', $cleaned);

        // Convert to appropriate type
        if (isBitcoin() && config('platform.currency.btc_denomination') === 'sats') {
            return (int) $cleaned;
        }

        return (float) $cleaned;
    }
}

if (!function_exists('minBid')) {
    /**
     * Get minimum bid amount for platform.
     *
     * @return float|int Minimum bid
     */
    function minBid()
    {
        return config('platform.pricing.minimum_bid', 1);
    }
}

if (!function_exists('minIncrement')) {
    /**
     * Get minimum increment amount for platform.
     *
     * @return float|int Minimum increment
     */
    function minIncrement()
    {
        return config('platform.pricing.minimum_increment', 1);
    }
}

if (!function_exists('formatCurrencyForInput')) {
    /**
     * Format amount for input field (no thousands separators).
     *
     * @param float|int|null $amount The amount
     * @return string Formatted for input
     */
    function formatCurrencyForInput($amount): string
    {
        if (is_null($amount)) {
            return '0';
        }

        $decimals = config('platform.currency.decimals');

        if (isBitcoin() && config('platform.currency.btc_denomination') === 'sats') {
            return (string) (int) $amount;
        }

        return number_format($amount, $decimals, '.', '');
    }
}

if (!function_exists('getPlatformRegion')) {
    /**
     * Get the current platform region.
     *
     * @return string Region identifier
     */
    function getPlatformRegion(): string
    {
        return config('platform.region', 'south-africa');
    }
}

if (!function_exists('isSouthAfrica')) {
    /**
     * Check if platform is South African version.
     *
     * @return bool
     */
    function isSouthAfrica(): bool
    {
        return getPlatformRegion() === 'south-africa';
    }
}

if (!function_exists('isInternational')) {
    /**
     * Check if platform is international version.
     *
     * @return bool
     */
    function isInternational(): bool
    {
        return getPlatformRegion() === 'international';
    }
}
