<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotification;
use App\Models\Auction;
use App\Models\CommunityRegion;
use App\Models\CommunitySellerSuspension;
use App\Models\Lot;
use App\Models\LotConfirmation;
use App\Models\LotImage;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CommunityAuctionController extends Controller
{
    /**
     * Landing — redirect to user's region, or pilot region, or region list.
     */
    public function index()
    {
        if (auth()->check() && auth()->user()->community_region_id) {
            $region = auth()->user()->communityRegion;
            if ($region && $region->is_active) {
                return redirect()->route('community.region', $region);
            }
        }

        $pilotSlug = config('community.pilot_region_slug');
        $pilot = CommunityRegion::where('slug', $pilotSlug)->where('is_active', true)->first();
        if ($pilot) {
            return redirect()->route('community.region', $pilot);
        }

        $regions = CommunityRegion::active()
            ->withCount(['users'])
            ->with(['schedules' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
        return view('community.regions-list', compact('regions'));
    }

    /**
     * Show the region's next upcoming/live/draft auction + lineup.
     */
    public function showRegion(CommunityRegion $region)
    {
        $auction = $this->activeAuctionFor($region);

        if ($auction) {
            $auction->loadMissing(['lots.images', 'lots.seller']);

            if ($auction->status === 'live') {
                return redirect()->route('auctions.show', $auction->slug);
            }
        }

        $userRegion = auth()->check() ? auth()->user()->communityRegion : null;
        $isMemberOfThisRegion = auth()->check() && auth()->user()->community_region_id === $region->id;

        $memberCount = $region->bidderCount();
        $minLots = $region->min_lots_for_viability ?? (int) config('community.min_lots_for_viability', 5);
        $minBidders = $region->min_bidders_for_viability ?? (int) config('community.min_bidders_for_viability', 20);
        $lotCount = $auction ? $auction->lots->count() : 0;
        $auctioneer = \App\Models\Auctioneer::where('slug', 'community-' . $region->slug)->first();

        return view('community.region', [
            'region' => $region,
            'auction' => $auction,
            'auctioneer' => $auctioneer,
            'userRegion' => $userRegion,
            'isMemberOfThisRegion' => $isMemberOfThisRegion,
            'startingBid' => (int) config('community.starting_bid', 20),
            'commissionPercent' => (float) config('community.commission_percent', 5),
            'memberCount' => $memberCount,
            'minLots' => $minLots,
            'minBidders' => $minBidders,
            'lotCount' => $lotCount,
        ]);
    }

    /**
     * Set the authenticated user's community region (with cooldown).
     */
    public function joinRegion(Request $request, CommunityRegion $region)
    {
        abort_unless($region->is_active, 404);
        $user = $request->user();

        if ($user->community_region_changed_at) {
            $cooldown = (int) config('community.region_change_cooldown_days', 30);
            if ($user->community_region_changed_at->gt(now()->subDays($cooldown))) {
                $daysLeft = (int) now()->diffInDays($user->community_region_changed_at->copy()->addDays($cooldown), false);
                return back()->with('error', "You can change your community region again in {$daysLeft} day(s).");
            }
        }

        $user->update([
            'community_region_id' => $region->id,
            'community_region_changed_at' => now(),
        ]);

        return redirect()->route('community.region', $region)
            ->with('success', "You're now part of {$region->name}.");
    }

    /**
     * Show the list-item form.
     */
    public function createLot(Request $request)
    {
        $user = $request->user();
        $region = $user->communityRegion;
        abort_unless($region && $region->is_active, 404, 'Select an active community region first.');

        [$allowed, $reason] = $user->canListInCommunity($region);
        abort_if(!$allowed, 403, $reason ?? 'Listing not allowed.');

        // Fee-debt gate: outstanding platform fees over threshold OR stale unpaid → block.
        [$feeOk, $feeReason] = app(\App\Services\CommunityCommissionService::class)->canSellerList($user->id);
        if (!$feeOk) {
            return redirect()->route('community.fees')->with('error', $feeReason);
        }

        $auction = $this->activeAuctionFor($region, allowLockedOrLive: false);
        abort_if(!$auction, 409, 'No open community auction accepting listings right now. Check back later.');

        $lotsThisWeek = Lot::where('seller_user_id', $user->id)
            ->where('event_id', $auction->id)
            ->count();

        return view('community.list-item', [
            'region' => $region,
            'auction' => $auction,
            'lotsThisWeek' => $lotsThisWeek,
            'weeklyLimit' => (int) ($region->listing_limit_per_week ?? config('community.listing_limit_per_week', 3)),
            'startingBid' => (int) config('community.starting_bid', 20),
            'minDescription' => (int) config('community.min_description_length', 20),
            'commissionPercent' => (float) config('community.commission_percent', 5),
        ]);
    }

    /**
     * Store a new community lot into the region's next draft auction.
     */
    public function storeLot(Request $request)
    {
        $user = $request->user();
        $region = $user->communityRegion;
        abort_unless($region && $region->is_active, 404);

        [$allowed, $reason] = $user->canListInCommunity($region);
        abort_if(!$allowed, 403, $reason ?? 'Listing not allowed.');

        // Fee-debt gate (mirrors createLot, in case the form was open before debt accrued).
        [$feeOk, $feeReason] = app(\App\Services\CommunityCommissionService::class)->canSellerList($user->id);
        if (!$feeOk) {
            return redirect()->route('community.fees')->with('error', $feeReason);
        }

        $auction = $this->activeAuctionFor($region, allowLockedOrLive: false);
        abort_if(!$auction, 409, 'No open community auction accepting listings right now.');

        $weeklyLimit = (int) ($region->listing_limit_per_week ?? config('community.listing_limit_per_week', 3));
        $startingBid = (int) config('community.starting_bid', 20);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'images' => ['required', 'array', 'min:' . (int) config('community.min_images', 1), 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,webp,heic', 'max:15360'],
        ], [
            'images.min' => 'At least one image is required.',
        ]);

        $existing = Lot::where('seller_user_id', $user->id)
            ->where('event_id', $auction->id)
            ->count();
        if ($existing >= $weeklyLimit) {
            return back()->withInput()->withErrors([
                'title' => "You've reached this week's listing limit ({$weeklyLimit} lots).",
            ]);
        }

        $duplicate = Lot::where('seller_user_id', $user->id)
            ->where('event_id', $auction->id)
            ->whereRaw('LOWER(title) = ?', [mb_strtolower(trim($validated['title']))])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'title' => 'You already listed an item with this title in this auction.',
            ]);
        }

        $lot = DB::transaction(function () use ($auction, $user, $validated, $startingBid, $request) {
            $nextLotNumber = ((int) $auction->lots()->max('lot_number')) + 1;

            $lot = Lot::create([
                'event_id' => $auction->id,
                'lot_number' => $nextLotNumber,
                'seller_user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image_tier' => 'basic',
                'starting_bid' => $startingBid,
                'current_bid' => null,
                'increment' => \App\Helpers\BidLadder::nextIncrement($startingBid),
                'status' => 'draft',
                'start_time' => $auction->goes_live_at,
                'end_time' => $auction->end_time,
                'subject_to_confirmation' => true,
                'confirmation_message' => 'Community lot — winning bid is subject to seller confirmation within 24 hours.',
            ]);

            $order = 0;
            foreach ($request->file('images') as $img) {
                $this->processAndStoreImage($img, $lot, $order++);
            }

            return $lot;
        });

        return redirect()->route('community.my-lots')
            ->with('success', 'Lot listed. It will appear at the next community auction.');
    }

    /**
     * Delete a seller's own community lot while the auction is still in draft.
     */
    public function deleteLot(Request $request, Lot $lot)
    {
        $user = $request->user();
        abort_unless($lot->seller_user_id === $user->id, 403);

        $auction = $lot->auction;
        abort_unless($auction && $auction->is_community, 404);

        $inDraft  = $auction->status === 'draft' && $lot->total_bids === 0;
        $isUnsold = $lot->status === 'unsold' && $auction->status === 'ended';
        abort_if(!$inDraft && !$isUnsold, 403, 'This lot can no longer be removed.');

        foreach ($lot->images as $img) {
            if ($img->optimized_path) Storage::disk('public')->delete($img->optimized_path);
            if ($img->thumbnail_path) Storage::disk('public')->delete($img->thumbnail_path);
            $img->delete();
        }
        $lot->delete();

        return redirect()->route('community.my-lots')
            ->with('success', 'Lot removed.');
    }

    /**
     * Seller's outstanding platform-fee invoice + payment history.
     */
    public function fees(Request $request)
    {
        $user = $request->user();

        $svc = app(\App\Services\CommunityCommissionService::class);
        $outstandingRows = \App\Models\CommunityCommissionLedger::where('seller_user_id', $user->id)
            ->where('status', 'accrued')
            ->with(['lot.images', 'region'])
            ->orderBy('accrued_at')
            ->get();

        $paidRows = \App\Models\CommunityCommissionLedger::where('seller_user_id', $user->id)
            ->whereIn('status', ['seller_paid', 'agent_paid'])
            ->with(['lot.images', 'region'])
            ->orderByDesc('seller_paid_at')
            ->limit(50)
            ->get();

        $outstandingTotal = $svc->sellerOutstandingDebt($user->id);
        [, $blockReason] = $svc->canSellerList($user->id);

        // Anti-double-payment: existing pending fee payment in the last 30 min?
        $pendingTx = \App\Models\Transaction::where('user_id', $user->id)
            ->where('type', 'community_fee_payment')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest('created_at')
            ->first();

        return view('community.fees', [
            'outstandingRows' => $outstandingRows,
            'paidRows' => $paidRows,
            'outstandingTotal' => $outstandingTotal,
            'blockReason' => $blockReason,
            'feeBlockThreshold' => (float) config('community.fee_debt_block_threshold', 100),
            'feeAgeDays' => (int) config('community.fee_debt_age_block_days', 30),
            'pendingTx' => $pendingTx,
        ]);
    }

    /**
     * Cancel a stale pending community-fee payment so the seller can start a new one.
     */
    public function cancelPendingFeePayment(Request $request)
    {
        $user = $request->user();
        $tx = \App\Models\Transaction::where('user_id', $user->id)
            ->where('type', 'community_fee_payment')
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if ($tx) {
            $tx->update(['status' => 'failed']);
        }

        return redirect()->route('community.fees')
            ->with('success', 'Pending payment cancelled. You can start a new payment now.');
    }

    /**
     * Seller's listed community lots.
     */
    public function myLots(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Hide parent lots that have been relisted — the child appears in this view
        // instead. The original is preserved on the database so the previous auction's
        // history page still shows it; this filter is purely presentational.
        $lots = Lot::where('seller_user_id', $user->id)
            ->whereDoesntHave('relistedTo')
            ->with(['auction.communityRegion', 'images', 'winningBidder'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('community.my-lots', compact('lots'));
    }

    /**
     * Show the confirmation decision UI for a lot awaiting the seller.
     */
    public function showConfirmation(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());

        // Already decided — could be double-click on Accept/Decline, browser back
        // after submitting, or the 24h auto-confirm cron firing between page-load
        // and click. Redirect with a friendly message instead of a 409.
        if (!$lot->isAwaitingConfirmation()) {
            $message = match (true) {
                $lot->status === 'sold' => 'This lot has already been confirmed. Here\'s the invoice.',
                $lot->status === 'unsold' && $lot->declined_at => 'You declined this lot.',
                $lot->status === 'unsold' => 'This lot is no longer awaiting your decision.',
                default => 'This lot is no longer awaiting your decision.',
            };
            $route = $lot->status === 'sold'
                ? route('community.invoice', $lot)
                : route('community.my-lots');
            return redirect($route)->with('success', $message);
        }

        $user = $request->user();
        $regionId = $lot->auction?->community_region_id;
        $pilotMode = $lot->auction?->pilot_mode ?? false;
        $limit = $pilotMode
            ? (int) config('community.pilot_decline_limit_30d', 2)
            : (int) config('community.standard_decline_limit_30d', 1);
        $declinesUsed = $user->communityDeclineCount(30, $regionId);
        $confirmedSales = $user->communityConfirmedSalesCount($regionId);
        $firstLotProtected = $confirmedSales < (int) config('community.first_lot_protection_sales_threshold', 1);

        return view('community.confirm-lot', [
            'lot' => $lot,
            'winningBid' => $lot->current_bid,
            'declinesUsed' => $declinesUsed,
            'declineLimit' => $limit,
            'firstLotProtected' => $firstLotProtected,
            'pilotMode' => $pilotMode,
        ]);
    }

    /**
     * Seller confirms a winning bid.
     */
    public function confirmLot(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());
        if (!$lot->isAwaitingConfirmation()) {
            return redirect()->route('community.my-lots')
                ->with('error', 'This lot has already been decided (status: ' . $lot->status . ').');
        }

        $lot->communityConfirm();

        // Notify the winning bidder their purchase was confirmed + link to invoice
        if ($lot->winning_bidder_id) {
            $this->notifyBuyer(
                $lot,
                'Your winning bid was confirmed',
                "The seller confirmed your R" . number_format((float) $lot->current_bid, 0) . " bid on '{$lot->title}'. Tap to view your invoice and seller contact details.",
                route('community.invoice', $lot)
            );
        }

        return redirect()->route('community.invoice', $lot)
            ->with('success', 'Sale confirmed. Buyer notified — share the invoice to arrange handover.');
    }

    /**
     * Invoice view — visible to both seller and buyer of a confirmed community lot.
     */
    public function invoice(Lot $lot, Request $request)
    {
        $user = $request->user();
        abort_unless($lot->isCommunityLot(), 404);
        abort_unless(in_array($lot->status, ['sold', 'pending_confirmation']), 409, 'Invoice not available yet.');

        $isSeller = $lot->seller_user_id === $user->id;
        $isBuyer = $lot->winning_bidder_id === $user->id;
        abort_unless($isSeller || $isBuyer || $user->isAdmin(), 403);

        $lot->load(['auction.communityRegion', 'seller', 'winningBidder', 'images']);

        return view('community.invoice', [
            'lot' => $lot,
            'isSeller' => $isSeller,
            'isBuyer' => $isBuyer,
        ]);
    }

    protected function notifyBuyer(Lot $lot, string $title, string $body, string $url): void
    {
        $notification = PushNotification::create([
            'sender_type' => 'auctioneer',
            'sender_id' => $lot->seller_user_id,
            'audience' => 'specific_user',
            'target_user_id' => $lot->winning_bidder_id,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        SendPushNotification::dispatch($notification);
    }

    /**
     * Seller declines a winning bid. Applies penalty ladder.
     */
    public function declineLot(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());
        if (!$lot->isAwaitingConfirmation()) {
            return redirect()->route('community.my-lots')
                ->with('error', 'This lot has already been decided (status: ' . $lot->status . ').');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user = $request->user();
        $regionId = $lot->auction?->community_region_id;
        $pilotMode = $lot->auction?->pilot_mode ?? false;
        $limit = $pilotMode
            ? (int) config('community.pilot_decline_limit_30d', 2)
            : (int) config('community.standard_decline_limit_30d', 1);
        $confirmedSales = $user->communityConfirmedSalesCount($regionId);
        $firstLotProtected = $confirmedSales < (int) config('community.first_lot_protection_sales_threshold', 1);

        $buyerId = $lot->winning_bidder_id;
        $bidAmount = $lot->current_bid;

        $lot->communityDecline($validated['reason']);

        // Notify the (former) winning bidder their bid was declined
        if ($buyerId) {
            $this->notifyBuyer(
                $lot,
                'Your winning bid was declined',
                "The seller declined your R" . number_format((float) $bidAmount, 0) . " bid on '{$lot->title}'. No payment is due. Keep an eye on upcoming community auctions.",
                route('community.index')
            );
        }

        if ($firstLotProtected) {
            return redirect()->route('community.my-lots')
                ->with('success', 'Decline recorded — first-lot protection used, no penalty applied.');
        }

        $declinesIn30d = $user->communityDeclineCount(30, $regionId);
        $declinesIn365d = $user->communityDeclineCount(365, $regionId);
        $message = 'Decline recorded.';

        if ($declinesIn365d >= (int) config('community.permanent_ban_threshold_365d', 3)) {
            CommunitySellerSuspension::create([
                'user_id' => $user->id,
                'community_region_id' => $regionId,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => true,
                'reason' => "Permanent ban: {$declinesIn365d} declines in 365 days.",
            ]);
            $message = 'Decline recorded. Permanent community-auction ban applied.';
        } elseif ($declinesIn30d > $limit) {
            $days = (int) config('community.suspension_days_on_breach', 30);
            CommunitySellerSuspension::create([
                'user_id' => $user->id,
                'community_region_id' => $regionId,
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
                'is_permanent' => false,
                'reason' => "Exceeded decline limit ({$declinesIn30d}/{$limit}) in 30d.",
            ]);
            $message = "Decline recorded. You're suspended from community listings for {$days} days.";
        }

        return redirect()->route('community.my-lots')->with('success', $message);
    }

    /**
     * Seller relists an unsold community lot into the region's next draft auction.
     */
    public function relistLot(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());

        if (!$lot->canBeRelisted()) {
            return redirect()->route('community.my-lots')
                ->with('error', 'This lot cannot be relisted (must be unsold, from an ended auction, not already relisted).');
        }

        $region = $lot->auction?->communityRegion;
        if (!$region || !$region->is_active) {
            return redirect()->route('community.my-lots')
                ->with('error', 'Region is no longer active.');
        }

        $targetAuction = $this->activeAuctionFor($region, allowLockedOrLive: false);
        if (!$targetAuction) {
            return redirect()->route('community.my-lots')
                ->with('error', 'No open community auction accepting listings right now. Check back later.');
        }

        $weeklyLimit = (int) ($region->listing_limit_per_week ?? config('community.listing_limit_per_week', 3));
        $existing = Lot::where('seller_user_id', $request->user()->id)
            ->where('event_id', $targetAuction->id)
            ->count();
        if ($existing >= $weeklyLimit) {
            return redirect()->route('community.my-lots')
                ->with('error', "You've reached this week's listing limit ({$weeklyLimit} lots). Relist next week.");
        }

        try {
            $newLot = $lot->relistTo($targetAuction);
        } catch (\Exception $e) {
            return redirect()->route('community.my-lots')
                ->with('error', 'Failed to relist: ' . $e->getMessage());
        }

        return redirect()->route('community.my-lots')
            ->with('success', "\"{$newLot->title}\" added to \"{$targetAuction->title}\".");
    }

    /**
     * Seller marks a sold lot as collected and paid (offline).
     */
    public function markPaid(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());

        if ($lot->status !== 'sold' || $lot->isPaidOffline()) {
            return redirect()->route('community.my-lots')
                ->with('error', 'This lot is not awaiting collection.');
        }

        $lot->update([
            'is_paid' => true,
            'payment_status' => 'paid_offline',
            'payment_completed_at' => now(),
        ]);

        return redirect()->route('community.my-lots')
            ->with('success', 'Lot marked as collected and paid.');
    }

    /**
     * Seller marks a sold community lot as never collected/paid by the buyer.
     * Voids the platform commission and issues a buyer strike. If the buyer
     * crosses the strike threshold, their bidding gets disabled.
     */
    public function voidBuyerNonPayment(Lot $lot, Request $request)
    {
        $this->authorizeSeller($lot, $request->user());

        if (!$lot->isCommunityLot()) {
            abort(403, 'Only community lots use this flow.');
        }
        if ($lot->status !== 'sold' || $lot->isPaidOffline() || $lot->payment_status === 'voided') {
            return redirect()->route('community.my-lots')
                ->with('error', 'This lot is not eligible for non-payment void.');
        }
        if (!$lot->winning_bidder_id) {
            return redirect()->route('community.my-lots')
                ->with('error', 'No winning bidder to report.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $buyerId = (int) $lot->winning_bidder_id;

        DB::transaction(function () use ($lot, $buyerId, $validated, $request) {
            // 1. Void the commission ledger row (if any) — wipes both platform and agent claims.
            $ledgerRow = \App\Models\CommunityCommissionLedger::where('lot_id', $lot->id)->first();
            if ($ledgerRow && $ledgerRow->status === 'accrued') {
                app(\App\Services\CommunityCommissionService::class)
                    ->void($ledgerRow, 'Buyer non-payment reported by seller.');
            }

            // 2. Mark the lot as buyer-non-payment voided. Revert status to 'unsold'
            //    and clear the winning bidder so the seller can relist it through
            //    the normal flow. payment_status='voided' is kept as the audit
            //    breadcrumb explaining why an "unsold" lot has bid history.
            $lot->update([
                'status' => 'unsold',
                'winning_bidder_id' => null,
                'payment_status' => 'voided',
                'is_paid' => false,
            ]);

            // 3. Issue a buyer strike.
            \App\Models\BuyerStrike::create([
                'user_id' => $buyerId,
                'lot_id' => $lot->id,
                'seller_user_id' => $request->user()->id,
                'reason' => $validated['reason'] ?? 'Buyer did not collect/pay.',
                'reported_at' => now(),
            ]);

            // 4. Apply auto-block if buyer has crossed the strike threshold.
            $window = (int) config('community.buyer_strike_window_months', 6);
            $threshold = (int) config('community.buyer_strike_block_threshold', 2);
            $activeStrikes = \App\Models\BuyerStrike::where('user_id', $buyerId)
                ->whereNull('reversed_at')
                ->where('reported_at', '>=', now()->subMonths($window))
                ->count();

            if ($activeStrikes >= $threshold) {
                \App\Models\User::where('id', $buyerId)->update([
                    'bidding_disabled' => true,
                    'bidding_disabled_reason' => "Auto-disabled: {$activeStrikes} non-payment strikes in {$window} months.",
                ]);
            }
        });

        return redirect()->route('community.my-lots')
            ->with('success', 'Sale voided. Platform fee waived; a strike has been recorded against the buyer.');
    }

    // ----- helpers -----

    private function authorizeSeller(Lot $lot, User $user): void
    {
        abort_unless($lot->seller_user_id === $user->id, 403, 'Not your lot.');
    }

    /**
     * Find the region's next auction accepting activity.
     * If $allowLockedOrLive: returns draft | upcoming | live (for display).
     * Else: returns only draft (for seller listings — can't list into locked auction).
     */
    private function activeAuctionFor(CommunityRegion $region, bool $allowLockedOrLive = true): ?Auction
    {
        $statuses = $allowLockedOrLive ? ['draft', 'upcoming', 'live'] : ['draft'];

        return Auction::where('community_region_id', $region->id)
            ->whereIn('status', $statuses)
            ->orderBy('goes_live_at')
            ->first();
    }

    /**
     * Process + store uploaded image (WebP optimized + 300px thumb). Mirrors LotController.
     */
    protected function processAndStoreImage($uploadedImage, Lot $lot, int $order): LotImage
    {
        $filename = uniqid('lot_' . $lot->id . '_') . '.webp';
        $fullTempPath = sys_get_temp_dir() . '/' . $filename;
        $uploadedImage->move(sys_get_temp_dir(), $filename);

        $manager = new ImageManager(new Driver());

        $optimized = $manager->read($fullTempPath);
        $optimized->orient();
        $optimized->scaleDown(width: 1920);
        $encodedOptimized = $optimized->toWebp(quality: (int) config('platform.images.quality', 90));
        $optimizedPath = 'images/lots/optimized/' . $filename;
        Storage::disk('public')->put($optimizedPath, $encodedOptimized);

        $thumb = $manager->read($fullTempPath);
        $thumb->orient();
        $thumb->scaleDown(width: 300);
        $encodedThumb = $thumb->toWebp(quality: 80);
        $thumbPath = 'images/lots/thumbnails/' . $filename;
        Storage::disk('public')->put($thumbPath, $encodedThumb);

        @unlink($fullTempPath);

        $isPrimary = $lot->images()->count() === 0;

        return LotImage::create([
            'lot_id' => $lot->id,
            'original_path' => null,
            'optimized_path' => $optimizedPath,
            'thumbnail_path' => $thumbPath,
            'order' => $order,
            'is_primary' => $isPrimary,
        ]);
    }
}
