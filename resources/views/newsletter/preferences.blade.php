<x-layouts::app :title="__('newsletter.title')">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto max-w-3xl">
            <section class="pm-panel p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">{{ __('newsletter.eyebrow') }}</p>
                <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text">{{ __('newsletter.title') }}</h1>
                <p class="mt-3 leading-7 text-pm-text-secondary">{{ __('newsletter.copy') }}</p>

                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-pm-success/30 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('newsletter.update') }}" class="mt-7 space-y-5">
                    @csrf
                    <input type="hidden" name="subscribed" value="0">
                    <label class="flex items-start gap-4 rounded-xl border border-pm-border bg-pm-subtle p-5">
                        <input type="checkbox" name="subscribed" value="1" @checked($user->newsletter_subscribed_at) class="mt-1 size-4 rounded border-pm-border-strong text-pm-accent focus:ring-pm-accent">
                        <span>
                            <span class="font-bold text-pm-text">{{ __('newsletter.toggle_title') }}</span>
                            <span class="mt-1 block text-sm leading-6 text-pm-text-secondary">{{ __('newsletter.toggle_copy') }}</span>
                        </span>
                    </label>
                    <button type="submit" class="pm-race-button">{{ __('newsletter.save') }}</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts::app>
