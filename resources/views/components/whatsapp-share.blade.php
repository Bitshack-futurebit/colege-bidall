@props([
    'lot' => null,
    'auction' => null,
    'size' => 'sm',
])

{{--
    Universal WhatsApp share button. Usage examples (DO NOT nest comments
    inside this block — Blade comments do not nest, the first inner closer
    terminates the whole block and the rest gets compiled as live markup,
    causing infinite component recursion and OOM):

      Lot share, small size:    <x-whatsapp-share :lot="..." />
      Auction share, small:     <x-whatsapp-share :auction="..." />
      Icon-only for grids:      <x-whatsapp-share :lot="..." size="icon" />

    Message is built client-side in JS template literals because PHP-side emojis
    came through as ? on this stack. See memory/feedback_whatsapp_emojis.md.
    Auto-detects community vs regular and picks the right copy.

    Visibility: hidden on draft (regular) and ended auctions. Community drafts
    show because that's the listing window — sellers want to share early.
--}}

@php
    // Prefer caller-supplied $auction to avoid lazy-loading $lot->auction in
    // tight loops (e.g. rendering this component once per lot card on an
    // auction page). Eloquent doesn't share parent relations into child
    // models, so $lot->auction triggers a fresh DB load and a fresh model
    // instance every time — that compounds into OOM at scale.
    $resolvedAuction = $auction ?? $lot?->auction;
    $shareData = null;

    if ($lot && $resolvedAuction) {
        $shareData = [
            'kind' => 'lot',
            'brand' => config('branding.name'),
            'url' => route('lots.show', $lot),
            'isCommunity' => (bool) $resolvedAuction->is_community,
            'regionName' => $resolvedAuction->communityRegion?->name ?? 'Community',
            'auctionTitle' => $resolvedAuction->title,
            'lotNumber' => (int) $lot->lot_number,
            'lotTitle' => $lot->title,
            'startingBid' => (float) $lot->starting_bid,
            'currentBid' => $lot->current_bid !== null ? (float) $lot->current_bid : null,
            'isLive' => $resolvedAuction->status === 'live',
            'goesLiveAt' => $resolvedAuction->goes_live_at?->format('D j M \a\t H:i'),
            'startTime' => $resolvedAuction->start_time?->format('D j M \a\t H:i'),
        ];
    } elseif ($auction) {
        $lotCount = $auction->lots_count
            ?? ($auction->relationLoaded('lots') ? $auction->lots->whereNull('withdrawn_at')->count() : 0);
        $shareData = [
            'kind' => 'auction',
            'brand' => config('branding.name'),
            'url' => route('auctions.show', $auction),
            'isCommunity' => (bool) $auction->is_community,
            'regionName' => $auction->communityRegion?->name ?? 'Community',
            'title' => $auction->title,
            'auctioneerName' => $auction->auctioneer->business_name ?? '',
            'isLive' => $auction->status === 'live',
            'goesLiveAt' => $auction->goes_live_at?->format('D j M \a\t H:i'),
            'startTime' => $auction->start_time?->format('D j M \a\t H:i'),
            'lotCount' => (int) $lotCount,
        ];
    }

    $visible = false;
    if ($shareData && $resolvedAuction) {
        $allowed = $shareData['isCommunity']
            ? ['draft', 'upcoming', 'live']
            : ['upcoming', 'live'];
        $visible = in_array($resolvedAuction->status, $allowed);
    }

    $btnClass = $size === 'icon'
        ? 'inline-flex items-center justify-center w-8 h-8 bg-green-600 hover:bg-green-700 text-white rounded-lg transition'
        : 'inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition';
@endphp

@if($visible)
<a x-data="{ msg: '' }"
   x-init="
       const d = @js($shareData);
       const fmtR = (n) => 'R' + Number(n).toLocaleString('en-ZA', { maximumFractionDigits: 0 });
       let m;
       if (d.kind === 'lot') {
           if (d.isCommunity) {
               m  = `\u{1F3D8}\u{FE0F} *${d.regionName}*\n\n`;
               m += `\u{1F4E6} *Lot #${d.lotNumber}: ${d.lotTitle}*\n`;
               m += `\u{1F4B0} Starts at ${fmtR(d.startingBid)}\n`;
               if (d.isLive) m += `\u{1F534} *BIDDING NOW*\n`;
               else if (d.goesLiveAt) m += `\u{1F4C5} Auction: ${d.goesLiveAt}\n`;
               m += `\n\u{1F91D} Local seller. Subject to confirmation.\n\n`;
               m += `Powered by ${d.brand}\n\n\u{1F449} View & bid:\n${d.url}`;
           } else {
               const priceLine = d.currentBid !== null
                   ? `\u{1F4B0} Current bid: ${fmtR(d.currentBid)}`
                   : `\u{1F4B0} Starting at ${fmtR(d.startingBid)}`;
               m  = `\u{1F528} *Lot #${d.lotNumber}: ${d.lotTitle}*\n`;
               m += `in *${d.auctionTitle}*\n\n`;
               m += priceLine + `\n`;
               if (d.isLive) m += `\u{1F534} *LIVE NOW*\n`;
               else if (d.startTime) m += `\u{1F4C5} Starts: ${d.startTime}\n`;
               m += `\nPowered by ${d.brand}\n\n\u{1F449} View & bid:\n${d.url}`;
           }
       } else {
           if (d.isCommunity) {
               m  = `\u{1F3D8}\u{FE0F} *COMMUNITY AUCTION* \u{1F3D8}\u{FE0F}\n\n*${d.regionName}*\n`;
               if (d.isLive) m += `\u{1F534} *BIDDING NOW*\n`;
               else if (d.goesLiveAt) m += `\u{1F4C5} ${d.goesLiveAt}\n`;
               if (d.lotCount > 0) m += `\u{1F4E6} ${d.lotCount} lots\n`;
               m += `\nPowered by ${d.brand}\n\n\u{1F449} Browse & bid:\n${d.url}`;
           } else {
               m  = `\u{1F4E2} *${d.title}* \u{1F4E2}\n\n`;
               if (d.auctioneerName) m += `by *${d.auctioneerName}*\n`;
               if (d.isLive) m += `\u{1F534} *LIVE NOW*\n`;
               else if (d.startTime) m += `\u{1F4C5} Starts: ${d.startTime}\n`;
               if (d.lotCount > 0) m += `\u{1F4E6} ${d.lotCount} lots\n`;
               m += `\nPowered by ${d.brand}\n\n\u{1F449} Browse & bid:\n${d.url}`;
           }
       }
       msg = m;
   "
   :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent(msg)"
   target="_blank"
   rel="noopener"
   class="{{ $btnClass }}"
   title="Share to WhatsApp"
   @click.stop>
    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
    @if($size !== 'icon')<span class="hidden sm:inline ml-1">Share</span>@endif
</a>
@endif
