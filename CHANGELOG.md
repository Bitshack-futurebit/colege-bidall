# Changelog — BidAll Auction Platform

Full session history. See `CLAUDE.md` for current project state.

---

### 2026-02-18 Session - Accounting Page, Payment Flow & Sales Breakdown

**All payment and accounting flows verified working end-to-end.**

**1. PayFast Gateway Fee Now Captured**
- **Bug**: `handleWebhook()` never stored `amount_fee` from PayFast payload, so `gateway_fee` in `payment_data` was always 0
- **Fix**: Extract `amount_fee` (PayFast sends as negative e.g. "-9.20"), store as positive in `payment_data` alongside `amount_gross` and `amount_net`
- **File**: `app/Services/Payments/PayFastGateway.php`

**2. Sales Breakdown on Accounting Page**
- **Change**: Accounting page now has two card rows
- **Row 1**: Account Balance | Payout Ready | Fees Paid | Received
- **Row 2 (Sales Breakdown)**:
  - **Total Sales** — all sold lots, sum of `current_bid` (always accurate regardless of payment method)
  - **Platform Payments** — paid via PayFast (from `sales_records`)
  - **Non-Platform Payments** — difference (arranged directly with buyer)
  - Platform Payments + Non-Platform Payments = Total Sales
- **File**: `resources/views/seller/accounting.blade.php`, `app/Http/Controllers/AuctioneerController.php`

**3. Payout flow tested and working**
- Auctioneer requests payout via `/seller/payouts`
- Admin approves via `/admin/payouts`
- `credit_balance` deducted correctly, `CreditTransaction` with type `payout` created

---

### 2026-02-18 Session - Accounting Page & Payment Flow Fixes

**Three bugs fixed that were preventing the accounting page from reflecting correct values.**

**1. Carbon TypeError in SalesRecord (Critical — broke all lot payments)**
- **Bug**: `now()->addHours($fundsHoldHours)` — `config()` returns a string, Carbon now requires int
- **Symptom**: PayFast webhook fired successfully but crashed at `SalesRecord::createFromLotPayment()`, so no sale income was ever credited to the auctioneer
- **Fix**: Cast to int: `(int) config('platform.payout.funds_hold_hours', 48)`
- **File**: `app/Models/SalesRecord.php`

**2. Accounting Stats Reading Wrong Source**
- **Bug**: `total_fees_paid` and `total_commissions_paid` on the Auctioneer model are only populated via `SalesRecord` (when bidder pays). So lot fees and 1% commissions showed R0 until a payment was received.
- **Fix**: Read lot fees and commissions directly from `credit_transactions` ledger (always accurate):
  - Lot fees = `creditTransactions()->where('type','lot_live')->sum('amount')`
  - Commissions = `creditTransactions()->where('type','lot_close')->sum('amount')`
  - Total Sales = `salesRecords()->sum('sale_price')` (from completed payments)
- **File**: `app/Http/Controllers/AuctioneerController.php` — `accounting()` method

**3. View Cache**
- Blade compiled view was stale after controller changes
- Fix: `php artisan view:clear`

---

### 2026-02-18 Session - Auction Winner Summary Email

**One email per bidder per auction — sent after auction ends, not in real time.**

**Design Decisions:**
- No outbid notification — bidders check back themselves
- One summary email per winner per auction (not one per lot) — cleaner, less spam
- Sent within 5 minutes of auction ending via scheduler
- Respects `email_notifications` user preference
- `winner_emails_sent` flag on auction prevents duplicate sends

**Files Created/Changed:**
- `database/migrations/2026_02_18_200000_add_winner_emails_sent_to_events_table.php`
- `app/Models/Auction.php` — added `winner_emails_sent` to `$fillable` and casts
- `app/Mail/AuctionWinnerSummary.php` — Mailable class
- `resources/views/emails/auction-winner-summary.blade.php` — HTML email template
- `app/Console/Commands/SendAuctionSummaryEmails.php` — artisan command
- `routes/console.php` — registered `everyFiveMinutes()`
- `resources/views/lots/show.blade.php` — removed false "you'll be notified" promise

---

### 2026-02-18 Session - Lot Action Rules: Upcoming Lock & Draft-Only Delete

**Summary of Lot Action Availability by Status:**

| Action | Draft | Upcoming | Live | Ended |
|--------|-------|----------|------|-------|
| Edit lot details | ✅ | ❌ | ❌ | ❌ |
| Delete lot | ✅ | ❌ | ❌ | ❌ |
| Withdraw lot | ❌ | ✅ | ❌ | ❌ |

**Files Changed:**
- `app/Models/Lot.php` — `canBeWithdrawn()`: now `status === 'upcoming'` only
- `app/Http/Controllers/LotController.php` — `update()` blocks upcoming/live/ended
- `resources/views/seller/lots/edit.blade.php` — upcoming shows locked view + withdraw only; draft shows edit form + delete only
- `resources/views/seller/auctions/show.blade.php` — "Edit Lot" only in draft; "View / Withdraw" only in upcoming

---

### 2026-02-18 Session - Event→Auction Terminology Sweep (Missed Files)

Fixed remaining `App\Models\Event` references in:
- `app/Console/Commands/CleanupOldImages.php` — `->whereHas('event')` → `->whereHas('auction')`
- `database/seeders/DatabaseSeeder.php` — `use App\Models\Event` → `use App\Models\Auction`
- `database/seeders/DemoDataSeeder.php` — full rename + `->where('status', 'active')` → `'live'`

---

### 2026-02-18 Session - Auctioneer Dashboard Quick Actions Cleanup

- Removed "Buy Credits" quick action tile (redundant with dashboard credit card)
- Reordered tiles: Create Auction → Accounting → View Analytics → Manage Followers
- **File**: `resources/views/seller/dashboard.blade.php`

---

### 2026-02-18 Session - Unified Balance System & Withdrawal Rules

**1. SalesRecord Relationship Bug Fix**
- `$lot->event->auctioneer_id` → `$lot->auction->auctioneer_id`
- **File**: `app/Models/SalesRecord.php` lines 75 and 90

**2. Unified Credit Balance System**
- Sale income now flows into single `credit_balance` instead of separate `payout_balance`
- Migration: `2026_02_18_112031_add_sale_income_payout_to_credit_transactions.php`
- CreditTransaction types: `purchase`, `lot_creation`, `lot_live`, `lot_close`, `refund`, `adjustment`, `sale_income`, `payout`

**3. Withdrawal Minimum Balance Rule**
- Minimum withdrawal: R500 (`MINIMUM_PAYOUT`)
- Minimum balance remaining: R100 (`MINIMUM_BALANCE`)
- Formula: `available = credit_balance - pending_clearance(48hr) - R100_reserve`

---

### 2026-02-17 Session - Bug Fixes, Won Lots UX Overhaul & Dashboard Navigation

**Key fixes:**
1. `Auction::end()` — changed `'platform_commission'` → `'lot_close'` (valid ENUM)
2. `deductCredits()` — exemption list changed to `['lot_close', 'adjustment']`
3. Won Lots page — rebuilt as compact filterable list with pagination (25/page)
4. Bidder Dashboard — added "Won Lots" quick action card (5th card)
5. Admin Credits — changed `'admin_credit'` → `'adjustment'` (valid ENUM)
6. ActivityLog — switched all `create()` calls to `ActivityLog::log()` static helper
7. Live Bidding fixes — `NaN` fallback, `minimumBid` getter, `catch(\Throwable)`, `x-show` for winning badge
8. Watchlist `minBid` getter fix — first bid uses `startingBid` not `currentBid + increment`
9. Won Lots payment section on lot detail page
10. PayFast webhook URL HTTPS fix — `absoluteUrl()` helper in `PayFastGateway`
11. PayFast sandbox signature validation — skip when `PAYFAST_SANDBOX=true`
12. Scheduler — changed `$lot->update(['status'=>'sold'])` to `$lot->close()` for commission deduction

---

### 2026-02-17 Session - Directory Cleanup, Deployment Guide, Environment Fixes & Payment Bugs

1. **Bid Increment Bug** — empty field no longer silently bids; shows "Please enter a valid bid amount"
2. **Directory Cleanup** — deleted 224MB of junk files, debug views, debug routes, 9 redundant docs
3. **Production Deployment Guide** — rewritten for Xneelo shared hosting
4. **Environment File Fixes**:
   - `SESSION_DRIVER`: `database` → `file`
   - `QUEUE_CONNECTION`: `database` → `sync`
   - `MAIL_ENCRYPTION`: `tls` → `ssl`
   - Pricing vars: `IMAGE_TIER_*_LIMIT` → `TIER_*_LIMIT`
5. **Won Lots ParseError** — removed unclosed `@if($needsPaymentSelection)` wrapper
6. **Payment Return Page** — added `lot_payment` case; auto-refresh on won lots page when payment pending

---

### 2026-02-12 Session - Image System Overhaul & Unlimited Images

1. Fixed image storage to use `public` disk
2. Removed 20-image limit — unlimited images, auto-tier pricing
3. Image carousel on lot cards (Alpine.js)
4. Minimum 1 image requirement enforced (frontend + backend)
5. Config path fixes (`platform.lot_fees` → `platform.pricing.lot_fees`)
6. Alpine.js double initialization fix
7. `'lot_fee'` → `'lot_live'` ENUM fix in AuctionController
8. Config path fix in `Auctioneer::calculateLotCost()`
9. `formatCurrency` helper fix — numeric strings no longer treated as config keys
10. Analytics page — `$lot->event` → `$lot->auction`
11. Seller → Auctioneer terminology in UI (routes unchanged)
12. Scheduler lot status fix — `'active'` → `'live'`

---

### 2026-02-12 Session - Lot Withdrawal Feature

- Delete only in `draft`; Withdraw only in `upcoming`
- Withdrawn lots visible with badge, cannot be bid on
- Auto-renumbering when lot deleted in draft
- Database: `withdrawn_at`, `withdrawal_reason` columns on lots
- Route: `POST /seller/auctions/{auction}/lots/{lot}/withdraw`

---

### 2026-02-12 Session - Lot Creation & Image Processing

- Fixed Intervention Image v3 compatibility (`ImageManager` + `Gd\Driver`)
- Single-screen lot creation with image upload
- Fixed `name="tier"` → `name="image_tier"` form field mismatch

---

### 2026-02-12 Session - Event → Auction Terminology Refactor

Full rename of Event → Auction throughout app. Database tables (`events`, `event_id`) unchanged.

---

### 2026-02-11 Session - Terminology Refactor

Initial Event → Auction documentation and model rename.

---

### 2026-02-11 Session - Payout System & Payment Method Selection

1. Complete auctioneer payout system (`sales_records`, `payouts` tables)
2. PayFast 48-hour hold protection (`funds_available_at`)
3. Payment method selection for won lots (Pay Now vs Arrange Collection)
4. Auction archival system (`auctions:archive-old` command, runs daily 3AM)

---

### 2026-02-11 Session - PayFast & Dashboard UX

1. PayFast sandbox configured and tested end-to-end
2. Quick action cards moved to top of all dashboards
3. Back button navigation on all sub-pages
4. Follower management streamlined
5. Admin auctioneers filter fix

---

### 2026-02-11 Session - Bidding & Followers

1. Bidding API fixed — `auth:sanctum` → `['web', 'auth']` middleware
2. Prevent self-outbidding
3. Winning bidder trophy badge
4. Watchlist page redesign — quick bid, auto-refresh, status indicators
5. Follower/following counts integrated into dashboards

---

### 2026-02-10 Session

1. Follow Auctioneers feature (`auctioneer_followers` table)
2. Auctioneer-centric navigation — auction cards → auctioneer profile
3. Optional auction registration (`requires_registration` field)
4. Deposit system config fix
5. Admin route name fixes
6. Admin navigation back buttons
7. Admin pricing controls (free accounts, custom flat fee)
8. Admin credit management
9. Profile data completion (address, city, province)
10. Auctioneer profile + banner images
11. Public auctioneer profile redesign

---

### Previous Updates

- Auctioneer Dashboard — fixed undefined `$stats` and `$recentActivity`
- ActivityLog — changed `auctioneer_id` → `user_id`
- Middleware — fixed `activated.auctioneer` → `auctioneer.active`
- Credits Page — added missing `credit_packages` and `features` config
- Analytics — added `lots_sold`, `total_sales`, `avg_sale_price`, `topLots`
- Helper Functions — added `getPriceValue()`
- AuctionPolicy — created authorization policy
- AuctionController — added `AuthorizesRequests` trait
- Auction Status Automation — Laravel Scheduler for automatic transitions
- Image Cleanup — automatic deletion of old lot images after 30 days
- Activation System — removed R500 fee, implemented auto-activation with R100 minimum credit purchase
