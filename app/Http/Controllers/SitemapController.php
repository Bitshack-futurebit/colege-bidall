<?php

namespace App\Http\Controllers;

use App\Models\Auctioneer;
use App\Models\Auction;
use App\Models\Lot;
use App\Models\CommunityRegion;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $auctioneers = Auctioneer::where('is_activated', true)
            ->select('slug', 'updated_at')
            ->get();

        $auctions = Auction::whereIn('status', ['live', 'upcoming'])
            ->orWhere(fn ($q) => $q->where('status', 'ended')->where('updated_at', '>=', now()->subDays(60)))
            ->select('id', 'slug', 'status', 'updated_at')
            ->get();

        $lots = Lot::whereHas('auction', fn ($q) => $q->whereIn('status', ['live', 'upcoming']))
            ->whereIn('status', ['active', 'pending'])
            ->select('id', 'lot_number', 'updated_at')
            ->get();

        $communities = CommunityRegion::select('slug', 'updated_at')->orderBy('name')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Static pages
        $xml .= $this->urlEntry(url('/'), 'daily', '1.0');
        $xml .= $this->urlEntry(url('/auctions'), 'hourly', '0.9');
        $xml .= $this->urlEntry(url('/auctioneers'), 'daily', '0.7');
        $xml .= $this->urlEntry(url('/communities'), 'daily', '0.7');
        $xml .= $this->urlEntry(url('/about'), 'monthly', '0.5');
        $xml .= $this->urlEntry(url('/how-it-works'), 'monthly', '0.5');

        // Auctioneers
        foreach ($auctioneers as $auctioneer) {
            $xml .= $this->urlEntry(
                url('/auctioneer/' . $auctioneer->slug),
                'weekly',
                '0.7',
                $auctioneer->updated_at->toW3cString()
            );
        }

        // Auctions
        foreach ($auctions as $auction) {
            $priority = $auction->status === 'live' ? '0.9' : ($auction->status === 'upcoming' ? '0.8' : '0.6');
            $freq = $auction->status === 'live' ? 'hourly' : ($auction->status === 'upcoming' ? 'daily' : 'weekly');
            $xml .= $this->urlEntry(
                url('/auctions/' . $auction->slug),
                $freq,
                $priority,
                $auction->updated_at->toW3cString()
            );
        }

        // Community regions
        foreach ($communities as $community) {
            $xml .= $this->urlEntry(
                url('/community/region/' . $community->slug),
                'weekly',
                '0.7',
                $community->updated_at->toW3cString()
            );
        }

        // Lots
        foreach ($lots as $lot) {
            $xml .= $this->urlEntry(
                url('/lots/' . $lot->id),
                'daily',
                '0.6',
                $lot->updated_at->toW3cString()
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function urlEntry(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        $entry = "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        if ($lastmod) {
            $entry .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $entry .= "    <changefreq>{$changefreq}</changefreq>\n";
        $entry .= "    <priority>{$priority}</priority>\n";
        $entry .= "  </url>\n";
        return $entry;
    }
}
