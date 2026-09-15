@props([
    'id',
    'title',
    'description' => null,
    'trigger' => null,
    'triggerClass' => 'pm-race-button',
    'size' => 'max-w-3xl',
])

@if ($trigger)
    <button type="button" class="{{ $triggerClass }}" onclick="document.getElementById(@js($id)).showModal()">
        {{ $trigger }}
    </button>
@endif

<dialog id="{{ $id }}" class="w-[min(94vw,72rem)] {{ $size }} rounded-2xl border border-pm-border bg-pm-panel p-0 text-pm-text shadow-2xl backdrop:bg-black/70">
    <div class="max-h-[88vh] overflow-y-auto p-5 sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-pm-text sm:text-xl">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $description }}</p>
                @endif
            </div>
            <button type="button" class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle text-lg text-pm-muted transition hover:text-pm-text" onclick="document.getElementById(@js($id)).close()" aria-label="{{ __('Close') }}">×</button>
        </div>

        {{ $slot }}
    </div>
</dialog>
