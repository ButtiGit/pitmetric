<x-layouts::app.sidebar_demo :title="$title ?? null" :section="$section ?? 'dashboard'">
    <flux:main data-pm-workspace class="pitmetric-app pm-mobile-shell-main min-w-0 overflow-x-hidden">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar_demo>
