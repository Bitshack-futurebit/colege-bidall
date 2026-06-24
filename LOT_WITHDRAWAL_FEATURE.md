# Lot Withdrawal Feature

## Overview

Implemented a comprehensive lot withdrawal system that prevents deletion of lots once auction is scheduled, while allowing auctioneers to withdraw lots before the auction goes live. Withdrawn lots remain **visible to all users for transparency**, but are clearly marked and cannot be bid on.

## Business Rules

### Deletion Rules
- **Can DELETE lots**: Only when auction status is `draft`
- **Cannot DELETE lots**: When auction status is `upcoming`, `live`, or `ended`
- **Reason**: Once credits are deducted (auction goes to upcoming), lots should be preserved for accounting

### Withdrawal Rules
- **Can WITHDRAW lots**: When auction status is `draft` or `upcoming`
- **Cannot WITHDRAW lots**: When auction status is `live` or `ended`
- **Fee Policy**: Lot fees are NON-REFUNDABLE once auction is scheduled (status = upcoming)
- **Withdrawn lots**:
  - Do NOT appear in public auction views
  - Do NOT go live when auction starts
  - Are visible to auctioneer (greyed out with "Withdrawn" badge)
  - Preserve record for accounting and audit trail

## Database Changes

### Migration: `2026_02_12_000001_add_withdrawal_to_lots_table.php`

Added to `lots` table:
- `withdrawn_at` (timestamp, nullable) - When lot was withdrawn
- `withdrawal_reason` (text, nullable) - Optional reason for withdrawal

## Model Updates

### Lot Model (`app/Models/Lot.php`)

**New Methods:**
- `isWithdrawn()` - Check if lot is withdrawn
- `canBeWithdrawn()` - Check if lot can be withdrawn (auction must be draft/upcoming)
- `withdraw($reason)` - Withdraw the lot

**New Scopes:**
- `active()` - Get only non-withdrawn lots
- `withdrawn()` - Get only withdrawn lots

## Controller Updates

### LotController (`app/Http/Controllers/LotController.php`)

**Modified `destroy()` method:**
- Added check: Can only delete when auction status is `draft`
- Returns error message suggesting "Withdraw" for scheduled auctions

**New `withdraw()` method:**
- Validates auction is in draft/upcoming status
- Records withdrawal timestamp and optional reason
- Shows warning about non-refundable fees

## Route Updates

### web.php

Added route:
```php
Route::post('/auctions/{auction}/lots/{lot}/withdraw', [LotController::class, 'withdraw'])
    ->name('seller.auctions.lots.withdraw');
```

## View Updates

### Seller Auction Show (`resources/views/seller/auctions/show.blade.php`)

**Lot Cards:**
- Show "Delete" button only when auction is `draft`
- Show "Withdraw" button when auction is `draft` or `upcoming`
- Show "Withdrawn [date]" badge for withdrawn lots
- Greyed out styling for withdrawn lots (opacity-60)

**Withdraw Modal:**
- Clean modal interface with reason field
- Warning about non-refundable fees
- Confirmation before withdrawal

### Public Auction Show (`resources/views/auctions/show.blade.php`)

**Lot Cards:**
- Withdrawn lots shown with 75% opacity
- Image displayed in greyscale for withdrawn lots
- "Withdrawn" badge (grey) prominently displayed
- Button shows "View Details" instead of "Bid Now"
- All lot information still visible

### Lot Detail Page (`resources/views/lots/show.blade.php`)

**Withdrawn Lot Display:**
- Large "Withdrawn" badge in header
- Prominent notice box with:
  - Withdrawal date and time
  - Withdrawal reason (if provided)
  - Clear explanation that bidding is not available
- Bidding interface completely hidden (`@if(!$lot->isWithdrawn())`)
- Lot details and images still visible
- Watchlist functionality still available

### Lot Edit Page (`resources/views/seller/lots/edit.blade.php`)

**Delete Section (Draft Only):**
- Shows delete option only when auction is draft
- Full warning about permanent deletion

**Withdraw Section (Draft/Upcoming):**
- Expandable form with reason field
- Clear warning about non-refundable fees
- Confirmation required

**Withdrawn Display:**
- Shows withdrawal date and reason
- Prevents further editing

## Public View Display

### AuctionController (`app/Http/Controllers/AuctionController.php`)

**Modified `show()` method:**
- Everyone sees ALL lots (including withdrawn) for transparency
- No filtering of withdrawn lots from public view
- Withdrawn lots displayed with visual indicators

## Bidding Protection

### Lot Model (`app/Models/Lot.php`)

**Modified `placeBid()` method:**
- Added validation check: `if ($this->isWithdrawn())`
- Throws exception: "This lot has been withdrawn and is no longer available for bidding"
- Prevents any bid attempts on withdrawn lots via API or direct access
- Backend enforcement even if UI is bypassed

## Automation Updates

### Auction Model (`app/Models/Auction.php`)

**Modified `goLive()` method:**
- Only sets active (non-withdrawn) lots to 'live' status
- Withdrawn lots remain in their current status

### UpdateAuctionStatuses Command

**Modified scheduled command:**
- Only transitions active (non-withdrawn) lots when auction goes live
- Withdrawn lots are skipped and never made public

## Transparency & Trust

### Why Withdrawn Lots Are Visible

**Transparency Benefits:**
- Bidders can see the complete auction catalog
- Builds trust by not hiding information
- Bidders understand if lot count changes
- Can see withdrawal reasons (e.g., "Item damaged", "Vendor withdrew")
- Reduces confusion and support questions
- Professional, honest approach

**Still Protected:**
- Cannot place bids on withdrawn lots
- Bidding interface completely hidden
- Backend validation prevents any bid attempts
- Lots never go live when auction starts
- Clear visual indicators (badge, greyscale, notice box)

### Visual Indicators

**Lot Grid (Public View):**
- 75% opacity on card
- Greyscale filter on image
- Grey "Withdrawn" badge
- "View Details" button (not "Bid Now")

**Lot Detail Page:**
- Prominent "Withdrawn" badge in header
- Notice box with withdrawal date and reason
- No bidding interface shown
- All other details still visible

## User Experience

### For Auctioneers

**Before Auction is Scheduled (Draft):**
- ✅ Can delete lots freely (no fees charged yet)
- ✅ Can withdraw lots
- 💰 No credits charged

**After Auction is Scheduled (Upcoming):**
- ❌ Cannot delete lots
- ✅ Can withdraw lots
- ⚠️ Fees already paid, non-refundable
- 📊 Withdrawn lots preserved for records

**After Auction Goes Live:**
- ❌ Cannot delete lots
- ❌ Cannot withdraw lots
- 🔒 All lots locked

### For Bidders

- Withdrawn lots are **visible** for transparency
- Shown with "Withdrawn" badge and greyed out image
- Cannot bid on withdrawn lots (interface hidden)
- Can view lot details and see withdrawal reason
- Clear indication of withdrawal date
- Can still add to watchlist
- Professional, transparent auction experience

## Testing Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Create auction in draft status
- [ ] Add lots and verify DELETE button works
- [ ] Click "Go Live" to move to upcoming status
- [ ] Verify DELETE button disappears
- [ ] Verify WITHDRAW button appears
- [ ] Withdraw a lot with reason
- [ ] **Seller View**: Verify withdrawn lot shows greyed out with "Withdrawn" badge
- [ ] **Public View**: View auction as guest/bidder
  - [ ] Verify withdrawn lot is VISIBLE in lot grid
  - [ ] Verify lot has greyscale image and "Withdrawn" badge
  - [ ] Verify button says "View Details" not "Bid Now"
- [ ] Click on withdrawn lot
  - [ ] Verify "Lot Withdrawn" notice box is shown
  - [ ] Verify withdrawal date and reason are displayed
  - [ ] Verify bidding interface is completely hidden
  - [ ] Verify lot details and images are still visible
- [ ] Try to bid via API (should fail with error message)
- [ ] Start auction (move to live status)
  - [ ] Verify WITHDRAW button disappears for remaining lots
  - [ ] Verify withdrawn lot never went live (status unchanged)
  - [ ] Verify active lots went live correctly

## Configuration

No additional configuration needed. Feature works with existing:
- Credit system
- Lot fees (basic/pro/premium)
- Auction status transitions
- Laravel scheduler

## Future Enhancements (Optional)

1. **Un-withdraw Feature**: Allow re-activating withdrawn lots (only when auction is still draft/upcoming)
2. **Withdrawal Reports**: Admin dashboard showing withdrawal statistics
3. **Auto-withdraw**: If auctioneer credit balance goes negative, auto-withdraw lots to bring back to positive
4. **Email Notifications**: Notify auctioneer when lot is withdrawn
5. **Withdrawal History**: Track all withdrawal actions with timestamps

## Support

For questions or issues with the withdrawal feature, check:
- Controller: `app/Http/Controllers/LotController.php`
- Model methods: `app/Models/Lot.php`
- Views: `resources/views/seller/auctions/show.blade.php` and `resources/views/seller/lots/edit.blade.php`
