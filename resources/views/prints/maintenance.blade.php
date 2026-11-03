<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-slot:actions>
        <form method="GET" action="{{ route('prints.maintenance') }}" class="print-maintenance-filters">
            @foreach (['from', 'to'] as $key)
                <label>{{ __('maintenance-report.'.$key) }}<input type="date" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"></label>
            @endforeach
            @foreach (['component_id' => $components, 'vehicle_id' => $vehicles, 'event_id' => $events] as $key => $options)
                <label>{{ __($key === 'component_id' ? 'maintenance-report.component' : ($key === 'vehicle_id' ? 'prints.vehicle' : 'prints.event')) }}
                    <select name="{{ $key }}"><option value="">{{ __('maintenance-report.all') }}</option>
                        @foreach ($options as $option)
                            <option value="{{ $option->id }}" @selected(($filters[$key] ?? '') == $option->id)>{{ $option->name }} #{{ $option->id }}{{ $key === 'component_id' && $option->serial_number ? ' · '.$option->serial_number : '' }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <button type="submit">{{ __('maintenance-report.filter') }}</button>
        </form>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
    </x-slot:actions>
    <p>{{ __('maintenance-report.scope') }}</p>
    <dl class="print-facts">
        <div><dt>{{ __('maintenance-report.period') }}</dt><dd>{{ __('maintenance-report.from') }} {{ $filters['from'] ?? '—' }} · {{ __('maintenance-report.to') }} {{ $filters['to'] ?? '—' }}</dd></div>
        <div><dt>{{ __('maintenance-report.component') }}</dt><dd>{{ $components->firstWhere('id', $filters['component_id'] ?? null)?->name ?? __('maintenance-report.all') }}</dd></div>
        <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $vehicles->firstWhere('id', $filters['vehicle_id'] ?? null)?->name ?? __('maintenance-report.all') }}</dd></div>
        <div><dt>{{ __('prints.event') }}</dt><dd>{{ $events->firstWhere('id', $filters['event_id'] ?? null)?->name ?? __('maintenance-report.all') }}</dd></div>
        <div><dt>{{ __('maintenance-report.total') }}</dt><dd>{{ $records->count() }}</dd></div>
        <div><dt>{{ __('maintenance-report.cost_total') }}</dt><dd>{{ $costCount ? '€ '.number_format($costTotal / 100, 2, ',', '.') : __('maintenance-report.unknown') }} · {{ $costCount }}/{{ $records->count() }} {{ __('maintenance-report.cost_coverage') }}</dd></div>
    </dl>
    @foreach (['history', 'usage_help', 'cost_help', 'operator_help'] as $key)
        <p class="print-caption">{{ __('maintenance-report.'.$key) }}</p>
    @endforeach
    @forelse ($records as $record)
        @php($metric = $record->schedule?->tracker?->metric)
        <section class="print-maintenance-record" data-maintenance-record="{{ $record->id }}">
            <h2>#{{ $record->id }} · {{ $record->component?->name ?? __('maintenance-report.unknown') }} · {{ $record->performed_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</h2>
            <dl class="print-facts">
                <div><dt>{{ __('maintenance-report.component') }}</dt><dd>{{ $record->component?->name ?? __('maintenance-report.unknown') }} · {{ $record->component?->serial_number ?? '—' }}</dd></div>
                <div><dt>{{ __('maintenance-report.vehicle_at_time') }}</dt><dd>{{ $historicalVehicles->get($record->report_vehicle_id)?->name ?? __('maintenance-report.unknown') }}</dd></div>
                <div><dt>{{ __('prints.event') }}</dt><dd>{{ $record->raceEvent?->name ?? __('maintenance-report.unknown') }}</dd></div>
                <div><dt>{{ __('maintenance-report.author') }}</dt><dd>{{ $record->creator?->name ?? __('maintenance-report.unknown') }}</dd></div>
                <div><dt>{{ __('maintenance-report.lifetime') }}</dt><dd>{{ $report->usage($record->report_lifetime, $metric) }}</dd></div>
                <div><dt>{{ __('maintenance-report.since_service') }}</dt><dd>{{ $report->usage($record->report_lifetime === null || $record->report_ambiguous_reset > 0 ? null : ($record->report_since_service ?? 0), $metric) }}</dd></div>
                <div><dt>{{ __('maintenance-report.cost') }}</dt><dd>{{ $record->cost_cents === null ? __('maintenance-report.unknown') : '€ '.number_format($record->cost_cents / 100, 2, ',', '.') }}</dd></div>
            </dl>
            <h3>{{ __('maintenance-report.work') }}</h3>
            <p class="print-note">{{ $record->description }}</p>
            @if ($record->notes)
                <p class="print-note">{{ $record->notes }}</p>
            @endif
            @if ($record->workOrders->isNotEmpty())
                <h3>{{ __('maintenance-report.orders') }}</h3>
                @foreach ($record->workOrders as $order)
                    <p>#{{ $order->id }} · {{ $order->title }}</p>
                    @if ($order->notes)
                        <p class="print-note">{{ $order->notes }}</p>
                    @endif
                @endforeach
            @endif
            <h3>{{ __('maintenance-report.resets') }}</h3>
            @forelse ($record->resetEvents as $reset)
                <p>{{ $reset->tracker?->metric?->name ?? __('maintenance-report.unknown') }} · {{ $reset->reset_at->timezone('Europe/Rome')->format('d/m/Y H:i') }} · {{ $reset->reason }}</p>
            @empty
                <p>{{ __('maintenance-report.no_reset') }}</p>
            @endforelse
        </section>
    @empty
        <p>{{ __('maintenance-report.empty') }}</p>
    @endforelse
</x-prints.layout>
