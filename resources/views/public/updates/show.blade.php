<x-layouts::public :title="$update->titleForLocale()" :description="$update->excerptForLocale()">
    <article class="pm-editorial-page pm-update-article">
        <div class="mx-auto max-w-7xl px-5 pt-10 lg:px-8 lg:pt-14">
            <a href="{{ route('updates.index') }}" class="pm-home-text-link pm-home-text-link--muted">{{ __('pitmetric.updates.back') }}</a>
        </div>

        <header class="mx-auto max-w-7xl px-5 pb-10 pt-12 lg:px-8 lg:pb-14 lg:pt-16">
            <div class="grid gap-8 border-y border-white/15 py-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-16 lg:py-10">
                <div>
                    <p class="pm-editorial-kicker">PitMetric / Devlog</p>
                    <time class="pm-editorial-meta mt-4 block" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d F Y') }}</time>
                </div>
                <div class="max-w-4xl">
                    <h1 class="pm-editorial-display text-white">{{ $update->titleForLocale() }}</h1>
                    @if ($update->excerptForLocale() !== '')
                        <p class="pm-editorial-copy mt-6 max-w-3xl text-base leading-8 text-zinc-300 sm:text-lg">{{ $update->excerptForLocale() }}</p>
                    @endif
                </div>
            </div>
        </header>

        @if ($update->mediaSource())
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="pm-media-frame overflow-hidden border border-white/10 bg-[#0d1014]">
                    @if ($update->media_type === 'image')
                        <img src="{{ $update->mediaSource() }}" alt="{{ $update->mediaAltForLocale() }}" class="max-h-[78vh] w-full object-contain">
                    @elseif ($update->media_type === 'video' && $update->videoEmbedUrl())
                        <div class="aspect-video">
                            <iframe src="{{ $update->videoEmbedUrl() }}" title="{{ $update->mediaAltForLocale() }}" class="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @elseif ($update->media_type === 'video')
                        <video controls playsinline preload="metadata" class="max-h-[78vh] w-full bg-black" aria-label="{{ $update->mediaAltForLocale() }}">
                            <source src="{{ $update->mediaSource() }}">
                        </video>
                    @endif
                </div>
            </div>
        @endif

        @if ($update->contentForLocale() !== '')
            <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 lg:grid-cols-[12rem_minmax(0,46rem)] lg:gap-16 lg:px-8 lg:py-16">
                <div class="hidden lg:block">
                    <div class="h-px w-full bg-white/10"></div>
                    <p class="pm-editorial-meta mt-4">Development note</p>
                </div>
                <div class="pm-editorial-copy whitespace-pre-line text-base leading-8 text-zinc-300 sm:text-lg">{{ $update->contentForLocale() }}</div>
            </div>
        @endif

        <footer class="mx-auto max-w-7xl px-5 pb-16 lg:px-8 lg:pb-20">
            <div class="border-t border-white/15 pt-7">
                <a href="{{ route('updates.index') }}" class="pm-home-text-link">{{ __('pitmetric.updates.back_to_log') }}</a>
            </div>
        </footer>
    </article>
</x-layouts::public>
