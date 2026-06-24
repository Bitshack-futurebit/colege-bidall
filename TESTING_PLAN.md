# Performance & Batch Polling Upgrade — Testing Plan

Test locally (`php artisan serve` + `npm run dev`) before deploying to production.
Check each box as you pass it. If anything fails, note the issue and stop.

---

## Pre-requisites

- [ ] Run `php artisan migrate` (adds performance indexes + any pending migrations)
- [ ] Run `npm run build` (recompiles JS with all polling changes)
- [ ] Clear caches: `php artisan cache:clear && php artisan route:clear && php artisan view:clear`

---

## 1. Lot Detail Page — Bidding (CRITICAL)

This is the core bidding experience. Test with **two browser windows** (two different user accounts).

### 1a. Real-time polling
- [ ] Open a lot detail page as User A
- [ ] Confirm countdown timer ticks down every second
- [ ] Open DevTools Network tab — confirm polls go to `/api/lots/{id}/status` every ~5 seconds
- [ ] Confirm the polling request has **no cookies/session** (check Request Headers — no `Cookie:` header)
- [ ] Place a bid as User B in the second window
- [ ] User A's view updates within 5 seconds: new current bid, bid count, winning/outbid status

### 1b. Winning/outbid detection
- [ ] As User A, place the highest bid — confirm green "You're winning" badge shows
- [ ] As User B, outbid User A — confirm User A sees orange "Outbid" within 5 seconds
- [ ] Confirm bid form reappears for User A with updated minimum bid amount

### 1c. Bid history
- [ ] On lot detail page, confirm bid history section loads and shows all bids
- [ ] Place a new bid — confirm it appears in the history

### 1d. Proxy bidding (if enabled)
- [ ] Set a proxy bid — confirm proxy max displays
- [ ] Have another user bid — confirm proxy auto-bids up to the max

### 1e. Visibility pause
- [ ] Open lot detail, switch to another browser tab
- [ ] Check Network tab — confirm **no polling requests** while tab is hidden
- [ ] Switch back — confirm polling resumes immediately with a fresh fetch

---

## 2. Watchlist Page — Batch Polling (NEW FEATURE)

This is the biggest change. Test thoroughly.

### 2a. Basic display
- [ ] Add 3-5 lots to your watchlist from different auctions
- [ ] Navigate to `/dashboard/watchlist`
- [ ] Confirm all lots display with correct: title, current bid, countdown timer, winning/outbid status, thumbnail image

### 2b. Batch endpoint
- [ ] Open DevTools Network tab
- [ ] Confirm ONE request goes to `/api/lots/batch-status?ids=...&v=...` every 20 seconds
- [ ] Confirm it is **NOT** making individual `/api/lots/{id}/status` requests (should be a single batch)
- [ ] Confirm the request URL contains all your watchlisted lot IDs and version numbers

### 2c. Change detection — no changes
- [ ] Watch the Network tab during a quiet period (no bids happening)
- [ ] Confirm the batch response body is `{"changed":false}` (tiny response)
- [ ] Confirm the lot cards do NOT flicker or re-render when nothing changed

### 2d. Change detection — with changes
- [ ] In a second browser window, place a bid on one of the watchlisted lots
- [ ] Within 20 seconds, confirm the watchlist card updates:
  - [ ] Current bid amount changes
  - [ ] Bid count increments
  - [ ] Winning/outbid status updates (green/orange badge)
  - [ ] Minimum bid amount updates in the bid input
- [ ] Confirm the batch response contains ONLY the changed lot (not all lots)

### 2e. Bidding from watchlist
- [ ] On the watchlist page, enter a bid amount and click "Bid"
- [ ] Confirm "Bid placed successfully!" message appears
- [ ] Confirm the card immediately updates to show "Winning" status
- [ ] Confirm the bid input resets to the new minimum bid

### 2f. Auto-refresh toggle
- [ ] Uncheck "Auto-refresh" checkbox
- [ ] Confirm polling stops (no more network requests)
- [ ] Re-check "Auto-refresh"
- [ ] Confirm polling resumes

### 2g. Manual refresh
- [ ] Click the refresh button (circular arrow icon)
- [ ] Confirm it triggers an immediate batch fetch
- [ ] Confirm the spinner animates briefly

### 2h. Visibility pause
- [ ] Switch to another tab while watchlist is open
- [ ] Confirm **no polling requests** while tab is hidden (check Network tab)
- [ ] Switch back — confirm an immediate fetch + polling resumes

### 2i. Remove from watchlist
- [ ] Click "Remove from watchlist" on a lot
- [ ] Confirm the confirmation dialog appears
- [ ] Confirm the lot is removed and page reloads without it

---

## 3. Auction Show Page — Lot Cards

### 3a. Images
- [ ] Open an auction with multiple lots
- [ ] Confirm lot card images load (should be **thumbnail** size, not full-size)
- [ ] Confirm lots below the fold use lazy loading (check `loading="lazy"` in page source for lots after #6)
- [ ] Confirm image carousel arrows work — swiping through images loads them on demand

### 3b. Lot count
- [ ] Confirm the auction card shows correct lot count (e.g., "25 lots")
- [ ] This should work whether the page uses `lots_count` or `lots->count()`

---

## 4. Notification Bell

### 4a. Polling interval
- [ ] Log in as any user
- [ ] Open DevTools Network tab
- [ ] Confirm notification bell polls `/api/notifications/unread` every **5 minutes** (300 seconds), not 30 seconds
- [ ] Confirm no poll happens while tab is hidden

### 4b. Unread count
- [ ] Send a push notification (as admin or auctioneer)
- [ ] Confirm the bell badge updates on next poll cycle
- [ ] Click the bell — confirm notification list shows correctly
- [ ] Mark notifications as read — confirm badge count decreases

---

## 5. Terms Acceptance Middleware

- [ ] Log in as a user who has NOT accepted the latest terms
- [ ] Confirm redirect to `/terms/accept`
- [ ] Accept terms
- [ ] Navigate around the site — confirm no further redirects (session-cached)
- [ ] Log in as a user who HAS accepted terms
- [ ] Confirm no redirect on any page (session cache hit)

---

## 6. Push Notifications — Dispatch

- [ ] As an auctioneer, send a notification from `/seller/notifications`
- [ ] Confirm the notification is **queued** (check `jobs` table in DB), not sent synchronously
- [ ] Run `php artisan queue:work --once` to process the job
- [ ] Confirm the notification was delivered

- [ ] As admin, send a notification from `/admin/notifications`
- [ ] Same queue check — confirm it's queued, not blocking the HTTP response

---

## 7. Performance Verification

### 7a. Sessionless endpoints
Open DevTools and check the following endpoints have **no `Set-Cookie` header** in the response:
- [ ] `GET /api/lots/{id}/status`
- [ ] `GET /api/lots/batch-status?ids=...`
- [ ] `GET /api/auctions/{id}/status`
- [ ] `GET /api/auctioneers/map`

### 7b. Request count comparison
With the watchlist open and 5 lots:
- [ ] Confirm **1 request per 20s** (batch), not 5 individual requests
- [ ] Confirm quiet periods return `{"changed":false}` (no wasted data transfer)

### 7c. Page load speed
- [ ] Home page loads without noticeable delay
- [ ] Auction show page with 50+ lots loads without hanging
- [ ] Watchlist page with 10+ lots loads normally

---

## 8. Edge Cases

- [ ] Watchlist with **0 lots** — shows empty state, no JS errors in console
- [ ] Watchlist with **ended lots** — shows "Final Price" and "View Details" button, no bid form
- [ ] Lot detail after auction ended — timer shows 0:00:00, no bid form
- [ ] Open watchlist, then let an auction end — next poll picks up `status: ended`
- [ ] Multiple tabs open (lot detail + watchlist for same lot) — both update independently

---

## 9. Mobile Testing

- [ ] Watchlist page responsive on mobile — cards stack in single column
- [ ] Bid form usable on mobile — input and button accessible
- [ ] Countdown timer readable on small screens
- [ ] Image thumbnails load correctly on mobile

---

## Production Deployment Checklist

After all tests pass locally:

1. Upload changed PHP files (controllers, middleware, routes, views)
2. Upload new migration file
3. Upload rebuilt `public/build/` folder (`npm run build`)
4. SSH to server and run:
   ```bash
   php8.3 artisan migrate
   php8.3 artisan route:cache
   php8.3 artisan cache:clear
   rm -f storage/framework/views2/*.php
   ```
5. Verify in production: open watchlist, check Network tab for batch endpoint
