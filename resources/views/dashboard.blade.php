<x-layouts::app :title="__('Dashboard')">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel overflow-hidden">
                <div class="grid gap-0 lg:grid-cols-[1fr_340px]">
                    <div class="p-6 sm:p-8 lg:p-9">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-pm-accent">
                            <span class="pm-status-dot"></span>
                            {{ __('pitmetric.dashboard.eyebrow') }}
                        </div>
                        <h1 class="mt-4 max-w-3xl text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ __('pitmetric.dashboard.welcome') }}</h1>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-pm-text-secondary sm:text-base">{{ __('pitmetric.dashboard.intro') }}</p>
                    </div>
                    <div class="relative hidden min-h-52 border-l border-pm-border lg:block">
                        <img src="https://images.unsplash.com/photo-1765202661219-cec5ad98f324?auto=format&fit=crop&fm=jpg&q=76&w=1000" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50">
                        <div class="absolute inset-0 bg-gradient-to-r from-pm-surface via-pm-surface/45 to-transparent"></div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    [__('pitmetric.dashboard.vehicle'), __('pitmetric.dashboard.vehicle_value')],
                    [__('pitmetric.dashboard.configuration'), __('pitmetric.dashboard.configuration_value')],
                    [__('pitmetric.dashboard.usage'), __('pitmetric.dashboard.usage_value')],
                    [__('pitmetric.dashboard.maintenance'), __('pitmetric.dashboard.maintenance_value')],
                ] as $index => [$label, $value])
                    <article class="pm-stat-card">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</span>
                            <span class="font-mono text-[11px] text-pm-muted">0{{ $index + 1 }}</span>
                        </div>
                        <p class="mt-5 text-base font-bold text-pm-text">{{ $value }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-5 lg:grid-cols-[1.25fr_.75fr]">
                <article class="pm-panel p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.dashboard.next_title') }}</p>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-pm-text-secondary">{{ __('pitmetric.dashboard.next_copy') }}</p>
                        </div>
                        <span class="mt-1 hidden h-2 w-2 shrink-0 rounded-full bg-pm-accent sm:block"></span>
                    </div>
                </article>

                <article class="pm-panel p-6 sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.dashboard.roadmap_title') }}</p>
                    <ol class="mt-5 space-y-3.5 text-sm text-pm-text-secondary">
                        @foreach ([__('pitmetric.dashboard.roadmap_1'), __('pitmetric.dashboard.roadmap_2'), __('pitmetric.dashboard.roadmap_3'), __('pitmetric.dashboard.roadmap_4')] as $index => $item)
                            <li class="flex items-center gap-3">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full border border-pm-border bg-pm-subtle font-mono text-[10px] text-pm-muted">{{ $index + 1 }}</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ol>
                </article>
            </section>
        </div>
    </div>
</x-layouts::app>
