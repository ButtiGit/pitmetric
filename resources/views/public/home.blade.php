<x-layouts::public>
    <div class="pm-home-editorial">
        <section data-pm-home-hero class="pm-home-editorial-hero relative isolate overflow-hidden border-b border-white/10">
            <img data-pm-hero-image src="https://images.unsplash.com/photo-1656978766399-1e117a291918?auto=format&fit=crop&fm=jpg&q=88&w=2400" alt="{{ __('pitmetric.home.hero_image_alt') }}" class="absolute inset-0 -z-20 h-full w-full object-cover object-center">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(5,7,10,.46)_0%,rgba(5,7,10,.18)_36%,rgba(5,7,10,.72)_78%,#080a0d_100%)]"></div>
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(5,7,10,.56),transparent_30%,transparent_70%,rgba(5,7,10,.38))]"></div>

            <div class="mx-auto flex min-h-[76vh] max-w-7xl flex-col justify-between px-5 pb-7 pt-24 lg:px-8 lg:pb-9 lg:pt-28">
                <div class="flex items-start justify-between gap-6 border-b border-white/15 pb-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/65 sm:text-xs">
                    <span>{{ __('pitmetric.home.eyebrow') }}</span>
                    <span class="hidden text-right sm:block">PitMetric / 2026 / Cuneo</span>
                </div>

                <div data-pm-hero-copy class="mx-auto my-12 max-w-3xl text-center sm:my-16">
                    <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#ff625e]">PitMetric / Technical history</p>
                    <h1 class="pm-home-editorial-title mt-5 text-white">{{ __('pitmetric.home.principle_title') }}</h1>
                    <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-zinc-200 sm:text-lg">{{ __('pitmetric.home.intro') }}</p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-7 gap-y-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="pm-home-text-link">{{ __('pitmetric.home.open_dashboard') }}</a>
                        @else
                            <a href="{{ route('register') }}" class="pm-home-text-link">{{ __('pitmetric.home.discover') }}</a>
                            <a href="{{ route('login') }}" class="pm-home-text-link pm-home-text-link--muted">{{ __('pitmetric.nav.login') }}</a>
                        @endauth
                    </div>
                </div>

                <div class="grid gap-5 border-t border-white/15 pt-4 sm:grid-cols-[1fr_auto] sm:items-end">
                    <p class="max-w-xl text-xs leading-5 text-white/60 sm:text-sm sm:leading-6">{{ __('pitmetric.home.track_note') }}</p>
                    <a href="https://unsplash.com/photos/a-race-car-on-a-track-GCDa5RBWcAw" target="_blank" rel="noopener" class="text-[10px] text-white/35 transition hover:text-white/75">Edoardo Giudici Saraval / Unsplash</a>
                </div>
            </div>
        </section>

        <div data-pm-cursor-car class="pm-cursor-car" aria-hidden="true">
            <svg viewBox="0 0 80 40" role="presentation" focusable="false">
                <rect class="pm-cursor-car__wing" x="5" y="9" width="8" height="22" rx="2" />
                <rect class="pm-cursor-car__wing" x="66" y="7" width="5" height="26" rx="1.5" />
                <rect class="pm-cursor-car__tyre" x="19" y="3" width="12" height="7" rx="2" />
                <rect class="pm-cursor-car__tyre" x="19" y="30" width="12" height="7" rx="2" />
                <rect class="pm-cursor-car__tyre" x="54" y="2" width="13" height="8" rx="2" />
                <rect class="pm-cursor-car__tyre" x="54" y="30" width="13" height="8" rx="2" />
                <path class="pm-cursor-car__body" d="M12 20C17 14 23 12 31 11L42 6L58 8L67 14V26L58 32L42 34L31 29C23 28 17 26 12 20Z" />
                <path class="pm-cursor-car__cockpit" d="M35 14L44 10L52 13L55 20L52 27L44 30L35 26L31 20Z" />
                <path class="pm-cursor-car__highlight" d="M16 18H31L41 11L42 14L32 20H16Z" />
                <circle cx="45" cy="20" r="2.2" fill="#ff625e" />
            </svg>
        </div>

        <section class="pm-home-ledger mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[.72fr_1.28fr] lg:gap-20">
                <header class="lg:sticky lg:top-28 lg:self-start">
                    <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#ff625e]">01 / {{ __('pitmetric.home.track_note_label') }}</p>
                    <h2 class="pm-home-editorial-subtitle mt-5 max-w-md text-white">{{ __('pitmetric.home.roadmap_title') }}</h2>
                    <p class="mt-6 max-w-md text-sm leading-7 text-zinc-400">{{ __('pitmetric.home.track_note') }}</p>
                </header>

                <div class="border-t border-white/15">
                    @foreach ([
                        ['01', __('pitmetric.home.configuration'), __('pitmetric.home.configuration_copy')],
                        ['02', __('pitmetric.home.usage'), __('pitmetric.home.usage_copy')],
                        ['03', __('pitmetric.home.maintenance'), __('pitmetric.home.maintenance_copy')],
                        ['04', __('pitmetric.home.costs'), __('pitmetric.home.costs_copy')],
                    ] as [$number, $heading, $copy])
                        <article class="pm-home-ledger-row grid gap-4 border-b border-white/15 py-7 sm:grid-cols-[4.5rem_12rem_1fr] sm:items-start sm:gap-6 lg:py-9">
                            <span class="pm-home-ledger-number">{{ $number }}</span>
                            <h3 class="text-base font-semibold text-white sm:pt-1">{{ $heading }}</h3>
                            <p class="max-w-xl text-sm leading-6 text-zinc-400 sm:pt-1">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-white/10 bg-[#0c0f13]">
            <div class="mx-auto grid max-w-7xl lg:grid-cols-12">
                <figure class="relative min-h-[420px] overflow-hidden border-b border-white/10 lg:col-span-7 lg:min-h-[620px] lg:border-b-0 lg:border-r">
                    <img src="https://images.unsplash.com/photo-1765202661219-cec5ad98f324?auto=format&fit=crop&fm=jpg&q=88&w=2000" alt="{{ __('pitmetric.home.garage_image_alt') }}" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/5 to-transparent"></div>
                    <figcaption class="absolute inset-x-5 bottom-5 flex items-end justify-between gap-5 text-[10px] uppercase tracking-[0.14em] text-white/50 sm:inset-x-7 sm:bottom-7">
                        <span>Garage / working history</span>
                        <a href="https://unsplash.com/photos/race-car-mechanics-working-in-a-garage-oSQGlIBvw3s" target="_blank" rel="noopener" class="transition hover:text-white/85">Edgar / Unsplash</a>
                    </figcaption>
                </figure>

                <div class="flex flex-col justify-between px-6 py-12 sm:px-9 lg:col-span-5 lg:px-12 lg:py-16">
                    <div>
                        <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#ff625e]">02 / {{ __('pitmetric.home.principle_label') }}</p>
                        <blockquote class="pm-home-editorial-quote mt-8 text-white">{{ __('pitmetric.home.principle_copy') }}</blockquote>
                    </div>

                    <dl class="mt-14 border-t border-white/15 font-mono text-xs">
                        <div class="grid grid-cols-[7rem_1fr] gap-4 border-b border-white/10 py-4">
                            <dt class="text-zinc-600">BUILD</dt>
                            <dd class="text-zinc-300">Race Build V3 · Engine #02 · Chain #04 · Tyres #08</dd>
                        </div>
                        <div class="grid grid-cols-[7rem_1fr] gap-4 border-b border-white/10 py-4">
                            <dt class="text-zinc-600">SESSION</dt>
                            <dd class="text-white">1,250 m × 40 laps = 50 km</dd>
                        </div>
                        <div class="grid grid-cols-[7rem_1fr] gap-4 py-4">
                            <dt class="text-zinc-600">UPDATE</dt>
                            <dd class="text-zinc-400">{{ __('pitmetric.home.propagation') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[.55fr_1.45fr] lg:gap-16">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#ff625e]">03 / {{ __('pitmetric.home.roadmap') }}</p>
                    <a href="{{ route('updates.index') }}" class="pm-home-text-link mt-7">{{ __('pitmetric.home.follow_updates') }}</a>
                </div>

                <div class="relative border-l border-white/15 pl-7 sm:pl-10">
                    @foreach ([
                        ['A', __('pitmetric.home.roadmap_1_title'), __('pitmetric.home.roadmap_1_copy')],
                        ['B', __('pitmetric.home.roadmap_2_title'), __('pitmetric.home.roadmap_2_copy')],
                        ['C', __('pitmetric.home.roadmap_3_title'), __('pitmetric.home.roadmap_3_copy')],
                    ] as [$number, $heading, $copy])
                        <article class="pm-home-roadmap-item relative pb-12 last:pb-0 sm:grid sm:grid-cols-[4rem_15rem_1fr] sm:gap-6">
                            <span class="pm-home-roadmap-marker">{{ $number }}</span>
                            <h2 class="pm-home-editorial-small-title text-white">{{ $heading }}</h2>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-zinc-400 sm:mt-1">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-[#0d1014]">
            <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-[1.2fr_.8fr] lg:items-end">
                    <div>
                        <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#ff625e]">04 / {{ __('partners.eyebrow') }}</p>
                        <h2 class="pm-home-editorial-subtitle mt-5 max-w-3xl text-white">{{ __('partners.title') }}</h2>
                        <p class="mt-6 max-w-2xl text-sm leading-7 text-zinc-400">{{ __('partners.copy') }}</p>
                    </div>

                    <div class="lg:border-l lg:border-white/15 lg:pl-10">
                        <ul class="divide-y divide-white/10 border-y border-white/10 text-sm text-zinc-300">
                            @foreach ([__('partners.benefit_1'), __('partners.benefit_2'), __('partners.benefit_3')] as $benefit)
                                <li class="py-3 leading-6">{{ $benefit }}</li>
                            @endforeach
                        </ul>
                        <a href="mailto:simonebuttice05@gmail.com?subject=PitMetric%20Partnership" class="pm-home-text-link mt-7">{{ __('partners.cta') }}</a>
                        <p class="mt-4 text-xs leading-5 text-zinc-600">{{ __('partners.note') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts::public>
