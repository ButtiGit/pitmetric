<x-layouts::public :title="__('pitmetric.updates.title')" :description="__('pitmetric.updates.description')">
    <section class="mx-auto max-w-6xl px-5 py-20 lg:px-8 lg:py-28">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">{{ __('pitmetric.updates.label') }}</p>
        <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('pitmetric.updates.title') }}</h1>
        <p class="mt-5 max-w-2xl leading-7 text-zinc-400">{{ __('pitmetric.updates.intro') }}</p>

        <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($updates as $update)
                <article class="flex min-h-64 flex-col rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <time class="text-xs uppercase tracking-[0.14em] text-zinc-500" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d M Y') }}</time>
                    <h2 class="mt-4 text-xl font-bold text-white"><a href="{{ route('updates.show', $update) }}" class="transition hover:text-[#ff625e]">{{ $update->title }}</a></h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-400">{{ $update->excerpt }}</p>
                    <a href="{{ route('updates.show', $update) }}" class="mt-auto pt-7 text-sm font-semibold text-[#ff625e] transition hover:text-[#ff817d]">{{ __('pitmetric.updates.read') }}</a>
                </article>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-white/15 p-10 text-center text-zinc-400">{{ __('pitmetric.updates.empty') }}</div>
            @endforelse
        </div>

        @if ($updates->hasPages())
            <div class="mt-10">{{ $updates->links() }}</div>
        @endif
    </section>
</x-layouts::public>
