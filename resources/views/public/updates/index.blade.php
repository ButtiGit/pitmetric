<x-layouts::public :title="__('pitmetric.updates.title')" :description="__('pitmetric.updates.description')">
    <section class="border-b border-white/10 bg-[#0a0d11]">
        <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
            <p class="font-mono text-xs font-semibold uppercase tracking-[0.22em] text-[#ff4b47]">{{ __('pitmetric.updates.label') }}</p>
            <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_.75fr] lg:items-end">
                <h1 class="text-5xl font-black tracking-[-0.05em] text-white sm:text-6xl">{{ __('pitmetric.updates.title') }}</h1>
                <p class="max-w-xl leading-7 text-zinc-400">{{ __('pitmetric.updates.intro') }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-20">
        <div class="grid gap-px overflow-hidden rounded-3xl border border-white/10 bg-white/10 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($updates as $update)
                <article class="group flex min-h-72 flex-col bg-[#0b0e12] p-7 transition hover:bg-[#10141a]">
                    <div class="flex items-center justify-between gap-4"><time class="font-mono text-xs uppercase tracking-[0.14em] text-zinc-500" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d M Y') }}</time><span class="h-2 w-2 rounded-full bg-[#E10600]"></span></div>
                    <h2 class="mt-6 text-2xl font-bold tracking-[-0.025em] text-white"><a href="{{ route('updates.show', $update) }}" class="transition group-hover:text-[#ff625e]">{{ $update->title }}</a></h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-400">{{ $update->excerpt }}</p>
                    <a href="{{ route('updates.show', $update) }}" class="mt-auto pt-8 text-sm font-bold text-white underline decoration-[#E10600] decoration-2 underline-offset-6">{{ __('pitmetric.updates.read') }}</a>
                </article>
            @empty
                <div class="md:col-span-2 xl:col-span-3 bg-[#0b0e12] p-14 text-center text-zinc-400">{{ __('pitmetric.updates.empty') }}</div>
            @endforelse
        </div>

        @if ($updates->hasPages())
            <div class="mt-10">{{ $updates->links() }}</div>
        @endif
    </section>
</x-layouts::public>
