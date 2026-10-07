@php
    $hasItalianTranslation = filled($update->title_it)
        && (blank($update->excerpt) || filled($update->excerpt_it))
        && (blank($update->content) || filled($update->content_it));

    $articleLanguage = app()->getLocale() === 'it' && $hasItalianTranslation ? 'it' : 'en';
    $articleTitle = $update->titleForLocale($articleLanguage);
    $articleExcerpt = $update->excerptForLocale($articleLanguage);
    $articleContent = $update->contentForLocale($articleLanguage);
    $articleMediaAlt = $update->mediaAltForLocale($articleLanguage);
@endphp

<x-layouts::public
    :title="$articleTitle"
    :description="$articleExcerpt"
    :image="$update->media_type === 'image' ? $update->mediaSource() : null"
    :image-alt="$articleMediaAlt"
    :content-locale="$articleLanguage"
    :has-italian-alternate="$hasItalianTranslation"
    og-type="article"
>
    @php
        $articleImage = $update->media_type === 'image' ? $update->mediaSource() : null;

        if (is_string($articleImage) && str_starts_with($articleImage, '/')) {
            $articleImage = url($articleImage);
        }

        $articleLocale = app()->getLocale() === 'it' && ! $hasItalianTranslation
            ? 'en'
            : app()->getLocale();

        $articleUrl = route('localized.updates.show', [
            'locale' => $articleLocale,
            'update' => $update,
        ]);

        $articleStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => $articleUrl.'#article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl,
            ],
            'isPartOf' => [
                '@id' => url('/').'#website',
            ],
            'headline' => $articleTitle,
            'description' => $articleExcerpt,
            'datePublished' => $update->published_at?->toAtomString(),
            'dateModified' => $update->updated_at?->toAtomString(),
            'inLanguage' => $articleLanguage,
            'author' => [
                '@type' => 'Person',
                'name' => 'Simone Butticè',
                'url' => route('localized.about', ['locale' => app()->getLocale()]),
            ],
        ];

        if ($articleImage !== null) {
            $articleStructuredData['image'] = [$articleImage];
        }
    @endphp

    <script type="application/ld+json">{!! json_encode($articleStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <article class="pm-editorial-page pm-update-article">
        <div class="mx-auto max-w-7xl px-5 pt-10 lg:px-8 lg:pt-14">
            <a href="{{ route('localized.updates.index', ['locale' => app()->getLocale()]) }}" class="pm-home-text-link pm-home-text-link--muted">{{ __('pitmetric.updates.back') }}</a>
        </div>

        <header class="mx-auto max-w-7xl px-5 pb-10 pt-12 lg:px-8 lg:pb-14 lg:pt-16">
            <div class="grid gap-8 border-y border-white/15 py-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-16 lg:py-10">
                <div>
                    <p class="pm-editorial-kicker">PitMetric / Devlog</p>
                    <time class="pm-editorial-meta mt-4 block" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->copy()->locale($articleLanguage)->translatedFormat('d F Y') }}</time>
                </div>
                <div class="max-w-4xl">
                    <h1 class="pm-editorial-display text-white">{{ $articleTitle }}</h1>
                    @if ($articleExcerpt !== '')
                        <p class="pm-editorial-copy mt-6 max-w-3xl text-base leading-8 text-zinc-300 sm:text-lg">{{ $articleExcerpt }}</p>
                    @endif
                </div>
            </div>
        </header>

        @if ($update->mediaSource())
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="pm-media-frame overflow-hidden border border-white/10 bg-[#0d1014]">
                    @if ($update->media_type === 'image')
                        <img src="{{ $update->mediaSource() }}" alt="{{ $articleMediaAlt }}" class="max-h-[78vh] w-full object-contain">
                    @elseif ($update->media_type === 'video' && $update->videoEmbedUrl())
                        <div class="aspect-video">
                            <iframe src="{{ $update->videoEmbedUrl() }}" title="{{ $articleMediaAlt }}" class="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @elseif ($update->media_type === 'video')
                        <video controls playsinline preload="metadata" class="max-h-[78vh] w-full bg-black" aria-label="{{ $articleMediaAlt }}">
                            <source src="{{ $update->mediaSource() }}">
                        </video>
                    @endif
                </div>
            </div>
        @endif

        @if ($articleContent !== '')
            <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 lg:grid-cols-[12rem_minmax(0,46rem)] lg:gap-16 lg:px-8 lg:py-16">
                <div class="hidden lg:block">
                    <div class="h-px w-full bg-white/10"></div>
                    <p class="pm-editorial-meta mt-4">Development note</p>
                </div>
                <div class="pm-editorial-copy whitespace-pre-line text-base leading-8 text-zinc-300 sm:text-lg">{{ $articleContent }}</div>
            </div>
        @endif

        <footer class="mx-auto max-w-7xl px-5 pb-16 lg:px-8 lg:pb-20">
            <div class="border-t border-white/15 pt-7">
                <a href="{{ route('localized.updates.index', ['locale' => app()->getLocale()]) }}" class="pm-home-text-link">{{ __('pitmetric.updates.back_to_log') }}</a>
            </div>
        </footer>
    </article>
</x-layouts::public>
