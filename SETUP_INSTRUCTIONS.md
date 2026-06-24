# Basic Bidall - Setup Instructions

## Local Development (Windows)

### Recommended: Laragon

1. Download and install [Laragon Full](https://laragon.org/download/) - includes PHP 8.3, Composer, MySQL
2. Start Laragon
3. Open Laragon Terminal and run:
   ```bash
   cd F:\basic_bidall
   composer install
   npm install
   npm run dev
   ```
4. Copy `.env.example` to `.env` and configure:
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
   ```
7. Visit: http://localhost:8000

---

## Production Deployment (Xneelo Shared Hosting)

> For the full step-by-step guide see `PRODUCTION_DEPLOYMENT_GUIDE.md`

### Key differences from standard Laravel deployment

- PHP runs via `/usr/bin/php8.3` - never use `php` directly
- App files go in `/usr/home/bidalujwfq/`, NOT inside `public_html`
- Only the contents of the local `public/` folder go in `public_html/`
- `index.php` and `artisan` must be edited with absolute paths after upload
- No Node.js on server - run `npm run build` locally and upload `public/build/`
- No sudo - database and SSL managed via cPanel
- No Redis - use `SESSION_DRIVER=file` and `QUEUE_CONNECTION=sync`

### Summary of steps

1. **Locally first**: `npm run build`, prepare `.env.production` (rename to `.env` on server)
2. **Upload via FTP**: app files → `/usr/home/bidalujwfq/`, public files → `public_html/`
3. **Install Composer** on server:
   ```bash
   curl -sS https://getcomposer.org/installer -o composer-setup.php
   /usr/bin/php8.3 composer-setup.php --install-dir=/usr/home/bidalujwfq --filename=composer
   ```
4. **Install dependencies**:
   ```bash
   COMPOSER_MEMORY_LIMIT=-1 /usr/bin/php8.3 /usr/home/bidalujwfq/composer install --optimize-autoloader --no-dev
   ```
5. **Edit `public_html/index.php`** - replace relative paths with absolute paths
6. **Edit `artisan`** - replace relative paths with absolute paths
7. **Create database** via cPanel > MySQL Databases
8. **Generate key**: `/usr/bin/php8.3 artisan key:generate`
9. **Run migrations**: `/usr/bin/php8.3 artisan migrate --force`
10. **Create storage symlink**: `ln -s /usr/home/bidalujwfq/storage/app/public /usr/home/bidalujwfq/public_html/storage`
11. **SSL** via cPanel > SSL/TLS (free Let's Encrypt)
12. **Cron job** — two-part setup required (Xneelo cron minimum is every 2 hours, not suitable for live auctions):
    - **Primary**: Set up a free [cron-job.org](https://cron-job.org) job to call `https://www.bidall.co.za/cron/run?token=YOUR_CRON_SECRET` every minute
    - **Fallback**: Add via cPanel > Cron Jobs (every 2 hours):
      ```
      /usr/bin/php8.3 /usr/home/bidalujwfq/artisan schedule:run >> /dev/null 2>&1
      ```
    - Set `CRON_SECRET` in `.env` to match the token in the cron-job.org URL
13. **Optimise**:
    ```bash
    /usr/bin/php8.3 artisan config:cache
    /usr/bin/php8.3 artisan route:cache
    /usr/bin/php8.3 artisan view:cache
    /usr/bin/php8.3 artisan event:cache
    ```

---

## Tech Stack

- Laravel 11 (PHP 8.3)
- Alpine.js 3
- Tailwind CSS 3
- MySQL/MariaDB
- PayFast payment gateway
- Vite (asset bundling)

## Features

- Sequential lot timing with soft close
- Real-time bidding via polling
- Prepaid credits system for auctioneers
- Tiered image upload (Basic/Pro/Premium) with unlimited images
- Auto image optimisation (WebP conversion, 1200px + 300px thumbnail)
- Map-based auctioneer discovery
- Follow auctioneers system
- Dark mode toggle
- WhatsApp integration
- Auctioneer payout system with 48-hour PayFast hold
