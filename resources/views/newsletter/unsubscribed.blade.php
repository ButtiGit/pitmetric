<x-layouts::public :title="__('newsletter.unsubscribed_title')">
    <section class="mx-auto max-w-2xl px-5 py-24 text-center lg:px-8">
        <div class="border border-white/10 bg-white/[0.03] p-8 [clip-path:polygon(0_0,calc(100%-12px)_0,100%_12px,100%_100%,12px_100%,0_calc(100%-12px))] sm:p-10">
            <div class="mx-auto grid size-10 place-items-center border border-[#E10600]/25 bg-[#E10600]/10"><span class="size-2 rounded-full bg-[#ff625e]" aria-hidden="true"></span></div>
            <h1 class="mt-5 text-3xl font-black text-white">{{ __('newsletter.unsubscribed_title') }}</h1>
            <p class="mt-4 leading-7 text-zinc-400">{{ __('newsletter.unsubscribed_copy') }}</p>
            <a href="{{ route('home') }}" class="pm-race-button mt-7">{{ __('newsletter.back') }}</a>
        </div>
    </section>
</x-layouts::public>
