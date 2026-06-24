# Production Deployment Guide - Xneelo Shared Hosting

This guide is written specifically for **Xneelo shared hosting** using Putty SSH and FTP/SFTP.
All commands use Xneelo's PHP wrapper. Do not use `php` directly - it will use the wrong version.

---

## Xneelo Directory Structure

On Xneelo shared hosting your file layout must be:

```
/usr/home/bidalujwfq/                  ← Laravel app root (private, not web-accessible)
/usr/home/bidalujwfq/app/
/usr/home/bidalujwfq/bootstrap/
/usr/home/bidalujwfq/config/
/usr/home/bidalujwfq/database/
/usr/home/bidalujwfq/resources/
/usr/home/bidalujwfq/routes/
/usr/home/bidalujwfq/storage/
/usr/home/bidalujwfq/vendor/
/usr/home/bidalujwfq/artisan           ← Modified with absolute paths
/usr/home/bidalujwfq/.env
/usr/home/bidalujwfq/public_html/      ← Contents of Laravel's public/ folder (web root)
/usr/home/bidalujwfq/public_html/index.php   ← Modified with absolute paths
/usr/home/bidalujwfq/public_html/.htaccess
/usr/home/bidalujwfq/public_html/build/      ← Pre-compiled Vite assets (built locally)
```

> Replace `bidalujwfq` with your actual Xneelo FTP username throughout this guide.

---

## Shared Hosting Limitations & Workarounds

| Limitation | Workaround |
|---|---|
| No Node.js/npm on server | Build assets locally before uploading |
| No global Composer | Install Composer locally to home dir |
| PHP must use `/usr/bin/php8.3` | Use full path for all PHP/artisan commands |
| No sudo access | Use cPanel for database, SSL, cron |
| Putty session timeouts | Use `nohup` for long-running commands |
| Can't edit Apache VirtualHost | Use `.htaccess` only |
| SSL managed by cPanel | Do not use certbot |
| No Redis | Use file/database cache and sessions |
| Memory limits on PHP CLI | Add `COMPOSER_MEMORY_LIMIT=-1` prefix |

---

## Before You Upload - Do This Locally First

These steps must be done on your local Windows machine before deploying.

### 1. Set environment to production

Edit your `.env.production` file with all live values (see Environment Configuration section below).

### 2. Build frontend assets locally

```bash
npm install
npm run build
```

This creates the `public/build/` folder. Upload this folder to the server - **do not run npm on the server**.

### 3. Verify nothing is broken locally

```bash
php artisan config:clear
php artisan route:clear
```

---

## Step 1: Upload Files via FTP/SFTP

Use FileZilla or your preferred FTP client. Connect with your Xneelo FTP credentials.

> **FileZilla: Enable hidden files first** — `.htaccess` is a hidden file and FileZilla does not show or upload hidden files by default. Before uploading, go to **Server → Force showing hidden files** and enable it. Without this, `.htaccess` will not be uploaded and the site will show a blank page.

**Upload the following to `/usr/home/bidalujwfq/`:**
- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `resources/`
- `routes/`
- `storage/`
- `artisan`
- `composer.json`
- `composer.lock`
- `.env` (your production .env - rename `.env.production` to `.env` before uploading)

**Do NOT upload:**
- `vendor/` - will be installed on server via Composer
- `node_modules/` - not needed on server
- `public/` - its contents go to `public_html` instead

**Upload the contents of your local `public/` folder to `/usr/home/bidalujwfq/public_html/`:**
- `index.php` (will be modified in Step 4)
- `.htaccess`
- `build/` (your compiled Vite assets from `npm run build`)
- `favicon.svg`
- Any other files from `public/`

**Do NOT upload to public_html:**
- `hot` (Vite dev file, local only)
- `storage/` - if it exists in your local `public/` folder as a symlink, do not upload it. Delete it from `public_html/` if it appears after upload (it will conflict with the symlink created in Step 11).

---

## Step 2: Install Composer on Server

Connect via Putty, then run these commands one at a time.

```bash
cd /usr/home/bidalujwfq

curl -sS https://getcomposer.org/installer -o composer-setup.php

/usr/bin/php8.3 composer-setup.php --install-dir=/usr/home/bidalujwfq --filename=composer
```

Verify it installed:
```bash
ls -la /usr/home/bidalujwfq/composer
```

---

## Step 3: Install PHP Dependencies

Run Composer install. The `COMPOSER_MEMORY_LIMIT=-1` flag prevents memory limit errors on shared hosting.

> **Important**: Type or paste this as a single line in Putty. Splitting it across two lines will cause bash to treat the flags as separate commands and show errors.

```bash
COMPOSER_MEMORY_LIMIT=-1 /usr/bin/php8.3 /usr/home/bidalujwfq/composer install --optimize-autoloader --no-dev
```

> **Putty timeout warning**: This can take 5-10 minutes. If your Putty session disconnects, prefix the command with `nohup` and append `&` to run it in the background:
> ```bash
> nohup COMPOSER_MEMORY_LIMIT=-1 /usr/bin/php8.3 /usr/home/bidalujwfq/composer install --optimize-autoloader --no-dev &
> ```
> Then check progress with: `tail -f nohup.out`

---

## Step 4: Fix Absolute Paths in index.php

SSH via Putty and edit `public_html/index.php`:

```bash
nano /usr/home/bidalujwfq/public_html/index.php
```

Find and replace these two lines:

**Line ~34 - Autoloader:**
```php
// ORIGINAL (remove this):
require __DIR__.'/../vendor/autoload.php';

// REPLACE WITH:
require '/usr/home/bidalujwfq/vendor/autoload.php';
```

**Line ~47 - Bootstrap:**
```php
// ORIGINAL (remove this):
$app = require_once __DIR__.'/../bootstrap/app.php';

// REPLACE WITH:
$app = require_once '/usr/home/bidalujwfq/bootstrap/app.php';
```

Save with `Ctrl+O`, exit with `Ctrl+X`.

> **Check the file is complete** — after editing, verify the last line of `index.php` reads `$app->handleRequest(Request::capture());`. If the file was uploaded truncated, add this line manually at the bottom before saving.

---

## Step 5: Fix Absolute Paths in artisan

```bash
nano /usr/home/bidalujwfq/artisan
```

**Line ~18 - Autoloader:**
```php
// ORIGINAL:
require __DIR__.'/../vendor/autoload.php';

// REPLACE WITH:
require '/usr/home/bidalujwfq/vendor/autoload.php';
```

**Line ~20 - Bootstrap:**
```php
// ORIGINAL:
$app = require_once __DIR__.'/../bootstrap/app.php';

// REPLACE WITH:
$app = require_once '/usr/home/bidalujwfq/bootstrap/app.php';
```

Save with `Ctrl+O`, exit with `Ctrl+X`.

---

## Step 6: Environment Configuration

If you haven't already uploaded your `.env`, create one from the example:

```bash
cd /usr/home/bidalujwfq
cp .env.example .env
nano .env
```

Set these values for production:

```env
APP_NAME="Basic Bidall"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database - get these from cPanel > MySQL Databases
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bidalujwfq_dbname
DB_USERNAME=bidalujwfq_dbuser
DB_PASSWORD=your-db-password

# Sessions & Cache - use file driver (no Redis on shared hosting)
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Mail - use cPanel email or external SMTP
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Basic Bidall"

# PayFast Live
PAYFAST_MERCHANT_ID=your-live-merchant-id
PAYFAST_MERCHANT_KEY=your-live-merchant-key
PAYFAST_PASSPHRASE=your-live-passphrase
PAYFAST_SANDBOX=false

# Pricing
TIER_BASIC_PRICE=1
TIER_PRO_PRICE=5
TIER_PREMIUM_PRICE=20

# External Cron Secret (for cron-job.org endpoint)
CRON_SECRET=your-secret-token-here
```

> **Important**: On Xneelo shared hosting, database names and usernames are prefixed with your FTP username. Create the database first in cPanel (see Step 8).

---

## Step 7: Generate Application Key

```bash
cd /usr/home/bidalujwfq
/usr/bin/php8.3 artisan key:generate
```

Verify the key was added to `.env`:
```bash
grep APP_KEY .env
```

---

## Step 8: Create Database via cPanel

Do not use MySQL CLI directly. Use cPanel:

1. Log in to your Xneelo cPanel
2. Go to **MySQL Databases**
3. Create a new database (name will be prefixed: `bidalujwfq_dbname`)
4. Create a new database user with a strong password
5. Add the user to the database with **All Privileges**
6. Update your `.env` with the database name, username, and password

---

## Step 9: Run Migrations

```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan migrate --force
```

---

## Step 10: Create Admin User

```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan tinker
```

When prompted to trust the project, type `Y` and press Enter. Then enter each line one at a time:

```php
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@yourdomain.com';
$user->password = Hash::make('your-secure-password');
$user->role = 'admin';
$user->address = '1 Main Street';
$user->city = 'Johannesburg';
$user->province = 'Gauteng';
$user->save();
exit
```

Each line should return a confirmation value. `$user->save()` should return `true`.

To add additional admin users later, repeat this same process.

---

## Step 11: Storage Setup

Create the storage symlink so uploaded images are accessible from the web.

> **Important**: If you uploaded the contents of `public/` via FTP, a `storage/` folder may already exist in `public_html/` (Windows treats the symlink as a folder). If so, delete it first before creating the symlink:
> ```bash
> rm -rf /usr/home/bidalujwfq/public_html/storage
> ```

```bash
ln -s /usr/home/bidalujwfq/storage/app/public /usr/home/bidalujwfq/public_html/storage
```

Verify the symlink was created correctly (not inside a folder):
```bash
ls -la /usr/home/bidalujwfq/public_html/ | grep storage
```
Should show: `lrwxrwxrwx ... storage -> /usr/home/bidalujwfq/storage/app/public`

> **If symlinks are not supported**, create the directories manually in `public_html/storage/` and update `FILESYSTEM_DISK=public` to point directly. Contact Xneelo support to confirm symlink support if images don't display.

Create required storage directories:
```bash
mkdir -p /usr/home/bidalujwfq/storage/app/public/images/lots/optimized
mkdir -p /usr/home/bidalujwfq/storage/app/public/images/lots/thumbnails
mkdir -p /usr/home/bidalujwfq/storage/app/public/auctioneers/logos
mkdir -p /usr/home/bidalujwfq/storage/app/public/auctioneers/profiles
mkdir -p /usr/home/bidalujwfq/storage/app/public/auctioneers/banners
```

Set correct permissions on storage:
```bash
chmod -R 775 /usr/home/bidalujwfq/storage
chmod -R 775 /usr/home/bidalujwfq/bootstrap/cache
```

**Fix Vite manifest path** — Laravel looks for `public/build/manifest.json` but Xneelo uses `public_html/`. Create a symlink so Laravel finds it:
```bash
mkdir -p /usr/home/bidalujwfq/public
ln -s /usr/home/bidalujwfq/public_html/build /usr/home/bidalujwfq/public/build
```

> **Important**: Do this before running `view:cache` or the site will show a 500 error.

---

## Step 12: SSL Certificate via cPanel

Do not use certbot. Xneelo manages SSL through cPanel:

1. Log in to cPanel
2. Go to **SSL/TLS** or **Let's Encrypt SSL**
3. Issue a free SSL certificate for your domain
4. Verify `https://yourdomain.com` loads correctly

SSL is required for PayFast live payments.

---

## Step 13: Set Up Cron for Laravel Scheduler

The scheduler handles auction status transitions (upcoming → live → ended), winner emails, and image cleanup. Auction timing requires minute-level accuracy.

> **Xneelo Limitation**: Xneelo shared hosting cron jobs run at a minimum of every 2 hours — not acceptable for live auction timing. The solution is to use **cron-job.org** (free external service) to call a secure endpoint every minute, with the Xneelo cron as a fallback.

### Part A: cron-job.org (Primary — every minute)

1. Create a free account at [cron-job.org](https://cron-job.org)
2. Create a new cron job:
   - **URL**: `https://www.bidall.co.za/cron/run?token=YOUR_CRON_SECRET`
   - **Schedule**: Every minute
3. The endpoint is secured by `CRON_SECRET` in `.env` — requests without the correct token receive a 403 response
4. The endpoint runs two commands:
   - `auctions:update-statuses` — transitions auction/lot statuses
   - `emails:send-auction-summaries` — sends winner emails after auctions end

Replace `YOUR_CRON_SECRET` with the value of `CRON_SECRET` from your `.env`.

### Part B: Xneelo cPanel Cron (Fallback — every 2 hours)

1. Log in to cPanel
2. Go to **Cron Jobs**
3. Set frequency to every 2 hours (`0 */2 * * *`)
4. Set the command to:
   ```
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan schedule:run >> /dev/null 2>&1
   ```

This fallback runs the full scheduler in case cron-job.org is unavailable, but it is not precise enough to use alone for live auctions.

Verify the scheduler commands:
```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan schedule:list
```

---

## Step 14: Optimise Laravel for Production

```bash
cd /usr/home/bidalujwfq

/usr/bin/php8.3 artisan config:cache
/usr/bin/php8.3 artisan route:cache
/usr/bin/php8.3 artisan view:cache
/usr/bin/php8.3 artisan event:cache
```

---

## Step 15: PayFast Webhook IP Whitelisting

Restrict PayFast webhook access to official PayFast IPs only. On shared hosting this is done via `.htaccess`.

Add this to `/usr/home/bidalujwfq/public_html/.htaccess` (before the Laravel rewrite rules):

```apache
# PayFast webhook IP whitelist
<IfModule mod_authz_core.c>
    <Location /payment/webhook>
        Require ip 197.97.145.144/28
        Require ip 41.74.179.192/27
        Require ip 102.216.36.0/28
        Require ip 102.216.36.128/28
        Require ip 144.126.193.139
    </Location>
</IfModule>
<IfModule !mod_authz_core.c>
    <Location /payment/webhook>
        Order Deny,Allow
        Deny from all
        Allow from 197.97.145.144/28
        Allow from 41.74.179.192/27
        Allow from 102.216.36.0/28
        Allow from 102.216.36.128/28
        Allow from 144.126.193.139
    </Location>
</IfModule>
```

> If Xneelo's shared hosting does not support `<Location>` in `.htaccess`, the application-level PayFast signature validation already provides security. The webhook URL itself is not guessable.

---

## Step 16: Verify Deployment

Work through this checklist in order:

- [ ] `https://yourdomain.com` loads without errors
- [ ] `/login` page loads correctly
- [ ] Register a bidder account - confirm redirect to dashboard
- [ ] Register an auctioneer account - confirm redirect to credits page
- [ ] Log in as admin - confirm admin panel loads
- [ ] Purchase credits (use PayFast sandbox first to test)
- [ ] Create a test auction and add lots with images
- [ ] Confirm images display correctly (storage symlink working)
- [ ] Publish auction and confirm scheduler transitions it to live
- [ ] Place a test bid
- [ ] Confirm auction ends and lot shows as sold/unsold

---

## Updating the Application

When you push changes to production:

1. Upload changed files via FTP (do not overwrite `public_html/index.php` - it has your path edits)
2. If `public/` files changed, upload those to `public_html/` (except `index.php`)
3. If JS/CSS changed, run `npm run build` locally first and upload `public/build/`
4. Via Putty:

```bash
cd /usr/home/bidalujwfq

# If composer.json changed:
COMPOSER_MEMORY_LIMIT=-1 /usr/bin/php8.3 /usr/home/bidalujwfq/composer install --optimize-autoloader --no-dev

# Run any new migrations:
/usr/bin/php8.3 artisan migrate --force

# Clear and rebuild caches:
/usr/bin/php8.3 artisan config:cache
/usr/bin/php8.3 artisan route:cache
/usr/bin/php8.3 artisan view:cache
/usr/bin/php8.3 artisan event:cache
```

---

## Maintenance Mode

Enable during updates:
```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan down --secret="your-secret-token"
```

Access the site while in maintenance mode:
```
https://yourdomain.com/your-secret-token
```

Disable after updates:
```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan up
```

---

## Database Backups

Xneelo cPanel includes automated backups, but also run manual backups before any major change:

**Via cPanel:**
1. Go to **Backup** or **Backup Wizard**
2. Download a full backup or database-only backup

**Via Putty:**
```bash
mysqldump -u YOUR_DB_USER -p YOUR_DB_NAME > /usr/home/bidalujwfq/backup_$(date +%Y%m%d).sql
```

Download the `.sql` file via FTP after creating it.

---

## Troubleshooting

### Enable error display temporarily

Edit `.env`:
```env
APP_DEBUG=true
```

Then clear config cache:
```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan config:clear
```

**Turn APP_DEBUG back to false immediately after diagnosing the issue.**

### Check Laravel logs via Putty

```bash
tail -100 /usr/home/bidalujwfq/storage/logs/laravel.log
```

Or from Xneelo's Spock article for browser-based error display:
https://spock.host-h.net/knowledge-base/enable-debugging-error-display-on-laravel/

### 500 Internal Server Error

1. Check Laravel log (above)
2. Verify `public_html/index.php` has correct absolute paths (Step 4)
3. Verify `artisan` has correct absolute paths (Step 5)
4. Verify `vendor/` exists: `ls /usr/home/bidalujwfq/vendor/`
5. Check `.env` exists and `APP_KEY` is set
6. Clear all caches:
   ```bash
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan config:clear
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan route:clear
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan view:clear
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan cache:clear
   ```

### 500 error — Vite manifest not found

Laravel looks for `public/build/manifest.json` but Xneelo uses `public_html/` as the web root. Fix:
```bash
mkdir -p /usr/home/bidalujwfq/public
ln -s /usr/home/bidalujwfq/public_html/build /usr/home/bidalujwfq/public/build
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan view:clear
```

### Images not displaying

1. Verify symlink exists: `ls -la /usr/home/bidalujwfq/public_html/storage`
2. If symlink missing, recreate: `ln -s /usr/home/bidalujwfq/storage/app/public /usr/home/bidalujwfq/public_html/storage`
3. Check storage directory permissions: `chmod -R 775 /usr/home/bidalujwfq/storage`

### PayFast webhook not working

1. Verify `APP_URL` in `.env` matches your live domain exactly (with https)
2. Confirm `payment/webhook` route is accessible - visit `https://yourdomain.com/payment/webhook` (should return 405, not 404)
3. Check `storage/logs/laravel.log` for webhook errors
4. Verify PayFast notify URL is set to `https://yourdomain.com/payment/webhook`

### Scheduler not running (auctions not going live)

1. Verify cron-job.org job is active and the URL and token are correct (Step 13)
2. Test the cron endpoint manually in a browser — copy the full URL carefully as one unbroken string (long tokens are easy to accidentally truncate when copying):
   ```
   https://www.bidall.co.za/cron/run?token=YOUR_FULL_CRON_SECRET
   ```
   Should return `{"status":"ok","time":"..."}`. A 403 means the token is wrong or truncated.
3. If debugging 403 on the cron endpoint — note that `abort(403)` does NOT appear in Laravel logs. To confirm if Laravel is processing the request at all, visit `/cron/nonexistent` — if you get 404, Laravel is running. If you get 403 on that too, Apache is blocking the path.
4. Test manually via Putty:
   ```bash
   /usr/bin/php8.3 /usr/home/bidalujwfq/artisan auctions:update-statuses
   ```
5. Check output for errors

### Composer install times out in Putty

Use `nohup` to keep it running after disconnect:
```bash
nohup COMPOSER_MEMORY_LIMIT=-1 /usr/bin/php8.3 /usr/home/bidalujwfq/composer install --optimize-autoloader --no-dev &
tail -f nohup.out
```

### .env file truncated after FTP upload

FileZilla can truncate the `.env` file during upload, cutting off everything after the standard Laravel settings (typically at `MAIL_FROM_NAME`). This causes missing PayFast credentials, CRON_SECRET, pricing config, etc.

**Verify the file is complete:**
```bash
grep CRON_SECRET /usr/home/bidalujwfq/.env
grep PAYFAST_MERCHANT_ID /usr/home/bidalujwfq/.env
```

**Fix — append the missing content via heredoc in Putty** (no file upload needed):
```bash
cat >> /usr/home/bidalujwfq/.env << 'ENVEOF'

# Platform Configuration
PLATFORM_REGION=south-africa
PAYMENT_GATEWAY=payfast
[... rest of missing content ...]
ENVEOF
```

Then rebuild config cache:
```bash
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan config:clear
/usr/bin/php8.3 /usr/home/bidalujwfq/artisan config:cache
```

**Note**: If you end up with duplicate keys (e.g. two `CRON_SECRET=` lines), phpdotenv uses the last value. Remove duplicates with `nano /usr/home/bidalujwfq/.env`.

### Sessions not persisting / users logged out constantly

Ensure `.env` has:
```env
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=yourdomain.com
```

---

## Post-Deployment Checklist

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] HTTPS working
- [ ] PayFast live credentials set (not sandbox)
- [ ] `PAYFAST_SANDBOX=false` in `.env`
- [ ] `CRON_SECRET` set in `.env`
- [ ] Email sending works
- [ ] cron-job.org job active (every minute, correct token in URL)
- [ ] Xneelo cPanel fallback cron set (every 2 hours)
- [ ] Storage symlink working - images display
- [ ] All cache commands run
- [ ] Admin user created and can log in
- [ ] Database backup taken
- [ ] Test auction completed end-to-end
