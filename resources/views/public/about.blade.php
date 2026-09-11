<x-layouts::public :title="__('pitmetric.about.title')" :description="__('pitmetric.about.description')">
    <section class="relative overflow-hidden border-b border-white/10 bg-[#0a0d11]">
        <div class="absolute inset-y-0 right-0 hidden w-[48%] lg:block"><img src="https://images.unsplash.com/photo-1761044291210-10e0b793d8c9?auto=format&fit=crop&fm=jpg&q=82&w=1600" alt="{{ __('pitmetric.about.image_alt') }}" class="h-full w-full object-cover opacity-35"><div class="absolute inset-0 bg-gradient-to-r from-[#0a0d11] via-[#0a0d11]/55 to-[#0a0d11]/10"></div></div>
        <div class="relative mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.22em] text-[#ff4b47]">{{ __('pitmetric.about.creator') }}</p>
                <h1 class="mt-4 text-5xl font-black tracking-[-0.05em] text-white sm:text-6xl">Simone Butticè</h1>
                <p class="mt-3 text-xl font-semibold text-zinc-200">Junior Full-Stack Web Developer</p>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-400">{{ __('pitmetric.about.role') }}</p>
                <div class="mt-8 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-300">
                    <span class="border border-white/10 px-3 py-2">Cuneo, Italy</span><span class="border border-white/10 px-3 py-2">PHP · JavaScript · MySQL</span><span class="border border-white/10 px-3 py-2">REST API · Ionic · Git</span>
                </div>
            </div>
        </div>
        <a href="https://unsplash.com/photos/close-up-of-a-racing-cars-steering-wheel-0WVAMqStEBY" target="_blank" rel="noopener" class="absolute bottom-4 right-5 hidden text-[10px] text-white/35 hover:text-white/70 lg:block">Photo: JIWON KANG / Unsplash</a>
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
                        [__('pitmetric.about.mobile_tools'), 'Ionic · Git · phpMyAdmin · Debugging · Legacy systems'],
                    ] as [$heading, $copy])
                        <div class="border-t border-white/10 pt-4"><h2 class="text-sm font-bold uppercase tracking-[0.08em] text-white">{{ $heading }}</h2><p class="mt-2 text-sm leading-6 text-zinc-400">{{ $copy }}</p></div>
                    @endforeach
                </div>
            </aside>

            <div class="space-y-12">
                <section>
                    <p class="font-mono text-xs uppercase tracking-[0.2em] text-[#E10600]">{{ __('pitmetric.about.experience') }}</p>
                    <div class="mt-5 border-l-2 border-[#E10600] pl-6">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"><h2 class="text-2xl font-bold text-white">Junior Software Developer · Edisoft</h2><span class="font-mono text-xs text-zinc-500">{{ __('pitmetric.about.present') }}</span></div>
                        <p class="mt-5 leading-7 text-zinc-400">{{ __('pitmetric.about.experience_copy') }}</p>
                        <ul class="mt-6 grid gap-3 text-sm leading-6 text-zinc-300 sm:grid-cols-2">
                            <li>— {{ __('pitmetric.about.exp_1') }}</li><li>— {{ __('pitmetric.about.exp_2') }}</li><li>— {{ __('pitmetric.about.exp_3') }}</li><li>— {{ __('pitmetric.about.exp_4') }}</li><li>— {{ __('pitmetric.about.exp_5') }}</li><li>— {{ __('pitmetric.about.exp_6') }}</li>
                        </ul>
                    </div>
                </section>

                <section class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.025] p-6"><p class="font-mono text-xs uppercase tracking-[0.18em] text-[#E10600]">{{ __('pitmetric.about.education') }}</p><h2 class="mt-4 text-xl font-bold text-white">{{ __('pitmetric.about.diploma') }}</h2><p class="mt-2 text-sm leading-6 text-zinc-400">Istituto Tecnico Industriale Statale “Mario Delpozzo” · Cuneo<br>2019 — 2024</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.025] p-6"><p class="font-mono text-xs uppercase tracking-[0.18em] text-[#E10600]">{{ __('pitmetric.about.languages') }}</p><div class="mt-4 space-y-3"><div class="flex justify-between gap-4"><span class="font-bold text-white">Italiano</span><span class="text-sm text-zinc-400">{{ __('pitmetric.about.native') }}</span></div><div class="flex justify-between gap-4"><span class="font-bold text-white">English</span><span class="text-sm text-zinc-400">B2+ · {{ __('pitmetric.about.professional') }}</span></div></div></div>
                </section>

                <section class="rounded-3xl border border-[#E10600]/30 bg-[#E10600]/10 p-7 sm:p-9"><p class="font-mono text-xs uppercase tracking-[0.2em] text-[#ff6a66]">{{ __('pitmetric.about.why') }}</p><h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white">{{ __('pitmetric.about.why_title') }}</h2><p class="mt-5 max-w-3xl leading-7 text-zinc-300">{{ __('pitmetric.about.why_copy') }}</p><p class="mt-6 text-sm text-zinc-400">{{ __('pitmetric.about.interests') }}: {{ __('pitmetric.about.interests_copy') }}</p></section>
            </div>
        </div>
    </section>
</x-layouts::public>
