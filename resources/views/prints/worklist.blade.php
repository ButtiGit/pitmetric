<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-slot:actions>
        <form method="GET" action="{{ route('prints.worklist') }}">
            @foreach (['event_id', 'vehicle_id', 'q'] as $key)
                @if (!empty($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
            @endforeach
            <label>{{ __('tasks.assignee') }} <select name="assigned_to"><option value="">{{ __('worklist.all') }}</option>
                @foreach ($members as $member)<option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>@endforeach
            </select></label>
            <button type="submit">{{ __('tasks.filter') }}</button>
        </form>
    </x-slot:actions>
    <p>{{ __('worklist.all_open') }}</p>
    <dl class="print-facts">
        <div><dt>{{ __('prints.event') }}</dt><dd>{{ $eventLabel ?? __('worklist.all') }}</dd></div>
        <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $vehicleLabel ?? __('worklist.all') }}</dd></div>
        <div><dt>{{ __('tasks.assignee') }}</dt><dd>{{ $assigneeLabel ?? __('worklist.all') }}</dd></div>
        <div><dt>{{ __('tasks.search') }}</dt><dd>{{ $filters['q'] ?? '—' }}</dd></div>
    </dl>
    <p class="print-caption">{{ __('worklist.maintenance_scope') }}</p>
    <p>{{ __('worklist.total') }}: {{ $rows->count() }}</p>
    @forelse ($groups as $rank => $items)
        <section><h2>{{ __('worklist.groups.'.$rank) }} · {{ $items->count() }}</h2>
            <table class="print-worklist"><thead><tr><th scope="col">{{ __('worklist.work') }}</th><th scope="col">{{ __('worklist.context') }}</th></tr></thead><tbody>
                @foreach ($items as $row)
                    <tr data-work-row="{{ $row['source'] }}:{{ $row['id'] }}"><td>
                        <p><span class="print-check-box" aria-hidden="true"></span><strong>{{ $row['title'] }}</strong></p>
                        <p class="print-caption">{{ __('worklist.sources.'.$row['source']) }} #{{ $row['id'] }} · {{ __('tasks.'.$row['priority']) }} · {{ $row['source'] === 'maintenance' ? __('readiness.order_'.$row['status']) : __('tasks.'.$row['status']) }}</p>
                        @if ($row['overdue'])<strong>{{ __('tasks.overdue') }}</strong>@endif
                        @if ($row['notes'])<p class="print-note">{{ $row['notes'] }}</p>@endif
                    </td><td>
                        <p>{{ __('prints.vehicle') }}: {{ $row['vehicle'] ?? __('worklist.no_vehicle') }}</p>
                        @if ($row['component'])<p>{{ __('prints.component') }}: {{ $row['component'] }}</p>@endif
                        <p>{{ __('prints.event') }}: {{ $row['event'] ?? __('worklist.no_event') }}</p>
                        <p>{{ __('tasks.assignee') }}: {{ $row['assignee'] ?? __('tasks.unassigned') }}</p>
                        <p>{{ __('tasks.due') }}: {{ $row['due_at']?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? __('tasks.no_due') }}</p>
                        <p class="print-caption">{{ __('worklist.operator') }}: ______________</p>
                    </td></tr>
                @endforeach
            </tbody></table>
        </section>
    @empty
        <p>{{ __('worklist.empty') }}</p>
    @endforelse
    <p class="print-caption">{{ __('worklist.manual') }}</p>
</x-prints.layout>
