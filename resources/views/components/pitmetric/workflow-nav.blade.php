@php
    $steps = ['garage', 'components', 'configurations', 'setups', 'circuits', 'events', 'sessions', 'maintenance', 'expenses'];
@endphp
@if (request()->routeIs(...array_map(fn ($step) => $step.'.*', $steps)))
    @cannot('team-write')
        <p class="mx-4 my-3 rounded-lg border border-pm-border bg-pm-subtle p-3 text-sm text-pm-muted" role="status">{{ __('workflow.read_only') }}</p>
    @endcannot
@endif
