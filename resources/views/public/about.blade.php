<x-layouts::public :title="__('pitmetric.about.title')" :description="__('pitmetric.about.description')">
    @php
        $isItalian = app()->getLocale() === 'it';
    @endphp

    <section class="relative overflow-hidden border-b border-white/10 bg-[#080a0d]">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_82%_34%,rgba(225,6,0,.16),transparent_30%),linear-gradient(135deg,transparent_0%,transparent_56%,rgba(225,6,0,.07)_56%,rgba(225,6,0,.07)_57%,transparent_57%)]"></div>
        <div class="pointer-events-none absolute inset-y-0 right-[18%] hidden w-px bg-gradient-to-b from-transparent via-[#E10600]/35 to-transparent lg:block"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:py-20 lg:grid-cols-[1.15fr_.72fr] lg:items-center lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.22em] text-[#ff4b47]">{{ __('pitmetric.about.creator') }}</p>
                <h1 class="mt-4 text-5xl font-black tracking-[-0.045em] text-white sm:text-6xl lg:text-7xl">Simone Butticè</h1>
                <p class="mt-4 text-xl font-semibold text-zinc-200 sm:text-2xl">Junior Full-Stack Web Developer</p>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-400">{{ __('pitmetric.about.role') }}</p>

                <div class="mt-8 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-300">
                    <span class="border border-white/10 bg-white/[0.025] px-3 py-2">Cuneo, Italy</span>
                    <span class="border border-white/10 bg-white/[0.025] px-3 py-2">PHP · JavaScript · MySQL</span>
                    <span class="border border-white/10 bg-white/[0.025] px-3 py-2">REST API · Ionic · Git</span>
                </div>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="mailto:simonebuttice05@gmail.com" class="pm-race-button">{{ $isItalian ? 'Scrivimi' : 'Email me' }}</a>
                    <a href="tel:+393892625367" class="pm-ghost-button">{{ $isItalian ? 'Chiamami' : 'Call me' }}</a>
                </div>
            </div>

            <div class="mx-auto w-full max-w-[410px] lg:mx-0 lg:justify-self-end">
                <div class="relative">
                    <div class="absolute -inset-3 translate-x-3 translate-y-3 border border-[#E10600]/25"></div>
                    <div class="relative overflow-hidden border border-white/10 bg-[#11151b] shadow-[0_28px_90px_rgba(0,0,0,.42)]">
                        <div class="absolute left-0 top-0 z-10 h-1 w-24 bg-[#E10600]"></div>
                        <img src="{{ asset('media/simone-buttice-profile.webp') }}" alt="{{ $isItalian ? 'Ritratto di Simone Butticè' : 'Portrait of Simone Butticè' }}" class="aspect-[4/5] w-full object-cover object-[50%_24%]" loading="eager" fetchpriority="high">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/65 to-transparent px-6 pb-6 pt-20">
                            <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff5b57]">PitMetric / Creator</p>
                            <p class="mt-2 text-xl font-bold text-white">Simone Butticè</p>
                            <p class="mt-1 text-sm text-zinc-300">Cuneo · Piemonte · Italia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-white/10 bg-[#0b0e12]">
        <div class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
            <div class="grid overflow-hidden border border-white/10 bg-white/[0.02] md:grid-cols-3">
                <a href="mailto:simonebuttice05@gmail.com" class="group p-6 transition hover:bg-white/[0.04] md:border-r md:border-white/10">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#E10600]">Email</p>
                    <p class="mt-2 break-all text-sm font-semibold text-white group-hover:text-[#ff625e]">simonebuttice05@gmail.com</p>
                </a>
                <a href="tel:+393892625367" class="group border-t border-white/10 p-6 transition hover:bg-white/[0.04] md:border-r md:border-t-0 md:border-white/10">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#E10600]">{{ $isItalian ? 'Telefono' : 'Phone' }}</p>
                    <p class="mt-2 text-sm font-semibold text-white group-hover:text-[#ff625e]">+39 389 262 5367</p>
                </a>
                <div class="border-t border-white/10 p-6 md:border-t-0">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-[#E10600]">{{ $isItalian ? 'Posizione' : 'Location' }}</p>
                    <p class="mt-2 text-sm font-semibold text-white">Cuneo (CN), Italia</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $isItalian ? 'Disponibile al trasferimento a Torino · Patente B' : 'Available to relocate to Turin · Driving licence B' }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-[.72fr_1.28fr]">
            <aside>
                <p class="font-mono text-xs uppercase tracking-[0.2em] text-[#E10600]">{{ __('pitmetric.about.stack_label') }}</p>
                <div class="mt-6 grid gap-7">
                    @foreach ([
                        [__('pitmetric.about.backend'), 'PHP · REST APIs · JSON · Token auth · Sessions'],
                        [__('pitmetric.about.frontend'), 'HTML5 · CSS3 · JavaScript · Bootstrap · AJAX · Fetch API · Responsive UI'],
                        [__('pitmetric.about.database'), 'MySQL · SQL · Relational modelling · JOINs · Subqueries · Query optimization'],
                        [__('pitmetric.about.mobile_tools'), 'Laravel · Livewire · Ionic · Git · phpMyAdmin · Debugging · Legacy systems'],
                    ] as [$heading, $copy])
                        <div class="border-t border-white/10 pt-4">
                            <h2 class="text-sm font-bold uppercase tracking-[0.08em] text-white">{{ $heading }}</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-400">{{ $copy }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>

            <div class="space-y-12">
                <section>
                    <p class="font-mono text-xs uppercase tracking-[0.2em] text-[#E10600]">{{ __('pitmetric.about.experience') }}</p>
                    <div class="mt-5 border-l-2 border-[#E10600] pl-6">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                            <h2 class="text-2xl font-bold text-white">Junior Software Developer · Edisoft</h2>
                            <span class="font-mono text-xs text-zinc-500">{{ __('pitmetric.about.present') }}</span>
                        </div>
                        <p class="mt-5 leading-7 text-zinc-400">{{ __('pitmetric.about.experience_copy') }}</p>
                        <ul class="mt-6 grid gap-3 text-sm leading-6 text-zinc-300 sm:grid-cols-2">
                            <li>— {{ __('pitmetric.about.exp_1') }}</li>
                            <li>— {{ __('pitmetric.about.exp_2') }}</li>
                            <li>— {{ __('pitmetric.about.exp_3') }}</li>
                            <li>— {{ __('pitmetric.about.exp_4') }}</li>
                            <li>— {{ __('pitmetric.about.exp_5') }}</li>
                            <li>— {{ __('pitmetric.about.exp_6') }}</li>
                        </ul>
                    </div>
                </section>

                <section class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                        <p class="font-mono text-xs uppercase tracking-[0.18em] text-[#E10600]">{{ __('pitmetric.about.education') }}</p>
                        <h2 class="mt-4 text-xl font-bold text-white">{{ __('pitmetric.about.diploma') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-400">Istituto Tecnico Industriale Statale “Mario Delpozzo” · Cuneo<br>2019 — 2024</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                        <p class="font-mono text-xs uppercase tracking-[0.18em] text-[#E10600]">{{ __('pitmetric.about.languages') }}</p>
                        <div class="mt-4 space-y-3">
                            <div class="flex justify-between gap-4"><span class="font-bold text-white">Italiano</span><span class="text-sm text-zinc-400">{{ __('pitmetric.about.native') }}</span></div>
                            <div class="flex justify-between gap-4"><span class="font-bold text-white">English</span><span class="text-sm text-zinc-400">B2+ · {{ __('pitmetric.about.professional') }}</span></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-[#E10600]/30 bg-[#E10600]/10 p-7 sm:p-9">
                    <p class="font-mono text-xs uppercase tracking-[0.2em] text-[#ff6a66]">{{ __('pitmetric.about.why') }}</p>
                    <h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white">{{ __('pitmetric.about.why_title') }}</h2>
                    <p class="mt-5 max-w-3xl leading-7 text-zinc-300">{{ __('pitmetric.about.why_copy') }}</p>
                    <p class="mt-6 text-sm text-zinc-400">{{ __('pitmetric.about.interests') }}: {{ __('pitmetric.about.interests_copy') }}</p>
                </section>
            </div>
        </div>
    </section>
</x-layouts::public>
