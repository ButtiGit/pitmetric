<x-layouts::app :title="__('championship.title')">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 max-[42rem]:pb-28 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1360px] space-y-5" data-pm-championships>
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-widest text-pm-accent">PitMetric / Motorsport</p><h1 class="mt-2 text-3xl font-black text-pm-text">{{ __('championship.title') }}</h1><p class="mt-2 text-sm text-pm-text-secondary">{{ __('championship.intro') }}</p></div>
                <x-crud-modal id="new-championship" :title="__('championship.new')" :trigger="__('championship.new')">
                    <form method="POST" action="{{ route('championships.store') }}" class="grid gap-4 sm:grid-cols-2">@csrf<x-championships.metadata /></form>
                </x-crud-modal>
            </header>
            @if (session('status'))<p role="status" class="rounded-xl border border-pm-success/30 bg-pm-success-subtle p-4 text-sm text-pm-success">{{ session('status') }}</p>@endif
            @if ($errors->any() && !old('_pm_dialog'))<p role="alert" class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">{{ $errors->first() }}</p>@endif
            <form method="GET" class="pm-panel flex flex-wrap items-end gap-3 p-4">
                <label class="grid w-full min-w-0 gap-2 sm:w-auto sm:min-w-56 sm:flex-1"><span class="pm-label">{{ __('championship.search') }}</span><input class="pm-input" name="search" value="{{ request('search') }}" maxlength="120"></label>
                <label class="grid gap-2"><span class="pm-label">{{ __('championship.status') }}</span><select class="pm-input" name="status"><option value="">{{ __('championship.all') }}</option>@foreach (['planned','active','completed','archived'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __('championship.'.($status === 'archived' ? 'archived_filter' : $status)) }}</option>@endforeach</select></label>
                <button class="pm-ghost-button" type="submit">{{ __('championship.filter') }}</button>
            </form>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($championships as $championship)
                    <article class="pm-panel flex min-w-0 flex-col gap-5 p-5" data-championship-card>
                        <div><p class="text-xs font-bold uppercase tracking-wider text-pm-accent">{{ $championship->season }}@if ($championship->category) / {{ $championship->category }}@endif</p><h2 class="mt-2 break-words text-xl font-black text-pm-text">{{ $championship->name }}</h2><p class="mt-2 text-xs text-pm-muted">{{ __('championship.'.$championship->status) }}</p></div>
                        <div class="flex flex-wrap gap-4 text-sm text-pm-text-secondary"><span>{{ $championship->rounds_count }} {{ __('championship.rounds') }}</span><span>{{ $championship->participants_count }} {{ __('championship.participants') }}</span></div>
                        <div class="mt-auto flex flex-wrap gap-2">
                            @if ($championship->trashed())
                                @can('team-write')<form method="POST" action="{{ route('championships.restore', $championship) }}">@csrf @method('PATCH')<button class="pm-ghost-button" type="submit">{{ __('championship.restore') }}</button></form>@endcan
                            @else
                                <a class="pm-race-button" href="{{ route('championships.show', $championship) }}">{{ __('championship.open') }}</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 xl:col-span-3"><x-pitmetric.empty-state :title="__('championship.empty')" :description="__('championship.empty_copy')" /></div>
                @endforelse
            </div>
            {{ $championships->links() }}
        </div>
    </div>
</x-layouts::app>
