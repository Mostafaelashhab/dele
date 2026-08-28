<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
        {{-- Both locales serve the same URL, so each page declares the pair
             rather than duplicating itself into two competing entries. --}}
        <xhtml:link rel="alternate" hreflang="ar-EG" href="{{ $url['loc'] }}"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $url['loc'] }}"/>
    </url>
@endforeach
</urlset>
