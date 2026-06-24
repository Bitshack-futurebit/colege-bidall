@props(['lot' => null, 'size' => 'sm'])

{{--
    "Ask the seller on WhatsApp" button for community lots.

    Visibility: only logged-in community members (users with a community_region_id
    set) see this — protects sellers' private numbers from public scraping while
    keeping in-community contact frictionless.

    Sized 'sm' for lot detail / list contexts; 'icon' for tight lot-card grids.
    Hides itself when the lot is not a community lot, the auction has ended, the
    viewer is the seller, the viewer is not a community member, or the seller
    has no phone on file.

    Message is built client-side in JS (per memory/feedback_whatsapp_emojis.md).
--}}

@php
    $contactSellerVisible = false;
    $contactSellerData = null;

    if ($lot && $lot->isCommunityLot() && auth()->check()) {
        $viewer = auth()->user();
        $auctionStatus = $lot->auction?->status;
        $sellerPhone = $lot->seller?->phone ? waNumber($lot->seller->phone) : null;
        $isSelf = (int) $lot->seller_user_id === (int) $viewer->id;

        if ($viewer->community_region_id
            && $sellerPhone
            && !$isSelf
            && in_array($auctionStatus, ['draft', 'upcoming', 'live'], true)) {
            $contactSellerVisible = true;
            $contactSellerData = [
                'phone' => $sellerPhone,
                'lotNumber' => (int) $lot->lot_number,
                'lotTitle' => $lot->title,
                'auctionTitle' => $lot->auction->title ?? '',
                'lotUrl' => route('lots.show', $lot),
            ];
        }
    }

    $contactSellerBtnClass = $size === 'icon'
        ? 'inline-flex items-center justify-center w-8 h-8 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition'
        : 'inline-flex items-center gap-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition';
@endphp

@if($contactSellerVisible)
<a x-data="{ url: '#' }"
   x-init="
       const d = @js($contactSellerData);
       const msg = `\u{1F44B} Hi, I'm interested in *Lot #${d.lotNumber} — ${d.lotTitle}* in ${d.auctionTitle}.\n\nA few questions before I bid.\n\n${d.lotUrl}`;
       url = `https://wa.me/${d.phone}?text=` + encodeURIComponent(msg);
   "
   :href="url"
   target="_blank"
   rel="noopener"
   class="{{ $contactSellerBtnClass }}"
   title="Ask the seller on WhatsApp"
   aria-label="Ask the seller on WhatsApp"
   @click.stop>
    {{-- Chat bubble icon — visually distinct from the share button's WhatsApp logo --}}
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    @if($size !== 'icon')<span class="hidden sm:inline ml-1">Ask seller</span>@endif
</a>
@endif
