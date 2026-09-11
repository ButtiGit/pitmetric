<x-layouts::public>
    <section class="mx-auto max-w-7xl px-5 pb-20 pt-20 lg:px-8 lg:pt-28">
        <div class="max-w-4xl">
            <span class="inline-flex rounded-full border border-[#E10600]/40 bg-[#E10600]/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#ff625e]">{{ __('pitmetric.home.badge') }}</span>
            <h1 class="mt-7 text-5xl font-black tracking-[-0.04em] text-white sm:text-6xl lg:text-7xl">{{ __('pitmetric.home.title_1') }}<br><span class="text-[#E10600]">{{ __('pitmetric.home.title_2') }}</span></h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-400">{{ __('pitmetric.home.intro') }}</p>
            <div class="mt-9 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#E10600] px-5 py-3 font-semibold text-white transition hover:bg-[#F01812]">{{ __('pitmetric.home.open_dashboard') }}</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-xl bg-[#E10600] px-5 py-3 font-semibold text-white transition hover:bg-[#F01812]">{{ __('pitmetric.home.discover') }}</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-5 py-3 font-semibold text-white transition hover:border-white/30">{{ __('pitmetric.nav.login') }}</a>
                @endauth
            </div>
        </div>

        <div class="mt-20 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('pitmetric.home.configuration'), __('pitmetric.home.configuration_copy')],
                [__('pitmetric.home.usage'), __('pitmetric.home.usage_copy')],
                [__('pitmetric.home.maintenance'), __('pitmetric.home.maintenance_copy')],
                [__('pitmetric.home.costs'), __('pitmetric.home.costs_copy')],
            ] as [$heading, $copy])
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <div class="mb-8 h-1 w-10 rounded-full bg-[#E10600]"></div>
                    <h2 class="text-lg font-bold text-white">{{ $heading }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-white/10 bg-white/[0.025]">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">{{ __('pitmetric.home.principle_label') }}</p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-white">{{ __('pitmetric.home.principle_title') }}</h2>
                <p class="mt-5 leading-7 text-zinc-400">{{ __('pitmetric.home.principle_copy') }}</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-[#0d1014] p-6 sm:p-8">
                <div class="grid gap-3 font-mono text-sm">
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4"><span class="text-zinc-500">01</span> <span class="ml-3 text-white">Race Build V3</span></div>
                    <div class="ml-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-zinc-300">Engine #02 · Chain #04 · Tyres #08</div>
                    <div class="rounded-xl border border-[#E10600]/35 bg-[#E10600]/10 p-4 text-[#ff7a76]">1,250 m × 40 laps = 50 km</div>
                    <div class="ml-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-zinc-300">{{ __('pitmetric.home.propagation') }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">{{ __('pitmetric.home.roadmap') }}</p><h2 class="mt-3 text-3xl font-bold text-white">{{ __('pitmetric.home.roadmap_title') }}</h2></div>
            <a href="{{ route('updates.index') }}" class="text-sm font-semibold text-zinc-300 hover:text-white">{{ __('pitmetric.home.follow_updates') }}</a>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['01', __('pitmetric.home.roadmap_1_title'), __('pitmetric.home.roadmap_1_copy')],
                ['02', __('pitmetric.home.roadmap_2_title'), __('pitmetric.home.roadmap_2_copy')],
                ['03', __('pitmetric.home.roadmap_3_title'), __('pitmetric.home.roadmap_3_copy')],
            ] as [$number, $heading, $copy])
                <article class="rounded-2xl border border-white/10 p-6"><span class="font-mono text-xs text-[#ff625e]">{{ $number }}</span><h3 class="mt-5 font-bold text-white">{{ $heading }}</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p></article>
            @endforeach
        </div>
    </section>
</x-layouts::public>
