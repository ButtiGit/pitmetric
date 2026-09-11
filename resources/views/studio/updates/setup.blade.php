<x-layouts::app :title="__('pitmetric.studio.title')">
    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="pm-elevated overflow-hidden rounded-3xl">
            <div class="pm-checkered p-6 sm:p-8 lg:p-10">
                <div class="max-w-3xl">
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ __('pitmetric.studio.setup_eyebrow') }}</p>
                    <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ __('pitmetric.studio.setup_title') }}</h1>
                    <p class="mt-4 text-sm leading-7 text-pm-text-secondary sm:text-base">{{ __('pitmetric.studio.setup_copy') }}</p>
                </div>
            </div>
        </section>

        @if (session('studio_setup_error'))
            <div class="rounded-2xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning">{{ session('studio_setup_error') }}</div>
        @endif

        <section class="grid gap-4 lg:grid-cols-[1fr_.8fr]">
            <article class="pm-race-card p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.studio.setup_step') }}</p>
                <h2 class="mt-3 text-xl font-black text-pm-text">{{ __('pitmetric.studio.setup_database') }}</h2>
                <p class="mt-3 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.setup_database_copy') }}</p>
                <pre class="mt-6 overflow-x-auto rounded-2xl border border-pm-border bg-pm-subtle p-4 text-xs leading-6 text-pm-text"><code>php artisan migrate --force --no-interaction
php artisan storage:link
php artisan optimize:clear --except=cache
php artisan optimize</code></pre>
            </article>

            <article class="pm-race-card p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.studio.setup_access') }}</p>
                <h2 class="mt-3 text-xl font-black text-pm-text">PITMETRIC_EDITOR_EMAILS</h2>
                <p class="mt-3 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.setup_access_copy') }}</p>
                <p class="mt-6 rounded-2xl border border-pm-border bg-pm-subtle p-4 font-mono text-xs text-pm-text">PITMETRIC_EDITOR_EMAILS=&lt;your-login-email&gt;</p>
            </article>
        </section>
    </div>
</x-layouts::app>
