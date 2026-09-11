<x-layouts::public>
    <section class="relative isolate overflow-hidden border-b border-white/10">
        <img src="https://images.unsplash.com/photo-1656978766399-1e117a291918?auto=format&fit=crop&fm=jpg&q=82&w=2200" alt="{{ __('pitmetric.home.hero_image_alt') }}" class="absolute inset-0 -z-20 h-full w-full object-cover object-center">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(6,8,11,.98)_0%,rgba(6,8,11,.92)_42%,rgba(6,8,11,.45)_73%,rgba(6,8,11,.78)_100%)]"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(6,8,11,.12),rgba(6,8,11,.94))]"></div>
        <div class="mx-auto grid min-h-[72vh] max-w-7xl items-end gap-12 px-5 pb-16 pt-28 lg:grid-cols-[1.2fr_.8fr] lg:px-8 lg:pb-20">
            <div class="max-w-4xl">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.24em] text-[#ff4b47]">{{ __('pitmetric.home.eyebrow') }}</p>
                <h1 class="mt-5 max-w-4xl text-5xl font-black leading-[.94] tracking-[-0.055em] text-white sm:text-6xl lg:text-8xl">{{ __('pitmetric.home.title_1') }}<br><span class="text-[#E10600]">{{ __('pitmetric.home.title_2') }}</span></h1>
                <p class="mt-7 max-w-2xl text-base leading-7 text-zinc-300 sm:text-lg">{{ __('pitmetric.home.intro') }}</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="pm-race-button">{{ __('pitmetric.home.open_dashboard') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="pm-race-button">{{ __('pitmetric.home.discover') }}</a>
                        <a href="{{ route('login') }}" class="pm-ghost-button pm-ghost-button-dark">{{ __('pitmetric.nav.login') }}</a>
                    @endauth
                </div>
            </div>
            <div class="hidden self-end lg:block"><div class="ml-auto max-w-sm border-l border-[#E10600] pl-5"><p class="font-mono text-xs uppercase tracking-[0.18em] text-zinc-500">{{ __('pitmetric.home.track_note_label') }}</p><p class="mt-2 text-sm leading-6 text-zinc-300">{{ __('pitmetric.home.track_note') }}</p></div></div>
        </div>
        <a href="https://unsplash.com/photos/a-race-car-on-a-track-GCDa5RBWcAw" target="_blank" rel="noopener" class="absolute bottom-4 right-5 text-[10px] text-white/35 transition hover:text-white/70">Photo: Edoardo Giudici Saraval / Unsplash</a>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([['01', __('pitmetric.home.configuration'), __('pitmetric.home.configuration_copy')],['02', __('pitmetric.home.usage'), __('pitmetric.home.usage_copy')],['03', __('pitmetric.home.maintenance'), __('pitmetric.home.maintenance_copy')],['04', __('pitmetric.home.costs'), __('pitmetric.home.costs_copy')]] as [$number, $heading, $copy])
                <article class="pm-race-card group p-7 lg:p-8"><div class="flex items-center justify-between"><span class="font-mono text-xs text-[#E10600]">{{ $number }}</span><span class="pm-status-dot opacity-50 transition group-hover:opacity-100"></span></div><h2 class="mt-8 text-xl font-bold text-white">{{ $heading }}</h2><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p></article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-white/10 bg-[#0d1014]">
        <div class="mx-auto grid max-w-7xl gap-0 lg:grid-cols-2">
            <div class="relative min-h-[420px] overflow-hidden"><img src="https://images.unsplash.com/photo-1765202661219-cec5ad98f324?auto=format&fit=crop&fm=jpg&q=82&w=1800" alt="{{ __('pitmetric.home.garage_image_alt') }}" class="absolute inset-0 h-full w-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/10"></div><a href="https://unsplash.com/photos/race-car-mechanics-working-in-a-garage-oSQGlIBvw3s" target="_blank" rel="noopener" class="absolute bottom-4 left-5 text-[10px] text-white/45 hover:text-white/80">Photo: Edgar / Unsplash</a></div>
            <div class="flex items-center px-6 py-14 sm:px-10 lg:px-14 lg:py-20"><div><p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#ff4b47]">{{ __('pitmetric.home.principle_label') }}</p><h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl">{{ __('pitmetric.home.principle_title') }}</h2><p class="mt-5 max-w-xl leading-7 text-zinc-400">{{ __('pitmetric.home.principle_copy') }}</p><div class="mt-9 grid gap-3 font-mono text-sm"><div class="border-l-2 border-white/20 pl-4 text-zinc-300">Race Build V3 · Engine #02 · Chain #04 · Tyres #08</div><div class="border-l-2 border-[#E10600] pl-4 text-white">1,250 m × 40 laps = 50 km</div><div class="border-l-2 border-white/20 pl-4 text-zinc-400">{{ __('pitmetric.home.propagation') }}</div></div></div></div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8"><div class="grid gap-12 lg:grid-cols-[.8fr_1.2fr] lg:items-end"><div><p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#ff4b47]">{{ __('pitmetric.home.roadmap') }}</p><h2 class="mt-4 text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl">{{ __('pitmetric.home.roadmap_title') }}</h2><a href="{{ route('updates.index') }}" class="pm-ghost-button pm-ghost-button-dark mt-7">{{ __('pitmetric.home.follow_updates') }}</a></div><div class="grid gap-5 sm:grid-cols-3">@foreach ([['A', __('pitmetric.home.roadmap_1_title'), __('pitmetric.home.roadmap_1_copy')],['B', __('pitmetric.home.roadmap_2_title'), __('pitmetric.home.roadmap_2_copy')],['C', __('pitmetric.home.roadmap_3_title'), __('pitmetric.home.roadmap_3_copy')]] as [$number, $heading, $copy])<article class="pm-race-card p-5"><span class="font-mono text-xs text-[#E10600]">{{ $number }}</span><h3 class="mt-3 font-bold text-white">{{ $heading }}</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p></article>@endforeach</div></div></section>
</x-layouts::public>
