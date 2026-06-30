> **⚠️ AUCTION COLLEGE PROJECT — SCOPE BANNER**
> This repo is the **auction TRAINING platform** for the **Auction College** project
> (`college.bidall.co.za`). That project = exactly 3 components: **SACA** (`F:\saca`),
> the **Online Auctioneering Academy** (`F:\online auctioneering\site`), and **this
> training platform**. Treat this app as **STANDALONE** — do NOT pull in the commercial
> **BidAll** (`bidall.co.za`, `F:\basic_bidall`, its credit purchases / relaunch) or any
> other BitShack app.
> Architecture/migration plan: `F:\Project extentions\auction colege\unified-college-site-architecture.md`

---

# Claude Instructions for Basic BidAll Auction Platform

## Project Overview

This is a Laravel 11 PHP application for an online auction platform. The platform allows:
- **Bidders** to browse auctions, place bids, and win items
- **Auctioneers/Sellers** to create auctions, manage lots, run auctions, and notify followers
- **Staff** to assist auctioneers with lot/auction/collection management
- **Admins** to manage users, monitor activity, broadcast notifications, and oversee the platform

## Tech Stack

- **Framework**: Laravel 11 (PHP 8.3.30)
- **Frontend**: Blade templates with Tailwind CSS and Alpine.js
- **Database**: MySQL/MariaDB
- **Payment**: Configurable payment gateway (PayFast, BTCPay, Test)
- **Push Notifications**: Web Push API via `minishlink/web-push` (VAPID) + in-app polling bell
- **Environment**: Windows (F:\basic_bidall)

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthController.php
│   │   ├── AuctioneerController.php - Auctioneer dashboard & management
│   │   ├── AuctionController.php - Auctions (database table: events)
│   │   ├── LotController.php - Individual auction items
│   │   ├── BidController.php - Bidding functionality
│   │   ├── DashboardController.php - Bidder dashboard
│   │   ├── PaymentController.php - PayFast payments & webhooks
│   │   ├── PushNotificationController.php - Push subscribe/unsubscribe + seller notifications
│   │   ├── Api/
│   │   │   ├── BiddingController.php - Real-time bidding API
│   │   │   ├── LotStatusController.php - Polling status API
│   │   │   ├── CreditController.php - Credit balance API
│   │   │   └── NotificationController.php - In-app bell API (unread/history/markRead)
│   │   └── Admin/
│   │       ├── AdminController.php - Admin functions
│   │       ├── PushNotificationController.php - Admin broadcast notifications
│   │       ├── TermsController.php - Terms & Conditions CRUD
│   │       └── BroadcastController.php - Email broadcasts
│   ├── Middleware/
│   │   ├── CheckRole.php - Role-based access control
│   │   ├── EnsureAuctioneerActivated.php - Activation check
│   │   ├── EnsureTermsAccepted.php - T&C acceptance enforcement
│   │   ├── EnsureUserActive.php - Suspend check (logout if suspended)
│   │   └── CheckStaffPermission.php - Staff action authorization
│   └── Policies/
│       └── AuctionPolicy.php - Auction authorization
├── Models/
│   ├── User.php - Users with roles (bidder/auctioneer/admin/staff)
│   ├── Auctioneer.php - Auctioneer profiles
│   ├── Auction.php - Auctions (database table: events)
│   ├── Lot.php - Items for auction
│   ├── Bid.php - Bid records
│   ├── ProxyBid.php - Automatic maximum bids
│   ├── PushNotification.php - Notification records with audience targeting
│   ├── PushSubscription.php - Browser push subscriptions
│   ├── TermsVersion.php - Versioned T&C with role targeting
│   ├── TermsAcceptance.php - User T&C acceptance audit trail
│   ├── StaffMember.php - Auctioneer team members + permissions
│   ├── StaffInvite.php - Staff invite tokens
│   ├── PromoCode.php - Promotional discount codes
│   ├── ActivityLog.php - Activity tracking
│   └── AuctioneerFollower.php - Follow relationships
├── Jobs/
│   └── SendPushNotification.php - Queued Web Push delivery
├── Mail/
│   └── BroadcastEmail.php - Admin email broadcasts
├── Helpers/
│   ├── helpers.php - Global helper functions
│   └── Currency.php - Currency formatting
config/
├── platform.php - Platform configuration (pricing, features)
├── regional.php - Regional settings
├── webpush.php - VAPID keys for Web Push API
├── branding.php - Brand name customization
├── auction.php - Auction configuration
└── facebook.php - Facebook sharing integration
resources/views/
├── auth/ - Login, registration, staff registration, accept terms
├── dashboard/ - Bidder dashboard
├── seller/ - Auctioneer dashboard + notifications
├── admin/ - Admin panel + notifications + terms management
├── components/ - Reusable components (nav with bell dropdown)
└── emails/ - Email templates (broadcast, winner summary)
```

## Key Features

### User Roles
- **Bidder**: Browse auctions, discover auctioneers, follow favorites, place bids, proxy bidding, manage watchlist
- **Auctioneer**: Create auctions, manage lots, track sales, view followers, send push notifications to followers
- **Staff**: Assist auctioneers — lot_manager, auction_manager, collections_manager (invite-based onboarding)
- **Admin**: Full platform access, user management, pricing controls, broadcast notifications, T&C management, promo codes

### In-App Notification System

**Architecture** (SwitchSA pattern — polling-based, reliable):
- `Alpine.store('bell')` in `app.js` — polls `/api/notifications/unread` every 30s
- Bell icon in nav with red unread count badge
- Dropdown panel with **New** and **History** tabs
- Click notification to view in modal with full body text
- "Clear all" marks all as read
- `notification_reads` pivot table for per-user read state
- `PushNotification::scopeForUser()` filters by audience targeting

**Sending:**
- Auctioneers: `/seller/notifications` — send to their followers
- Admins: `/admin/notifications` — send to All Users, All Bidders, All Auctioneers, All Admins, or specific auctioneer's followers

**Audience Targeting** (on `PushNotification` model):
- `all_users` — visible to everyone
- `all_bidders` / `all_auctioneers` / `all_admins` — role-filtered
- `followers` — only users following the specified `auctioneer_id`

**Browser Push** (supplementary, not primary):
- Web Push API with VAPID keys via `minishlink/web-push`
- Service worker handles push + notificationclick events
- Browser push is unreliable (FCM delivery issues) — in-app bell is the primary UX

### Proxy Bidding
- Users set a maximum automatic bid amount on a lot
- System auto-outbids competing bids up to the max
- API: `POST /api/lots/{lot}/proxy`, `DELETE /api/lots/{lot}/proxy`
- Model: `ProxyBid` with `lot_id`, `user_id`, `max_amount`, `is_active`
- Alpine.js UI in lot detail page with manual/proxy bid mode toggle

### Staff Management
- Auctioneers invite team members via shareable token links
- Three roles: `lot_manager`, `auction_manager`, `collections_manager`
- Staff register at `/register/staff/{token}` — auto-linked to auctioneer
- Staff see seller dashboard, scoped by their permissions
- `CheckStaffPermission` middleware protects actions
- Routes: `/seller/staff` (manage), `/seller/staff/invite` (create token)

### Terms & Conditions System
- Admin creates versioned T&C at `/admin/terms`
- Role-specific terms (bidder, auctioneer, staff, or all)
- Draft/publish workflow
- `EnsureTermsAccepted` middleware redirects to `/terms/accept` if unaccepted
- Audit trail: IP address, user agent, acceptance timestamp
- Model: `TermsVersion` with `currentForUser()` method, cached 5min

### Promo Codes
- Admin creates at `/admin/promo-codes`
- Benefits: free accounts, custom lot fees, custom per-tier pricing, bonus credits, free relist reset
- Usage limits and expiry dates
- Applied to auctioneers via `promo_code_id`

### Account Suspension
- `User.is_active` boolean field + `suspension_reason`
- `EnsureUserActive` middleware auto-logs out suspended users
- Shows suspension reason + WhatsApp support link

### Pay-As-You-Go Credit System

**Activation:**
- Auctioneers are **auto-activated** upon registration (no activation fee)
- Start with R0 balance
- Must purchase minimum R100 in credits to create first auction

**How It Works:**
1. **Minimum Deposit**: R100 minimum credit purchase to get started
2. **Lot Creation**: Credits deducted when auction goes LIVE (not when created)
3. **Commission**: 1% deducted from credit balance after auction ends
4. **Sale Income**: When bidders pay via PayFast, the net amount flows back into `credit_balance` automatically
5. **Negative Balances**: Allowed (from commissions), must top-up R100 minimum to continue
6. **Payouts**: Auctioneer can withdraw from `credit_balance` (R500 minimum, R100 minimum balance must remain)

**Credit Pricing (per lot - customizable by admin):**
- Basic (1 image): R1 (default)
- Pro (2-5 images): R5 (default)
- Premium (6+ images): R20 (default)
- **No maximum image limit** - unlimited images allowed
- Tier auto-calculated based on actual image count
- Admins can override with free accounts, custom flat fees, or promo codes

### Image Tiers & Pricing
- **Unlimited Images**: No maximum limit on images per lot
- **Auto-Tier Calculation** based on image count:
  - **Basic**: 1 image - R1
  - **Pro**: 2-5 images - R5
  - **Premium**: 6+ images - R20
- **Minimum Requirement**: At least 1 image per lot (enforced)
- **Image Storage**: All images stored on `public` disk for web accessibility
- **Image Carousel**: Navigate through multiple images on lot cards with arrows and dots
- **Image Optimization**: Automatic WebP conversion (1200px optimized + 300px thumbnails)
- **EXIF Rotation**: `orient()` called before resize — mobile camera photos auto-rotate correctly
- **Ideal Dimensions**: 1200x900px landscape (4:3 ratio), minimum 600x450px, max 15MB per image

### Auctioneer Ratings
- Bidders can rate auctioneers 1-5 stars on their profile page
- **Eligibility**: Bidder must have placed at least one bid on a live or ended auction by that auctioneer
- One rating per bidder per auctioneer (can update)
- Average rating + count displayed in stats grid on auctioneer profile
- Model: `AuctioneerRating` with unique `(user_id, auctioneer_id)`
- Route: `POST /auctioneer/{slug}/rate`
- Helper: `User::hasBidOnAuctioneerAuctions($auctioneerId)`

### Dutch Auctions
- **Reverse auctions**: Price starts high and drops over time; first to buy wins
- **Three auction types**: `auction_type` field on Auction — `english` (traditional), `dutch`, or `sealed`
- **Two lot modes** (`dutch_lot_mode`):
  - **Simultaneous** (`simultaneous`): All lots drop at the same time, each with independent timing
  - **Sequential** (`sequential`): Lots run one after another with configurable gap (`dutch_lot_gap`)
- **Duration-based configuration**: Auctioneer sets start price, floor price, duration (3-10 min), and strategy
  - Platform auto-calculates optimal `drop_amount` and `drop_interval` via `Lot::calculateDropMatrix()`
  - Duration stored on lot as `dutch_duration` (seconds)
  - Computed values stored as `dutch_drop_amount` and `dutch_drop_interval`
- **Drop strategies** (`Lot::DROP_STRATEGIES`):
  - `constant` — Same drop rate throughout (1 phase)
  - `fast_sell` — Fast drops early, slows near floor (3 phases)
  - `max_value` — Rush to decision zone, crawl at the bottom (3 phases)
  - `high_drama` — Builds tension throughout, very slow finish (3 phases)
  - Set at auction level as default, overridable per-lot
- **Multi-quantity lots**: `quantity` field, buyers purchase at current price, `quantity_sold` tracks sales
- **Floor price**: Minimum price the lot can drop to (`dutch_floor_price`)
- **Timing**: Each lot has `dutch_start_time` and `dutch_end_time` computed at go-live
  - `calculateSimultaneousEndTime()` — all lots start together, end based on individual durations
  - `calculateSequentialSchedule()` — lots chained with gap, auction end = last lot end
- **Dutch buy API**: `POST /api/lots/{lot}/buy` — `lockForUpdate()` prevents race conditions
- **Lot closure**: `closeDutch()` marks as sold (if any bought) or unsold
- **Relist**: Dutch partial sales reduce quantity to unsold amount on relist
- **Scheduler**: `UpdateAuctionStatuses` filters Dutch vs English lots for closing logic
- **JS components**: `dutchPrice()` (auction page) and `dutchBuy()` (lot detail) — inline in Blade views
- **Drop preview**: `dropPreview()` Alpine component on lot create/edit — live preview of computed matrix

### Sealed (Silent) Auctions
- **Secret bid auctions**: Bidders submit bids that no one else can see until the auction ends
- **Two modes**: `sealed_mode` on Auction — `highest` (highest bid wins) or `lowest` (lowest bid wins)
- **First-price**: Winner pays their bid amount
- **Updatable bids**: Bidders can revise their bid any number of times before close (upsert pattern — one bid per user per lot)
- **2-bid minimum**: Lot needs bids from 2+ distinct bidders to be valid; otherwise unsold
- **Sealed from all**: Auctioneer cannot see bids before close either; no bid count shown
- **Reserve price**: For highest mode = minimum (bid must be >=), for lowest mode = maximum (bid must be <=)
- **Timing**: All lots open simultaneously, single end time (like English). Max 12 hours. No soft close
- **No proxy bidding** for sealed auctions
- **Winner only revealed**: After close, only winning bid + winner shown. Other bids stay private forever
- **API**: `POST /api/lots/{lot}/sealed-bid` — rate limited 10/min
- **Model methods**: `Lot::placeSealed()`, `Lot::closeSealed()`, `Lot::getUserSealedBid()`
- **Auction methods**: `isSealed()`, `isSealedHighest()`, `isSealedLowest()`
- **JS component**: Inline Alpine.js `sealedBid` in `lots/show.blade.php`
- **Purple color scheme** throughout UI to distinguish from English (blue) and Dutch (amber)

### Auction Features
- **Auctioneer Discovery**: Auction cards link to auctioneer profiles for relationship building
- **Follow Auctioneers**: Bidders can follow favorite auctioneers and see their latest auctions
- **Optional Auction Registration**: Auctioneers choose if registration required (defaults to no)
- **Deposit System**: Optional refundable deposits for auction registration
- **Lot Watchlist**: Bidders can save favorite lots
- **Proxy Bidding**: Set maximum automatic bid amount (English auctions only)
- **Paddle Numbers**: Per-auction bidder identification
- **Bid Anonymity**: Configurable anonymous bidding
- Soft close timing (extends when bids placed near end) — English only
- Real-time bid updates via polling (3 second interval)
- Email notifications (win notification only)
- **Maximum English auction duration: 12 hours** (enforced at creation/update)

### Admin Features

**Pricing Controls (per auctioneer):**
- **Free Accounts**: Set auctioneers to pay R0 for all lots
- **Custom Flat Fee**: Override tiered pricing with single price per lot
- **Custom Per-Tier Pricing**: Override individual tier prices
- **Promo Codes**: Promotional pricing with usage limits
- **Pricing Notes**: Document reason for custom pricing
- Pricing logic: Free account → Promo code → Custom fee → Standard tiered pricing

**Notifications:**
- **Push Notifications**: Broadcast to All Users, All Bidders, All Auctioneers, All Admins, or specific auctioneer's followers
- **Email Broadcasts**: Quick mail to individual users or bulk broadcast by role

**Terms & Conditions**: Create, version, publish, role-target T&C documents

**User Management:**
- View all users, auctioneers, and their activity
- Suspend/activate accounts with reason tracking
- View complete transaction history
- Monitor platform revenue and statistics

**Revenue Dashboard (`/admin/revenue`):**
- Total revenue, platform fees (lot_live), and commissions (lot_close) with period filter
- Revenue by Auctioneer table — searchable
- **Bug note**: Always `clone` query builders before adding `where` conditions

## Configuration

### Platform Config (`config/platform.php`)
```php
'minimum_deposit' => 100
'credit_packages' => [...]
'pricing' => [
    'tier_basic' => ['price' => 1, 'images' => 1],
    'tier_pro' => ['price' => 5, 'images' => 5],
    'tier_premium' => ['price' => 20, 'images' => 20],
]
'platform_fee_percent' => 1
```

### Web Push Config (`config/webpush.php`)
```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:support@bidall.co.za
```

### Regional Config (`config/regional.php`)
- Currency settings (ZAR default)
- Map discovery features
- WhatsApp support group URL

## Database Models & Relationships

### User
- `hasOne(Auctioneer)`
- `hasMany(Bid)`
- `hasMany(AuctioneerFollower, 'followedAuctioneers')`
- `hasOne(StaffMember, 'staffMembership')`
- `belongsToMany(PushNotification, 'notification_reads', 'readNotifications')`
- Methods: `isAdmin()`, `isAuctioneer()`, `isBidder()`, `isStaff()`, `isFollowingAuctioneer($id)`, `hasAcceptedCurrentTerms()`, `unacceptedTerms()`, `hasStaffPermission($permission)`

### Auctioneer
- `belongsTo(User)`
- `hasMany(Auction)` (database table: events)
- `hasMany(CreditTransaction)`
- `hasMany(AuctioneerFollower, 'followers')`
- `hasMany(StaffMember)`
- Fields:
  - `business_name`, `slug`, `description`
  - `logo`, `profile_image`, `banner_image`
  - `whatsapp_number`
  - `credit_balance` — unified account balance
  - `is_activated`
  - `is_free_account`, `custom_lot_fee`, `pricing_notes` (admin controls)
  - Social: `website`, `facebook`, `instagram`, `twitter`, `linkedin`

### Auction
- Model name: `Auction` (database table: `events`)
- `belongsTo(Auctioneer)`
- `hasMany(Lot)`
- Statuses: `draft`, `upcoming`, `live`, `ended`
- Fields: `auction_type` (english/dutch/sealed), `sealed_mode` (highest/lowest), `dutch_lot_mode` (simultaneous/sequential), `dutch_lot_gap`, `dutch_drop_strategy`
- Methods: `isDutch()`, `isEnglish()`, `isSealed()`, `isSealedHighest()`, `isSealedLowest()`, `isDutchSimultaneous()`, `isDutchSequential()`, `calculateSimultaneousEndTime()`, `calculateSequentialSchedule()`

### Lot
- `belongsTo(Auction)` (foreign key: `event_id`)
- `hasMany(Bid)`
- `hasMany(Image)`
- `hasMany(ProxyBid)`
- Statuses: `pending`, `active`, `sold`, `unsold`
- Dutch fields: `dutch_start_price`, `dutch_floor_price`, `dutch_drop_amount`, `dutch_drop_interval`, `dutch_drop_strategy`, `dutch_duration`, `dutch_start_time`, `dutch_end_time`, `quantity`, `quantity_sold`
- Key methods: `getCurrentDutchPrice()`, `calculateDropMatrix()`, `calculateDutchDuration()`, `getDutchNextDropIn()`, `closeDutch()`, `isAtDutchFloor()`, `quantityRemaining()`, `isDutchSoldOut()`, `placeSealed()`, `closeSealed()`, `getUserSealedBid()`
- `DROP_STRATEGIES` constant defines phase multipliers for each strategy

### PushNotification
- `belongsTo(Auctioneer)` (nullable)
- `belongsToMany(User, 'notification_reads', 'readBy')`
- Fields: `sender_type` (auctioneer/admin), `audience` (followers/all_users/all_bidders/all_auctioneers/all_admins), `auctioneer_id`, `title`, `body`, `url`, `sent_count`, `failed_count`
- Scope: `forUser($user)` — filters by role + followed auctioneers
- Method: `getTargetUserIds()` — resolves audience to user IDs for push delivery

### AuctioneerRating
- `belongsTo(User)`, `belongsTo(Auctioneer)`
- Fields: `rating` (1-5 tinyint)
- Unique constraint: `(user_id, auctioneer_id)`

### ProxyBid
- `belongsTo(Lot)`, `belongsTo(User)`
- Fields: `max_amount`, `is_active`
- Scope: `active()`

### StaffMember
- `belongsTo(User)`, `belongsTo(Auctioneer)`
- Fields: `staff_role` (lot_manager/auction_manager/collections_manager), `is_active`
- Methods: `canManageLots()`, `canManageAuctions()`, `canManageCollections()`

### TermsVersion
- Fields: `version`, `title`, `role` (nullable), `content`, `published_at`
- Methods: `currentForUser($user)`, `currentForRole($role)`

### CreditTransaction
- `belongsTo(Auctioneer)`, `belongsTo(Lot)` (nullable)
- Types: `purchase`, `lot_live`, `lot_close`, `sale_income`, `payout`, `adjustment`, `refund`
- Fields: `amount`, `balance_after`, `description`

### Transaction
- `belongsTo(User)`, `belongsTo(Auctioneer)` (nullable)
- Types: `credit_purchase`, `deposit`, `lot_payment`, `deposit_refund`, `platform_fee`
- Fields: `amount`, `platform_fee`, `status`, `payment_method`, `payment_id`

**Note**: CreditTransaction = auctioneer credit ledger (internal). Transaction = payment gateway (external).

## Routes Structure

```
/ - Homepage
/login, /register - Auth
/register/staff/{token} - Staff registration
/terms/accept - Accept T&C (GET/POST)

/dashboard - Bidder dashboard
  /dashboard/bids, /dashboard/watchlist, /dashboard/following, /dashboard/won, /dashboard/profile

/seller/* - Auctioneer dashboard
  /seller/dashboard, /seller/credits, /seller/followers, /seller/analytics
  /seller/auctions/* - Manage auctions
  /seller/notifications - Send/view push notifications
  /seller/staff - Manage team members
  /seller/profile

/admin/* - Admin panel
  /admin/dashboard, /admin/users, /admin/auctioneers, /admin/auctions
  /admin/transactions, /admin/revenue, /admin/activity
  /admin/notifications - Broadcast push notifications
  /admin/terms - T&C management (CRUD)
  /admin/promo-codes - Promo code management

/api/* - API routes (web session auth, CSRF excluded)
  /api/lots/{lot}/bid (POST), /api/lots/{lot}/status (GET), /api/lots/{lot}/bids (GET)
  /api/lots/{lot}/watchlist (POST)
  /api/lots/{lot}/proxy (POST/DELETE) - Proxy bidding
  /api/lots/{lot}/buy (POST) - Dutch auction buy (throttled 20/min)
  /api/lots/{lot}/sealed-bid (POST) - Sealed auction bid (throttled 10/min)
  /api/auctions/{auction}/status (GET)
  /api/credits/balance (GET)
  /api/auctioneers/map (GET, public)
  /api/push/subscribe (POST), /api/push/unsubscribe (POST)
  /api/notifications/unread (GET), /api/notifications/history (GET)
  /api/notifications/{id}/read (POST), /api/notifications/read-all (POST)
  /api/csrf-token (GET, no auth) - CSRF refresh for PWA
```

## API Routes & Authentication

- **Middleware**: `['web', 'auth']` on all API routes except CSRF refresh and map
- **CSRF**: API routes excluded from CSRF verification in `bootstrap/app.php`
- **Guest redirect**: `redirectGuestsTo` returns 401 JSON for `api/*` requests (prevents API URLs from poisoning `redirect()->intended()`)
- **Rate limiting**: bid (30/min), proxy (10/min), Dutch buy (20/min)
- **Response Format**: JSON

### Key API Controllers
- **BiddingController**: `place()`, `history()`, `toggleWatchlist()`, `setProxy()`, `cancelProxy()`, `dutchBuy()`, `sealedBid()`
- **LotStatusController**: `status()`, `auctionStatus()`
- **NotificationController**: `unread()`, `history()`, `markRead()`, `markAllRead()`

### JavaScript Components (Alpine.js)
- **Alpine.store('bell')** - In-app notification bell (polling, tabs, modal, read/unread)
- **bidding()** - Main bidding component with proxy bid support
- **countdown()** - Countdown timer
- **auctioneerMap()** - Leaflet map with gavel markers
- **pushBanner()** - Browser push opt-in banner
- **dutchPrice()** - Dutch lot price ticker with strategy-aware drops (inline in auctions/show)
- **dutchBuy()** - Dutch lot buy interface with price countdown (inline in lots/show)
- **dropPreview()** - Live drop matrix preview on lot create/edit forms
- All components include `formatCurrency()` for consistent formatting

## Service Worker (`public/service-worker.js`)

- Cache versioned as `bidall-vN` — bump version to force update
- Ignores non-http schemes (chrome-extension://)
- Auth pages (`/login`, `/register`, `/password/*`, `/accept-terms`): bare `return;` — never intercept
- API routes: network only
- Page navigations: network first, cache fallback
- Static assets: cache first, network fallback
- Push + notificationclick handlers for browser push

## CSRF Protection

- `/api/csrf-token` endpoint returns fresh token (no auth required)
- `visibilitychange` listener refreshes all CSRF tokens when tab becomes visible
- Logout forms use `refreshCsrfAndSubmit()` to fetch fresh token before submit
- See `memory/reference_csrf_fixes.md` for full pattern reference

## Registration Flow

1. User visits `/register`, selects **"Bidder"** or **"Auctioneer"**
2. All users: Name, Email, Phone, Password, Address, City, Province
3. Auctioneers additionally: Business Name, WhatsApp Number
4. Staff: Register via `/register/staff/{token}` — auto-linked to auctioneer
5. After registration: Bidders → `/dashboard`, Auctioneers → `/seller/credits`
6. `EnsureTermsAccepted` middleware may redirect to `/terms/accept` on first login

## Laravel Scheduler - Automated Tasks

- **`auctions:update-statuses`** - Every minute: upcoming→live→ended transitions
- **`images:cleanup`** - Daily 2 AM: delete lot images from closed lots older than 30 days
- **`emails:send-auction-summaries`** - Every 5 minutes: winner summary emails

## Production Deploy Workflow

**Standard deploy**: Upload files → SSH:
```bash
php8.3 artisan route:cache && php8.3 artisan cache:clear && rm -f storage/framework/views2/*.php
```

**JavaScript changes**: Run `npm run build` locally, upload `public/build/` folder

**New migrations**: Run `php8.3 artisan migrate` on server

## Development Guidelines

- Follow Laravel conventions, use type hints
- Keep controllers thin
- Always validate user input
- Always `clone` query builders before adding `where` conditions
- Never enable `DB_LOG_QUERIES=true` in production

## Troubleshooting

**419 on login/logout**: See CSRF Protection section above. Check service worker isn't intercepting auth pages. Check `redirectGuestsTo` returns 401 for API routes.

**API endpoint shows as page after login**: API polling stored URL as intended redirect. Fix: `redirectGuestsTo` in `bootstrap/app.php` must abort(401) for `api/*`.

**Notifications not showing for user**: Check audience targeting — `PushNotification::forUser()` filters by role and followed auctioneers. User must follow the auctioneer for `followers` audience.

**Revenue query showing zeros**: Always `clone` base query before adding `where` conditions.

**Service worker not updating**: Bump `CACHE_NAME` version in `service-worker.js`. Hard refresh (Ctrl+Shift+R).

**Uploaded PHP but old code runs on production**: OPcache disabled (2026-03-05) — should load fresh. If re-enabled, toggle `opcache.enable` in `.user.ini`.

## Notes for Claude

- This is a Windows environment, use Windows paths
- Use dedicated tools (Read, Edit, Write) instead of bash commands for file operations
- The user prefers concise responses without emojis
- Always read files before editing them
- Check that variables passed to views match what the view expects
- For notifications, use in-app bell pattern (not browser push) — see `memory/reference_inapp_notifications.md`
- For CSRF issues, see `memory/reference_csrf_fixes.md`
