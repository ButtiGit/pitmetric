{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ([
        route('home'),
        route('about'),
        route('app'),
        route('updates.index'),
    ] as $url)
        <url>
            <loc>{{ $url }}</loc>
        </url>
    @endforeach

    @foreach ($updates as $update)
        <url>
            <loc>{{ route('updates.show', $update) }}</loc>
            @if ($update->updated_at)
                <lastmod>{{ $update->updated_at->toAtomString() }}</lastmod>
            @endif
        </url>
    @endforeach
</urlset>
