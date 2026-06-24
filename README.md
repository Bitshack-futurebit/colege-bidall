# Bidall - South African Online Auction Platform

A production-ready auction platform built for South African auctioneers, hosted on Xneelo shared hosting.

Live at: **https://www.bidall.co.za**

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 11 (PHP 8.3) |
| Frontend | Blade + Alpine.js 3 + Tailwind CSS |
| Database | MySQL/MariaDB |
| Payments | PayFast |
| Images | Intervention Image v3 (WebP) |
| Maps | Leaflet.js + OpenStreetMap |
| Build Tool | Vite |
| Hosting | Xneelo shared hosting |

---

## Features

### User Roles
- **Bidder** — Browse auctions, place bids, watchlist, follow auctioneers
- **Auctioneer** — Create auctions, manage lots, track sales, receive payouts
- **Admin** — Full platform management, pricing controls, payout processing

### Auction System
- Sequential lot timing with configurable gaps
- Soft close (extends when bids placed near end)
- Real-time bidding via Alpine.js polling (3-second interval)
- Reserve prices, buyers premium support
- Optional auction registration with deposits
- Automatic status transitions via scheduler (draft → upcoming → live → ended)

### Credit System (Auctioneers)
- Auto-activated on registration
- Pay-as-you-go credits (minimum R100 purchase)
- Tiered lot fees deducted when auction goes live:
  - Basic (1 image): R1
  - Pro (2-5 images): R5
  - Premium (6+ images): R20
- 1% commission deducted from credit balance when auction ends
- Negative balances allowed (from commissions), must top-up to create new auctions

### Payout System
- 48-hour PayFast hold on all payments before funds available
- Auctioneers request payouts (minimum R500)
- Admin processes bank transfers and approves payouts
- Full accounting page with total sales, platform payments, non-platform payments, fees

### Images
- Unlimited images per lot (auto-tiered for pricing)
- WebP conversion: 1200px optimised + 300px thumbnail
- Auto-deleted 30 days after auction ends
- Stored on `public` disk for web accessibility

### Email Notifications
- Winner summary email after auction ends (one email per bidder listing all won lots)
- Sends once only — duplicate protection via `winner_emails_sent` flag

### Auctioneer Discovery
- Public auctioneer profiles with banner, logo, bio
- Follow/unfollow system for bidders
- Map-based discovery (Leaflet.js)

---

## Project Structure

```
app/
├── Console/Commands/       — Scheduler commands
├── Http/Controllers/       — Web & API controllers
│   ├── Admin/AdminController.php
│   ├── AuctionController.php
│   ├── AuctioneerController.php
│   ├── BidController.php
│   ├── DashboardController.php
│   ├── LotController.php
│   └── PaymentController.php
├── Mail/                   — Email templates (Mailables)
├── Models/                 — Eloquent models
├── Services/Payments/      — PayFast & BTCPay gateways
└── Helpers/                — Currency formatting helpers
config/
├── platform.php            — Pricing, credits, payout config
└── regional.php            — Currency, map, regional features
resources/views/
├── admin/                  — Admin panel
├── auth/                   — Login, register
├── components/             — Reusable Blade components
├── dashboard/              — Bidder dashboard
├── emails/                 — Email templates
└── seller/                 — Auctioneer dashboard
routes/
├── web.php                 — Web routes
├── api.php                 — API routes (bidding, polling)
└── console.php             — Scheduler definitions
```

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | All users (bidder/auctioneer/admin roles) |
| `auctioneers` | Auctioneer profiles and credit balances |
| `events` | Auctions (model: `Auction`) |
| `lots` | Individual auction items |
| `lot_images` | Optimised images per lot |
| `bids` | All bids placed |
| `event_registrations` | Auction registration records |
| `transactions` | PayFast payment records |
| `credit_transactions` | Auctioneer credit ledger |
| `sales_records` | Per-lot sales with fee breakdown |
| `payouts` | Payout requests and processing |
| `auctioneer_followers` | Bidder follow relationships |
| `watchlist` | Saved lots |
| `activity_logs` | Admin audit trail |

---

## Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `auctions:update-statuses` | Every minute | Status transitions + lot open/close |
| `emails:send-auction-summaries` | Every 5 minutes | Winner emails after auction ends |
| `images:cleanup` | Daily 2 AM | Delete lot images 30+ days old |
| `auctions:archive-old` | Daily 3 AM | Soft delete auctions 30+ days ended |

**Cron setup (Xneelo)**: cron-job.org hits `/cron/run?token=CRON_SECRET` every minute as primary. Xneelo cPanel cron every 2 hours as fallback.

---

## Local Development

Requirements: PHP 8.3, Composer, Node.js, MySQL. Recommended: [Laragon](https://laragon.org/download/).

```bash
composer install
npm install
cp .env.example .env
# Configure DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

Run the scheduler locally:
```bash
php artisan schedule:work
```

---

## Production Deployment

See `PRODUCTION_DEPLOYMENT_GUIDE.md` for the full step-by-step Xneelo deployment guide.

Key Xneelo specifics:
- PHP binary: `/usr/bin/php8.3`
- App root: `/usr/home/bidalujwfq/`
- Web root: `/usr/home/bidalujwfq/public_html/`
- Vite build symlink required: `ln -s public_html/build public/build`
- Storage symlink: `ln -s storage/app/public public_html/storage`

---

## Configuration

Key environment variables (see `.env.example` for full list):

```env
APP_URL=https://www.bidall.co.za
PAYMENT_GATEWAY=payfast
PAYFAST_MERCHANT_ID=
PAYFAST_MERCHANT_KEY=
PAYFAST_PASSPHRASE=
PAYFAST_SANDBOX=false
CRON_SECRET=                    # Token for cron-job.org endpoint
TIER_BASIC_PRICE=1
TIER_PRO_PRICE=5
TIER_PREMIUM_PRICE=20
PLATFORM_PERCENTAGE_FEE=1
MINIMUM_PAYOUT=500
FUNDS_HOLD_HOURS=48
```

---

## Documentation

| File | Contents |
|------|---------|
| `CLAUDE.md` | Full developer reference (architecture, features, history) |
| `PRODUCTION_DEPLOYMENT_GUIDE.md` | Step-by-step Xneelo deployment |
| `SETUP_INSTRUCTIONS.md` | Local and production setup summary |
| `SCHEDULER_SETUP.md` | Cron and scheduler configuration |
| `PAYOUT_SYSTEM.md` | Payout system documentation |
| `PAYFAST_TESTING.md` | PayFast sandbox testing guide |
| `LOT_WITHDRAWAL_FEATURE.md` | Lot withdrawal business rules |

---

## License

Proprietary — All rights reserved.
