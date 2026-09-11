<x-layouts::public :title="$update->titleForLocale()" :description="$update->excerptForLocale()">
    <article class="mx-auto max-w-5xl px-5 py-14 lg:px-8 lg:py-20">
        <a href="{{ route('updates.index') }}" class="pm-ghost-button">{{ __('pitmetric.updates.back') }}</a>

        <header class="mt-12 max-w-4xl">
            <div class="flex items-center gap-3">
                <span class="pm-status-dot"></span>
                <time class="font-mono text-xs uppercase tracking-[0.14em] text-zinc-500" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d F Y') }}</time>
            </div>
            <h1 class="mt-5 text-4xl font-black tracking-[-0.045em] text-white sm:text-5xl lg:text-6xl">{{ $update->titleForLocale() }}</h1>
            @if ($update->excerptForLocale() !== '')
                <p class="mt-6 max-w-3xl text-lg leading-8 text-zinc-400">{{ $update->excerptForLocale() }}</p>
            @endif
        </header>

        @if ($update->mediaSource())
            <div class="pm-media-frame mt-10">
                @if ($update->media_type === 'image')
                    <img src="{{ $update->mediaSource() }}" alt="{{ $update->mediaAltForLocale() }}" class="max-h-[72vh] w-full object-cover">
                @elseif ($update->media_type === 'video' && $update->videoEmbedUrl())
                    <div class="aspect-video">
                        <iframe src="{{ $update->videoEmbedUrl() }}" title="{{ $update->mediaAltForLocale() }}" class="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                    </div>
                @elseif ($update->media_type === 'video')
                    <video controls playsinline preload="metadata" class="max-h-[72vh] w-full bg-black" aria-label="{{ $update->mediaAltForLocale() }}">
                        <source src="{{ $update->mediaSource() }}">
                    </video>
                @endif
            </div>
        @endif

        @if ($update->contentForLocale() !== '')
            <div class="mt-12 max-w-3xl whitespace-pre-line text-base leading-8 text-zinc-300 sm:text-lg">{{ $update->contentForLocale() }}</div>
        @endif

        <footer class="mt-16 border-t border-white/10 pt-8">
            <a href="{{ route('updates.index') }}" class="pm-race-button">{{ __('pitmetric.updates.back_to_log') }}</a>
        </footer>
    </article>
</x-layouts::public>
