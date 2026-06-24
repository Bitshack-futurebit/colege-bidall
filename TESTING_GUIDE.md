# 🚀 Complete Testing Guide - Basic Bidall Platform

## Prerequisites Check

Before starting, you need:
- ✅ PHP 8.1+ (Recommended: Install [Laragon](https://laragon.org/) - includes PHP, MySQL, Composer)
- ✅ Composer
- ✅ Node.js 18+ and NPM
- ✅ MySQL 8.0+

### Quick Install: Laragon (RECOMMENDED)
1. Download Laragon from https://laragon.org/download/
2. Install and start Laragon
3. Laragon includes: PHP, MySQL, Composer, Apache - everything you need!

---

## Step 1: Install Dependencies

Open your terminal in `F:\basic_bidall` and run:

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

**Expected output:**
- Composer will install ~50 packages (Laravel, etc.)
- NPM will install ~200 packages (Tailwind, Alpine.js, etc.)

**If you get errors:**
- "composer: command not found" → Install Composer or use Laragon
- "npm: command not found" → Install Node.js from https://nodejs.org/

---

## Step 2: Configure Environment

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

Now edit `.env` file with your settings:

```env
APP_NAME="Basic Bidall"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basic_bidall
DB_USERNAME=root
DB_PASSWORD=

# Platform Settings
CURRENCY_CODE=ZAR
CURRENCY_SYMBOL=R
PAYMENT_GATEWAY=payfast

# PayFast Sandbox Credentials (for testing)
PAYFAST_MERCHANT_ID=10000100
PAYFAST_MERCHANT_KEY=46f0cd694581a
PAYFAST_PASSPHRASE=
PAYFAST_SANDBOX=true

# Map Settings (Optional)
MAP_CENTER_LAT=-25.7461
MAP_CENTER_LNG=28.1881
MAP_DEFAULT_ZOOM=6

# Features
FEATURE_WHATSAPP=true
FEATURE_MAP=true
```

---

## Step 3: Create Database

### Option A: Using Laragon
1. Start Laragon
2. Click "Database" → "Create Database"
3. Name it: `basic_bidall`

### Option B: Using MySQL Command Line
```sql
CREATE DATABASE basic_bidall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Option C: Using phpMyAdmin
1. Open http://localhost/phpmyadmin
2. Click "New" → Enter "basic_bidall" → Click "Create"

---

## Step 4: Run Migrations

```bash
# Run all migrations to create tables
php artisan migrate

# IMPORTANT: Creates all database tables:
# - users, auctioneers, events, lots, bids
# - images, transactions, activities
# - watchlists, event_registrations
```

**Expected output:**
```
Migration table created successfully.
Migrating: 2024_01_01_000000_create_users_table
Migrated:  2024_01_01_000000_create_users_table (45.23ms)
...
(~15-20 migrations)
```

---

## Step 5: Seed Test Data (Optional but Recommended)

Let me create a seeder for you with test data:

```bash
php artisan db:seed
```

This will create:
- 1 Admin user
- 3 Test auctioneers (1 activated, 2 pending)
- 5 Test bidders
- 2 Live events with lots
- Sample bids

**Test Login Credentials:**
- Admin: admin@bidall.co.za / password
- Auctioneer: auctioneer@test.com / password
- Bidder: bidder@test.com / password

---

## Step 6: Start Development Servers

You need TWO terminal windows:

### Terminal 1: Laravel Server
```bash
cd F:\basic_bidall
php artisan serve
```

**Expected output:**
```
Starting Laravel development server: http://127.0.0.1:8000
```

### Terminal 2: Vite Dev Server (for CSS/JS)
```bash
cd F:\basic_bidall
npm run dev
```

**Expected output:**
```
VITE v5.x.x  ready in 450 ms

➜  Local:   http://localhost:5173/
➜  Network: use --host to expose
➜  press h to show help
```

**Keep both terminals running!**

---

## Step 7: Access the Platform

Open your browser and go to: **http://localhost:8000**

You should see:
- ✅ Homepage with navigation
- ✅ Live events (if seeded)
- ✅ Auctioneer map
- ✅ Login/Register buttons

---

## Testing Checklist

### 🎯 Test 1: Public Browsing (No Login Required)

1. **Homepage**
   - [ ] Navigate to http://localhost:8000
   - [ ] See platform stats
   - [ ] See live/upcoming events
   - [ ] Map displays (if data seeded)

2. **Browse Events**
   - [ ] Click "Browse Events" or navigate to /events
   - [ ] See event listings
   - [ ] Filter by status (All/Live/Upcoming)
   - [ ] Click on an event

3. **View Event Detail**
   - [ ] See event information
   - [ ] See lot listings
   - [ ] Search/filter lots
   - [ ] Click on a lot

4. **View Lot Detail**
   - [ ] See lot images (gallery)
   - [ ] See current bid amount
   - [ ] See countdown timer (if live)
   - [ ] See bid history
   - [ ] "Login to Bid" prompt for guests

5. **Other Pages**
   - [ ] Navigate to /about
   - [ ] Navigate to /how-it-works (see pricing)
   - [ ] View auctioneer profile (click on auctioneer name)

---

### 🎯 Test 2: Bidder Registration & Login

1. **Register**
   - [ ] Click "Sign Up"
   - [ ] Fill in registration form
   - [ ] Submit
   - [ ] Redirected to dashboard

2. **Login**
   - [ ] Logout
   - [ ] Click "Login"
   - [ ] Enter credentials
   - [ ] Successfully logged in
   - [ ] Redirected to dashboard

3. **Bidder Dashboard**
   - [ ] See stats (active bids, winning, watchlist)
   - [ ] See winning lots (if any)
   - [ ] Navigate to "My Bids"
   - [ ] Navigate to "Watchlist"
   - [ ] Navigate to "Won Lots"
   - [ ] Navigate to "Profile"

---

### 🎯 Test 3: Event Registration & Bidding

1. **Register for Event**
   - [ ] Navigate to an event
   - [ ] Click "Register Now"
   - [ ] If deposit required, payment page appears
   - [ ] Successfully registered

2. **Place Bid**
   - [ ] Navigate to a live lot
   - [ ] See "Quick Bid" button
   - [ ] Click "Quick Bid"
   - [ ] Bid amount updates
   - [ ] See "You have the top bid" message
   - [ ] Bid appears in bid history

3. **Custom Bid**
   - [ ] Enter custom amount
   - [ ] Click "Place Bid"
   - [ ] Bid accepted
   - [ ] Current bid updates

4. **Watchlist**
   - [ ] Click "Add to Watchlist"
   - [ ] Navigate to /dashboard/watchlist
   - [ ] See lot in watchlist
   - [ ] Click "Remove from Watchlist"
   - [ ] Lot removed

5. **Real-Time Updates**
   - [ ] Open same lot in two browser windows
   - [ ] Place bid in one window
   - [ ] Watch bid update in other window (within 3 seconds)
   - [ ] Countdown updates every second

---

### 🎯 Test 4: Seller Registration & Activation

1. **Register as Seller**
   - [ ] Logout
   - [ ] Click "Register as Auctioneer"
   - [ ] Fill in business information
   - [ ] Submit
   - [ ] Redirected to activation page

2. **Account Activation**
   - [ ] See activation fee (R500)
   - [ ] Click "Pay & Activate"
   - [ ] Redirected to payment page
   - [ ] See PayFast sandbox form
   - [ ] (In sandbox: simulate successful payment)
   - [ ] Redirected back with success message
   - [ ] Account activated

---

### 🎯 Test 5: Seller - Credit Management

1. **Buy Credits**
   - [ ] Navigate to /seller/credits
   - [ ] See credit packages
   - [ ] See current balance
   - [ ] Click "Purchase Now" on a package
   - [ ] Payment process initiated
   - [ ] Credits added to balance

2. **View Balance**
   - [ ] Dashboard shows credit balance
   - [ ] Balance updates after purchase

---

### 🎯 Test 6: Seller - Create Event

1. **Create Event**
   - [ ] Navigate to /seller/events/create
   - [ ] Fill in event details:
     - Title: "Test Auction Event"
     - Description
     - Start time (future date)
     - End time
   - [ ] Set deposit if desired
   - [ ] Click "Save & Publish"
   - [ ] Event created successfully

2. **View Event List**
   - [ ] Navigate to /seller/events
   - [ ] See created event
   - [ ] Status shows "Upcoming" or "Draft"
   - [ ] Click event to view details

3. **Edit Event**
   - [ ] Click "Edit" on event
   - [ ] Modify title/description
   - [ ] Save changes
   - [ ] Changes reflected

---

### 🎯 Test 7: Seller - Create Lots

1. **Navigate to Create Lot**
   - [ ] From event detail, click "Add Lots"
   - [ ] Or navigate to /seller/events/{event}/lots/create

2. **Create Basic Lot**
   - [ ] Select "Basic" tier (1 image, R1)
   - [ ] Fill in:
     - Title: "Antique Clock"
     - Description
     - Starting bid: R100
     - Increment: R10
   - [ ] Upload 1 image
   - [ ] Click "Create Lot"
   - [ ] Lot created, R1 deducted from credits

3. **Create Pro Lot**
   - [ ] Select "Pro" tier (5 images, R5)
   - [ ] Fill in details
   - [ ] Upload 3-5 images
   - [ ] Set reserve price (optional)
   - [ ] Create lot

4. **Create Premium Lot**
   - [ ] Select "Premium" tier (20 images, R20)
   - [ ] Upload 10+ images
   - [ ] Create lot

5. **Image Optimization**
   - [ ] After upload, images converted to WebP
   - [ ] Thumbnails generated
   - [ ] View lot to see image gallery working

---

### 🎯 Test 8: Seller - Analytics

1. **View Dashboard Analytics**
   - [ ] Navigate to /seller/dashboard
   - [ ] See event count stats
   - [ ] See lots created/sold
   - [ ] See recent events

2. **Event Analytics**
   - [ ] Navigate to /seller/analytics
   - [ ] See total events, lots, sales
   - [ ] See recent event performance
   - [ ] See top performing lots

---

### 🎯 Test 9: Admin Functions

1. **Admin Login**
   - [ ] Login as admin@bidall.co.za
   - [ ] Redirected to /admin/dashboard

2. **Admin Dashboard**
   - [ ] See platform statistics
   - [ ] See total users, events, revenue
   - [ ] See recent activity
   - [ ] See recent transactions

3. **User Management**
   - [ ] Navigate to /admin/users
   - [ ] See user list
   - [ ] Filter by role
   - [ ] Click on user to view details
   - [ ] Suspend/activate user

4. **Auctioneer Management**
   - [ ] Navigate to /admin/auctioneers
   - [ ] See auctioneer list
   - [ ] Filter by activation status
   - [ ] View auctioneer details

5. **Event Management**
   - [ ] Navigate to /admin/events
   - [ ] See all events
   - [ ] Filter by status
   - [ ] Delete event (draft only)

6. **Transactions**
   - [ ] Navigate to /admin/transactions
   - [ ] See transaction list
   - [ ] Filter by type/status
   - [ ] View transaction details

7. **Revenue Dashboard**
   - [ ] Navigate to /admin/revenue
   - [ ] See revenue breakdown
   - [ ] Filter by period
   - [ ] See top auctioneers

8. **Activity Logs**
   - [ ] Navigate to /admin/activity
   - [ ] See activity timeline
   - [ ] Filter by user/period

9. **Email Broadcast**
   - [ ] Navigate to /admin/broadcast
   - [ ] Select recipients
   - [ ] Write subject/message
   - [ ] See preview
   - [ ] Send test email (optional)
   - [ ] Send broadcast

---

### 🎯 Test 10: Real-Time Bidding (Advanced)

1. **Setup**
   - [ ] Create event with start time = now
   - [ ] Add lot with end time = 5 minutes from now
   - [ ] Event goes live automatically

2. **Soft Close Testing**
   - [ ] Wait until 1 minute before lot ends
   - [ ] Place bid
   - [ ] End time extends by 2 minutes
   - [ ] Repeat: place bid in last 2 minutes
   - [ ] End time extends again

3. **Multi-User Testing**
   - [ ] Open lot in 2 browsers (different accounts)
   - [ ] Place bid in browser 1
   - [ ] Browser 2 updates within 3 seconds
   - [ ] "Outbid" status shows
   - [ ] Alternate bids between browsers

4. **Winning Bidder**
   - [ ] Lot ends
   - [ ] Status changes to "Sold"
   - [ ] Winning bidder sees "Won" lot
   - [ ] Platform fee deducted from auctioneer credits

---

### 🎯 Test 11: Mobile Responsiveness

1. **Open on Mobile/Resize Browser**
   - [ ] Homepage responsive
   - [ ] Navigation works (hamburger menu)
   - [ ] Event grid adjusts
   - [ ] Lot detail readable
   - [ ] Bid buttons accessible
   - [ ] Forms usable

---

### 🎯 Test 12: Dark Mode

1. **Toggle Dark Mode**
   - [ ] Click dark mode toggle in nav
   - [ ] All colors invert properly
   - [ ] Text remains readable
   - [ ] Forms styled correctly
   - [ ] Preference persists on refresh

---

## Common Issues & Solutions

### Issue: "Class not found" errors
**Solution:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Issue: CSS not loading
**Solution:**
```bash
# Make sure Vite is running
npm run dev

# Or build for production
npm run build
```

### Issue: Images not uploading
**Solution:**
```bash
# Create storage link
php artisan storage:link

# Check storage/app/public permissions
```

### Issue: Database connection error
**Solution:**
- Verify MySQL is running
- Check .env database credentials
- Ensure database exists

### Issue: Routes not found
**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: PayFast not working
**Solution:**
- Using sandbox mode (PAYFAST_SANDBOX=true)
- Use sandbox credentials
- Check webhook URL is accessible

---

## Performance Testing

Once basic functionality works:

1. **Load Testing**
   - Create 50+ events
   - Create 500+ lots
   - Test search/filter performance

2. **Concurrent Bidding**
   - Have 5+ users bid on same lot
   - Verify all bids processed correctly
   - Check for race conditions

3. **Image Optimization**
   - Upload large images (5MB+)
   - Verify conversion to WebP
   - Check file sizes reduced

---

## Next Steps After Testing

1. **Fix any bugs found**
2. **Optimize performance**
3. **Add production .env settings**
4. **Set up proper email (SMTP)**
5. **Configure PayFast production credentials**
6. **Set up scheduled tasks** (lot closures, cleanup)
7. **Deploy to production server**

---

## Need Help?

Check these files for more info:
- `README.md` - Project overview
- `QUICK_START.md` - Quick start guide
- `SETUP_INSTRUCTIONS.md` - Detailed setup
- `BITCOIN_CLONE_GUIDE.md` - For Bitcoin version later

**Ready to test!** Start with Step 1 and work through systematically. 🚀
