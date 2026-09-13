<x-layouts::app :title="__('demo.nav.'.$initialSection)">
    @php
        $copy = [
            'locale' => app()->getLocale() === 'it' ? 'it' : 'en',
            'section' => $initialSection,
            'seed' => __('demo.seed'),
            'reset' => __('demo.reset'),
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5">
            <section class="pm-panel p-4 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full bg-pm-accent"></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-pm-accent/30 bg-pm-accent-subtle px-2.5 py-1 text-[11px] font-bold tracking-[0.08em] text-pm-accent">{{ __('demo.local_badge') }}</span>
                                <span class="truncate text-xs text-pm-muted">{{ auth()->user()->email }}</span>
                            </div>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('demo.local_copy') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" id="demo-seed" class="pm-ghost-button">{{ __('demo.seed') }}</button>
                        <button type="button" id="demo-reset" class="pm-danger-button">{{ __('demo.reset') }}</button>
                    </div>
                </div>
            </section>

            <main id="pitmetric-demo" data-user="{{ auth()->id() }}" data-section="{{ $initialSection }}" data-copy='@json($copy)' class="space-y-4 sm:space-y-5"></main>
        </div>
    </div>
</x-layouts::app>
