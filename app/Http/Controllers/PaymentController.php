<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Lot;
use App\Models\Auction;
use App\Models\SalesRecord;
use App\Models\ActivityLog;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a payment.
     */
    public function create(Request $request, PaymentGatewayFactory $gateways)
    {
        $request->validate([
            'type' => ['required', 'in:activation_fee,credit_purchase,deposit,lot_payment,community_fee_payment'],
            'amount' => ['required', 'numeric', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'payment_method' => ['nullable', 'in:payfast,blink'],
        ]);

        // Buyer-selected gateway (Card vs Bitcoin); defaults to PayFast.
        $gateway = $gateways->make($request->input('payment_method') ?: 'payfast');

        // Anti-double-payment guard for community fee payments — block if the
        // user already has a pending payment in flight in the last 30 minutes.
        if ($request->type === 'community_fee_payment') {
            $existingPending = Transaction::where('user_id', auth()->id())
                ->where('type', 'community_fee_payment')
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(30))
                ->exists();
            if ($existingPending) {
                return redirect()->route('community.fees')
                    ->with('error', 'You already have a payment in progress. Wait for it to confirm or cancel it before starting another.');
            }
        }

        $payment = $gateway->createPayment(
            amount: $request->amount,
            user: auth()->user(),
            type: $request->type,
            metadata: $request->metadata ?? []
        );

        // Store payment ID in session for tracking (multiple keys for compatibility)
        session([
            'pending_payment' => $payment['payment_id'],
            'pending_payment_' . $request->type => $payment['payment_id'],
        ]);

        // Redirect to payment gateway
        if (isset($payment['method']) && $payment['method'] === 'POST') {
            // For gateways requiring form submission (like PayFast)
            return view('payment.redirect', ['payment' => $payment]);
        }

        return redirect($payment['redirect_url']);
    }

    /**
     * Handle payment return (success page).
     */
    public function return(Request $request)
    {
        // Log what PayFast sends back
        Log::info('PayFast return URL accessed', [
            'all_data' => $request->all(),
            'query' => $request->query(),
            'session_keys' => array_keys(session()->all()),
        ]);

        // PayFast sends custom_str1 which contains our payment_id
        // Try query params first, then check various session keys
        $paymentId = $request->query('custom_str1')
            ?? $request->query('payment_id')
            ?? session('pending_payment')
            ?? session('pending_payment_credit_purchase')
            ?? session('pending_payment_activation_fee')
            ?? session('pending_payment_deposit')
            ?? session('pending_payment_lot_payment')
            ?? session('pending_credit_payment'); // Legacy key

        if (!$paymentId) {
            Log::warning('PayFast return - no payment ID found', [
                'query' => $request->query(),
                'session_keys' => array_keys(session()->all()),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Payment reference not found.');
        }

        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            return redirect()->route('dashboard')
                ->with('error', 'Payment not found.');
        }

        // Map transaction status to display status
        $displayStatus = match($transaction->status) {
            'completed' => 'success',
            'failed' => 'cancelled',
            default => 'pending',
        };

        // Payment status will be updated by webhook
        // Show pending status page
        return view('payment.return', [
            'transaction' => $transaction,
            'status' => $displayStatus,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'reference' => $transaction->payment_id,
            'relatedId' => $transaction->payment_data['event_id'] ?? null,
        ]);
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel(Request $request)
    {
        $paymentId = $request->query('payment_id') ?? session('pending_payment');

        if ($paymentId) {
            $transaction = Transaction::where('payment_id', $paymentId)->first();

            if ($transaction && $transaction->status === 'pending') {
                $transaction->update(['status' => 'failed']);
            }

        }

        return redirect()->route('dashboard')
            ->with('warning', 'Payment was cancelled. You can try again anytime.');
    }

    /**
     * Pay for won lots via PayFast
     */
    public function payForLotsNow(Request $request, PaymentGatewayInterface $gateway)
    {
        $request->validate([
            'lot_ids' => ['required', 'string'],
        ]);

        $lotIds = explode(',', $request->lot_ids);

        // Prevent duplicate submissions — if a pending payment exists for these exact lots, skip
        $existingPending = Transaction::where('user_id', auth()->id())
            ->where('type', 'lot_payment')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->get()
            ->first(function ($tx) use ($lotIds) {
                $txLotIds = $tx->payment_data['lot_ids'] ?? [];
                sort($txLotIds);
                $sorted = $lotIds;
                sort($sorted);
                return $txLotIds == $sorted;
            });

        if ($existingPending) {
            return redirect()->back()->with('info', 'A payment is already in progress for these lots. Please complete or cancel it before trying again.');
        }

        $lots = Lot::with('auction.auctioneer')
            ->whereIn('id', $lotIds)
            ->where('winning_bidder_id', auth()->id())
            ->where('status', 'sold')
            ->get();

        if ($lots->isEmpty()) {
            return redirect()->back()->with('error', 'No valid lots found');
        }

        // Calculate total with buyer's premium
        $total = $lots->sum(function ($lot) {
            return $lot->getTotalAmountDue();
        });

        // Create payment
        $payment = $gateway->createPayment(
            amount: $total,
            user: auth()->user(),
            type: 'lot_payment',
            metadata: [
                'lot_ids' => $lotIds,
                'event_id' => $lots->first()->event_id,
            ]
        );

        // Store payment ID in session
        session([
            'pending_payment' => $payment['payment_id'],
            'pending_payment_lot_payment' => $payment['payment_id'],
        ]);

        // Redirect to payment gateway
        if (isset($payment['method']) && $payment['method'] === 'POST') {
            return view('payment.redirect', ['payment' => $payment]);
        }

        return redirect($payment['redirect_url']);
    }

    /**
     * Arrange collection with auctioneer (no PayFast payment)
     */
    public function arrangeCollection(Request $request)
    {
        $request->validate([
            'lot_ids' => ['required', 'string'],
        ]);

        $lotIds = explode(',', $request->lot_ids);
        $lots = Lot::with('auction.auctioneer.user')
            ->whereIn('id', $lotIds)
            ->where('winning_bidder_id', auth()->id())
            ->where('status', 'sold')
            ->whereNull('payment_status')
            ->get();

        if ($lots->isEmpty()) {
            return redirect()->back()->with('info', 'Collection has already been arranged for these lots.');
        }

        // Mark lots as awaiting collection (auctioneer must confirm payment)
        foreach ($lots as $lot) {
            $lot->update([
                'is_paid' => false,
                'payment_status' => 'awaiting_collection',
                'payment_method_selected_at' => now(),
            ]);
        }

        // Log activity for auctioneer
        ActivityLog::create([
            'user_id' => $lots->first()->auction->auctioneer->user_id,
            'type' => 'collection_arranged',
            'description' => auth()->user()->name . ' selected "Arrange Collection" for ' . $lots->count() . ' lot(s) in ' . $lots->first()->auction->title,
            'subject_type' => 'App\\Models\\Auction',
            'subject_id' => $lots->first()->auction->id,
        ]);

        return redirect()->back()->with('success', 'Auctioneer has been notified. Contact them to arrange payment and collection.');
    }

    /**
     * Handle payment webhook from gateway.
     */
    public function webhook(Request $request, PaymentGatewayInterface $gateway)
    {
        Log::info('Payment webhook received', [
            'gateway' => $gateway->getName(),
            'payload' => $request->all(),
        ]);

        try {
            $result = $gateway->handleWebhook($request->all());

            if (!$result['success']) {
                Log::error('Webhook processing failed', $result);
                return response()->json(['error' => 'Webhook processing failed'], 400);
            }

            // Handle successful payment
            if ($result['action'] === 'completed' && isset($result['transaction'])) {
                $this->handleCompletedPayment($result['transaction']);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Show the Lightning (Blink) checkout page — renders the BOLT11 invoice as a QR
     * and polls for confirmation. Scoped to the owning user.
     */
    public function lightningCheckout(Request $request, string $paymentId)
    {
        $transaction = Transaction::where('payment_id', $paymentId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Already paid — skip the QR and go straight to the result page.
        if ($transaction->status === 'completed') {
            return redirect()->route('payment.return', ['payment_id' => $paymentId]);
        }

        return view('payment.blink', [
            'transaction' => $transaction,
            'paymentId' => $paymentId,
            'invoice' => $transaction->payment_data['payment_request'] ?? null,
            'amountZar' => $transaction->amount,
            'amountSats' => $transaction->payment_data['amount_sats'] ?? null,
            'type' => $transaction->type,
        ]);
    }

    /**
     * JSON status endpoint polled by the Lightning checkout page.
     * Reads local state, which the Blink webhook flips to 'completed' on payment.
     */
    public function lightningStatus(string $paymentId)
    {
        $transaction = Transaction::where('payment_id', $paymentId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'not_found', 'paid' => false], 404);
        }

        return response()->json([
            'status' => $transaction->status,
            'paid' => $transaction->status === 'completed',
            'redirect' => route('payment.return', ['payment_id' => $paymentId]),
        ]);
    }

    /**
     * Handle the Blink (Lightning) webhook.
     *
     * Separate from the generic webhook() because Blink signs with Svix over the
     * RAW request body — we must verify against the raw bytes BEFORE parsing, which
     * the PaymentGatewayInterface::handleWebhook(array) signature can't express.
     */
    public function blinkWebhook(Request $request, PaymentGatewayFactory $gateways)
    {
        // Resolve Blink directly — it may be a per-transaction option while the
        // global default gateway is still PayFast.
        $gateway = $gateways->make('blink');

        // Verify the Svix signature on the raw body before trusting anything.
        $verified = $gateway->verifyWebhookSignature($request->getContent(), [
            'svix-id' => $request->header('svix-id'),
            'svix-timestamp' => $request->header('svix-timestamp'),
            'svix-signature' => $request->header('svix-signature'),
        ]);

        if (!$verified) {
            Log::error('Blink webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            $result = $gateway->handleWebhook($request->all());

            if (!($result['success'] ?? false)) {
                Log::error('Blink webhook processing failed', $result);
                return response()->json(['error' => 'Webhook processing failed'], 400);
            }

            if (($result['action'] ?? null) === 'completed' && isset($result['transaction'])) {
                $this->handleCompletedPayment($result['transaction']);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Blink webhook exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle completed payment actions.
     */
    protected function handleCompletedPayment(Transaction $transaction)
    {
        $type = $transaction->type;
        $user = $transaction->user;

        Log::info('Handling completed payment', [
            'type' => $type,
            'user_id' => $user->id,
            'amount' => $transaction->amount,
        ]);

        switch ($type) {
            case 'activation_fee':
                // Activate auctioneer account
                if ($user->auctioneer) {
                    $user->auctioneer->activate();
                    Log::info('Auctioneer activated', ['auctioneer_id' => $user->auctioneer->id]);
                }
                break;

            case 'credit_purchase':
                // Add credits to auctioneer
                if ($user->auctioneer) {
                    $credits = $transaction->amount; // 1:1 ratio
                    $user->auctioneer->addCredits(
                        $credits,
                        'purchase',
                        'Credit purchase via ' . $transaction->payment_method
                    );
                    Log::info('Credits added', [
                        'auctioneer_id' => $user->auctioneer->id,
                        'credits' => $credits,
                    ]);
                }
                break;

            case 'deposit':
                // Update auction registration with deposit
                if (isset($transaction->payment_data['event_id'])) {
                    $auctionId = $transaction->payment_data['event_id'];
                    $registration = $user->auctionRegistrations()
                        ->where('event_id', $auctionId)
                        ->first();

                    if ($registration) {
                        $registration->update([
                            'deposit_paid' => $transaction->amount,
                        ]);
                        Log::info('Deposit recorded', [
                            'auction_id' => $auctionId,
                            'amount' => $transaction->amount,
                        ]);
                    }
                }
                break;

            case 'lot_payment':
                // Mark lots as paid and create sales records
                if (isset($transaction->payment_data['lot_ids'])) {
                    $lotIds = $transaction->payment_data['lot_ids'];
                    $lots = Lot::with('auction.auctioneer')
                        ->whereIn('id', $lotIds)
                        ->where('winning_bidder_id', $user->id)
                        ->get();

                    foreach ($lots as $lot) {
                        // Update lot payment status
                        $lot->update([
                            'is_paid' => true,
                            'payment_status' => 'paid_platform',
                            'payment_method_selected_at' => now(),
                            'payment_completed_at' => now(),
                        ]);

                        // Create sales record and update auctioneer payout balance
                        SalesRecord::createFromLotPayment($lot, $transaction);

                        Log::info('Lot paid and sales record created', [
                            'lot_id' => $lot->id,
                            'auctioneer_id' => $lot->auction->auctioneer_id,
                        ]);
                    }

                    Log::info('Lots marked as paid', [
                        'lot_ids' => $lotIds,
                        'user_id' => $user->id,
                        'count' => $lots->count(),
                    ]);
                }
                break;

            case 'community_fee_payment':
                // Flip seller's accrued community-fee ledger rows to seller_paid.
                $result = app(\App\Services\CommunityCommissionService::class)
                    ->markSellerPaid((int) $user->id, (int) $transaction->id);

                Log::info('Community fee payment cleared', [
                    'user_id' => $user->id,
                    'rows_paid' => $result['count'],
                    'amount_cleared' => $result['amount'],
                    'transaction_id' => $transaction->id,
                ]);
                break;

        }

        // TODO: Send email notification to user
        // Mail::to($user)->send(new PaymentConfirmationMail($transaction));
    }
}
