{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @foreach ([
        'localized.home',
        'localized.about',
        'localized.app',
        'localized.updates.index',
    ] as $routeName)
        @foreach (['en', 'it'] as $locale)
            <url>
                <loc>{{ route($routeName, ['locale' => $locale]) }}</loc>
                <xhtml:link rel="alternate" hreflang="en" href="{{ route($routeName, ['locale' => 'en']) }}" />
                <xhtml:link rel="alternate" hreflang="it" href="{{ route($routeName, ['locale' => 'it']) }}" />
                <xhtml:link rel="alternate" hreflang="x-default" href="{{ route($routeName, ['locale' => 'en']) }}" />
            </url>
        @endforeach
    @endforeach

    @foreach ($updates as $update)
        @php
            $hasItalianTranslation = filled($update->title_it)
                && (blank($update->excerpt) || filled($update->excerpt_it))
                && (blank($update->content) || filled($update->content_it));

            $availableLocales = $hasItalianTranslation ? ['en', 'it'] : ['en'];
        @endphp

        @foreach ($availableLocales as $locale)
            <url>
                <loc>{{ route('localized.updates.show', ['locale' => $locale, 'update' => $update]) }}</loc>
                <xhtml:link rel="alternate" hreflang="en" href="{{ route('localized.updates.show', ['locale' => 'en', 'update' => $update]) }}" />
                @if ($hasItalianTranslation)
                    <xhtml:link rel="alternate" hreflang="it" href="{{ route('localized.updates.show', ['locale' => 'it', 'update' => $update]) }}" />
                @endif
                <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('localized.updates.show', ['locale' => 'en', 'update' => $update]) }}" />
                @if ($update->updated_at)
                    <lastmod>{{ $update->updated_at->toAtomString() }}</lastmod>
                @endif
            </url>
        @endforeach
    @endforeach
</urlset>
