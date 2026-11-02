@props(['vehicle'])
<dl class="print-facts">
    <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $vehicle?->name ?? '—' }}</dd></div>
    <div><dt>{{ __('garage.fields.identifier') }}</dt><dd>{{ $vehicle?->identifier ?: '—' }}</dd></div>
    <div><dt>{{ __('garage.fields.manufacturer') }} / {{ __('garage.fields.model') }}</dt><dd>{{ trim(($vehicle?->manufacturer ?? '').' '.($vehicle?->model ?? '')) ?: '—' }}</dd></div>
    <div><dt>{{ __('garage.fields.year') }}</dt><dd>{{ $vehicle?->year ?? '—' }}</dd></div>
</dl>
