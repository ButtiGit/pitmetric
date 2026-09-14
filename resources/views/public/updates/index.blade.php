<x-layouts::public :title="__('pitmetric.updates.title')" :description="__('pitmetric.updates.description')">
    @php
        $updateCollection = $updates->getCollection();
        $featured = $updateCollection->first();
    @endphp

    <div class="pm-editorial-page pm-updates-editorial">
        <section class="pm-editorial-hero border-b border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-24">
                <p class="pm-editorial-kicker">{{ __('pitmetric.updates.label') }}</p>
                <div class="mt-6 grid gap-8 border-t border-white/15 pt-7 lg:grid-cols-[.9fr_1.1fr] lg:items-end lg:gap-20">
                    <h1 class="pm-editorial-display text-white">{{ __('pitmetric.updates.title') }}</h1>
                    <p class="pm-editorial-copy max-w-xl text-base leading-7 text-zinc-300 lg:justify-self-end">{{ __('pitmetric.updates.intro') }}</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-20">
            @if ($featured)
                <article class="pm-journal-feature border-y border-white/15">
                    <div class="grid lg:grid-cols-[1.08fr_.92fr]">
                        <a href="{{ route('updates.show', $featured) }}" class="relative min-h-[300px] overflow-hidden bg-[#0d1014] lg:min-h-[520px] lg:border-r lg:border-white/10">
                            @if ($featured->mediaSource() && $featured->media_type === 'image')
                                <img src="{{ $featured->mediaSource() }}" alt="{{ $featured->mediaAltForLocale() }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 hover:scale-[1.018]">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-black/15"></div>
                            @elseif ($featured->media_type === 'video')
                                <div class="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_center,rgba(225,6,0,.16),transparent_42%),linear-gradient(145deg,#12161d,#090b0f)]">
                                    <div class="pm-play-mark" aria-hidden="true"><span></span></div>
                                </div>
                            @else
                                <div class="absolute inset-0 pm-speed-grid opacity-55"></div>
                                <div class="absolute inset-x-7 bottom-7 border-l-2 border-[#E10600] pl-5">
                                    <p class="pm-editorial-meta">PitMetric / Devlog</p>
                                    <p class="mt-2 text-lg font-semibold text-white">{{ __('pitmetric.updates.text_only') }}</p>
                                </div>
                            @endif
                        </a>

                        <div class="flex min-h-[360px] flex-col px-0 py-8 sm:px-8 lg:min-h-[520px] lg:px-10 lg:py-10">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-4">
                                <time class="pm-editorial-meta" datetime="{{ $featured->published_at?->toDateString() }}">{{ $featured->published_at?->translatedFormat('d M Y') }}</time>
                                <span class="pm-editorial-meta text-[#ff625e]">{{ __('pitmetric.updates.latest') }}</span>
                            </div>

                            <h2 class="pm-editorial-section-title mt-8 text-white">
                                <a href="{{ route('updates.show', $featured) }}" class="transition hover:text-[#ff625e]">{{ $featured->titleForLocale() }}</a>
                            </h2>
                            <p class="pm-editorial-copy mt-6 text-base leading-7 text-zinc-300">{{ $featured->excerptForLocale() }}</p>

                            <div class="mt-auto pt-10">
                                <a href="{{ route('updates.show', $featured) }}" class="pm-home-text-link">{{ __('pitmetric.updates.read') }}</a>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

            @if ($updateCollection->count() > 1)
                <div class="mt-16">
                    <div class="flex items-end justify-between gap-5 border-b border-white/15 pb-4">
                        <p class="pm-editorial-kicker">Archive</p>
                        <span class="pm-editorial-meta">PitMetric / Devlog</span>
                    </div>

                    @foreach ($updateCollection->slice(1) as $index => $update)
                        <article class="pm-journal-row grid gap-5 border-b border-white/10 py-7 md:grid-cols-[4rem_minmax(0,1fr)_10rem] md:items-center lg:grid-cols-[5rem_minmax(0,1fr)_14rem] lg:py-9">
                            <div>
                                <span class="font-mono text-[10px] tracking-[0.18em] text-[#E10600]">{{ str_pad((string) ($index + 2), 2, '0', STR_PAD_LEFT) }}</span>
                                <time class="mt-2 block font-mono text-[10px] uppercase tracking-[0.1em] text-zinc-600" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d M Y') }}</time>
                            </div>

                            <div class="md:pr-8">
                                <h2 class="text-xl font-semibold tracking-[-0.02em] text-white sm:text-2xl">
                                    <a href="{{ route('updates.show', $update) }}" class="transition hover:text-[#ff625e]">{{ $update->titleForLocale() }}</a>
                                </h2>
                                <p class="pm-editorial-copy mt-3 max-w-2xl text-sm leading-6 text-zinc-400">{{ $update->excerptForLocale() }}</p>
                                <a href="{{ route('updates.show', $update) }}" class="pm-home-text-link mt-4">{{ __('pitmetric.updates.read') }}</a>
                            </div>

                            <a href="{{ route('updates.show', $update) }}" class="relative hidden aspect-[4/3] overflow-hidden border border-white/10 bg-[#0d1014] md:block">
                                @if ($update->mediaSource() && $update->media_type === 'image')
                                    <img src="{{ $update->mediaSource() }}" alt="{{ $update->mediaAltForLocale() }}" class="h-full w-full object-cover transition duration-500 hover:scale-[1.025]">
                                @elseif ($update->media_type === 'video')
                                    <div class="absolute inset-0 grid place-items-center pm-speed-grid"><div class="pm-play-mark scale-75" aria-hidden="true"><span></span></div></div>
                                @else
                                    <div class="absolute inset-0 pm-speed-grid opacity-40"></div>
                                @endif
                            </a>
                        </article>
                    @endforeach
                </div>
            @elseif (! $featured)
                <div class="border-y border-white/10 py-16 text-center text-zinc-400">{{ __('pitmetric.updates.empty') }}</div>
            @endif

            @if ($updates->hasPages())
                <div class="mt-10">{{ $updates->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts::public>
