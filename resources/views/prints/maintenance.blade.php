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
    <x-prints.maintenance-records :$records :$historicalVehicles :$report />
</x-prints.layout>
