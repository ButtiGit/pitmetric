<x-layouts::public :title="__('pitmetric.updates.title')" :description="__('pitmetric.updates.description')">
    @php
        $updateCollection = $updates->getCollection();
        $featured = $updateCollection->first();
    @endphp

    <section class="relative overflow-hidden border-b border-white/10 bg-[#080a0d]">
        <div class="pointer-events-none absolute inset-0 opacity-40 [background-image:linear-gradient(120deg,transparent_0%,transparent_48%,rgba(225,6,0,.18)_48%,rgba(225,6,0,.18)_49%,transparent_49%,transparent_100%)] [background-size:90px_90px]"></div>
        <div class="relative mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
            <p class="font-mono text-xs font-semibold uppercase tracking-[0.22em] text-[#ff4b47]">{{ __('pitmetric.updates.label') }}</p>
            <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_.75fr] lg:items-end">
                <h1 class="text-5xl font-black tracking-[-0.05em] text-white sm:text-6xl">{{ __('pitmetric.updates.title') }}</h1>
                <p class="max-w-xl leading-7 text-zinc-400">{{ __('pitmetric.updates.intro') }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-20">
        @if ($featured)
            <article class="pm-race-card group grid overflow-hidden lg:grid-cols-[1.1fr_.9fr]">
                <div class="flex min-h-[360px] flex-col p-7 sm:p-9 lg:p-12">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="pm-status-dot"></span>
                        <time class="font-mono text-xs uppercase tracking-[0.16em] text-zinc-500" datetime="{{ $featured->published_at?->toDateString() }}">{{ $featured->published_at?->translatedFormat('d M Y') }}</time>
                        <span class="text-xs uppercase tracking-[0.14em] text-zinc-600">{{ __('pitmetric.updates.latest') }}</span>
                    </div>
                    <h2 class="mt-7 max-w-3xl text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl lg:text-5xl">
                        <a href="{{ route('updates.show', $featured) }}" class="transition group-hover:text-[#ff625e]">{{ $featured->titleForLocale() }}</a>
                    </h2>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-zinc-400">{{ $featured->excerptForLocale() }}</p>
                    <div class="mt-auto pt-9">
                        <a href="{{ route('updates.show', $featured) }}" class="pm-race-button">{{ __('pitmetric.updates.read') }}</a>
                    </div>
                </div>

                <div class="relative min-h-[280px] overflow-hidden border-t border-white/10 bg-[#101319] lg:border-l lg:border-t-0">
                    @if ($featured->mediaSource() && $featured->media_type === 'image')
                        <img src="{{ $featured->mediaSource() }}" alt="{{ $featured->mediaAltForLocale() }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.025]">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10"></div>
                    @elseif ($featured->media_type === 'video')
                        <div class="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_center,rgba(225,6,0,.16),transparent_40%),linear-gradient(145deg,#12161d,#090b0f)]">
                            <div class="pm-play-mark" aria-hidden="true"><span></span></div>
                        </div>
                    @else
                        <div class="absolute inset-0 pm-speed-grid"></div>
                        <div class="absolute bottom-8 left-8 right-8 border-l-2 border-[#E10600] pl-5">
                            <p class="font-mono text-xs uppercase tracking-[0.18em] text-zinc-500">PitMetric / Devlog</p>
                            <p class="mt-2 text-lg font-bold text-white">{{ __('pitmetric.updates.text_only') }}</p>
                        </div>
                    @endif
                </div>
            </article>
        @endif

        @if ($updateCollection->count() > 1)
            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($updateCollection->slice(1) as $update)
                    <article class="pm-race-card group flex min-h-[340px] flex-col overflow-hidden">
                        @if ($update->mediaSource() && $update->media_type === 'image')
                            <a href="{{ route('updates.show', $update) }}" class="block h-40 overflow-hidden border-b border-white/10">
                                <img src="{{ $update->mediaSource() }}" alt="{{ $update->mediaAltForLocale() }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]">
                            </a>
                        @elseif ($update->media_type === 'video')
                            <a href="{{ route('updates.show', $update) }}" class="relative grid h-40 place-items-center overflow-hidden border-b border-white/10 bg-[#0f1319]">
                                <div class="absolute inset-0 pm-speed-grid opacity-40"></div>
                                <div class="pm-play-mark relative z-10 scale-75" aria-hidden="true"><span></span></div>
                            </a>
                        @endif

                        <div class="flex flex-1 flex-col p-7">
                            <div class="flex items-center justify-between gap-4">
                                <time class="font-mono text-xs uppercase tracking-[0.14em] text-zinc-500" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d M Y') }}</time>
                                <span class="pm-status-dot"></span>
                            </div>
                            <h2 class="mt-6 text-2xl font-bold tracking-[-0.025em] text-white">
                                <a href="{{ route('updates.show', $update) }}" class="transition group-hover:text-[#ff625e]">{{ $update->titleForLocale() }}</a>
                            </h2>
                            <p class="mt-4 text-sm leading-6 text-zinc-400">{{ $update->excerptForLocale() }}</p>
                            <a href="{{ route('updates.show', $update) }}" class="mt-auto pt-8 text-sm font-bold text-white underline decoration-[#E10600] decoration-2 underline-offset-8 transition hover:text-[#ff625e]">{{ __('pitmetric.updates.read') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif (! $featured)
            <div class="pm-race-card p-14 text-center text-zinc-400">{{ __('pitmetric.updates.empty') }}</div>
        @endif

        @if ($updates->hasPages())
            <div class="mt-10">{{ $updates->links() }}</div>
        @endif
    </section>
</x-layouts::public>
