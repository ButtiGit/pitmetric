<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main data-pm-workspace class="pitmetric-app min-w-0 overflow-x-hidden">
        <x-pitmetric.workflow-nav />
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
