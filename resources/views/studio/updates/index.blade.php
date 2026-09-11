<x-layouts::app :title="__('pitmetric.studio.title')">
    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <div class="rounded-2xl border border-pm-success/30 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
        @endif

        <section class="pm-elevated overflow-hidden rounded-3xl">
            <div class="relative overflow-hidden p-6 sm:p-8">
                <div class="pm-speed-grid absolute inset-0 opacity-15"></div>
                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-pm-success/25 bg-pm-success-subtle px-3 py-1.5 text-xs font-bold text-pm-success">
                            <span class="size-2 rounded-full bg-pm-success"></span>{{ __('pitmetric.studio.ready_to_publish') }}
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-[-0.04em] text-pm-text sm:text-4xl">{{ __('pitmetric.studio.title') }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">{{ __('pitmetric.studio.intro_simple') }}</p>
                        <p class="mt-3 text-xs text-pm-muted">{{ __('pitmetric.studio.signed_in_as') }} <span class="font-semibold text-pm-text-secondary">{{ auth()->user()->email }}</span></p>
                    </div>
                    <a href="{{ route('studio.updates.create') }}" class="pm-race-button shrink-0">＋ {{ __('pitmetric.studio.new_post') }}</a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($updates as $update)
                <article class="pm-race-card group flex min-h-72 flex-col overflow-hidden">
                    @if ($update->mediaSource() && $update->media_type === 'image')
                        <div class="h-36 overflow-hidden border-b border-pm-border"><img src="{{ $update->mediaSource() }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"></div>
                    @elseif ($update->media_type === 'video')
                        <div class="pm-speed-grid grid h-36 place-items-center border-b border-pm-border bg-pm-subtle"><div class="pm-play-mark scale-50"><span></span></div></div>
                    @endif
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $update->status === 'published' ? 'bg-pm-success-subtle text-pm-success' : 'bg-pm-warning-subtle text-pm-warning' }}">{{ __('pitmetric.studio.'.$update->status) }}</span>
                            <span class="font-mono text-xs text-pm-muted">{{ $update->updated_at?->format('d/m/Y') }}</span>
                        </div>
                        <h2 class="mt-5 text-xl font-black tracking-[-0.025em] text-pm-text">{{ $update->titleForLocale() }}</h2>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-pm-text-secondary">{{ $update->excerptForLocale() }}</p>
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
                    </div>
                </article>
            @empty
                <div class="pm-race-card col-span-full p-12 text-center sm:p-16">
                    <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-pm-accent-subtle text-2xl text-pm-accent">＋</div>
                    <p class="mt-5 text-xl font-black text-pm-text">{{ __('pitmetric.studio.empty_title') }}</p>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.empty_copy') }}</p>
                    <a href="{{ route('studio.updates.create') }}" class="pm-race-button mt-7">{{ __('pitmetric.studio.new_post') }}</a>
                </div>
            @endforelse
        </section>

        @if ($updates->hasPages())
            <div>{{ $updates->links() }}</div>
        @endif
    </div>
</x-layouts::app>
