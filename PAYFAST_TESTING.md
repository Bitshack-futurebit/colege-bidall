# PayFast Sandbox Testing Guide

## ✅ Configuration Complete

Your PayFast sandbox credentials are now configured:
- **Merchant ID**: 10045660
- **Merchant Key**: vtwrigffppkx0
- **Passphrase**: (Empty for testing - set in production)
- **Sandbox Mode**: Enabled

## Local Development Setup

### ngrok Configuration (Required for Webhooks)

PayFast webhooks need a publicly accessible URL. Use ngrok during local development:

**1. Start ngrok:**
```bash
ngrok http 8000 --request-header-add "ngrok-skip-browser-warning:true"
```

**2. Update `.env` with ngrok URL:**
```env
APP_URL=https://your-ngrok-url.ngrok-free.dev
```

**3. Clear Laravel config:**
```bash
php artisan config:clear
```

**4. Access platform via ngrok URL:**
- Use `https://your-ngrok-url.ngrok-free.dev` instead of `localhost:8000`
- This ensures PayFast can reach your webhook endpoints

**Note:** ngrok free tier URLs change on restart. Update APP_URL each time you restart ngrok.

## Testing Checklist

### 1. Credit Purchase Flow (Auctioneer)

**Steps:**
1. Login as auctioneer: `robert.anderson@example.com` / `password`
2. Go to: **Auctioneer Dashboard → Buy Credits**
3. Enter amount: **R100** (minimum)
4. Click **"Purchase Credits"**
5. Should redirect to PayFast sandbox payment page
6. Complete payment with test card details

**PayFast Sandbox Test Card:**
```
Card Number: 4111 1111 1111 1111
Expiry: Any future date (e.g., 12/26)
CVV: Any 3 digits (e.g., 123)
```

**Expected Result:**
- Redirected back to platform
- Credits added to auctioneer balance
- Transaction recorded in database
- Status shows "completed"

### 2. Lot Payment Flow (Bidder)

**Steps:**
1. Register/login as bidder: `john.smith@example.com` / `password`
2. Win a lot in a live auction
3. Go to: **Dashboard → Won (Unpaid)**
4. Click **"Pay Now"**
5. Complete payment at PayFast sandbox
6. Return to platform

**Expected Result:**
- Lot marked as paid
- Transaction recorded
- Status updated

### 3. Webhook Testing (Already Configured)

**✅ Webhook Setup Complete:**
- CSRF exclusion added for `payment/webhook` route
- Webhook signature validation implemented
- Automatic credit addition on successful payment

**Webhook URL (auto-generated from APP_URL):**
```
https://your-ngrok-url.ngrok-free.dev/payment/webhook
```

**To verify webhook is working:**
1. Complete a test payment
2. Check logs: `storage/logs/laravel.log`
3. Look for: "PayFast webhook received" and "Credits added"
4. Verify credits appear in auctioneer dashboard

### 4. Manual Transaction Completion (Debug Tool)

**For pending transactions (when webhook fails):**

Visit: `http://localhost:8000/debug-transactions`

This page shows:
- All pending credit purchase transactions
- Current auctioneer credit balances
- "Complete & Add Credits" button for manual processing

**When to use:**
- Testing with localhost URLs (webhook can't reach localhost)
- Troubleshooting webhook issues
- Completing old transactions from before ngrok setup

### 4. Test Scenarios

**A. Successful Payment**
- Complete payment with test card
- Verify webhook is received (check logs: `storage/logs/laravel.log`)
- Verify credits/payment is processed
- Check transaction status in admin panel

**B. Cancelled Payment**
- Start payment flow
- Click "Cancel" on PayFast page
- Verify redirected back with cancellation message
- Check transaction status is "failed"

**C. Webhook Validation**
- Check logs for: "PayFast webhook received"
- Verify signature validation passes
- Confirm transaction is updated

## Troubleshooting

### Common Issues & Solutions

**1. PayFast redirect not working**
- **Problem**: User stuck on PayFast page after payment
- **Solution**: Access site via ngrok URL (not localhost)
- **Why**: PayFast needs publicly accessible return_url

**2. Credits not being added automatically**
- **Problem**: Payment completes but credits don't appear
- **Solution**: Check if webhook is being received
- **Check logs**: Look for "PayFast webhook received" entry
- **If missing**: Webhook can't reach localhost - use ngrok and access via ngrok URL
- **Manual fix**: Use `/debug-transactions` to complete pending payments

**3. Session key mismatch error**
- **Problem**: "Payment reference not found" on return
- **Solution**: Fixed in latest code - controller now checks multiple session keys
- **Clear cache**: Run `php artisan config:clear` if issues persist

**4. CSRF token mismatch on webhook**
- **Problem**: Webhook returns 419 error
- **Solution**: ✅ Already fixed - `payment/webhook` excluded from CSRF in `bootstrap/app.php`

**5. Signature validation failing**
- **Problem**: PayFast returns signature error
- **Solution**:
  - Ensure passphrase in `.env` matches PayFast dashboard (or both empty for testing)
  - Signature generation now uses alphabetical sorting (✅ fixed)
  - Passphrase only added to signature, not form data (✅ fixed)

## Database Checks

**Check transactions:**
```sql
SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10;
```

**Check credit transactions:**
```sql
SELECT * FROM credit_transactions ORDER BY created_at DESC LIMIT 10;
```

**Check auctioneer balance:**
```sql
SELECT id, business_name, credit_balance FROM auctioneers;
```

## Production Deployment Guide

### Prerequisites
- ✅ PayFast integration tested and working in sandbox
- ✅ All payment flows verified (credit purchase, lot payment)
- ✅ Webhook processing confirmed
- ✅ Production domain with HTTPS certificate

### Production Configuration

**1. Update `.env` file:**
```env
# Set production URL (must be HTTPS)
APP_URL=https://yourdomain.com

# PayFast Production Credentials
PAYFAST_MERCHANT_ID=your_production_merchant_id
PAYFAST_MERCHANT_KEY=your_production_merchant_key
PAYFAST_PASSPHRASE=your_production_passphrase
PAYFAST_SANDBOX=false
```

**2. Clear Laravel cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**3. Verify PayFast Production Account:**
- Log into https://www.payfast.co.za/ (NOT sandbox)
- Get production merchant credentials
- Set strong passphrase in PayFast dashboard
- Webhook URL will be auto-generated: `https://yourdomain.com/payment/webhook`

### Production Checklist

- [ ] Set `PAYFAST_SANDBOX=false` in `.env`
- [ ] Update to production merchant credentials
- [ ] Set production passphrase (both in `.env` and PayFast dashboard)
- [ ] Verify `APP_URL` is production domain with HTTPS
- [ ] Clear all Laravel caches
- [ ] Test with small real payment first (R10-R50)
- [ ] Verify webhook is received (check logs)
- [ ] Monitor transactions for first 24 hours
- [ ] Remove or protect debug routes (`/debug-transactions`, `/test-payment-debug`)

### What Works in Production

✅ **Automatic Payment Processing**:
- User clicks "Purchase Credits"
- Redirected to PayFast (production gateway)
- Completes payment
- PayFast sends webhook to your server
- Credits automatically added to account
- User redirected back with success message

✅ **No ngrok Required**:
- Production domain is publicly accessible
- PayFast can reach webhook directly
- No tunneling or proxy needed

## Support

**PayFast Sandbox Support:**
- Dashboard: https://sandbox.payfast.co.za/
- Docs: https://developers.payfast.co.za/
- Test Cards: https://developers.payfast.co.za/docs#test-cards

**Local Logs:**
```bash
tail -f storage/logs/laravel.log
```
