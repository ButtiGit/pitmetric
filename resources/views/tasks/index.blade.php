<x-layouts::app :title="__('tasks.title')">
    <div class="pitmetric-app min-h-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-5">
            <section class="pm-panel flex flex-wrap items-center justify-between gap-4 p-5 sm:p-7">
                <div><p class="text-xs font-bold uppercase tracking-widest text-pm-accent">{{ $workspace->name }}</p><h1 class="mt-2 text-2xl font-black text-pm-text sm:text-3xl">{{ __('tasks.title') }}</h1><p class="mt-2 text-sm text-pm-text-secondary">{{ __('tasks.subtitle') }}</p></div>
                @if ($events->isNotEmpty())
                    <x-crud-modal id="create-team-task" :title="__('tasks.new')" :trigger="__('tasks.new')">
                        <form method="POST" action="{{ route('tasks.store') }}">@csrf<input type="hidden" name="_pm_dialog" value="create-team-task"><x-pitmetric.event-task-fields :events="$events" :entries="$entries" :members="$members" /></form>
                    </x-crud-modal>
                @else
                    <a class="pm-ghost-button" href="{{ route('events.index') }}">{{ __('tasks.events') }}</a>
                @endif
            </section>
            @if (session('status'))<p role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle p-4 text-sm text-pm-success">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle p-4 text-sm text-pm-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

            <nav class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="{{ __('tasks.title') }}">
                @foreach ($counts as $state => $count)
                    <a href="{{ route('tasks.index', [...$filters, 'bucket' => $state]) }}" @if ($bucket === $state) aria-current="page" @endif @class(['pm-panel min-w-0 p-4 transition hover:border-pm-accent/50', 'border-pm-accent bg-pm-accent/5' => $bucket === $state])>
                        <span class="block text-sm font-bold text-pm-text-secondary">{{ __('tasks.'.$state) }}</span><strong class="mt-2 block text-3xl font-black text-pm-text">{{ $count }}</strong>
                    </a>
                @endforeach
            </nav>
            <p class="text-xs leading-5 text-pm-muted">{{ __('tasks.urgent_help') }}</p>

            <form action="{{ route('tasks.index') }}" method="GET" class="pm-panel grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <input type="hidden" name="bucket" value="{{ $bucket }}">
                <label class="grid gap-2"><span class="pm-label">{{ __('tasks.event') }}</span><select name="event_id" class="pm-input"><option value="">{{ __('tasks.all_events') }}</option>@foreach ($events as $event)<option value="{{ $event->id }}" @selected(($filters['event_id'] ?? '') == $event->id)>{{ $event->name }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ __('tasks.vehicle') }}</span><select name="vehicle_id" class="pm-input"><option value="">{{ __('tasks.all_vehicles') }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected(($filters['vehicle_id'] ?? '') == $vehicle->id)>{{ $vehicle->name }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ __('tasks.search') }}</span><input name="q" class="pm-input" maxlength="120" value="{{ $filters['q'] ?? '' }}"></label>
                <div class="flex flex-wrap items-end gap-3"><label class="flex min-h-11 items-center gap-2 text-sm text-pm-text-secondary"><input type="checkbox" name="mine" value="1" @checked(($filters['mine'] ?? '') === '1')>{{ __('tasks.mine') }}</label><button type="submit" class="pm-race-button">{{ __('tasks.filter') }}</button><a href="{{ route('tasks.index') }}" class="text-sm text-pm-muted underline">{{ __('tasks.reset') }}</a></div>
            </form>

            <x-pitmetric.vehicle-readiness :readiness="$vehicleReadiness" :filters="$filters" />
            <div><a class="pm-ghost-button" href="{{ route('prints.worklist', \Illuminate\Support\Arr::only($filters, ['event_id', 'vehicle_id', 'mine', 'q'])) }}" target="_blank" rel="noopener">{{ __('worklist.open') }}</a><p class="mt-2 text-xs text-pm-muted">{{ __('worklist.all_open') }}</p></div>

            <section class="grid gap-4 lg:grid-cols-2" aria-label="{{ __('tasks.'.$bucket) }}">
                @forelse ($tasks as $task)
                    @php($overdue = $task->status !== 'done' && $task->due_at?->isPast())
                    <article @class(['pm-panel min-w-0 space-y-4 p-5', 'border-pm-danger/40' => $overdue]) data-team-task="{{ $task->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-2"><h2 class="min-w-0 break-words text-lg font-black text-pm-text [overflow-wrap:anywhere]">{{ $task->title }}</h2><x-pitmetric.status-badge :label="__('tasks.'.$task->status)" :variant="$task->status === 'done' ? 'success' : 'neutral'" /></div>
                        <div class="flex flex-wrap gap-2 text-xs font-semibold"><span class="rounded-lg border border-pm-border px-2 py-1 text-pm-text-secondary">{{ __('tasks.priority') }}: {{ __('tasks.'.$task->priority) }}</span>@if ($overdue)<span class="rounded-lg bg-pm-danger-subtle px-2 py-1 text-pm-danger">{{ __('tasks.overdue') }}</span>@endif</div>
                        <div class="space-y-2 text-sm text-pm-text-secondary [overflow-wrap:anywhere]">
                            <a href="{{ route('events.show', $task->event_id) }}" class="block font-semibold text-pm-accent underline underline-offset-4">{{ $task->raceEvent->name }}</a>
                            @if ($task->eventEntry)<p>{{ $task->eventEntry->vehicle?->name }} · {{ $task->eventEntry->driver?->display_name }}</p>@endif
                            <p>{{ __('tasks.assignee') }}: <strong>{{ $task->assignee?->name ?? __('tasks.unassigned') }}</strong></p>
                            <p>{{ $task->due_at ? __('tasks.due').': '.$task->due_at->format('d/m/Y H:i') : __('tasks.no_due') }}</p>
                            @if ($task->description)<p class="whitespace-pre-wrap break-words">{{ $task->description }}</p>@endif
                            @if ($task->completed_at)<p class="text-pm-success">{{ __('tasks.completed', ['name' => $task->completedBy?->name ?? __('tasks.unknown'), 'date' => $task->completed_at->format('d/m/Y H:i')]) }}</p>@endif
                        </div>
                        @can('update', $task)
                            <div class="flex flex-wrap gap-2 border-t border-pm-border pt-4">
                                @foreach ($task->status === 'done' ? ['todo' => 'reopen'] : ($task->status === 'todo' ? ['in_progress' => 'start', 'done' => 'complete'] : ['done' => 'complete']) as $target => $label)
                                    <form method="POST" action="{{ route('tasks.status', $task) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $target }}"><input type="hidden" name="expected_status" value="{{ $task->status }}"><button class="{{ $target === 'done' ? 'pm-race-button' : 'pm-ghost-button' }}" type="submit">{{ __('tasks.'.$label) }}</button></form>
                                @endforeach
                                <x-crud-modal :id="'edit-team-task-'.$task->id" :title="__('tasks.edit')" :trigger="__('tasks.edit')" trigger-class="pm-ghost-button">
                                    <form method="POST" action="{{ route('tasks.update', $task) }}">@csrf @method('PUT')<input type="hidden" name="_pm_dialog" value="edit-team-task-{{ $task->id }}"><x-pitmetric.event-task-fields :events="$events" :entries="$entries" :members="$members" :task="$task" /></form>
                                </x-crud-modal>
                            </div>
                        @endcan
                        <details class="border-t border-pm-border pt-3 text-xs text-pm-muted"><summary class="min-h-10 cursor-pointer font-semibold">{{ __('tasks.history') }}</summary><ol class="space-y-2">@foreach ($taskHistory->get($task->id, collect())->take(5) as $log)<li>{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->user?->name ?? __('tasks.unknown') }} · {{ $log->action === 'created' ? __('tasks.created') : (isset($log->after_values['status']) ? __('tasks.'.$log->after_values['status']) : __('tasks.updated')) }}</li>@endforeach</ol></details>
                    </article>
                @empty
                    <div class="pm-panel p-8 text-center lg:col-span-2"><h2 class="font-black text-pm-text">{{ __('tasks.empty') }}</h2><p class="mt-2 text-sm text-pm-text-secondary">{{ $events->isEmpty() ? __('tasks.need_event') : __('tasks.empty_help') }}</p></div>
                @endforelse
            </section>
            {{ $tasks->links() }}
            <section class="pm-panel flex flex-wrap items-center justify-between gap-4 p-5"><div><h2 class="font-black text-pm-text">{{ __('tasks.maintenance') }}</h2><p class="mt-2 text-sm text-pm-text-secondary">{{ __('tasks.maintenance_help', $maintenance) }}</p></div><a class="pm-ghost-button" href="{{ route('maintenance.index') }}">{{ __('tasks.maintenance_open') }}</a></section>
        </div>
    </div>
</x-layouts::app>
