@props(['report', 'links' => false])
@php
    $event = $report['event'];
    $summary = $report['summary'];
    $money = fn ($cents) => $cents === null ? __('weekend.unknown') : number_format($cents / 100, 2, ',', '.').' €';
    $documents = $report['documents'];
@endphp
<p class="print-brand">PITMETRIC WEEKEND REPORT</p>
<h2>{{ $event->name }}</h2>
<dl class="print-facts">
    <div><dt>{{ __('prints.circuit') }}</dt><dd>{{ $event->circuitLayout?->circuit?->name ?? '—' }} · {{ $event->circuitLayout?->name ?? '—' }}</dd></div>
    <div><dt>{{ __('maintenance-report.period') }}</dt><dd>{{ $event->start_date->format('d/m/Y') }} · {{ $event->end_date->format('d/m/Y') }}</dd></div>
    <div><dt>{{ __('weekend.sessions') }}</dt><dd>{{ $summary['sessions'] }}</dd></div>
    <div><dt>{{ __('weekend.drafts') }}</dt><dd>{{ $report['draft_count'] }}</dd></div>
    <div><dt>{{ __('prints.recorded_laps') }}</dt><dd>{{ $summary['laps_known'] ? $summary['laps'] : __('weekend.unknown') }} · {{ $summary['laps_known'] }}/{{ $summary['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
    <div><dt>{{ __('weekend.distance') }}</dt><dd>{{ $summary['distance_known'] ? number_format($summary['distance_meters'] / 1000, 3, ',', '.').' km' : __('weekend.unknown') }} · {{ $summary['distance_known'] }}/{{ $summary['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
    <div><dt>{{ __('weekend.runtime') }}</dt><dd>{{ $summary['runtime_known'] ? number_format($summary['duration_seconds'] / 60, 1, ',', '.').' min' : __('weekend.unknown') }} · {{ $summary['runtime_known'] }}/{{ $summary['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
    <div><dt>{{ __('weekend.cost_per_km') }}</dt><dd>{{ $money($report['eur_expense_count'] ? $summary['cost_per_km_cents'] : null) }}</dd></div>
    <div><dt>{{ __('weekend.cost_per_hour') }}</dt><dd>{{ $money($report['eur_expense_count'] ? $summary['cost_per_hour_cents'] : null) }}</dd></div>
    <div><dt>{{ __('weekend.eur_total') }}</dt><dd>{{ $money($report['eur_expense_count'] ? $summary['cost_cents'] : null) }}</dd></div>
</dl>
<p class="print-caption">{{ __('weekend.scope') }}</p>
<p class="print-caption">{{ __('weekend.history') }}</p>
<section><h2>{{ __('weekend.entries') }}</h2>
    <p class="print-caption">{{ __('weekend.best_help') }}</p>
    @forelse ($report['entries'] as $row)
        <section class="print-weekend-entry" data-weekend-entry="{{ $row['entry']->id }}">
            <h3>{{ $row['entry']->driver?->display_name ?? '—' }} · {{ $row['entry']->vehicle?->name ?? '—' }}</h3>
            <dl class="print-facts">
                <div><dt>{{ __('weekend.sessions') }}</dt><dd>{{ $row['sessions'] }}</dd></div>
                <div><dt>{{ __('prints.best_lap') }}</dt><dd>{{ $documents->lapTime($row['best_lap_ms']) }}</dd></div>
                <div><dt>{{ __('weekend.distance') }}</dt><dd>{{ $row['distance_known'] ? number_format($row['distance_meters'] / 1000, 3, ',', '.').' km' : __('weekend.unknown') }} · {{ $row['distance_known'] }}/{{ $row['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
                <div><dt>{{ __('prints.recorded_laps') }}</dt><dd>{{ $row['laps_known'] ? $row['laps'] : __('weekend.unknown') }} · {{ $row['laps_known'] }}/{{ $row['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
                <div><dt>{{ __('weekend.runtime') }}</dt><dd>{{ $row['runtime_known'] ? number_format($row['duration_seconds'] / 60, 1, ',', '.').' min' : __('weekend.unknown') }} · {{ $row['runtime_known'] }}/{{ $row['sessions'] }} {{ __('weekend.coverage') }}</dd></div>
                <div><dt>{{ __('weekend.direct') }}</dt><dd>{{ $money($row['direct_cost_cents']) }}</dd></div>
            </dl>
        </section>
    @empty
        <p>{{ __('weekend.empty') }}</p>
    @endforelse
</section>
<section><h2>{{ __('weekend.chronology') }}</h2>
    @forelse ($report['sessions'] as $row)
        @php($session = $row['session'])
        <section data-weekend-session="{{ $session->id }}">
            <h3>#{{ $session->id }} · {{ $session->started_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }} · {{ $session->session_type }}</h3>
            <p class="{{ $session->status === 'finalized' ? 'print-caption' : 'print-warning' }}">{{ __('weekend.'.($session->status === 'finalized' ? 'finalized' : 'provisional')) }}</p>
            <dl class="print-facts">
                <div><dt>{{ __('prints.driver') }}</dt><dd>{{ $session->eventEntry?->driver?->display_name ?? '—' }}</dd></div>
                <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $session->vehicle?->name ?? '—' }}</dd></div>
                <div><dt>{{ __('weekend.layout') }}</dt><dd>{{ $session->circuitLayout?->name ?? '—' }}</dd></div>
                <div><dt>{{ __('prints.best_lap') }}</dt><dd>{{ $row['best_time'] }} · {{ __('prints.timed_laps') }}: {{ $session->timingLaps->count() }}</dd></div>
                <div><dt>{{ __('prints.recorded_laps') }}</dt><dd>{{ $session->completed_laps ?? '—' }}</dd></div>
                <div><dt>{{ __('weekend.runtime') }}</dt><dd>{{ $row['runtime_seconds'] === null ? __('weekend.unknown') : number_format($row['runtime_seconds'] / 60, 1, ',', '.').' min' }}</dd></div>
                <div><dt>{{ __('weekend.distance') }}</dt><dd>{{ $row['distance_meters'] === null ? __('weekend.unknown') : number_format($row['distance_meters'] / 1000, 3, ',', '.').' km' }} · {{ __('weekend.'.$row['distance_source']) }}</dd></div>
                <div><dt>{{ __('weekend.session_cost') }}</dt><dd>{{ $money($row['cost_cents']) }}</dd></div>
            </dl>
            @if ($links)
                <p><a href="{{ route('sessions.index') }}#session-{{ $session->id }}" target="_blank" rel="noopener">{{ __('weekend.details') }}</a></p>
            @endif
            <h3>{{ __('prints.used_setup') }} · {{ $session->setupSnapshot?->name ?? '—' }}</h3>
            <p class="print-caption">{{ __('prints.snapshot_help') }}</p>
            @if ($session->setupSnapshot)
                <x-prints.setup-values :rows="$row['setup_rows']" />
            @else
                <p>{{ __('prints.no_snapshot') }}</p>
            @endif
            <h3>{{ __('weekend.changes') }}</h3>
            @if ($row['baseline_id'] !== null && $session->setupSnapshot && $session->eventEntry)
                <p class="print-caption">{{ __('weekend.baseline') }}: #{{ $row['baseline_id'] }}</p>
                @if (count($row['changes']))
                    <table><thead><tr><th scope="col">{{ __('prints.parameter') }}</th><th scope="col">{{ __('weekend.before') }}</th><th scope="col">{{ __('weekend.after') }}</th></tr></thead><tbody>
                        @foreach ($row['changes'] as $change)
                            <tr><td>{{ $change['label'] }}</td><td>{{ $change['before'] }}</td><td>{{ $change['after'] }}</td></tr>
                        @endforeach
                    </tbody></table>
                @else
                    <p>{{ __('weekend.unchanged') }}</p>
                @endif
            @else
                <p>{{ __('weekend.no_baseline') }}</p>
            @endif
            @if ($session->vehicle?->category === 'kart')
                <h3>{{ __('prints.titles.tyres') }}</h3>
                <x-prints.tyre-readings :$session />
            @endif
            <h3>{{ __('prints.weather') }}</h3>
            <x-prints.weather-cards :cards="$row['weather']" />
            @if ($session->notes)
                <h3>{{ __('prints.notes') }}</h3><p class="print-note">{{ $session->notes }}</p>
            @endif
        </section>
    @empty
        <p>{{ __('weekend.empty') }}</p>
    @endforelse
</section>
<section><h2>{{ __('weekend.operations') }}</h2>
    <p>{{ __('weekend.done') }}: {{ $report['operations']['tasks_done'] }} · {{ __('weekend.open_tasks') }}: {{ $report['operations']['tasks_open'] }}</p>
    @forelse ($event->tasks->sortBy([['status', 'asc'], ['due_at', 'asc'], ['id', 'asc']]) as $task)
        <section class="print-weekend-entry" data-weekend-task="{{ $task->id }}">
            <h3>#{{ $task->id }} · {{ $task->title }}</h3>
            <dl class="print-facts">
                <div><dt>{{ __('prints.status') }} / {{ __('tasks.priority') }}</dt><dd>{{ __('tasks.'.$task->status) }} · {{ __('tasks.'.$task->priority) }}</dd></div>
                <div><dt>{{ __('tasks.assignee') }}</dt><dd>{{ $task->assignee?->name ?? '—' }}</dd></div>
                <div><dt>{{ __('tasks.due') }}</dt><dd>{{ $task->due_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div><dt>{{ __('weekend.completed_by') }}</dt><dd>{{ $task->completedBy?->name ?? '—' }} · {{ $task->completed_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div><dt>{{ __('prints.driver') }} / {{ __('prints.vehicle') }}</dt><dd>{{ $task->eventEntry?->driver?->display_name ?? '—' }} · {{ $task->eventEntry?->vehicle?->name ?? '—' }}</dd></div>
            </dl>
            @if ($task->description)
                <p class="print-note">{{ $task->description }}</p>
            @endif
        </section>
    @empty
        <p>{{ __('weekend.empty') }}</p>
    @endforelse
</section>
<section><h2>{{ __('weekend.maintenance') }}</h2>
    <p class="print-caption">{{ __('maintenance-report.history') }}</p>
    <p class="print-caption">{{ __('maintenance-report.usage_help') }}</p>
    <p class="print-caption">{{ __('maintenance-report.operator_help') }}</p>
    <x-prints.maintenance-records :records="$report['maintenance']['records']" :historical-vehicles="$report['maintenance']['historicalVehicles']" :report="$report['maintenance']['report']" />
</section>
<section><h2>{{ __('weekend.costs') }}</h2>
    <p class="print-caption">{{ __('weekend.cost_help') }}</p>
    <dl class="print-facts">
        <div><dt>{{ __('weekend.direct') }}</dt><dd>{{ $money($report['direct_cost_cents']) }}</dd></div>
        <div><dt>{{ __('weekend.general') }}</dt><dd>{{ $money($report['general_cost_cents']) }}</dd></div>
        @foreach ($report['currency_totals'] as $currency => $cents)
            <div><dt>{{ __('weekend.recorded') }} · {{ $currency }}</dt><dd>{{ number_format($cents / 100, 2, ',', '.') }} {{ $currency }}</dd></div>
        @endforeach
    </dl>
    <h3>{{ __('weekend.categories') }}</h3>
    <table><thead><tr><th scope="col">{{ __('weekend.categories') }}</th><th scope="col">{{ __('weekend.amount') }}</th></tr></thead><tbody>
        @forelse ($report['expense_categories'] as $category)
            <tr><td>{{ \Illuminate\Support\Facades\Lang::has('weekend.cost_categories.'.$category['category']) ? __('weekend.cost_categories.'.$category['category']) : ucfirst(str_replace('_', ' ', $category['category'])) }} · {{ $category['count'] }}</td><td>{{ $money($category['amount_cents']) }}</td></tr>
        @empty
            <tr><td colspan="2">{{ __('weekend.empty') }}</td></tr>
        @endforelse
    </tbody></table>
    <table><thead><tr><th scope="col">{{ __('prints.measured') }}</th><th scope="col">{{ __('prints.notes') }} / {{ __('weekend.source') }}</th><th scope="col">{{ __('weekend.amount') }}</th></tr></thead><tbody>
        @forelse ($report['expenses'] as $expense)
            <tr data-weekend-expense="{{ $expense->id }}"><td>#{{ $expense->id }} · {{ $expense->occurred_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') }}</td><td>{{ $expense->description }}<br>{{ \Illuminate\Support\Facades\Lang::has('weekend.cost_categories.'.$expense->category) ? __('weekend.cost_categories.'.$expense->category) : ucfirst(str_replace('_', ' ', $expense->category)) }} · {{ $expense->related_type ? __('weekend.linked_operation') : '—' }} #{{ $expense->related_id ?? '—' }}</td><td>{{ number_format($expense->amount_cents / 100, 2, ',', '.') }} {{ $expense->currency }}</td></tr>
        @empty
            <tr><td colspan="3">{{ __('weekend.empty') }}</td></tr>
        @endforelse
    </tbody></table>
</section>
<section><h2>{{ __('weekend.notes') }}</h2>
    @if ($event->notes)
        <p class="print-note">{{ $event->notes }}</p>
    @endif
    @forelse ($event->eventNotes->sortBy([['occurred_at', 'asc'], ['id', 'asc']]) as $note)
        <h3>{{ $note->occurred_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }} · {{ __('weekend.note_kinds.'.$note->kind) }} · {{ $note->eventEntry?->driver?->display_name ?? '—' }}</h3>
        <p class="print-note">{{ $note->body }}</p>
    @empty
        @unless ($event->notes)
            <p>{{ __('weekend.empty') }}</p>
        @endunless
    @endforelse
</section>
