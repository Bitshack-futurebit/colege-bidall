<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Convert hex color to space-separated RGB string for CSS variables.
     * e.g. '#1d4ed8' => '29 78 216'
     */
    public static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r} {$g} {$b}";
    }

    /**
     * Generate a full shade palette (50-950) from a single hex color.
     * Returns array like ['50' => '239 246 255', '100' => '219 234 254', ...]
     */
    public static function generatePalette(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Target lightness values for each shade level
        // These match Tailwind's default shade distribution
        $shadeLightness = [
            '50'  => 0.97,
            '100' => 0.94,
            '200' => 0.86,
            '300' => 0.76,
            '400' => 0.64,
            '500' => 0.50,
            '600' => 0.42,
            '700' => 0.34,
            '800' => 0.26,
            '900' => 0.20,
            '950' => 0.12,
        ];

        $palette = [];
        foreach ($shadeLightness as $shade => $targetL) {
            // Desaturate lighter shades slightly for a more natural palette
            $satAdjust = 1.0;
            if ($targetL > 0.8) {
                $satAdjust = 0.6 + ($targetL - 0.8) * -1.5;
                $satAdjust = max(0.3, $satAdjust);
            } elseif ($targetL < 0.2) {
                $satAdjust = 0.7;
            }

            $shadeS = min(1.0, $s * $satAdjust);
            [$sr, $sg, $sb] = self::hslToRgb($h, $shadeS, $targetL);
            $palette[$shade] = "{$sr} {$sg} {$sb}";
        }

        return $palette;
    }

    private static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0, 0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match (true) {
            $max === $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6,
            $max === $g => (($b - $r) / $d + 2) / 6,
            default     => (($r - $g) / $d + 4) / 6,
        };

        return [$h, $s, $l];
    }

    private static function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s === 0.0) {
            $v = (int) round($l * 255);
            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = (int) round(self::hueToRgb($p, $q, $h + 1 / 3) * 255);
        $g = (int) round(self::hueToRgb($p, $q, $h) * 255);
        $b = (int) round(self::hueToRgb($p, $q, $h - 1 / 3) * 255);

        return [$r, $g, $b];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }
}
