<x-layouts::app :title="__('pitmetric.studio.title')">
    @php($setupCommand = 'php artisan migrate --force --no-interaction && (php artisan storage:link || true) && php artisan optimize:clear --except=cache && php artisan optimize')

    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="pm-elevated overflow-hidden rounded-3xl">
            <div class="relative overflow-hidden p-6 sm:p-8 lg:p-10">
                <div class="pm-speed-grid absolute inset-0 opacity-20"></div>
                <div class="relative max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-pm-success/25 bg-pm-success-subtle px-3 py-1.5 text-xs font-bold text-pm-success">
                        <span class="size-2 rounded-full bg-pm-success"></span>
                        {{ __('pitmetric.studio.account_authorized') }} · {{ auth()->user()->email }}
                    </div>
                    <h1 class="mt-6 text-3xl font-black tracking-[-0.04em] text-pm-text sm:text-4xl">{{ __('pitmetric.studio.setup_title') }}</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-pm-text-secondary sm:text-base">{{ __('pitmetric.studio.setup_copy') }}</p>
                </div>
            </div>
        </section>

        @if (session('studio_setup_error'))
            <div class="rounded-2xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning">{{ session('studio_setup_error') }}</div>
        @endif

        <section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
            <article class="pm-race-card p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="grid size-11 shrink-0 place-items-center rounded-2xl bg-pm-accent text-lg font-black text-white">1</div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-pm-accent">{{ __('pitmetric.studio.one_last_step') }}</p>
                        <h2 class="mt-2 text-2xl font-black tracking-[-0.025em] text-pm-text">{{ __('pitmetric.studio.setup_database') }}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.setup_database_copy') }}</p>
                    </div>
                </div>

                <div class="mt-7 rounded-2xl border border-pm-border bg-pm-subtle p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-[11px] font-bold uppercase tracking-[0.15em] text-pm-muted">Coolify / PitMetric / Terminal</span>
                        <button type="button" class="pm-ghost-button !min-h-8 !px-3 !py-1.5 text-xs" onclick="navigator.clipboard.writeText(@js($setupCommand)); this.textContent=@js(__('pitmetric.studio.copied')); setTimeout(() => this.textContent=@js(__('pitmetric.studio.copy_command')), 1400)">{{ __('pitmetric.studio.copy_command') }}</button>
                    </div>
                    <code class="mt-4 block break-all font-mono text-xs leading-6 text-pm-text">{{ $setupCommand }}</code>
                </div>

                <div class="mt-6 flex items-start gap-3 rounded-2xl border border-pm-info/20 bg-pm-info-subtle p-4 text-sm leading-6 text-pm-info">
                    <span class="mt-2 h-px w-5 shrink-0 bg-current" aria-hidden="true"></span>
                    <p>{{ __('pitmetric.studio.setup_after') }}</p>
                </div>
            </article>

            <article class="pm-elevated rounded-3xl p-6 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-pm-muted">{{ __('pitmetric.studio.what_you_can_post') }}</p>
                <div class="mt-6 grid gap-3">
                    @foreach ([
                        ['TXT', __('pitmetric.studio.type_text'), __('pitmetric.studio.type_text_copy')],
                        ['IMG', __('pitmetric.studio.type_image'), __('pitmetric.studio.type_image_copy')],
                        ['VID', __('pitmetric.studio.type_video'), __('pitmetric.studio.type_video_copy')],
                    ] as [$icon, $title, $copy])
                        <div class="flex items-start gap-4 rounded-2xl border border-pm-border bg-pm-subtle p-4">
                            <div class="grid size-10 shrink-0 place-items-center rounded-xl border border-pm-border bg-pm-surface font-mono text-[10px] font-black tracking-[0.08em] text-pm-accent">{{ $icon }}</div>
                            <div><p class="font-bold text-pm-text">{{ $title }}</p><p class="mt-1 text-sm leading-5 text-pm-text-secondary">{{ $copy }}</p></div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-6 text-xs leading-5 text-pm-muted">{{ __('pitmetric.studio.no_email_change') }}</p>
            </article>
        </section>
    </div>
</x-layouts::app>
