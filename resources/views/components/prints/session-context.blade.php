@props(['session'])
<dl class="print-facts">
    <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $session->vehicle?->name ?? '—' }}</dd></div>
    <div><dt>{{ __('prints.driver') }}</dt><dd>{{ $session->eventEntry?->driver?->display_name ?? '—' }}</dd></div>
    <div><dt>{{ __('prints.event') }}</dt><dd>{{ $session->raceEvent?->name ?? '—' }}</dd></div>
    <div><dt>{{ __('prints.started') }}</dt><dd>{{ $session->started_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</dd></div>
    <div><dt>{{ __('prints.circuit') }}</dt><dd>{{ ($session->circuitLayout ?? $session->raceEvent?->circuitLayout)?->circuit?->name ?? '—' }} / {{ ($session->circuitLayout ?? $session->raceEvent?->circuitLayout)?->name ?? '—' }}</dd></div>
    <div><dt>{{ __('prints.configuration') }}</dt><dd>{{ $session->configurationVersion?->configuration?->name ?? '—' }} @if ($session->configurationVersion) · v{{ $session->configurationVersion->version_number }} @endif</dd></div>
    <div><dt>{{ __('prints.type') }}</dt><dd>{{ __('prints.session_types.'.$session->session_type) }}</dd></div>
    <div><dt>{{ __('prints.status') }}</dt><dd>{{ __('prints.statuses.'.$session->status) }}</dd></div>
</dl>
