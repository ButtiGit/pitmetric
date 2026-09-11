<x-layouts::public :title="__('pitmetric.cookies.policy_title')" :description="__('pitmetric.cookies.policy_copy')">
    <section class="mx-auto max-w-4xl px-5 py-20 lg:px-8 lg:py-28">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">{{ __('pitmetric.cookies.settings') }}</p>
        <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('pitmetric.cookies.policy_title') }}</h1>
        <p class="mt-6 max-w-3xl text-lg leading-8 text-zinc-400">{{ __('pitmetric.cookies.policy_copy') }}</p>

        <div class="mt-12 grid gap-5">
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-7">
                <h2 class="text-xl font-bold text-white">{{ __('pitmetric.cookies.necessary_title') }}</h2>
                <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.cookies.necessary_copy') }}</p>
            </article>
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-7">
                <h2 class="text-xl font-bold text-white">{{ __('pitmetric.cookies.preferences_title') }}</h2>
                <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.cookies.preferences_copy') }}</p>
            </article>
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-7">
                <h2 class="text-xl font-bold text-white">{{ __('pitmetric.cookies.optional_title') }}</h2>
                <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.cookies.optional_copy') }}</p>
            </article>
        </div>

        <a href="{{ route('home') }}" class="mt-10 inline-flex rounded-xl border border-white/15 px-4 py-2.5 text-sm font-semibold text-white transition hover:border-white/30">{{ __('pitmetric.cookies.back') }}</a>
    </section>
</x-layouts::public>
