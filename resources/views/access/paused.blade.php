<x-layouts::app :title="__('users.paused_title')">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
        <div class="mx-auto w-full max-w-[760px]">
            <section class="pm-panel p-5 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="grid size-11 shrink-0 place-items-center rounded-xl border border-pm-warning/30 bg-pm-warning-subtle text-xl font-black text-pm-warning">!</div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">PitMetric</p>
                        <h1 class="mt-2 text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">{{ __('users.paused_title') }}</h1>
                        <p class="mt-3 text-sm leading-7 text-pm-text-secondary">{{ __('users.paused_copy') }}</p>

                        <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('home') }}" class="pm-race-button w-full sm:w-auto">{{ __('users.back_home') }}</a>
                            <a href="{{ route('profile.edit') }}" class="pm-ghost-button w-full sm:w-auto">{{ __('users.settings') }}</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
