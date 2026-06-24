<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Auctioneer;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show homepage with map and live events.
     */
    public function index()
    {
        // Cache auction lists 60s — homepage is hammered by crawlers + guests
        $liveAuctions = \Cache::remember('homepage_live_auctions', 60, function () {
            return Auction::live()
                ->whereHas('auctioneer.user')
                ->with('auctioneer.user', 'communityRegion')
                ->withCount('lots')
                ->orderBy('start_time')
                ->limit(6)
                ->get();
        });

        // Upcoming auctions for the homepage. Community drafts are the listing-window
        // phase and ARE upcoming from a bidder's POV — match the convention used on
        // the public auctions index and auctioneer profile.
        $upcomingFilter = function ($q) {
            $q->where('status', 'upcoming')
              ->orWhere(fn ($q2) => $q2->where('status', 'draft')->where('is_community', true));
        };

        $upcomingAuctions = \Cache::remember('homepage_upcoming_auctions_v2', 60, function () use ($upcomingFilter) {
            return Auction::where($upcomingFilter)
                ->whereHas('auctioneer.user')
                ->with('auctioneer.user', 'communityRegion')
                ->withCount('lots')
                ->orderBy('start_time')
                ->limit(6)
                ->get();
        });

        $stats = \Cache::remember('homepage_stats_v2', 300, function () use ($upcomingFilter) {
            return [
                'total_auctioneers' => Auctioneer::activated()->count(),
                'live_auctions' => Auction::live()->count(),
                'upcoming_auctions' => Auction::where($upcomingFilter)->count(),
            ];
        });

        return view('home', compact('liveAuctions', 'upcomingAuctions', 'stats'));
    }

    /**
     * Show about page.
     */
    public function about()
    {
        return view('pages.about');
    }

    /**
     * Show how it works page.
     */
    public function howItWorks()
    {
        $pricing = [
            'activation_fee' => formatPrice('platform.pricing.activation_fee'),
            'tier_basic' => formatPrice('platform.pricing.tier_basic.price'),
            'tier_pro' => formatPrice('platform.pricing.tier_pro.price'),
            'tier_premium' => formatPrice('platform.pricing.tier_premium.price'),
            'platform_percentage' => config('platform.pricing.platform_percentage'),
            'platform_lot_fee' => formatPrice('platform.pricing.platform_lot_fee'),
        ];

        return view('pages.how-it-works', compact('pricing'));
    }

    /**
     * Show terms of service page.
     */
    public function terms()
    {
        $termsVersions = \App\Models\TermsVersion::published()->get();

        return view('pages.terms', compact('termsVersions'));
    }

    /**
     * Show privacy policy page.
     */
    public function privacy()
    {
        return view('pages.privacy');
    }

    /**
     * Show contact/support page.
     */
    public function contact()
    {
        return view('pages.contact');
    }
}
