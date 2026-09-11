<x-layouts::app :title="__('Dashboard')">
    <div class="pitmetric-app flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="pm-elevated overflow-hidden rounded-3xl">
            <div class="grid gap-0 lg:grid-cols-[1.15fr_.85fr]">
                <div class="p-6 sm:p-8 lg:p-10">
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ __('pitmetric.dashboard.eyebrow') }}</p>
                    <h1 class="mt-4 max-w-2xl text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ __('pitmetric.dashboard.welcome') }}</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-pm-text-secondary">{{ __('pitmetric.dashboard.intro') }}</p>
                </div>
                <div class="relative min-h-56 overflow-hidden border-t border-pm-border lg:border-l lg:border-t-0">
                    <img src="https://images.unsplash.com/photo-1765202661219-cec5ad98f324?auto=format&fit=crop&fm=jpg&q=75&w=1200" alt="" class="absolute inset-0 h-full w-full object-cover opacity-45">
                    <div class="absolute inset-0 bg-gradient-to-r from-pm-surface via-pm-surface/35 to-transparent"></div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('pitmetric.dashboard.vehicle'), __('pitmetric.dashboard.vehicle_value'), '01'],
                [__('pitmetric.dashboard.configuration'), __('pitmetric.dashboard.configuration_value'), '02'],
                [__('pitmetric.dashboard.usage'), __('pitmetric.dashboard.usage_value'), '03'],
                [__('pitmetric.dashboard.maintenance'), __('pitmetric.dashboard.maintenance_value'), '04'],
            ] as [$label, $value, $number])
                <article class="pm-race-card p-5">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</span><span class="font-mono text-xs text-pm-accent">{{ $number }}</span></div>
                    <p class="mt-8 text-lg font-bold text-pm-text">{{ $value }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-4 lg:grid-cols-[1fr_.8fr]">
            <article class="pm-race-card p-6 sm:p-7">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.dashboard.next_title') }}</p>
                <p class="mt-4 max-w-2xl text-base leading-7 text-pm-text-secondary">{{ __('pitmetric.dashboard.next_copy') }}</p>
                <div class="mt-7 h-1 w-20 rounded-full bg-pm-accent"></div>
            </article>
            <article class="pm-race-card p-6 sm:p-7">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.dashboard.roadmap_title') }}</p>
                <ol class="mt-5 grid gap-3 text-sm text-pm-text-secondary">
                    @foreach ([__('pitmetric.dashboard.roadmap_1'), __('pitmetric.dashboard.roadmap_2'), __('pitmetric.dashboard.roadmap_3'), __('pitmetric.dashboard.roadmap_4')] as $item)
                        <li class="flex items-center gap-3"><span class="size-1.5 rounded-full bg-pm-accent"></span><span>{{ $item }}</span></li>
                    @endforeach
                </ol>
            </article>
        </section>
    </div>
</x-layouts::app>
