<x-layouts::public :title="__('newsletter.unsubscribed_title')">
    <section class="mx-auto max-w-2xl px-5 py-24 text-center lg:px-8">
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 sm:p-10">
            <div class="mx-auto size-10 rounded-full bg-[#E10600]/15 p-2 text-[#ff625e]">✓</div>
            <h1 class="mt-5 text-3xl font-black text-white">{{ __('newsletter.unsubscribed_title') }}</h1>
            <p class="mt-4 leading-7 text-zinc-400">{{ __('newsletter.unsubscribed_copy') }}</p>
            <a href="{{ route('home') }}" class="pm-race-button mt-7">{{ __('newsletter.back') }}</a>
        </div>
    </section>
</x-layouts::public>
