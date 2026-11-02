@props(['session'])
<p class="print-caption">{{ __('prints.pressures_help') }}</p>
@if ($session->tyreReadings->isNotEmpty())
<table class="print-pressures">
    <thead><tr><th scope="col">{{ __('prints.measured') }}</th><th scope="col">{{ __('prints.phase') }}</th>
    @foreach (['fl', 'fr', 'rl', 'rr'] as $corner)<th scope="col">{{ __('prints.corners.'.$corner) }}</th>
    @endforeach<th scope="col">{{ __('prints.notes') }}</th></tr></thead>
    <tbody>
    @foreach ($session->tyreReadings->sortBy([['measured_at', 'asc'], ['id', 'asc']]) as $reading)
        <tr data-print-reading="{{ $reading->id }}">
            <td>{{ $reading->measured_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}<br><span class="print-caption">{{ $reading->creator?->name ?? '—' }}</span></td>
            <td>{{ __('prints.'.$reading->phase) }}</td>
            @foreach (['fl', 'fr', 'rl', 'rr'] as $corner)<td>{{ isset($reading->pressures[$corner]) ? number_format((float) $reading->pressures[$corner], 2, app()->getLocale() === 'it' ? ',' : '.', '') : '—' }}</td>
    @endforeach
            <td class="print-note">{{ $reading->notes ?: '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<p>{{ __('prints.no_pressures') }}</p>
@endif
