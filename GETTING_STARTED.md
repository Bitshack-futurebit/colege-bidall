# Getting Started — Bidall Platform

This platform is production-ready and live at **https://www.bidall.co.za**.

---

## For Developers

### Local Development Setup

1. Install [Laragon](https://laragon.org/download/) (includes PHP 8.3, Composer, MySQL)
2. Clone/copy the project to `F:\basic_bidall`
3. Open Laragon Terminal:

```bash
cd F:\basic_bidall
composer install
npm install
cp .env.example .env
```

4. Configure `.env`:
```env
DB_DATABASE=basic_bidall
DB_USERNAME=root
DB_PASSWORD=
```

5. Run setup:
```bash
php artisan key:generate
php artisan migrate
```

6. Start dev servers:
```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 (auction timing)
php artisan schedule:work
```

7. Visit `http://localhost:8000`

---

## For Deployment

See `PRODUCTION_DEPLOYMENT_GUIDE.md` for the full Xneelo deployment walkthrough.

Quick reference:
- PHP binary on server: `/usr/bin/php8.3`
- App root: `/usr/home/bidalujwfq/`
- Web root: `/usr/home/bidalujwfq/public_html/`
- Build assets locally with `npm run build` before uploading

---

## Key Documentation

| Need | Read |
|------|------|
| Full platform architecture | `CLAUDE.md` |
| Deploy to Xneelo | `PRODUCTION_DEPLOYMENT_GUIDE.md` |
| Cron and scheduler setup | `SCHEDULER_SETUP.md` |
| PayFast testing | `PAYFAST_TESTING.md` |
| Payout system | `PAYOUT_SYSTEM.md` |

---

## Admin Access

- URL: `https://www.bidall.co.za/admin`
- Create admin user via `php artisan tinker` (see `PRODUCTION_DEPLOYMENT_GUIDE.md` Step 10)

---

## Useful Artisan Commands

```bash
# Update auction statuses immediately
php artisan auctions:update-statuses

# Send winner emails
php artisan emails:send-auction-summaries

# End a live auction manually
php artisan auctions:end {id}

# Clear all caches
php artisan config:clear && php artisan route:clear && php artisan view:clear

# On production (use php8.3)
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan migrate --force
```
