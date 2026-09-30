<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main data-pm-workspace class="pitmetric-app pm-mobile-shell-main min-w-0 overflow-x-hidden">
        <x-pitmetric.workspace-bar :title="$title ?? null">
            <x-pitmetric.follow-up-bell />
        </x-pitmetric.workspace-bar>
        @if (request()->routeIs('garage.*', 'components.*', 'component-installations.*', 'configurations.*', 'events.*', 'sessions.*', 'maintenance.*'))
            @cannot('team-write')
                <p class="mx-4 mt-4 rounded-xl border border-pm-border bg-pm-subtle p-3 text-sm text-pm-text-secondary sm:mx-6 lg:mx-8" role="status">{{ __('workflow.read_only') }}</p>
            @endcannot
        @endif
        <x-pitmetric.track-capture-feed />
        <x-pitmetric.mobile-dashboard />
        {{ $slot }}
        <x-pitmetric.quick-capture />
        <x-pitmetric.mobile-navigation />
    </flux:main>
</x-layouts::app.sidebar>
