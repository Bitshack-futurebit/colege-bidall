<?php

return [
    // Flat starting bid for every community lot (ZAR)
    'starting_bid' => env('COMMUNITY_STARTING_BID', 20),

    // Platform commission on sold community lots (%)
    'commission_percent' => env('COMMUNITY_COMMISSION_PERCENT', 5),

    // Decline (lowball protection) limits
    'pilot_decline_limit_30d' => env('COMMUNITY_PILOT_DECLINE_LIMIT', 2),
    'standard_decline_limit_30d' => env('COMMUNITY_STANDARD_DECLINE_LIMIT', 1),
    'permanent_ban_threshold_365d' => env('COMMUNITY_PERMANENT_BAN_THRESHOLD', 3),
    'suspension_days_on_breach' => env('COMMUNITY_SUSPENSION_DAYS', 30),

    // First lot protection — new sellers (below this many confirmed sales)
    // have their first decline not counted against them.
    'first_lot_protection_sales_threshold' => 1,

    // Viability gate — auction won't go live if under these minimums
    'min_lots_for_viability' => env('COMMUNITY_MIN_LOTS', 5),
    'min_bidders_for_viability' => env('COMMUNITY_MIN_BIDDERS', 20),

    // Per-seller per-region listing cap per week
    'listing_limit_per_week' => env('COMMUNITY_LISTING_LIMIT_WEEK', 3),

    // Seller-facing hard limit (bidders can change region but with cooldown)
    'region_change_cooldown_days' => 30,

    // Per-lot maximum live duration (seconds)
    'lot_max_duration_seconds' => env('COMMUNITY_LOT_MAX_DURATION', 240),

    // Seller confirmation window (hours) after lot closes
    'confirmation_window_hours' => env('COMMUNITY_CONFIRMATION_HOURS', 24),

    // Listing form validation minimums
    'min_description_length' => 20,
    'min_images' => 1,

    // Lineup lock is this many hours before go-live
    'lineup_lock_hours_before_live' => 6,

    // Safety cap: force-end an auction if the live lot chain hasn't closed by this many hours
    'auction_safety_cap_hours' => 12,

    // Pilot region slug (for landing page redirect if user has no region set)
    'pilot_region_slug' => env('COMMUNITY_PILOT_REGION', 'lower-south-coast'),

    // Agent commission ladder — applied per community, per calendar month.
    // Tier 1 = first R1000 of platform commission split 50/50.
    // Tier 2 = everything above that, split 70/30 in agent's favour.
    'commission_tier1_cap'             => env('COMMUNITY_TIER1_CAP', 1000),
    'commission_tier1_platform_pct'    => env('COMMUNITY_TIER1_PLATFORM_PCT', 50),
    'commission_tier2_platform_pct'    => env('COMMUNITY_TIER2_PLATFORM_PCT', 30),

    // Seller fee debt gates — coupled: BOTH must trip to block listings.
    // Block only when debt > threshold AND at least one line is older than the
    // age cutoff. Lets big sellers carry recent debt within the grace window;
    // never penalises trivial debt.
    'fee_debt_block_threshold' => env('COMMUNITY_FEE_DEBT_BLOCK', 50),
    'fee_debt_age_block_days'  => env('COMMUNITY_FEE_DEBT_AGE_DAYS', 30),

    // Agent payout policy
    'agent_payout_min'         => env('COMMUNITY_AGENT_PAYOUT_MIN', 500),

    // Buyer strikes
    'buyer_strike_window_months' => 6,
    'buyer_strike_block_threshold' => 2,
];
