<x-layouts::public :title="$update->title" :description="$update->excerpt">
    <article>
        <header class="border-b border-white/10 bg-[#0a0d11]">
            <div class="mx-auto max-w-4xl px-5 py-16 lg:px-8 lg:py-24">
                <a href="{{ route('updates.index') }}" class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500 transition hover:text-white">{{ __('pitmetric.updates.back') }}</a>
                <time class="mt-10 block font-mono text-xs uppercase tracking-[0.14em] text-[#ff4b47]" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d F Y') }}</time>
                <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight tracking-[-0.045em] text-white sm:text-6xl">{{ $update->title }}</h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-400">{{ $update->excerpt }}</p>
            </div>
        </header>
        <div class="mx-auto max-w-3xl px-5 py-14 lg:px-8 lg:py-20">
            <div class="whitespace-pre-line text-base leading-8 text-zinc-300">{{ $update->content }}</div>
        </div>
    </article>
</x-layouts::public>
