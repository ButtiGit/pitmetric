@props([
    'title',
    'description',
])

<section
    {{ $attributes
        ->class(['mx-auto flex w-full max-w-2xl flex-col items-center rounded-xl border border-pm-border bg-pm-elevated px-4 py-8 text-center sm:px-6'])
        ->merge(['data-pitmetric-component' => 'empty-state']) }}
>
    @isset($icon)
        <div aria-hidden="true" class="mb-4 shrink-0 text-pm-text-secondary" data-pitmetric-empty-state-icon>
            {{ $icon }}
        </div>
    @endisset

    <div class="min-w-0">
        <h2 class="break-words text-lg font-semibold text-pm-text">{{ $title }}</h2>
        <p class="mt-2 break-words text-sm leading-6 text-pm-text-secondary">{{ $description }}</p>
    </div>

    @isset($actions)
        <div class="mt-5 flex w-full flex-col justify-center gap-2 sm:w-auto sm:flex-row sm:flex-wrap" data-pitmetric-empty-state-actions>
            {{ $actions }}
        </div>
    @endisset
</section>
