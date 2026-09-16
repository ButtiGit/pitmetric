@props([
    'title',
    'description' => null,
    'eyebrow' => null,
    'help' => null,
    'headingLevel' => 1,
])

@php
    $headingLevel = in_array((int) $headingLevel, [1, 2, 3], true) ? (int) $headingLevel : 1;
    $headingClasses = 'break-words text-2xl font-semibold tracking-tight text-pm-text sm:text-3xl';
@endphp

<header
    {{ $attributes
        ->class(['flex min-w-0 flex-col gap-4 border-b border-pm-border pb-4 lg:flex-row lg:items-start lg:justify-between'])
        ->merge(['data-pitmetric-component' => 'page-header']) }}
>
    <div class="min-w-0 flex-1">
        @isset($breadcrumbs)
            <div class="mb-2 min-w-0 text-sm text-pm-text-secondary" data-pitmetric-page-header-breadcrumbs>
                {{ $breadcrumbs }}
            </div>
        @endisset

        @if (filled($eyebrow))
            <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-pm-accent">{{ $eyebrow }}</p>
        @endif

        <div class="flex min-w-0 items-center gap-2">
            @if ($headingLevel === 1)
                <h1 class="{{ $headingClasses }}">{{ $title }}</h1>
            @elseif ($headingLevel === 2)
                <h2 class="{{ $headingClasses }}">{{ $title }}</h2>
            @else
                <h3 class="{{ $headingClasses }}">{{ $title }}</h3>
            @endif

            @if (filled($help))
                <x-pitmetric.help-tooltip :text="$help" />
            @endif
        </div>

        @if (filled($description))
            <p class="mt-2 max-w-3xl break-words text-sm leading-6 text-pm-text-secondary sm:text-base">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full min-w-0 flex-col gap-2 sm:flex-row sm:flex-wrap lg:w-auto lg:max-w-[50%] lg:justify-end" data-pitmetric-page-header-actions>
            {{ $actions }}
        </div>
    @endisset
</header>
