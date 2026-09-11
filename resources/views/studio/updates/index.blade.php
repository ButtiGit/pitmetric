<x-layouts::app :title="__('pitmetric.studio.title')">
    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <div class="rounded-2xl border border-pm-success/30 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
        @endif

        <section class="pm-elevated overflow-hidden rounded-3xl">
            <div class="pm-checkered flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-8">
                <div>
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ __('pitmetric.studio.eyebrow') }}</p>
                    <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ __('pitmetric.studio.title') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">{{ __('pitmetric.studio.intro') }}</p>
                </div>
                <a href="{{ route('studio.updates.create') }}" class="pm-race-button shrink-0">{{ __('pitmetric.studio.new_post') }}</a>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($updates as $update)
                <article class="pm-race-card flex min-h-72 flex-col p-6">
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $update->status === 'published' ? 'bg-pm-success-subtle text-pm-success' : 'bg-pm-warning-subtle text-pm-warning' }}">{{ __('pitmetric.studio.'.$update->status) }}</span>
                        <span class="font-mono text-xs text-pm-muted">#{{ str_pad((string) $update->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h2 class="mt-6 text-xl font-black tracking-[-0.025em] text-pm-text">{{ $update->title }}</h2>
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-pm-text-secondary">{{ $update->excerpt }}</p>
                    <div class="mt-auto flex flex-wrap gap-2 pt-7">
                        <a href="{{ route('studio.updates.edit', $update) }}" class="pm-ghost-button">{{ __('pitmetric.studio.edit') }}</a>
                        @if ($update->status === 'published')
                            <a href="{{ route('updates.show', $update) }}" target="_blank" class="pm-ghost-button">{{ __('pitmetric.studio.view') }}</a>
                        @endif
                        <form method="POST" action="{{ route('studio.updates.destroy', $update) }}" onsubmit="return confirm(@js(__('pitmetric.studio.delete_confirm')))" class="ml-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="pm-danger-button">{{ __('pitmetric.studio.delete') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="pm-race-card col-span-full p-12 text-center">
                    <p class="text-lg font-bold text-pm-text">{{ __('pitmetric.studio.empty_title') }}</p>
                    <p class="mt-2 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.empty_copy') }}</p>
                    <a href="{{ route('studio.updates.create') }}" class="pm-race-button mt-6">{{ __('pitmetric.studio.new_post') }}</a>
                </div>
            @endforelse
        </section>

        @if ($updates->hasPages())
            <div>{{ $updates->links() }}</div>
        @endif
    </div>
</x-layouts::app>
