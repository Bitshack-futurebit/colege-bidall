# Auctioneer Payout & Accounting System

## Overview

Complete transparent accounting system that tracks sales, fees, commissions, and payouts for auctioneers.

## ⚠️ IMPORTANT: PayFast 48-Hour Hold

**PayFast holds e-commerce funds for 48 hours before they're available for payout.**

The system automatically tracks this and prevents:
- ✅ Auctioneers requesting payouts for funds still in hold
- ✅ Admins processing payouts before funds are available
- ✅ Cash flow issues from premature payouts

**How it works:**
- Sale recorded: `funds_available_at = paid_date + 48 hours`
- Auctioneer sees: "Available Now" (cleared) + "Pending Clearance" (in hold)
- Can only request payouts from cleared funds
- Full transparency on what's available vs pending

## How It Works

### Money Flow

```
Bidder Pays R1,000 for Won Lot
    ↓
PayFast charges 2.9% + R2 = R32 fee
    ↓
Platform receives R968 (R1,000 - R32)
    ↓
Platform keeps R10 (1% commission - already deducted from auctioneer credits)
    ↓
Auctioneer is owed R958 (R968 - R10)
    ↓
Sales record created with funds_available_at = now() + 48 hours
    ↓
⏳ 48-HOUR HOLD (PayFast requirement)
    ↓
Funds become available (shown as "Available Balance")
    ↓
Auctioneer requests payout when available balance ≥ R500
    ↓
Admin processes payout (validates cleared funds)
    ↓
Available Balance reduced, funds transferred to auctioneer's bank
```

### Detailed Breakdown

**When Lot is Paid:**
| Item | Amount | Who Gets It |
|------|--------|-------------|
| Sale Price | R1,000.00 | - |
| PayFast Fee (2.9% + R2) | -R32.00 | PayFast |
| **Net Received** | **R968.00** | Platform |
| Platform Commission (1%) | -R10.00 | Platform |
| **Net to Auctioneer** | **R958.00** | Added to Payout Balance |

## Database Structure

### Auctioneers Table (New Fields)

```php
payout_balance          // Current balance owed to auctioneer
total_sales             // Lifetime gross sales
total_fees_paid         // Lifetime PayFast/gateway fees
total_commissions_paid  // Lifetime platform commissions (1%)
total_payouts_received  // Lifetime payouts made to auctioneer
```

### Sales Records Table

Tracks each individual lot sale with complete breakdown:
```php
id
auctioneer_id
lot_id
transaction_id           // Link to payment transaction
sale_price               // Winning bid amount
payment_gateway_fee      // PayFast fee
platform_commission      // 1% commission
net_to_auctioneer        // What auctioneer gets
payment_gateway          // payfast, btcpay, etc
commission_rate          // % rate at time of sale
sale_date
paid_date
funds_available_at       // When funds clear 48-hour hold (paid_date + 48 hours)
```

### Payouts Table

Tracks payout requests and history:
```php
id
auctioneer_id
amount                   // Payout amount
status                   // pending, processing, completed, failed
method                   // eft, paypal, bank_transfer
reference                // Bank reference number
bank_name
account_holder
account_number
branch_code
processed_by             // Admin user who processed
requested_at
processed_at
notes                    // Admin notes
```

## Implementation Steps

### 1. Run Migrations

```bash
php artisan migrate
```

This creates:
- Payout balance fields on auctioneers table
- `sales_records` table
- `payouts` table

### 2. Integration Points

#### A. When Bidder Pays for Lot

Update your lot payment webhook handler:

```php
// In PaymentController::handleCompletedPayment()
case 'lot_payment':
    if (isset($transaction->payment_data['lot_ids'])) {
        $lotIds = $transaction->payment_data['lot_ids'];
        $lots = \App\Models\Lot::whereIn('id', $lotIds)->get();

        foreach ($lots as $lot) {
            // Mark lot as paid
            $lot->update(['is_paid' => true]);

            // Create sales record and update auctioneer payout balance
            \App\Models\SalesRecord::createFromLotPayment($lot, $transaction);
        }
    }
    break;
```

#### B. Capture PayFast Fees

Update webhook to capture gateway fees:

```php
// In PayFastGateway::handleWebhook()
$transaction->update([
    'status' => 'completed',
    'payment_data' => array_merge($transaction->payment_data ?? [], [
        'payfast_payment_id' => $payload['pf_payment_id'] ?? null,
        'gateway_fee' => $payload['amount_fee'] ?? 0, // ← Add this
        'completed_at' => now(),
    ]),
]);
```

### 3. Auctioneer Dashboard Views

#### Accounting Overview

Show auctioneers their complete financial picture:

```php
// In auctioneer dashboard
$auctioneer = auth()->user()->auctioneer;

// Display:
- Payout Balance: {{ formatCurrency($auctioneer->payout_balance) }}
- Total Sales: {{ formatCurrency($auctioneer->total_sales) }}
- Total Fees Paid: {{ formatCurrency($auctioneer->total_fees_paid) }}
- Total Commissions: {{ formatCurrency($auctioneer->total_commissions_paid) }}
- Total Received: {{ formatCurrency($auctioneer->total_payouts_received) }}
```

#### Sales History

```php
// Get recent sales with breakdown
$salesRecords = $auctioneer->salesRecords()
    ->with('lot')
    ->orderBy('paid_date', 'desc')
    ->paginate(20);

// Display table showing:
- Lot title
- Sale price
- Gateway fee
- Platform commission
- Net amount
- Date
```

#### Request Payout

```php
// Check if can request payout
@if($auctioneer->canRequestPayout())
    <form method="POST" action="{{ route('seller.payouts.request') }}">
        @csrf
        <input type="number" name="amount"
               max="{{ $auctioneer->payout_balance }}"
               min="{{ config('platform.minimum_payout', 500) }}">
        // Bank details fields
        <button type="submit">Request Payout</button>
    </form>
@else
    <p>Minimum payout: R500 (Current: {{ formatCurrency($auctioneer->payout_balance) }})</p>
@endif
```

#### Payout History

```php
// Get payout history
$payouts = $auctioneer->payouts()
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Display:
- Date requested
- Amount
- Status
- Reference number
- Date completed
```

### 4. Admin Interface

#### Pending Payouts

```php
// List all pending payout requests
$pendingPayouts = \App\Models\Payout::where('status', 'pending')
    ->with('auctioneer.user')
    ->orderBy('requested_at', 'desc')
    ->get();

// For each payout, show:
- Auctioneer name
- Amount requested
- Bank details
- Available balance
- Request date
- "Approve" button
```

#### Process Payout

```php
// When admin approves
$payout = Payout::findOrFail($id);
$payout->markAsCompleted(
    admin: auth()->user(),
    reference: $request->bank_reference,
    notes: $request->admin_notes
);

// This automatically:
- Updates payout status to 'completed'
- Deducts from auctioneer's payout_balance
- Increments total_payouts_received
- Logs activity
```

#### Sales Reports

```php
// Platform revenue breakdown
$totalSales = SalesRecord::sum('sale_price');
$totalGatewayFees = SalesRecord::sum('payment_gateway_fee');
$totalCommissions = SalesRecord::sum('platform_commission');
$totalPaidToAuctioneers = Payout::where('status', 'completed')->sum('amount');

// Net platform revenue
$platformRevenue = $totalCommissions;
$platformProfit = $platformRevenue - $totalGatewayFees; // If platform pays fees
```

## Configuration

Add to `config/platform.php`:

```php
'minimum_payout' => 500, // Minimum R500 to request payout
'payout_methods' => ['eft', 'bank_transfer'], // Available methods
'payout_processing_days' => 5, // Business days to process
'funds_hold_hours' => 48, // PayFast hold period before funds available
```

## Security Considerations

1. **Payout Requests**: Only auctioneers can request
2. **Payout Processing**: Only admins can process
3. **Audit Trail**: All payouts logged with admin, date, reference
4. **Balance Validation**: Can't request more than available balance
5. **Minimum Thresholds**: Prevents tiny payouts

## Reports & Analytics

### For Auctioneers

- Sales breakdown by auction
- Monthly sales trends
- Fee analysis
- Payout history

### For Admins

- Platform revenue by period
- Outstanding payout liabilities
- Gateway fee analysis
- Commission earnings
- Payout processing queue

## Implementation Status

### ✅ COMPLETED (2026-02-11)

All features have been fully implemented and tested:

1. ✅ **Migrations Run** - All database tables created
2. ✅ **Webhook Integration** - `PaymentController::handleCompletedPayment()` creates sales records via `SalesRecord::createFromLotPayment()`
3. ✅ **Auctioneer Dashboard** - `/seller/accounting` shows payout balance, sales, and quick actions
4. ✅ **Request Payout Page** - `/seller/payouts` with form validation and bank details
5. ✅ **Admin Processing Page** - `/admin/payouts/{id}` with approve/reject forms
6. ✅ **Sales History View** - `/seller/sales` with filters, sorting, and detailed breakdown
7. ✅ **Payout History View** - `/seller/payouts` shows all requests with status
8. ✅ **Admin Dashboard Integration** - Pending payouts count and quick access

### Additional Features Implemented

9. ✅ **Payment Method Selection** - Bidders choose "Pay Now" (PayFast) or "Arrange Collection"
10. ✅ **Payment Status Tracking** - Lots track payment_status, payment_method_selected_at, payment_completed_at
11. ✅ **Auction Archival** - Automatic soft delete of auctions 30 days after ending
12. ✅ **Performance Optimizations** - Eager loading, indexed queries, mobile responsive
13. ✅ **Security & Validation** - Balance checks, admin-only processing, full audit trail

## Testing Checklist

All items tested and verified:

- ✅ Bidder pays for lot → Sale record created
- ✅ Auctioneer payout balance increases correctly
- ✅ Fee calculations are accurate (sale price - gateway fee - 1% commission)
- ✅ Auctioneer can request payout when balance ≥ R500
- ✅ Admin can see pending payout requests with full details
- ✅ Admin can process payout with reference number
- ✅ Payout balance decreases after processing
- ✅ All balances reconcile correctly
- ✅ Activity logging works on all payout actions
- ✅ Only PayFast payments add to payout balance
- ✅ Offline payments (arrange collection) work correctly

## Complete Workflow Example

### Scenario: John's Auctions sells 3 lots

**Step 1: Sales via PayFast**
- Lot A sold for R500 → Bidder pays via PayFast
- Lot B sold for R750 → Bidder pays via PayFast
- Lot C sold for R250 → Bidder arranges collection (offline)

**Step 2: Sales Records Created (Lots A & B only) - Feb 11**
```
Lot A: R500 - R16.50 (PayFast) - R5.00 (1%) = R478.50 to auctioneer
       funds_available_at = Feb 13 (48 hours)

Lot B: R750 - R23.75 (PayFast) - R7.50 (1%) = R718.75 to auctioneer
       funds_available_at = Feb 13 (48 hours)

Available Balance: R0 (funds still in PayFast hold)
Pending Clearance: R1,197.25 (available Feb 13)

Lot C: Not added to payout_balance (offline payment)
Platform still deducts R2.50 (1%) from credit_balance
```

**Step 2.5: Wait for Clearance (Feb 11-13)**
- Feb 11: Available R0, Pending R1,197.25
- Feb 12: Available R0, Pending R1,197.25
- Feb 13: Available R1,197.25, Pending R0 ✅

**Step 3: Auctioneer Requests Payout - Feb 13**
- Visits `/seller/accounting` → sees R1,197.25 available (cleared)
- Goes to `/seller/payouts` → fills form:
  - Amount: R1,197.25
  - Bank: FNB
  - Account: 62012345678
- Clicks "Submit Request"

**Step 4: Admin Processes**
- Sees notification: "1 pending payout"
- Reviews request at `/admin/payouts/123`
- Makes EFT transfer via bank
- Gets reference: TXN20240211XYZ
- Enters reference and clicks "Approve"

**Step 5: System Updates**
```
John's payout_balance: R1,197.25 → R0.00
John's total_payouts_received: +R1,197.25
Payout status: pending → completed
Activity log created
```

**Step 6: Auctioneer Receives Money**
- Bank transfer arrives in 1-3 business days
- Can see completed payout in history with reference number

## Key Business Rules

### What Adds to Payout Balance
- ✅ PayFast payments (bidder clicked "Pay Now")
- ❌ Offline payments (bidder arranged collection)

### Platform Commission (1%)
- ✅ Deducted on ALL sales (PayFast + offline)
- ✅ Taken from credit_balance
- ✅ Can result in negative credit balance

### Payout Restrictions
- Minimum: R500
- Can't request if pending payout exists
- Can't process if insufficient balance
- Full audit trail maintained

## Questions?

This system provides complete transparency and proper accounting for all money flowing through the platform. Auctioneers can see exactly what they've earned and track their payouts, while admins have full visibility into platform finances.

**Need Help?**
- Auctioneer questions: See `/seller/accounting` for complete breakdown
- Admin questions: Check `/admin/payouts` for all requests and stats
- Technical questions: Review this documentation or check CLAUDE.md
