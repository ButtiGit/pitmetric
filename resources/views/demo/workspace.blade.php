<x-layouts::app :title="__('demo.nav.'.$initialSection)">
    @php
        $copy = [
            'locale' => app()->getLocale() === 'it' ? 'it' : 'en',
            'section' => $initialSection,
            'localBadge' => __('demo.local_badge'),
            'localCopy' => __('demo.local_copy'),
            'seed' => __('demo.seed'),
            'reset' => __('demo.reset'),
            'locked' => __('demo.locked'),
            'lockedCopy' => __('demo.locked_copy'),
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full bg-pm-accent"></span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-pm-accent/30 bg-pm-accent-subtle px-2.5 py-1 text-[11px] font-bold tracking-[0.08em] text-pm-accent">{{ __('demo.local_badge') }}</span>
                                <span class="text-xs text-pm-muted">localStorage · {{ auth()->user()->email }}</span>
                            </div>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('demo.local_copy') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="demo-seed" class="pm-ghost-button">{{ __('demo.seed') }}</button>
                        <button type="button" id="demo-reset" class="pm-danger-button">{{ __('demo.reset') }}</button>
                    </div>
                </div>
            </section>

            <main id="pitmetric-demo" data-user="{{ auth()->id() }}" data-section="{{ $initialSection }}" data-copy='@json($copy)' class="space-y-5"></main>

            <section class="pm-panel p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle text-sm">🔒</div>
                    <div><h2 class="font-bold text-pm-text">{{ __('demo.locked') }}</h2><p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ __('demo.locked_copy') }}</p></div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
