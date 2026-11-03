@props(['records', 'historicalVehicles', 'report'])
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
