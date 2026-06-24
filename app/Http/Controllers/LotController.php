<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Lot;
use App\Models\LotImage;
use App\Models\ProxyBid;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class LotController extends Controller
{
    use AuthorizesRequests;
    /**
     * Show single lot (public).
     */
    public function show(Lot $lot)
    {
        $lot->load([
            'auction.auctioneer.user',
            'auction.communityRegion',
            'images',
            'bids' => function ($query) {
                $query->orderBy('amount', 'desc')->limit(10);
            },
            'bids.user',
            'winningBidder'
        ]);

        // Dutch live auctions: redirect to the auction page (lots run sequentially there)
        if ($lot->auction->isDutch() && $lot->auction->isLive()) {
            return redirect()->route('auctions.show', $lot->auction);
        }

        // Check if user has watchlisted
        $isWatchlisted = false;
        $isRegistered = !$lot->auction->requiresRegistration();
        if (auth()->check()) {
            $isWatchlisted = auth()->user()->hasWatchlisted($lot->id);
            if ($lot->auction->requiresRegistration()) {
                $isRegistered = auth()->user()->isRegisteredForAuction($lot->event_id);
            }
        }

        // Check if user has bid
        $userBid = null;
        $userProxyMax = null;
        $userSealedBid = null;
        if (auth()->check()) {
            if ($lot->auction->isSealed()) {
                $userSealedBid = $lot->getUserSealedBid(auth()->id());
            } else {
                $userBid = $lot->bids()
                    ->where('user_id', auth()->id())
                    ->orderBy('amount', 'desc')
                    ->first();

                $userProxyMax = ProxyBid::where('lot_id', $lot->id)
                    ->where('user_id', auth()->id())
                    ->active()
                    ->value('max_amount');
                if ($userProxyMax !== null) {
                    $userProxyMax = (float) $userProxyMax;
                }
            }
        }

        $proxyEnabled = (bool) $lot->auction->allow_proxy_bidding;
        $isOwner = $lot->isOwnedBy(auth()->user());

        return view('lots.show', compact('lot', 'isWatchlisted', 'isRegistered', 'userBid', 'userProxyMax', 'proxyEnabled', 'userSealedBid', 'isOwner'));
    }

    /**
     * Show create lot form.
     */
    public function create(Auction $auction)
    {
        $this->authorize('update', $auction);

        // Block adding lots in the last hour before an upcoming auction goes live
        if ($auction->status === 'upcoming' && $auction->start_time->subHour()->isPast()) {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Lots cannot be added within 1 hour of the auction start time.');
        }

        // Block adding lots to live or ended auctions
        if (in_array($auction->status, ['live', 'ended'])) {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Lots cannot be added to a ' . $auction->status . ' auction.');
        }

        $auctioneer = $auction->auctioneer;

        // Check credit balance for each tier
        $tierCosts = [
            'basic' => $auctioneer->calculateLotCost('basic'),
            'pro' => $auctioneer->calculateLotCost('pro'),
            'premium' => $auctioneer->calculateLotCost('premium'),
        ];

        $canAfford = [
            'basic' => $auctioneer->canCreateLot('basic'),
            'pro' => $auctioneer->canCreateLot('pro'),
            'premium' => $auctioneer->canCreateLot('premium'),
        ];

        return view('seller.lots.create', compact('auction', 'auctioneer', 'tierCosts', 'canAfford'));
    }

    /**
     * Store new lot.
     */
    public function store(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $auctioneer = $auction->auctioneer;

        // Block adding lots in the last hour before an upcoming auction goes live
        if ($auction->status === 'upcoming' && $auction->start_time->subHour()->isPast()) {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Lots cannot be added within 1 hour of the auction start time.');
        }

        // Block adding lots to live or ended auctions
        if (in_array($auction->status, ['live', 'ended'])) {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Lots cannot be added to a ' . $auction->status . ' auction.');
        }

        // Pre-validate images (no max limit - charge based on actual count)
        $maxSize = config('platform.images.max_upload_size', 15360); // KB
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:' . $maxSize], // Configurable max (default 15MB)
        ]);

        // Auto-calculate tier based on image count (unlimited images allowed)
        $imageCount = count($request->file('images'));
        $tier = 'basic';
        if ($imageCount >= 6) {
            $tier = 'premium'; // 6+ images = Premium tier pricing
        } elseif ($imageCount >= 2) {
            $tier = 'pro'; // 2-5 images = Pro tier pricing
        }

        // For upcoming auctions, check credits and deduct immediately
        if ($auction->status === 'upcoming') {
            $cost = $auctioneer->calculateLotCost($tier);
            if (!$auctioneer->canCreateLot($tier)) {
                return back()->withInput()
                    ->with('error', sprintf(
                        'Insufficient credits. This lot requires %s (%s tier). Your balance: %s.',
                        formatCurrency($cost),
                        ucfirst($tier),
                        formatCurrency($auctioneer->credit_balance)
                    ));
            }
        }

        // Override submitted tier with calculated tier
        $request->merge(['image_tier' => $tier]);

        $lotRules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image_tier' => ['required', 'in:basic,pro,premium'],
            'subject_to_confirmation' => ['nullable'],
            'confirmation_message' => ['nullable', 'required_if:subject_to_confirmation,1', 'string', 'max:1000'],
            'tender_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            // Internal supplier record (never shown to bidders). All optional.
            // Either supplier_id (picked from search) OR the free-text fields below.
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_id_number' => ['nullable', 'string', 'max:50'],
            'supplier_address' => ['nullable', 'string', 'max:1000'],
            'supplier_id_document' => ['nullable', 'file', 'image', 'max:10240'],
        ];

        if ($auction->isDutch()) {
            $lotRules['dutch_start_price'] = ['required', 'numeric', 'min:0.01'];
            $lotRules['dutch_floor_price'] = ['required', 'numeric', 'min:0.01', 'lt:dutch_start_price'];
            $lotRules['dutch_duration'] = ['required', 'integer', 'min:60', 'max:3600'];
            $lotRules['dutch_drop_strategy'] = ['required', 'in:constant,fast_sell,max_value,high_drama'];
            $lotRules['quantity'] = ['nullable', 'integer', 'min:1'];
        } elseif ($auction->isSealed()) {
            $lotRules['reserve_price'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $lotRules['starting_bid'] = ['required', 'numeric', 'min:' . minBid()];
            $lotRules['reserve_price'] = ['nullable', 'numeric', 'min:0'];
            $lotRules['increment'] = ['required', 'numeric', 'min:' . minIncrement()];
        }

        $validated = $request->validate($lotRules);

        // Create lot
        $validated['event_id'] = $auction->id;
        $validated['status'] = 'draft';
        $validated['subject_to_confirmation'] = $request->boolean('subject_to_confirmation');
        if (!$validated['subject_to_confirmation']) {
            $validated['confirmation_message'] = null;
        }

        if ($auction->isDutch()) {
            // Compute drop matrix from duration + strategy
            $matrix = Lot::calculateDropMatrix(
                (float) $validated['dutch_start_price'],
                (float) $validated['dutch_floor_price'],
                (int) $validated['dutch_duration'],
                $validated['dutch_drop_strategy']
            );
            $validated['dutch_drop_amount'] = $matrix['drop_amount'];
            $validated['dutch_drop_interval'] = $matrix['drop_interval'];
            $validated['starting_bid'] = $validated['dutch_start_price'];
            $validated['increment'] = 0;
            $validated['quantity'] = $validated['quantity'] ?? 1;
        } elseif ($auction->isSealed()) {
            $validated['starting_bid'] = 0;
            $validated['increment'] = 0;
        }

        // Handle tender document upload
        if ($request->hasFile('tender_document')) {
            $validated['tender_document'] = $request->file('tender_document')->store('tender-documents', 'public');
        }

        // Resolve the supplier for this lot. Two paths:
        // 1. Form posted an explicit supplier_id (auctioneer picked an existing supplier
        //    from the search dropdown) — just verify ownership and link it.
        // 2. Otherwise fall back to find-or-create using the free-text fields.
        $pickedId = $request->input('supplier_id');
        $supplier = null;
        if ($pickedId) {
            $supplier = \App\Models\Supplier::where('id', $pickedId)
                ->where('auctioneer_id', $auctioneer->id)
                ->first();
            // If the picked supplier doesn't belong to this auctioneer, silently
            // ignore it (validation already confirmed the row exists — this is a
            // belt-and-braces ownership check).
        }
        if (!$supplier) {
            $supplier = \App\Models\Supplier::findOrCreateForAuctioneer(
                $auctioneer->id,
                $request->input('supplier_name') ?: null,
                $request->input('supplier_id_number') ?: null,
                $request->input('supplier_address') ?: null,
                $request->hasFile('supplier_id_document')
                    ? $request->file('supplier_id_document')->store('supplier-id-docs', 'public')
                    : null
            );
        }
        if ($supplier) {
            $validated['supplier_id'] = $supplier->id;
        }
        // Drop the form-only fields that don't belong on the lot model itself.
        unset(
            $validated['supplier_name'],
            $validated['supplier_id_number'],
            $validated['supplier_address'],
            $validated['supplier_id_document']
        );

        $lot = Lot::create($validated);

        // Process and store images
        foreach ($request->file('images') as $index => $image) {
            $this->processAndStoreImage($image, $lot, $index);
        }

        // Update auction lot count
        $auction->increment('total_lots');

        // Deduct credits immediately for upcoming auctions
        if ($auction->status === 'upcoming') {
            $cost = $auctioneer->calculateLotCost($tier);
            $auctioneer->deductCredits(
                $cost,
                'lot_live',
                $lot->id,
                "Lot #{$lot->lot_number} - {$lot->title} (added to upcoming auction)"
            );
            return redirect()->route('seller.auctions.show', $auction)
                ->with('success', sprintf(
                    'Lot added successfully with %d image(s). %s deducted from your credits. Balance: %s',
                    count($request->file('images')),
                    formatCurrency($cost),
                    formatCurrency($auctioneer->fresh()->credit_balance)
                ));
        }

        return redirect()->route('seller.auctions.show', $auction)
            ->with('success', 'Lot created successfully with ' . count($request->file('images')) . ' image(s)!');
    }

    /**
     * Show edit lot form.
     */
    public function edit(Auction $auction, Lot $lot)
    {
        $this->authorize('update', $auction);

        if ((int) $lot->event_id !== (int) $auction->id) {
            abort(404);
        }

        if (in_array($auction->status, ['upcoming', 'live', 'ended'])) {
            return redirect()->route('seller.auctions.show', $auction)
                ->with('error', 'Lots cannot be edited once the auction is scheduled. Use "Withdraw" to remove a lot.');
        }

        $lot->load('images');

        $maxImages = $lot->getMaxImages();
        $remainingImages = $maxImages - $lot->images()->count();

        return view('seller.lots.edit', compact('auction', 'lot', 'maxImages', 'remainingImages'));
    }

    /**
     * Update lot.
     */
    public function update(Request $request, Auction $auction, Lot $lot)
    {
        $this->authorize('update', $auction);

        if ((int) $lot->event_id !== (int) $auction->id) {
            abort(404);
        }

        // Can't edit once auction is scheduled or running
        if (in_array($auction->status, ['upcoming', 'live', 'ended'])) {
            return back()->with('error', 'Lot details cannot be edited once the auction is scheduled. You can only withdraw the lot.');
        }

        // Can't edit if lot is live or sold
        if (in_array($lot->status, ['live', 'sold'])) {
            return back()->with('error', 'Cannot edit live or sold lots.');
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'subject_to_confirmation' => ['nullable'],
            'confirmation_message' => ['nullable', 'required_if:subject_to_confirmation,1', 'string', 'max:1000'],
            'tender_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            // Internal supplier record (never shown to bidders). All optional.
            // Either supplier_id (picked from search) OR the free-text fields below.
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_id_number' => ['nullable', 'string', 'max:50'],
            'supplier_address' => ['nullable', 'string', 'max:1000'],
            'supplier_id_document' => ['nullable', 'file', 'image', 'max:10240'],
        ];

        if ($auction->isDutch()) {
            $rules['dutch_start_price'] = ['required', 'numeric', 'min:0.01'];
            $rules['dutch_floor_price'] = ['required', 'numeric', 'min:0.01', 'lt:dutch_start_price'];
            $rules['dutch_duration'] = ['required', 'integer', 'min:60', 'max:3600'];
            $rules['dutch_drop_strategy'] = ['required', 'in:constant,fast_sell,max_value,high_drama'];
            $rules['quantity'] = ['nullable', 'integer', 'min:1'];
        } elseif ($auction->isSealed()) {
            $rules['reserve_price'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $rules['starting_bid'] = ['required', 'numeric', 'min:' . minBid()];
            $rules['reserve_price'] = ['nullable', 'numeric', 'min:0'];
            $rules['increment'] = ['required', 'numeric', 'min:' . minIncrement()];
        }

        $validated = $request->validate($rules);

        $validated['subject_to_confirmation'] = $request->boolean('subject_to_confirmation');
        if (!$validated['subject_to_confirmation']) {
            $validated['confirmation_message'] = null;
        }

        if ($auction->isDutch()) {
            // Compute drop matrix from duration + strategy
            $matrix = Lot::calculateDropMatrix(
                (float) $validated['dutch_start_price'],
                (float) $validated['dutch_floor_price'],
                (int) $validated['dutch_duration'],
                $validated['dutch_drop_strategy']
            );
            $validated['dutch_drop_amount'] = $matrix['drop_amount'];
            $validated['dutch_drop_interval'] = $matrix['drop_interval'];
            $validated['starting_bid'] = $validated['dutch_start_price'];
            $validated['increment'] = 0;
            $validated['quantity'] = $validated['quantity'] ?? 1;
        }

        // Handle tender document upload/removal
        if ($request->boolean('remove_tender_document') && $lot->tender_document) {
            Storage::disk('public')->delete($lot->tender_document);
            $validated['tender_document'] = null;
        } elseif ($request->hasFile('tender_document')) {
            // Delete old document if replacing
            if ($lot->tender_document) {
                Storage::disk('public')->delete($lot->tender_document);
            }
            $validated['tender_document'] = $request->file('tender_document')->store('tender-documents', 'public');
        }

        // Resolve/update the supplier for this lot. Two paths:
        // 1. Form posted supplier_id (picked from search) — link to that row, don't
        //    mutate its details, and don't touch the currently-linked supplier's files.
        // 2. Otherwise fall back to the existing edit-in-place flow: upload/remove the
        //    current supplier's id_document, then find-or-create from free-text fields.
        $auctioneerId = $lot->auction->auctioneer_id;
        $pickedId = $request->input('supplier_id');

        if ($pickedId) {
            $picked = \App\Models\Supplier::where('id', $pickedId)
                ->where('auctioneer_id', $auctioneerId)
                ->first();
            $validated['supplier_id'] = $picked?->id;
        } else {
            $currentSupplier = $lot->supplier;

            // Handle supplier ID document upload/removal against the currently-linked supplier
            $idDocumentPath = null;
            $removeDoc = $request->boolean('remove_supplier_id_document');
            if ($request->hasFile('supplier_id_document')) {
                if ($currentSupplier && $currentSupplier->id_document) {
                    Storage::disk('public')->delete($currentSupplier->id_document);
                }
                $idDocumentPath = $request->file('supplier_id_document')->store('supplier-id-docs', 'public');
            } elseif ($removeDoc && $currentSupplier && $currentSupplier->id_document) {
                Storage::disk('public')->delete($currentSupplier->id_document);
                $currentSupplier->update(['id_document' => null]);
            }

            $supplier = \App\Models\Supplier::findOrCreateForAuctioneer(
                $auctioneerId,
                $request->input('supplier_name') ?: null,
                $request->input('supplier_id_number') ?: null,
                $request->input('supplier_address') ?: null,
                $idDocumentPath
            );
            $validated['supplier_id'] = $supplier?->id;
        }

        // Drop form-only fields before save.
        unset(
            $validated['supplier_name'],
            $validated['supplier_id_number'],
            $validated['supplier_address'],
            $validated['supplier_id_document']
        );
        // supplier_id is left in $validated (it's a real column on the lot).

        $lot->update($validated);

        // Handle additional images for draft lots
        if ($request->hasFile('images')) {
            $maxSize = config('platform.images.max_upload_size', 15360);
            $request->validate([
                'images.*' => ['image', 'max:' . $maxSize],
            ]);

            $currentCount = $lot->images()->count();
            foreach ($request->file('images') as $index => $image) {
                $this->processAndStoreImage($image, $lot, $currentCount + $index);
            }

            $newCount = $lot->images()->count();
            return redirect()->route('seller.auctions.show', $auction)
                ->with('success', 'Lot updated and ' . count($request->file('images')) . ' image(s) added. Total: ' . $newCount . ' images.');
        }

        return redirect()->route('seller.auctions.show', $auction)
            ->with('success', 'Lot updated successfully!');
    }

    /**
     * Withdraw lot from auction.
     */
    public function withdraw(Request $request, Auction $auction, Lot $lot)
    {
        $this->authorize('update', $auction);

        if ((int) $lot->event_id !== (int) $auction->id) {
            abort(404);
        }

        if (!$lot->canBeWithdrawn()) {
            return back()->with('error', 'This lot cannot be withdrawn. Withdrawal is only available when the auction is in upcoming status.');
        }

        $validated = $request->validate([
            'withdrawal_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $lot->withdraw($validated['withdrawal_reason'] ?? null);

        return back()->with('success', 'Lot withdrawn successfully. Note: Lot fees are non-refundable once the auction is scheduled.');
    }

    /**
     * Delete lot.
     */
    public function destroy(Auction $auction, Lot $lot)
    {
        $this->authorize('update', $auction);

        if ((int) $lot->event_id !== (int) $auction->id) {
            abort(404);
        }

        // Can only delete when auction is in draft status
        if ($auction->status !== 'draft') {
            return back()->with('error', 'Cannot delete lots once auction is scheduled. Use "Withdraw" instead to remove the lot from bidding.');
        }

        // Can't delete if live or sold
        if (in_array($lot->status, ['live', 'sold'])) {
            return back()->with('error', 'Cannot delete live or sold lots.');
        }

        $deletedLotNumber = $lot->lot_number;

        // Delete images
        foreach ($lot->images as $image) {
            $image->delete(); // This also deletes physical files
        }

        $lot->delete();

        // Renumber remaining lots to maintain sequential numbering
        $this->renumberLots($auction, $deletedLotNumber);

        // Update auction lot count
        $auction->decrement('total_lots');

        return redirect()->route('seller.auctions.show', $auction)
            ->with('success', 'Lot deleted successfully. Lot numbers have been renumbered.');
    }

    /**
     * Upload images for lot.
     */
    public function uploadImages(Request $request, Lot $lot)
    {
        $this->authorize('update', $lot->auction);

        if (in_array($lot->auction->status, ['upcoming', 'live', 'ended'])) {
            return back()->with('error', 'Images cannot be added to lots once the auction is scheduled.');
        }

        $currentCount = $lot->images()->count();

        $maxSize = config('platform.images.max_upload_size', 15360); // KB
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['required', 'image', 'max:' . $maxSize], // Configurable max (default 15MB)
        ]);

        foreach ($request->file('images') as $index => $image) {
            $this->processAndStoreImage($image, $lot, $currentCount + $index);
        }

        $newCount = $currentCount + count($request->file('images'));
        $newTier = $this->calculateTier($newCount);

        return back()->with('success', "Images uploaded successfully! Total: {$newCount} images (charged as {$newTier} tier)");
    }

    /**
     * Calculate tier based on image count.
     */
    protected function calculateTier(int $imageCount): string
    {
        if ($imageCount >= 6) {
            return 'premium';
        } elseif ($imageCount >= 2) {
            return 'pro';
        }
        return 'basic';
    }

    /**
     * Delete lot image.
     */
    public function deleteImage(Lot $lot, LotImage $image)
    {
        $this->authorize('update', $lot->auction);

        if ($image->lot_id !== $lot->id) {
            abort(404);
        }

        if (in_array($lot->auction->status, ['upcoming', 'live', 'ended'])) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Images cannot be deleted once the auction is scheduled.'], 403);
            }
            return back()->with('error', 'Images cannot be deleted once the auction is scheduled.');
        }

        // Prevent deleting the last image - every lot must have at least 1 image
        if ($lot->images()->count() <= 1) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last image. Every lot must have at least 1 image.'
                ], 400);
            }
            return back()->with('error', 'Cannot delete the last image. Every lot must have at least 1 image.');
        }

        $image->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.'
            ]);
        }

        return back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Relist an unsold lot into a target draft auction.
     */
    public function relist(Request $request, Lot $lot)
    {
        $auctioneer = auth()->user()->auctioneer;

        if (!$auctioneer || $lot->auction->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        if (!$lot->canBeRelisted()) {
            return back()->with('error', 'This lot cannot be relisted. Only unsold lots from ended auctions can be relisted.');
        }

        $validated = $request->validate([
            'target_auction_id' => ['required', 'exists:events,id'],
        ]);

        $targetAuction = Auction::find($validated['target_auction_id']);

        if ($targetAuction->auctioneer_id !== $auctioneer->id) {
            return back()->with('error', 'You can only relist to your own auctions.');
        }

        if ($targetAuction->status !== 'draft') {
            return back()->with('error', 'You can only relist lots to draft auctions.');
        }

        try {
            $newLot = $lot->relistTo($targetAuction);

            $freeLabel = $lot->free_relist_eligible ? 'FREE ' : '';

            return redirect()
                ->route('seller.auctions.lots.edit', [$targetAuction, $newLot])
                ->with('success', "Lot {$freeLabel}relisted successfully to \"{$targetAuction->title}\". Review and edit the lot below.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to relist lot: ' . $e->getMessage());
        }
    }

    /**
     * Bulk relist unsold lots into a target draft auction.
     */
    public function bulkRelist(Request $request)
    {
        $auctioneer = auth()->user()->auctioneer;

        if (!$auctioneer) {
            abort(403);
        }

        $validated = $request->validate([
            'lot_ids' => ['required', 'array', 'min:1'],
            'lot_ids.*' => ['required', 'integer', 'exists:lots,id'],
            'target_auction_id' => ['required', 'exists:events,id'],
        ]);

        $targetAuction = Auction::find($validated['target_auction_id']);

        if ($targetAuction->auctioneer_id !== $auctioneer->id) {
            return back()->with('error', 'You can only relist to your own auctions.');
        }

        if ($targetAuction->status !== 'draft') {
            return back()->with('error', 'You can only relist lots to draft auctions.');
        }

        $lots = Lot::whereIn('id', $validated['lot_ids'])
            ->whereHas('auction', function ($q) use ($auctioneer) {
                $q->where('auctioneer_id', $auctioneer->id);
            })
            ->get();

        $relisted = 0;
        $free = 0;
        $paid = 0;
        $errors = [];

        foreach ($lots as $lot) {
            if (!$lot->canBeRelisted()) {
                $errors[] = "\"{$lot->title}\" cannot be relisted.";
                continue;
            }

            try {
                $lot->relistTo($targetAuction);
                $relisted++;
                if ($lot->free_relist_eligible) {
                    $free++;
                } else {
                    $paid++;
                }
            } catch (\Exception $e) {
                $errors[] = "\"{$lot->title}\": {$e->getMessage()}";
            }
        }

        $message = "{$relisted} lot(s) relisted to \"{$targetAuction->title}\" ({$free} free, {$paid} paid).";

        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' ', $errors);
        }

        return redirect()->route('seller.unsold-lots')
            ->with($relisted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Toggle watchlist.
     */
    public function toggleWatchlist(Lot $lot)
    {
        $user = auth()->user();

        if ($lot->auction->isLiveFormat()) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Watchlist is not available for live auctions.'], 422);
            }
            return back()->with('error', 'Watchlist is not available for live auctions.');
        }

        if ($user->hasWatchlisted($lot->id)) {
            $user->watchlist()->where('lot_id', $lot->id)->delete();
            $message = 'Removed from watchlist.';
        } else {
            $user->watchlist()->create(['lot_id' => $lot->id]);
            $message = 'Added to watchlist!';
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'watchlisted' => $user->hasWatchlisted($lot->id),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Renumber lots after deletion to maintain sequential order.
     */
    protected function renumberLots(Auction $auction, int $deletedLotNumber): void
    {
        // Get all lots with lot_number greater than the deleted lot
        $lotsToRenumber = $auction->lots()
            ->where('lot_number', '>', $deletedLotNumber)
            ->orderBy('lot_number')
            ->get();

        foreach ($lotsToRenumber as $lot) {
            // Decrement lot number
            $newLotNumber = $lot->lot_number - 1;

            // Recalculate end time based on new lot number
            $gapSeconds = config('auction.lot_gap_seconds', 7);
            $secondsToAdd = ($newLotNumber - 1) * $gapSeconds;
            $newEndTime = $auction->end_time->copy()->addSeconds($secondsToAdd);

            // Update lot
            $lot->update([
                'lot_number' => $newLotNumber,
                'end_time' => $newEndTime,
            ]);
        }
    }

    /**
     * Soft-delete an unsold lot from an ended auction.
     * Lot is hidden from public views but preserved in financial reports.
     */
    public function bulkDeleteUnsold(Request $request)
    {
        $auctioneer = auth()->user()->auctioneer;

        if (!$auctioneer) {
            abort(403);
        }

        $validated = $request->validate([
            'lot_ids' => ['required', 'array', 'min:1'],
            'lot_ids.*' => ['integer', 'exists:lots,id'],
        ]);

        $lots = Lot::whereIn('id', $validated['lot_ids'])
            ->whereHas('auction', fn ($q) => $q->where('auctioneer_id', $auctioneer->id)->where('status', 'ended'))
            ->where('status', 'unsold')
            ->get();

        if ($lots->isEmpty()) {
            return back()->with('error', 'No valid unsold lots found to delete.');
        }

        $count = $lots->count();

        foreach ($lots as $lot) {
            foreach ($lot->images as $image) {
                $image->delete();
            }
            $lot->delete();
        }

        return redirect()->route('seller.unsold-lots')
            ->with('success', "$count unsold lot(s) deleted.");
    }

    public function destroyUnsold(Lot $lot)
    {
        $auctioneer = auth()->user()->auctioneer;

        if (!$auctioneer || $lot->auction->auctioneer_id !== $auctioneer->id) {
            abort(403);
        }

        if ($lot->status !== 'unsold') {
            return back()->with('error', 'Only unsold lots can be deleted from this page.');
        }

        if ($lot->auction->status !== 'ended') {
            return back()->with('error', 'The auction must be ended before deleting unsold lots.');
        }

        $title = $lot->title;

        // Delete images (physical files + DB records)
        foreach ($lot->images as $image) {
            $image->delete();
        }

        $lot->delete();

        return redirect()->route('seller.unsold-lots')
            ->with('success', "\"$title\" has been deleted.");
    }

    /**
     * Process and store image with optimization.
     */
    protected function processAndStoreImage($uploadedImage, Lot $lot, int $order): LotImage
    {
        $originalSize = $uploadedImage->getSize();

        // Generate unique filename
        $filename = uniqid('lot_' . $lot->id . '_') . '.webp';

        // Store original temporarily in system temp directory
        $fullTempPath = sys_get_temp_dir() . '/' . $filename;
        $uploadedImage->move(sys_get_temp_dir(), $filename);

        // Initialize ImageManager with GD driver
        $manager = new ImageManager(new Driver());

        // Create optimized version (1920px wide, full HD)
        $optimizedImage = $manager->read($fullTempPath);
        $optimizedImage->orient(); // Fix EXIF rotation from mobile cameras
        $optimizedImage->scaleDown(width: 1920);
        $encodedOptimized = $optimizedImage->toWebp(quality: (int) config('platform.images.quality', 90));

        $optimizedPath = 'images/lots/optimized/' . $filename;
        Storage::disk('public')->put($optimizedPath, $encodedOptimized);
        $optimizedSize = Storage::disk('public')->size($optimizedPath);

        // Create thumbnail (300px wide)
        $thumbnailImage = $manager->read($fullTempPath);
        $thumbnailImage->orient(); // Fix EXIF rotation from mobile cameras
        $thumbnailImage->scaleDown(width: 300);
        $encodedThumbnail = $thumbnailImage->toWebp(quality: 80);

        $thumbnailPath = 'images/lots/thumbnails/' . $filename;
        Storage::disk('public')->put($thumbnailPath, $encodedThumbnail);
        $thumbnailSize = Storage::disk('public')->size($thumbnailPath);

        // Delete original temp file
        @unlink($fullTempPath);

        // Create database record
        $isPrimary = $lot->images()->count() === 0; // First image is primary

        return LotImage::create([
            'lot_id' => $lot->id,
            'original_path' => null, // We delete originals
            'optimized_path' => $optimizedPath,
            'thumbnail_path' => $thumbnailPath,
            'order' => $order,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'thumbnail_size' => $thumbnailSize,
            'is_primary' => $isPrimary,
        ]);
    }
}
