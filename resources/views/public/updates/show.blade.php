<x-layouts.public :title="$update->title" :description="$update->excerpt">
    <article class="mx-auto max-w-3xl px-5 py-20 lg:px-8 lg:py-28">
        <a href="{{ route('updates.index') }}" class="text-sm font-semibold text-zinc-400 hover:text-white">← Tutti gli updates</a>
        <time class="mt-10 block text-xs uppercase tracking-[0.14em] text-zinc-500" datetime="{{ $update->published_at?->toDateString() }}">{{ $update->published_at?->translatedFormat('d F Y') }}</time>
        <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ $update->title }}</h1>
        <p class="mt-6 text-xl leading-8 text-zinc-400">{{ $update->excerpt }}</p>
        <div class="mt-10 whitespace-pre-line text-base leading-8 text-zinc-300">{{ $update->content }}</div>
    </article>
</x-layouts.public>
