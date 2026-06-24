<?php

namespace App\Http\Middleware;

use App\Models\Auctioneer;
use App\Services\WhiteLabelContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveWhiteLabel
{
    public function __construct(private WhiteLabelContext $context)
    {
    }

    /**
     * Routes considered "canonical BidAll" — visiting any of these clears white-label stickiness,
     * so users who navigate to the main site naturally exit the branded experience.
     */
    private const BIDALL_EXIT_PATHS = [
        '/',
        'auctioneers',
        'about',
        'how-it-works',
        'terms',
        'privacy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // Admin panel pages never apply white-label branding — admins always see BidAll chrome.
        if (str_starts_with($path, 'admin/') || $path === 'admin') {
            View::share('whiteLabel', $this->context);
            return $next($request);
        }

        $isCanonicalBidall = in_array($path, self::BIDALL_EXIT_PATHS, true);

        // Explicit escape param or canonical BidAll page → clear stickiness and lock into BidAll mode.
        // Once bidall_mode is set, the user stays in BidAll branding even on white-label auctioneer pages.
        if ($request->query('bidall') === '1' || $isCanonicalBidall) {
            session()->forget('white_label_slug');
            session()->put('bidall_mode', true);
        }

        // Opt-in escape into the branded experience: ?brand=1 on any auctioneer link.
        if ($request->query('brand') === '1') {
            session()->forget('bidall_mode');
        }

        $auctioneer = $this->resolveAuctioneer($request);

        // If this is a direct/external navigation to a white-label auctioneer page
        // (e.g. typed URL, bookmark, QR code, link from Google/email/social), restore the
        // branded experience by clearing any leftover bidall_mode flag. Internal clicks from
        // BidAll pages (Referer matches our origin) remain in BidAll mode.
        if ($auctioneer && $auctioneer->isWhiteLabel()) {
            $referer = (string) $request->headers->get('referer', '');
            $appOrigin = rtrim(url('/'), '/');
            $isInternalReferer = $referer !== '' && str_starts_with($referer, $appOrigin);
            if (!$isInternalReferer) {
                session()->forget('bidall_mode');
            }
        }

        $inBidallMode = session('bidall_mode', false);

        // If the visitor is locked into BidAll mode, suppress white-label activation entirely.
        // They'll see the auctioneer's page with BidAll branding.
        if ($inBidallMode) {
            View::share('whiteLabel', $this->context);
            return $next($request);
        }

        // Fallback: re-activate from session if no route param resolved an auctioneer.
        // This keeps the white-label experience sticky across dashboard/profile/auth pages.
        if (!$auctioneer && session()->has('white_label_slug')) {
            $auctioneer = Auctioneer::where('slug', session('white_label_slug'))->first();
        }

        if ($auctioneer && $auctioneer->isWhiteLabel()) {
            $this->context->activate($auctioneer);
            session()->put('white_label_slug', $auctioneer->slug);
        } elseif ($auctioneer && !$auctioneer->isWhiteLabel()) {
            // User visited a different, non-white-label auctioneer — drop stickiness.
            session()->forget('white_label_slug');
        }

        View::share('whiteLabel', $this->context);

        return $next($request);
    }

    private function resolveAuctioneer(Request $request): ?Auctioneer
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        // 1. Direct auctioneer route parameter
        $auctioneer = $route->parameter('auctioneer');
        if ($auctioneer instanceof Auctioneer) {
            return $auctioneer;
        }

        // 2. Auction route parameter — load its auctioneer
        $auction = $route->parameter('auction');
        if ($auction instanceof \App\Models\Auction) {
            return $auction->auctioneer;
        }

        // 3. Lot route parameter — load auction's auctioneer
        $lot = $route->parameter('lot');
        if ($lot instanceof \App\Models\Lot) {
            return $lot->auction?->auctioneer;
        }

        // 4. Query string fallback for scoped browsing
        if ($request->filled('auctioneer')) {
            return Auctioneer::where('slug', $request->get('auctioneer'))->first();
        }

        return null;
    }
}
