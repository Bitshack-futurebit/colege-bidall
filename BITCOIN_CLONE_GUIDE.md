# Bitcoin Clone Guide - From Basic Bidall to BitBidall

This guide shows you how to clone the South African platform and launch an international Bitcoin-only version.

## 🎯 Overview

Thanks to the abstraction layer built into the codebase, creating a Bitcoin version is **90% configuration** and **10% BTCPay implementation**.

**Time Estimate**: 4-8 hours (mostly BTCPay API integration)

---

## 📋 What's Already Abstracted

✅ **Currency System** - Just change config, all displays update
✅ **Payment Gateway Interface** - Swap PayFast for BTCPay
✅ **Regional Features** - WhatsApp, map, etc. toggle via config
✅ **Language Files** - Separate en_US translations ready
✅ **Branding** - Logo, colors, name all configurable
✅ **Business Logic** - Models work with any currency

---

## 🚀 Step-by-Step Cloning Process

### Step 1: Clone Repository

```bash
# Option A: Fresh clone
git clone https://github.com/yourusername/basic_bidall.git bitbidall
cd bitbidall

# Option B: Duplicate locally
cp -r F:/basic_bidall F:/bitbidall
cd F:/bitbidall
```

### Step 2: Update .env for Bitcoin

Copy `.env.example` to `.env` and change these values:

```env
# Application
APP_NAME="BitBidall"
APP_URL=https://bitbidall.com
APP_LOCALE=en_US
APP_TIMEZONE=UTC

# Platform Configuration
PLATFORM_REGION=international
PAYMENT_GATEWAY=btcpay

# Currency Configuration (CRITICAL)
CURRENCY_CODE=BTC
CURRENCY_SYMBOL=₿
CURRENCY_NAME=Bitcoin
CURRENCY_DECIMALS=8
BTC_DENOMINATION=sats           # Display in satoshis

# Branding
BRAND_NAME="BitBidall"
BRAND_SHORT_NAME="BitBidall"
BRAND_TAGLINE="Bitcoin-Only Global Auctions"
BRAND_COLOR_PRIMARY=#f7931a     # Bitcoin orange
BRAND_COLOR_SECONDARY=#4d4d4d   # Gray

# Regional Features (Disable SA-specific)
FEATURE_WHATSAPP=false
FEATURE_MAP=true
FEATURE_DEPOSITS=true
FEATURE_BUYERS_PREMIUM=true
FEATURE_RESERVE_PRICES=true

# Pricing (in satoshis)
ACTIVATION_FEE=100000           # 100k sats (~$40-50)
TIER_BASIC_PRICE=5000           # 5k sats (~$2-3)
TIER_PRO_PRICE=25000            # 25k sats (~$10-12)
TIER_PREMIUM_PRICE=100000       # 100k sats (~$40-50)
PLATFORM_PERCENTAGE_FEE=1       # Still 1%
PLATFORM_LOT_FEE=1000           # 1k sats (~$0.40-0.50)

MINIMUM_BID=1000                # 1k sats minimum
MINIMUM_INCREMENT=1000          # 1k sats minimum increment

# BTCPay Server (Replace with your instance)
BTCPAY_SERVER_URL=https://btcpay.yourdomain.com
BTCPAY_API_KEY=your_api_key_here
BTCPAY_STORE_ID=your_store_id_here
BTCPAY_SANDBOX=false

# Map Configuration (World view)
MAP_DEFAULT_LAT=20.0
MAP_DEFAULT_LNG=0.0
MAP_DEFAULT_ZOOM=2

# Contact (International)
CONTACT_EMAIL=support@bitbidall.com
CONTACT_PHONE=
```

### Step 3: Update Database Name

```env
DB_DATABASE=bitbidall
```

Create the database:
```bash
mysql -u root -e "CREATE DATABASE bitbidall"
```

### Step 4: Install Dependencies

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

### Step 5: Implement BTCPay Gateway

The placeholder is at `app/Services/Payments/BTCPayGateway.php`.

**What you need to implement**:

1. **Create Invoice** (`createPayment` method)
   ```php
   // API: POST /api/v1/stores/{storeId}/invoices
   $response = Http::withHeaders([
       'Authorization' => 'token ' . $this->apiKey,
   ])->post("{$this->serverUrl}/api/v1/stores/{$this->storeId}/invoices", [
       'amount' => $amountSats,
       'currency' => 'BTC',
       'orderId' => $paymentId,
       // ... rest of invoice data
   ]);

   return [
       'redirect_url' => $response->json('checkoutLink'),
       'lightning_invoice' => $response->json('lightningInvoice'),
       // ...
   ];
   ```

2. **Verify Payment** (`verifyPayment` method)
   ```php
   // API: GET /api/v1/stores/{storeId}/invoices/{invoiceId}
   $response = Http::withHeaders([
       'Authorization' => 'token ' . $this->apiKey,
   ])->get("{$this->serverUrl}/api/v1/stores/{$this->storeId}/invoices/{$invoiceId}");

   return [
       'success' => $response->json('status') === 'Settled',
       // ...
   ];
   ```

3. **Handle Webhooks** (`handleWebhook` method)
   - Already structured, just validate signature
   - Update transaction status based on BTCPay webhook

**BTCPay API Documentation**: https://docs.btcpayserver.org/API/Greenfield/v1/

**Estimated Time**: 2-4 hours

### Step 6: Update Branding Assets

Replace these files:
- `/public/images/logo.svg` - New Bitcoin-themed logo
- `/public/images/logo-dark.svg` - Dark mode version
- `/public/images/icon.svg` - App icon
- `/public/favicon.ico` - Favicon

**Design Tips**:
- Use Bitcoin orange (#f7931a)
- Include ₿ symbol or Bitcoin imagery
- Keep it simple and professional

### Step 7: Test Everything

```bash
# Start servers
php artisan serve
npm run dev

# Test in browser
http://localhost:8000
```

**Test Checklist**:
- [ ] All prices display in sats
- [ ] Currency symbol shows ₿ or "sats"
- [ ] BTCPay payment creates invoice
- [ ] Webhook updates transaction status
- [ ] Credits deducted in sats
- [ ] Language is en_US
- [ ] No WhatsApp buttons (if disabled)
- [ ] Map shows world view
- [ ] Logo/branding is Bitcoin-themed

### Step 8: Deploy

```bash
# Build assets
npm run build

# Deploy to server
# (Same process as SA version, just different domain)
```

**Deployment**:
- Domain: `bitbidall.com` (or your choice)
- Separate database: `bitbidall`
- Separate `.env` file with Bitcoin config
- Point to BTCPay Server instance

---

## 🔄 Maintaining Both Versions

### Shared Updates (Bug Fixes, Features)

When you fix a bug or add a feature to one version:

1. **Commit to Git**
   ```bash
   cd F:/basic_bidall
   git add .
   git commit -m "Fix: Description"
   git push
   ```

2. **Pull into other version**
   ```bash
   cd F:/bitbidall
   git pull origin main
   ```

3. **No conflicts** (because everything is config-based!)

### Version-Specific Updates

If a feature only applies to one version:

**Use feature flags**:
```php
@if(config('regional.features.whatsapp_integration'))
    <!-- WhatsApp button (SA only) -->
@endif

@if(isBitcoin())
    <!-- Lightning invoice QR (Bitcoin only) -->
@endif
```

**Or separate files**:
- `resources/views/payments/payfast.blade.php` (SA)
- `resources/views/payments/btcpay.blade.php` (Bitcoin)

---

## 📊 Configuration Comparison

| Setting | South Africa | Bitcoin/International |
|---------|--------------|----------------------|
| **PLATFORM_REGION** | south-africa | international |
| **PAYMENT_GATEWAY** | payfast | btcpay |
| **CURRENCY_CODE** | ZAR | BTC |
| **CURRENCY_SYMBOL** | R | ₿ |
| **BTC_DENOMINATION** | - | sats |
| **ACTIVATION_FEE** | 500 (R500) | 100000 (100k sats) |
| **TIER_BASIC_PRICE** | 1 (R1) | 5000 (5k sats) |
| **TIER_PRO_PRICE** | 5 (R5) | 25000 (25k sats) |
| **TIER_PREMIUM_PRICE** | 20 (R20) | 100000 (100k sats) |
| **PLATFORM_LOT_FEE** | 1 (R1) | 1000 (1k sats) |
| **FEATURE_WHATSAPP** | true | false |
| **APP_LOCALE** | en_ZA | en_US |
| **APP_TIMEZONE** | Africa/Johannesburg | UTC |
| **MAP_DEFAULT_LAT** | -25.7479 | 20.0 |
| **MAP_DEFAULT_ZOOM** | 6 (SA) | 2 (World) |
| **BRAND_COLOR_PRIMARY** | #22c55e (Green) | #f7931a (Orange) |

---

## 💡 Helper Functions You Can Use

All these work automatically based on config:

```php
// In PHP/Blade
formatCurrency(1250)              // SA: "R1,250.00" | BTC: "1,250 sats"
currencySymbol()                  // SA: "R" | BTC: "₿"
isBitcoin()                       // SA: false | BTC: true
isSouthAfrica()                   // SA: true | BTC: false

formatPrice('platform.pricing.activation_fee')
// SA: "R500" | BTC: "100,000 sats"

minBid()                          // Returns minimum bid for platform
minIncrement()                    // Returns minimum increment

getPlatformRegion()               // "south-africa" | "international"
```

```html
<!-- In Blade -->
{{ formatCurrency($lot->current_bid) }}

@if(config('regional.features.whatsapp_integration'))
    <button>WhatsApp</button>
@endif

{{ __('auction.place_bid') }}
<!-- SA: "Place Bid" | BTC: "Place Bid" (but from en_US file) -->

{{ __('payments.pay_deposit') }}
<!-- SA: "Pay Deposit" | BTC: "Pay Deposit (Lightning/On-chain)" -->
```

---

## 🎨 UI Differences

### South Africa Version
- Green theme (#22c55e)
- "R" currency symbol
- PayFast payment buttons
- WhatsApp contact buttons
- South Africa map focus
- "Basic Bidall" branding

### Bitcoin Version
- Orange theme (#f7931a)
- "₿" or "sats" display
- BTCPay payment with Lightning/On-chain options
- No WhatsApp (international)
- World map view
- "BitBidall" branding
- QR codes for Lightning invoices
- On-chain confirmation tracking

---

## 🔧 BTCPay Server Setup

### 1. Install BTCPay Server

**Options**:
- Self-hosted: https://docs.btcpayserver.org/Deployment/
- Third-party host: https://directory.btcpayserver.org/
- LunaNode/Digital Ocean one-click deploy

**Recommended**: Use Docker deployment for simplicity

### 2. Create Store

1. Login to BTCPay dashboard
2. Create new store: "BitBidall"
3. Connect Bitcoin node (or use shared node)
4. Enable Lightning Network (optional but recommended)

### 3. Generate API Key

1. Account Settings → API Keys
2. Create new key with permissions:
   - `btcpay.store.canviewinvoices`
   - `btcpay.store.cancreateinvoice`
   - `btcpay.store.webhooks.canmodifywebhooks`
3. Copy API key to `.env`

### 4. Configure Webhooks

1. Store Settings → Webhooks
2. Add webhook: `https://bitbidall.com/api/payment/webhook`
3. Events: Invoice settlement, Invoice expired
4. Save

### 5. Get Store ID

Found in store settings URL: `/stores/{storeId}/settings`

---

## 📝 Pricing Recommendations (Bitcoin)

Convert USD equivalents to sats based on current rate (~$40k BTC):

| Feature | USD | Sats (approx) |
|---------|-----|---------------|
| Activation | $40-50 | 100,000 |
| Basic Tier | $2-3 | 5,000 |
| Pro Tier | $10-12 | 25,000 |
| Premium Tier | $40-50 | 100,000 |
| Lot Fee | $0.40-0.50 | 1,000 |
| Min Bid | $0.40-0.50 | 1,000 |

**Update these values in `.env` as Bitcoin price fluctuates!**

---

## ✅ Launch Checklist

- [ ] `.env` configured for Bitcoin
- [ ] BTCPay Server connected and tested
- [ ] Database created and migrated
- [ ] All prices converted to sats
- [ ] Branding updated (logo, colors, name)
- [ ] Language files reviewed (en_US)
- [ ] Payment flow tested end-to-end
- [ ] Lightning invoice generation works
- [ ] On-chain payments work
- [ ] Webhooks receive and process correctly
- [ ] Credits deduct in sats
- [ ] Domain configured (bitbidall.com)
- [ ] SSL certificate installed
- [ ] Email configured
- [ ] Cron jobs set up

---

## 🎉 You're Done!

Your Bitcoin version is now live with:
- ✅ All auction features working
- ✅ Bitcoin payments via BTCPay
- ✅ Sats denomination
- ✅ International focus
- ✅ Separate branding

**Both platforms share 95% of code** - bugs fixed in one automatically apply to the other!

---

## 📞 Need Help?

- BTCPay Docs: https://docs.btcpayserver.org/
- BTCPay Chat: https://chat.btcpayserver.org/
- Laravel Docs: https://laravel.com/docs

Good luck with the launch! 🚀₿
