<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-w-0 overflow-x-hidden">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
