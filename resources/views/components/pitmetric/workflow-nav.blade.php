@php
    $steps = ['garage', 'components', 'configurations', 'setups', 'circuits', 'events', 'sessions', 'maintenance', 'expenses'];
@endphp
@if (request()->routeIs(...array_map(fn ($step) => $step.'.*', $steps)))
    <nav class="pm-workflow-nav" aria-label="{{ __('workflow.navigation') }}">
        @foreach ($steps as $step)
            <a href="{{ route($step.'.index') }}" wire:navigate @if (request()->routeIs($step.'.*')) aria-current="page" @endif>{{ __('workflow.'.$step) }}</a>
        @endforeach
    </nav>
    @cannot('team-write')
        <p class="mx-4 my-3 rounded-lg border border-pm-border bg-pm-subtle p-3 text-sm text-pm-muted" role="status">{{ __('workflow.read_only') }}</p>
    @endcannot
@endif
