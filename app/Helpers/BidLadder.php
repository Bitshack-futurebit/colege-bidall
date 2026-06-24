<?php

namespace App\Helpers;

/**
 * Community auction bid increment ladder.
 *
 * Tiers (ZAR):
 *   R0      – R100   : R10
 *   R100    – R500   : R25
 *   R500    – R1,000 : R50
 *   R1,000  – R2,500 : R100
 *   R2,500  – R10,000: R250
 *   R10,000 – R25,000: R500
 *   R25,000+         : R1,000
 */
class BidLadder
{
    public const TIERS = [
        [0,      100,     10],
        [100,    500,     25],
        [500,    1000,    50],
        [1000,   2500,    100],
        [2500,   10000,   250],
        [10000,  25000,   500],
        [25000,  PHP_INT_MAX, 1000],
    ];

    /**
     * Increment applied to the current bid to produce the next valid bid.
     */
    public static function nextIncrement(float $currentBid): int
    {
        $value = max(0, (int) floor($currentBid));
        foreach (self::TIERS as [$min, $max, $increment]) {
            if ($value >= $min && $value < $max) {
                return $increment;
            }
        }
        return 1000;
    }

    /**
     * Next valid bid amount for the given current bid.
     */
    public static function nextBid(float $currentBid): int
    {
        return ((int) floor($currentBid)) + self::nextIncrement($currentBid);
    }

    /**
     * A bid value N increments above the given current bid (for quick-bid buttons).
     * Increment is recalculated as we step up tiers.
     */
    public static function bidNSteps(float $currentBid, int $steps): int
    {
        $value = (int) floor($currentBid);
        for ($i = 0; $i < $steps; $i++) {
            $value += self::nextIncrement($value);
        }
        return $value;
    }
}
