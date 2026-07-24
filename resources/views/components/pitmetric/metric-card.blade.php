@props([
    'label',
    'value',
    'supportingText' => null,
    'variant' => 'neutral',
])

@php
    $metricVariant = in_array($variant, ['neutral', 'success', 'warning', 'danger', 'info'], true) ? $variant : 'neutral';

    $variantClasses = [
        'neutral' => 'border-pm-border bg-pm-elevated',
        'success' => 'border-pm-success bg-pm-success-subtle',
        'warning' => 'border-pm-warning bg-pm-warning-subtle',
        'danger' => 'border-pm-danger bg-pm-danger-subtle',
        'info' => 'border-pm-info bg-pm-info-subtle',
    ];

    $stateLabels = [
        'success' => 'Healthy',
        'warning' => 'Attention',
        'danger' => 'Action required',
        'info' => 'Information',
    ];

    $stateTextClasses = [
        'success' => 'text-pm-success',
        'warning' => 'text-pm-warning',
        'danger' => 'text-pm-danger',
        'info' => 'text-pm-info',
    ];
@endphp

<article
    {{ $attributes
        ->class(['flex min-w-0 flex-col rounded-xl border p-4', $variantClasses[$metricVariant]])
        ->merge([
            'data-pitmetric-component' => 'metric-card',
            'data-pitmetric-variant' => $metricVariant,
        ]) }}
>
    <div class="flex min-w-0 items-start justify-between gap-3">
        <p class="min-w-0 break-words text-sm font-medium text-pm-text-secondary">{{ $label }}</p>

        @isset($icon)
            <span aria-hidden="true" class="shrink-0 text-pm-text-secondary" data-pitmetric-metric-icon>
                {{ $icon }}
            </span>
        @endisset
    </div>

    <p class="mt-3 break-words text-3xl font-semibold tracking-tight text-pm-text tabular-nums">{{ $value }}</p>

    @if ($metricVariant !== 'neutral' || filled($supportingText))
        <div class="mt-3 grid gap-1">
            @if ($metricVariant !== 'neutral')
                <p class="text-sm font-semibold {{ $stateTextClasses[$metricVariant] }}" data-pitmetric-metric-state>
                    {{ $stateLabels[$metricVariant] }}
                </p>
            @endif

            @if (filled($supportingText))
                <p class="break-words text-sm leading-5 text-pm-text-secondary" data-pitmetric-metric-support>{{ $supportingText }}</p>
            @endif
        </div>
    @endif
</article>
