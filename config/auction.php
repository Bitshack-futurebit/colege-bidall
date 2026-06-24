<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Percentage Fee — PARKED (free standalone product)
    |--------------------------------------------------------------------------
    |
    | The 1% commission the platform deducts from the auctioneer on each sale.
    | Set to 0 for the free standalone product: every close path computes a 0
    | fee and its `if ($platformFee > 0)` guard skips the credit deduction, so
    | no platform commission is ever charged. Restore to 1 to re-enable.
    |
    */

    'platform_percentage_fee' => 0,

    /*
    |--------------------------------------------------------------------------
    | Lot Closing Stagger
    |--------------------------------------------------------------------------
    |
    | The number of seconds between each lot's closing time. Lots close in
    | sequence starting from the auction end time, with each subsequent lot
    | closing this many seconds after the previous one.
    |
    | Example: If auction ends at 14:00:00 and gap is 7 seconds:
    |   - Lot 1 closes at 14:00:00
    |   - Lot 2 closes at 14:00:07
    |   - Lot 3 closes at 14:00:14
    |   - etc.
    |
    */

    'lot_gap_seconds' => env('AUCTION_LOT_GAP_SECONDS', 7),

    /*
    |--------------------------------------------------------------------------
    | Soft Close (Anti-Sniping) Settings
    |--------------------------------------------------------------------------
    |
    | Prevents last-second bid sniping by extending the lot closing time
    | when bids are placed near the end.
    |
    | soft_close_time: Number of seconds before closing when soft close starts
    | soft_close_extension: Number of seconds to extend when bid is placed
    |
    | Example with current settings (120 seconds each):
    |   - Lot scheduled to close at 14:00:00
    |   - Soft close period: 13:58:00 - 14:00:00 (last 2 minutes)
    |   - Bid placed at 13:59:30 → Extends closing to 14:01:30 (+ 2 minutes)
    |   - Another bid at 14:01:20 → Extends closing to 14:03:20 (+ 2 minutes)
    |   - Continues until no bids in final 2 minutes
    |
    */

    'soft_close_time' => env('AUCTION_SOFT_CLOSE_TIME', 120),
    'soft_close_extension' => env('AUCTION_SOFT_CLOSE_EXTENSION', 120),

    /*
    |--------------------------------------------------------------------------
    | Polling Interval
    |--------------------------------------------------------------------------
    |
    | How often (in milliseconds) the lot detail page refreshes to check
    | for new bids and time remaining updates.
    |
    */

    'polling_interval' => env('AUCTION_POLLING_INTERVAL', 3000),

];
