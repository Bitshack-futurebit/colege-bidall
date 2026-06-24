{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/auctions') }}</loc>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/about') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ url('/how-it-works') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    @foreach($auctioneers as $auctioneer)
    <url>
        <loc>{{ url('/auctioneer/' . $auctioneer->slug) }}</loc>
        <lastmod>{{ $auctioneer->updated_at->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @foreach($auctions as $auction)
    <url>
        <loc>{{ url('/auctions/' . $auction->slug) }}</loc>
        <lastmod>{{ $auction->updated_at->toIso8601String() }}</lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
