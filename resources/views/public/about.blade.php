<x-layouts::public :title="__('pitmetric.about.title')" :description="__('pitmetric.about.description')">
    @php
        $isItalian = app()->getLocale() === 'it';
    @endphp

    <div class="pm-editorial-page pm-about-editorial">
        <section class="pm-editorial-hero border-b border-white/10">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:py-20 lg:grid-cols-[1.1fr_.72fr] lg:items-center lg:gap-20 lg:px-8 lg:py-24">
                <div>
                    <p class="pm-editorial-kicker">{{ __('pitmetric.about.creator') }}</p>
                    <div class="mt-5 max-w-3xl border-t border-white/15 pt-7">
                        <h1 class="pm-editorial-display text-white">Simone Butticè</h1>
                        <p class="mt-4 text-base font-semibold uppercase tracking-[0.08em] text-zinc-300 sm:text-lg">Junior Full-Stack Web Developer</p>
                        <p class="pm-editorial-copy mt-7 max-w-2xl text-base leading-8 text-zinc-300 sm:text-lg">{{ __('pitmetric.about.role') }}</p>
                    </div>

                    <div class="mt-9 grid max-w-2xl border-y border-white/10 sm:grid-cols-2">
                        <a href="mailto:simonebuttice05@gmail.com" class="group py-5 pr-5 sm:border-r sm:border-white/10">
                            <span class="pm-editorial-meta">Email</span>
                            <span class="mt-2 block break-all text-sm font-semibold text-white transition group-hover:text-[#ff625e]">simonebuttice05@gmail.com</span>
                        </a>
                        <div class="border-t border-white/10 py-5 sm:border-t-0 sm:pl-5">
                            <span class="pm-editorial-meta">{{ $isItalian ? 'Base' : 'Based in' }}</span>
                            <span class="mt-2 block text-sm font-semibold text-white">Cuneo, Piemonte, Italia</span>
                        </div>
                    </div>
                </div>

                <figure class="pm-editorial-portrait mx-auto w-full max-w-[390px] lg:mx-0 lg:justify-self-end">
                    <div class="relative overflow-hidden border border-white/12 bg-[#0d1014]">
                        <div class="absolute left-0 top-0 z-10 h-[3px] w-24 bg-[#E10600]"></div>
                        <img src="{{ asset('media/simone-buttice-profile-original.jpeg') }}" width="938" height="1061" alt="{{ $isItalian ? 'Ritratto di Simone Butticè' : 'Portrait of Simone Butticè' }}" class="aspect-[4/5] w-full object-cover object-[50%_24%]" loading="eager" fetchpriority="high" decoding="async">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/95 via-black/58 to-transparent px-5 pb-5 pt-20">
                            <p class="pm-editorial-meta text-[#ff625e]">PitMetric / Creator</p>
                            <p class="mt-2 text-lg font-semibold text-white">Simone Butticè</p>
                        </div>
                    </div>
                </figure>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-24">
            <div class="grid gap-14 lg:grid-cols-[.72fr_1.28fr] lg:gap-20">
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <p class="pm-editorial-kicker">{{ __('pitmetric.about.stack_label') }}</p>
                    <div class="mt-6 border-t border-white/15">
                        @foreach ([
                            [__('pitmetric.about.backend'), 'PHP · REST APIs · JSON · Token auth · Sessions'],
                            [__('pitmetric.about.frontend'), 'HTML5 · CSS3 · JavaScript · Bootstrap · AJAX · Fetch API · Responsive UI'],
                            [__('pitmetric.about.database'), 'MySQL · SQL · Relational modelling · JOINs · Subqueries · Query optimization'],
                            [__('pitmetric.about.mobile_tools'), 'Laravel · Livewire · Ionic · Git · phpMyAdmin · Debugging · Legacy systems'],
                        ] as [$heading, $copy])
                            <div class="border-b border-white/10 py-5">
                                <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-white">{{ $heading }}</h2>
                                <p class="pm-editorial-copy mt-2 text-sm leading-6 text-zinc-400">{{ $copy }}</p>
                            </div>
                        @endforeach
                    </div>
                </aside>

                <div>
                    <section>
                        <div class="flex items-baseline justify-between gap-5 border-b border-white/15 pb-4">
                            <p class="pm-editorial-kicker">{{ __('pitmetric.about.experience') }}</p>
                            <span class="pm-editorial-meta">{{ __('pitmetric.about.present') }}</span>
                        </div>
                        <h2 class="pm-editorial-section-title mt-7 text-white">Junior Software Developer · Edisoft</h2>
                        <p class="pm-editorial-copy mt-5 leading-7 text-zinc-300">{{ __('pitmetric.about.experience_copy') }}</p>

                        <div class="mt-8 border-t border-white/10">
                            @foreach ([
                                __('pitmetric.about.exp_1'),
                                __('pitmetric.about.exp_2'),
                                __('pitmetric.about.exp_3'),
                                __('pitmetric.about.exp_4'),
                                __('pitmetric.about.exp_5'),
                                __('pitmetric.about.exp_6'),
                            ] as $index => $experience)
                                <div class="grid gap-3 border-b border-white/10 py-4 sm:grid-cols-[3.5rem_1fr]">
                                    <span class="font-mono text-[10px] tracking-[0.18em] text-[#E10600]">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <p class="pm-editorial-copy text-sm leading-6 text-zinc-300">{{ $experience }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="mt-16 grid border-y border-white/10 md:grid-cols-2">
                        <div class="py-7 pr-0 md:border-r md:border-white/10 md:pr-8">
                            <p class="pm-editorial-kicker">{{ __('pitmetric.about.education') }}</p>
                            <h2 class="mt-4 text-lg font-semibold text-white">{{ __('pitmetric.about.diploma') }}</h2>
                            <p class="pm-editorial-copy mt-3 text-sm leading-6 text-zinc-400">Istituto Tecnico Industriale Statale “Mario Delpozzo” · Cuneo<br>2019 — 2024</p>
                        </div>
                        <div class="border-t border-white/10 py-7 md:border-t-0 md:pl-8">
                            <p class="pm-editorial-kicker">{{ __('pitmetric.about.languages') }}</p>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between gap-5"><dt class="font-semibold text-white">Italiano</dt><dd class="text-zinc-400">{{ __('pitmetric.about.native') }}</dd></div>
                                <div class="flex justify-between gap-5"><dt class="font-semibold text-white">English</dt><dd class="text-right text-zinc-400">B2+ · {{ __('pitmetric.about.professional') }}</dd></div>
                            </dl>
                        </div>
                    </section>

                    <section class="mt-16 border-l-2 border-[#E10600] pl-6 sm:pl-8">
                        <p class="pm-editorial-kicker">{{ __('pitmetric.about.why') }}</p>
                        <h2 class="pm-editorial-section-title mt-4 max-w-3xl text-white">{{ __('pitmetric.about.why_title') }}</h2>
                        <p class="pm-editorial-copy mt-5 max-w-3xl leading-7 text-zinc-300">{{ __('pitmetric.about.why_copy') }}</p>
                        <p class="pm-editorial-copy mt-6 text-sm leading-6 text-zinc-500">{{ __('pitmetric.about.interests') }}: {{ __('pitmetric.about.interests_copy') }}</p>
                    </section>
                </div>
            </div>
        </section>
    </div>
</x-layouts::public>
