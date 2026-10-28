{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $versions)
@foreach ($versions as $locale => $entry)
    <url>
        <loc>{{ $entry['url'] }}</loc>
@if ($entry['updated_at'])
        <lastmod>{{ $entry['updated_at'] }}</lastmod>
@endif
@foreach ($versions as $altLocale => $alt)
        <xhtml:link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $alt['url'] }}"/>
@endforeach
    </url>
@endforeach
@endforeach
</urlset>
