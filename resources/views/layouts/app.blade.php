<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main data-pm-workspace class="pitmetric-app pm-mobile-shell-main min-w-0 overflow-x-hidden">
        <x-pitmetric.workflow-nav />
        <x-pitmetric.follow-up-bell />
        <x-pitmetric.track-capture-feed />
        <x-pitmetric.mobile-dashboard />
        {{ $slot }}
        <x-pitmetric.quick-capture />
        <x-pitmetric.mobile-navigation />
    </flux:main>
</x-layouts::app.sidebar>
