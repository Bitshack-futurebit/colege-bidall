# Lot Relisting System - Design Document

**Date:** 2026-02-21
**Status:** Approved for Future Implementation
**Decision Makers:** User + Claude

---

## Table of Contents

1. [Problem Statement](#problem-statement)
2. [Requirements](#requirements)
3. [Solution Overview](#solution-overview)
4. [Core Business Rules](#core-business-rules)
5. [Image Handling](#image-handling)
6. [Lot Fee Logic](#lot-fee-logic)
7. [Technical Specifications](#technical-specifications)
8. [User Experience](#user-experience)
9. [Implementation Roadmap](#implementation-roadmap)
10. [Edge Cases & Decisions](#edge-cases--decisions)

---

## Problem Statement

When auctions end, lots can be **unsold** for two reasons:
1. **No bids received** - Market conditions, timing, exposure issues
2. **Reserve not met** - Bids placed but didn't reach auctioneer's reserve price

Currently, unsold lots remain in the ended auction with no way to relist them in future auctions. Auctioneers must manually recreate lots, losing all data and images.

**Goal:** Provide a streamlined way to relist unsold lots while:
- Maintaining data integrity
- Being fair to auctioneers
- Generating platform revenue
- Encouraging quality listings

---

## Requirements

### Functional Requirements

1. ✅ **Relist unsold lots** to new auctions
2. ✅ **Lots stay with original auctioneer** (no transfer between auctioneers)
3. ✅ **Full editability** when relisting:
   - Add/remove images
   - Change reserve price
   - Remove reserve completely
   - Edit description, title, starting bid, etc.
4. ✅ **Preserve history** - Track which lots are relisted from which
5. ✅ **Image handling** - Images copied (not shared) between listings
6. ✅ **Fair fee structure** - Based on why lot didn't sell

### Non-Functional Requirements

- Simple UX (no complex workflows)
- Clear business rules (no debate needed)
- Analytics-friendly (track relist patterns)
- Performance-conscious (don't slow down system)

---

## Solution Overview

### Approach: Clone Lot on Relist with Linking

When relisting:
1. **Original lot** stays in original auction as `unsold` (preserved for history)
2. **New lot** is created by copying all data from original
3. **Images are copied** (not shared) - each lot has independent images
4. **Link maintained** via `relisted_from_lot_id` and `original_lot_id` fields
5. **New lot** can be fully edited before or after relisting
6. **Fee logic** depends on why original didn't sell

**Example:**
```
Auction #1 (ended) → Lot #100 (unsold, no bids)
                       ↓ (relist)
Auction #2 (upcoming) → Lot #150 (new lot, copied from #100)
                        - relisted_from_lot_id = 100
                        - original_lot_id = 100
                        - is_free_relist = true
                        - Images copied to new files
```

---

## Core Business Rules

### Rule 1: No Bids = FREE Relist ✅

**Condition:** Lot received zero bids when auction ended

**Logic:**
- Market conditions/timing likely the cause
- Not auctioneer's fault
- Give free retry

**Fee:** R0 (skip lot fee when auction goes live)

**Unlimited:** Can relist multiple times for free as long as it keeps getting no bids

**Badge:** 🎁 **FREE RELIST** - No bids received

---

### Rule 2: Reserve Not Met = PAID Relist ✅

**Condition:** Lot received bids but didn't reach reserve price

**Logic:**
- People were interested (they bid!)
- Reserve was too high (auctioneer's decision)
- Should pay for another attempt

**Fee:** Normal lot fee based on image tier (R1/R5/R20)

**Badge:** 📊 **Reserve not met** - Highest bid: RX,XXX

---

## Image Handling

### Decision: Copy Images (Option A) ✅

**How it works:**
1. Original lot has images: `lot_100_image_1.webp`, `lot_100_image_2.webp`
2. When relisting, **copy images** to new files: `lot_150_image_1.webp`, `lot_150_image_2.webp`
3. Each lot has independent image files

**Why copying instead of sharing:**

| Scenario | Shared Images (Option B) | Copied Images (Option A) ✅ |
|----------|-------------------------|---------------------------|
| Delete original lot | ❌ Relisted lot breaks | ✅ Relisted lot unaffected |
| Edit original lot images | ❌ Relisted lot affected | ✅ Independent |
| Auto-cleanup (30 days) | ❌ Deletes images from active relisted lot | ✅ Only original lot cleaned |
| Edit relisted lot images | ❌ Original lot affected | ✅ Independent |
| Storage cost | Lower | Slightly higher (acceptable) |
| Data integrity | Poor | Excellent |
| Complexity | High (reference counting) | Low (simple) |

**Storage Cost (acceptable):**
- Typical lot: 5 images × 115KB = 575KB
- Relisted lot: Another 575KB
- **Total:** 1.15MB for both lots
- WebP compression = 98% reduction from originals
- Modern storage is cheap

**Verdict:** Copy images. Data integrity > small storage cost.

---

## Lot Fee Logic

### Current System (Reminder)

- **Basic tier** (1 image): R1
- **Pro tier** (2-5 images): R5
- **Premium tier** (6+ images): R20
- **Charged when:** Auction goes **LIVE** (not when created)
- **Non-refundable** once auction reaches upcoming/live status

### Relisting Fee Logic

```
IF lot has 0 bids:
    ✅ FREE RELIST
    - Skip lot fee entirely
    - Can upgrade tier (add more images) - still free
    - Unlimited free relists as long as no bids

ELSE IF lot has bids but reserve not met:
    💰 PAID RELIST
    - Charge normal lot fee based on image tier
    - Pay when relisted auction goes live
```

### Edge Cases - Decisions Made

#### 1. Free Relist + Upgrade Tier (Pro → Premium)

**Scenario:** No bids, eligible for free relist, but auctioneer adds more images

**Decision:** ✅ **Still FREE**
- Already earned the free relist
- Let them improve the listing
- Platform makes money on the sale (1% commission), not listing fees

#### 2. Multiple Free Relists (No Bids Every Time)

**Scenario:** Relist 3 times, still no bids each time

**Decision:** ✅ **Unlimited FREE relists**
- Keep allowing until it gets bids or they give up
- Not the auctioneer's fault if market isn't interested
- Eventually they'll remove it or find a buyer

#### 3. Single Low Bid (R1 on R10,000 reserve)

**Scenario:** One silly/placeholder bid, reserve not met

**Decision:** ✅ **Paid relist** (any bid counts)
- Stick to simple rule: bids > 0 = paid relist
- Prevents gaming the system
- No complex "minimum threshold" logic

---

## Technical Specifications

### Database Changes

#### New Fields on `lots` Table

```php
Schema::table('lots', function (Blueprint $table) {
    // Relisting tracking
    $table->unsignedBigInteger('relisted_from_lot_id')->nullable()
          ->comment('Direct parent lot (if this is a relist)');

    $table->unsignedBigInteger('original_lot_id')->nullable()
          ->comment('First lot in the chain');

    $table->integer('relist_count')->default(0)
          ->comment('How many times this item has been relisted');

    // Fee eligibility
    $table->boolean('free_relist_eligible')->default(false)
          ->comment('True if lot had 0 bids (qualifies for free relist)');

    $table->boolean('is_free_relist')->default(false)
          ->comment('True if this lot is using a free relist');

    // Indexes
    $table->index('relisted_from_lot_id');
    $table->index('original_lot_id');
    $table->index('free_relist_eligible');
});
```

### Model Methods (Lot.php)

```php
/**
 * Check if lot is eligible for free relist
 */
public function isFreeRelistEligible(): bool
{
    return $this->status === 'unsold' && $this->free_relist_eligible;
}

/**
 * Check if lot can be relisted
 */
public function canBeRelisted(): bool
{
    return $this->status === 'unsold' &&
           in_array($this->auction->status, ['ended']);
}

/**
 * Relist this lot to a new auction
 */
public function relistTo(Auction $targetAuction): Lot
{
    // Create new lot by cloning
    $newLot = $this->replicate();

    // Set new auction
    $newLot->event_id = $targetAuction->id;
    $newLot->status = 'pending';

    // Set relisting metadata
    $newLot->relisted_from_lot_id = $this->id;
    $newLot->original_lot_id = $this->original_lot_id ?? $this->id;
    $newLot->relist_count = ($this->relist_count ?? 0) + 1;
    $newLot->is_free_relist = $this->free_relist_eligible;

    // Clear auction-specific data
    $newLot->winner_id = null;
    $newLot->final_price = null;
    $newLot->closed_at = null;

    $newLot->save();

    // Copy images
    $this->copyImagesToLot($newLot);

    return $newLot;
}

/**
 * Copy all images to another lot
 */
protected function copyImagesToLot(Lot $targetLot): void
{
    foreach ($this->images as $image) {
        // Copy physical files
        $newOptimizedPath = str_replace(
            "lot_{$this->id}_",
            "lot_{$targetLot->id}_",
            $image->optimized_path
        );
        $newThumbnailPath = str_replace(
            "lot_{$this->id}_",
            "lot_{$targetLot->id}_",
            $image->thumbnail_path
        );

        Storage::disk('public')->copy($image->optimized_path, $newOptimizedPath);
        Storage::disk('public')->copy($image->thumbnail_path, $newThumbnailPath);

        // Create new image record
        LotImage::create([
            'lot_id' => $targetLot->id,
            'optimized_path' => $newOptimizedPath,
            'thumbnail_path' => $newThumbnailPath,
            'original_filename' => $image->original_filename,
            'order' => $image->order,
        ]);
    }
}

/**
 * Relationships
 */
public function relistedFrom()
{
    return $this->belongsTo(Lot::class, 'relisted_from_lot_id');
}

public function relistedTo()
{
    return $this->hasMany(Lot::class, 'relisted_from_lot_id');
}

public function originalLot()
{
    return $this->belongsTo(Lot::class, 'original_lot_id');
}
```

### Auction Ending Logic Update

```php
// In UpdateAuctionStatuses command or Auction model

foreach ($auction->lots()->whereNull('withdrawn_at')->get() as $lot) {
    if ($lot->hasWinningBid()) {
        $lot->status = 'sold';
        $lot->free_relist_eligible = false;
    } else {
        $lot->status = 'unsold';

        // Check if lot received any bids
        $hadBids = $lot->bids()->count() > 0;
        $lot->free_relist_eligible = !$hadBids; // No bids = free relist
    }

    $lot->save();
}
```

### Lot Fee Deduction Logic Update

```php
// In AuctionController when auction goes live

foreach ($auction->lots as $lot) {
    // Skip fee for free relists
    if ($lot->is_free_relist) {
        CreditTransaction::create([
            'auctioneer_id' => $auctioneer->id,
            'lot_id' => $lot->id,
            'type' => 'lot_live',
            'amount' => 0,
            'balance_after' => $auctioneer->credit_balance,
            'description' => 'Free relist - lot had no bids in previous auction',
        ]);
        continue;
    }

    // Normal fee deduction
    $cost = $auctioneer->calculateLotCost($lot->image_tier);
    $auctioneer->deductCredits($cost, 'lot_live', $lot->id,
        "Lot fee for {$lot->title}");
}
```

---

## User Experience

### Auctioneer Dashboard - New Section

**Location:** `/seller/unsold-lots`

**Page Elements:**
- List of all unsold lots from ended auctions
- Filterable by: auction, date, free relist eligible
- Sortable by: date, bids received, highest bid

**Lot Card Display:**

```
┌─────────────────────────────────────────┐
│ [Image] Vintage Clock                   │
│                                         │
│ Auction: Spring Sale 2026 (Ended)      │
│ Status: 🎁 FREE RELIST - No bids       │  ← Badge
│                                         │
│ [Relist to Existing Auction ▼]         │  ← Dropdown
│ [Create New Auction & Relist]          │
└─────────────────────────────────────────┘

OR

┌─────────────────────────────────────────┐
│ [Image] Antique Vase                    │
│                                         │
│ Auction: Winter Sale 2025 (Ended)      │
│ Status: 📊 Reserve not met             │  ← Badge
│ Reserve: R5,000 | Highest bid: R4,200  │
│ Relist cost: R5 (Pro tier)             │  ← Cost preview
│                                         │
│ [Relist to Existing Auction ▼]         │
│ [Create New Auction & Relist]          │
└─────────────────────────────────────────┘
```

### Relist Flow

**Option A: Relist to Existing Draft Auction**

1. Click "Relist to Existing Auction" dropdown
2. Select from list of draft/upcoming auctions
3. Confirmation modal:
   ```
   Relist "Vintage Clock" to "Summer Sale 2026"?

   ✓ Images will be copied
   ✓ You can edit this lot after relisting
   ✓ Cost: FREE (no bids received)

   [Cancel] [Confirm Relist]
   ```
4. Redirect to lot edit page (can edit immediately)

**Option B: Create New Auction & Relist**

1. Click "Create New Auction & Relist"
2. Quick auction creation form (title, dates)
3. Creates auction + relists lot
4. Redirect to lot edit page

### Quick Stats (Dashboard Card)

```
┌─────────────────────────────────┐
│ Unsold Lots                     │
│                                 │
│ 🎁 Free Relists Available: 8   │
│ 📊 Reserve Not Met: 3           │
│                                 │
│ [View All Unsold Lots →]       │
└─────────────────────────────────┘
```

### Badges & Visual Indicators

**Free Relist Badge:**
- Color: Green/Gold
- Icon: 🎁 Gift
- Text: "FREE RELIST"
- Subtext: "No bids received"

**Paid Relist Badge:**
- Color: Blue
- Icon: 📊 Chart
- Text: "Reserve not met"
- Subtext: "Highest bid: RX,XXX | Relist cost: RX"

**Relist History Badge (on lots):**
- Small badge showing "Relisted 2x"
- Click to view history chain

---

## Implementation Roadmap

### Phase 1: Database & Models
- [ ] Create migration for new lot fields
- [ ] Update Lot model with relationships
- [ ] Add `relistTo()` method
- [ ] Add `copyImagesToLot()` method
- [ ] Update auction ending logic to set `free_relist_eligible`

### Phase 2: Fee Logic
- [ ] Update lot fee deduction to skip free relists
- [ ] Add transaction record for free relists (R0 with description)
- [ ] Test fee logic with both scenarios

### Phase 3: UI - Unsold Lots Page
- [ ] Create `/seller/unsold-lots` route
- [ ] Create controller method
- [ ] Create blade view with lot cards
- [ ] Add badges (free vs paid relist)
- [ ] Add filters and sorting

### Phase 4: Relist Functionality
- [ ] Create relist route/controller method
- [ ] Build dropdown for existing auctions
- [ ] Build "create new auction" flow
- [ ] Add confirmation modal
- [ ] Redirect to lot edit after relist

### Phase 5: Dashboard Integration
- [ ] Add "Unsold Lots" stat card to auctioneer dashboard
- [ ] Add navigation link to unsold lots page
- [ ] Add "Unsold" count to navigation badge

### Phase 6: Analytics (Future, Optional)
- [ ] Track relist count per lot
- [ ] Track free vs paid relists
- [ ] Average relists before sale
- [ ] Most relisted categories
- **Only if doesn't impact current UX**

### Phase 7: Testing
- [ ] Test free relist flow (no bids)
- [ ] Test paid relist flow (reserve not met)
- [ ] Test image copying
- [ ] Test multiple relists
- [ ] Test tier upgrades on free relist
- [ ] Test fee deduction

---

## Edge Cases & Decisions

### 1. Free Relist + Tier Upgrade

**Q:** No bids, free relist, but add more images (Pro → Premium)?

**A:** ✅ Still FREE - Already earned free relist, let them improve listing

**Reason:** Platform makes money on sale (commission), not listing fees

---

### 2. Unlimited Free Relists

**Q:** No bids 3 times in a row - still free every time?

**A:** ✅ Unlimited FREE - Keep allowing until it gets bids or they give up

**Reason:** Not auctioneer's fault if market isn't interested. Eventually they'll remove it.

---

### 3. Single Low Bid (Gaming)

**Q:** Someone bids R1 on R10,000 reserve - paid relist?

**A:** ✅ PAID - Any bid counts, no exceptions

**Reason:** Simple rule prevents gaming. Auctioneer set reserve, got a bid, didn't meet it.

---

### 4. Reserve Set to R0

**Q:** No reserve, item sells to any bidder - can it be "unsold"?

**A:** ❌ No - If reserve = R0 or null, any bid wins automatically

**Result:** Encourages auctioneers to use realistic reserves or remove them

---

### 5. Editing After Relist

**Q:** Can auctioneer edit relisted lot before auction goes live?

**A:** ✅ Yes - Full editability (images, reserve, description, everything)

**Reason:** That's the whole point - improve listing for better chance of sale

---

### 6. Relist Chain History

**Q:** Should we show lot's full relist history?

**A:** ✅ Nice to have, not critical for MVP

**Implementation:**
- Add "View History" link on lot
- Shows chain: Auction 1 (no bids) → Auction 2 (no bids) → Auction 3 (current)

---

### 7. Bulk Relist

**Q:** Select multiple unsold lots, relist all at once?

**A:** ✅ Good feature for future, not MVP

**Implementation:**
- Checkboxes on unsold lots page
- "Relist Selected (5)" button
- Choose target auction
- All relisted in batch

---

### 8. Analytics Impact on UX

**Q:** Add detailed relist tracking?

**A:** ✅ Only if doesn't slow down or complicate current system

**Approach:**
- Add fields now (relist_count, etc.)
- Build analytics dashboard later
- Don't let reporting requirements complicate core UX

---

## Business Logic Summary

### Platform Revenue Philosophy

> **"Platform makes money on the sale (1% commission), not the listing fees. Lot fees just cover costs. Happy auctioneers = more listings = more sales = more revenue."**

**Implications:**
- Be generous with free relists
- Don't nickel-and-dime auctioneers
- Focus on commission from successful sales
- Encourage relisting until item sells

### Win-Win Outcomes

**For Auctioneers:**
- Fair system (objective rules)
- Free retry when market doesn't respond
- Can improve listings on relist
- Unlimited attempts if no bids

**For Platform:**
- Revenue from sales (commission)
- More lots listed = more auction activity
- Happy customers = retention
- Simple, defensible rules

**For Bidders:**
- More items available
- Better listings (auctioneers improve on relist)
- Realistic reserves (auctioneers learn from market)

---

## Success Metrics (Future)

**Measure these after implementation:**

1. **Relist Rate:** % of unsold lots that get relisted
2. **Free vs Paid:** Ratio of free relists to paid relists
3. **Success Rate:** % of relisted lots that eventually sell
4. **Relists to Sale:** Average number of relists before lot sells
5. **Reserve Adjustments:** % of relists where reserve is lowered
6. **Tier Changes:** % of relists where images are added/removed
7. **Auctioneer Satisfaction:** Survey auctioneers about relist feature

**Don't implement tracking if it impacts UX - can add later**

---

## Future Enhancements (Post-MVP)

### Enhancement 1: Relist Recommendations
- AI suggests when to relist (market trends)
- Suggests reserve price adjustments
- Suggests better categories/tags

### Enhancement 2: Bulk Operations
- Bulk relist multiple lots
- Bulk edit relisted lots
- Bulk remove from auction

### Enhancement 3: Market Insights
- Show similar items that sold
- Show optimal reserve price range
- Show best auction timing

### Enhancement 4: Auto-Relist
- Auctioneer sets rule: "Auto-relist to next auction if no bids"
- Automatic relisting without manual action
- Email notification when auto-relisted

### Enhancement 5: Relist Templates
- Save lot as template for future relisting
- Preset reserve, description, images
- Quick relist from template

**All future enhancements - implement only if requested and time permits**

---

## Approval & Next Steps

### ✅ Approved By: User
### ✅ Date: 2026-02-21
### ⏸️ Status: Ready for Implementation (awaiting go-ahead)

### When Ready to Implement:

1. User gives go-ahead
2. Follow implementation roadmap (Phase 1-7)
3. Test thoroughly
4. Deploy to production
5. Monitor success metrics
6. Iterate based on feedback

---

**End of Document**

_This document captures the complete design discussion and decisions for the Lot Relisting System. Reference this when implementation is approved._
