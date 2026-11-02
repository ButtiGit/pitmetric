@props(['tasks'])
<table><thead><tr><th scope="col">{{ __('checklist.task') }}</th><th scope="col">{{ __('checklist.responsibility') }}</th></tr></thead><tbody>
    @forelse ($tasks as $task)
        <tr data-checklist-task="{{ $task->id }}"><td>{{ $task->title }} · {{ __('tasks.'.$task->priority) }} · {{ __('tasks.'.$task->status) }}
            @if ($task->due_at?->isPast())<strong>{{ __('tasks.overdue') }}</strong>@endif
            @if ($task->description)<p class="print-note">{{ $task->description }}</p>@endif
        </td><td>{{ $task->assignee?->name ?? __('tasks.unassigned') }}<br>{{ $task->due_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}<br>{{ $task->eventEntry?->driver?->display_name ?? '' }}</td></tr>
    @empty
        <tr><td colspan="2">{{ __('checklist.no_tasks') }}</td></tr>
    @endforelse
</tbody></table>
