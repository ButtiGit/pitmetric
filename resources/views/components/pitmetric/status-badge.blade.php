@props([
    'label' => null,
    'status' => null,
    'variant' => 'neutral',
    'symbol' => null,
])

@php
    $statusVariant = match ($status) {
        'active', 'completed', 'done', 'healthy', 'available' => 'success',
        'in_progress', 'warning' => 'warning',
        'blocked', 'overdue', 'failed' => 'danger',
        'planned', 'pending', 'todo' => 'info',
        default => 'neutral',
    };

    $requestedVariant = $variant === 'neutral' && $status !== null ? $statusVariant : $variant;
    $badgeVariant = in_array($requestedVariant, ['neutral', 'success', 'warning', 'danger', 'info'], true)
        ? $requestedVariant
        : 'neutral';
    $resolvedLabel = $label ?? ($status !== null ? ucwords(str_replace('_', ' ', $status)) : '—');
    $hasSymbol = $symbol !== null && $symbol !== '';

    $variantClasses = [
        'neutral' => 'border-pm-border-strong bg-pm-subtle text-pm-text-secondary',
        'success' => 'border-pm-success bg-pm-success-subtle text-pm-success',
        'warning' => 'border-pm-warning bg-pm-warning-subtle text-pm-warning',
        'danger' => 'border-pm-danger bg-pm-danger-subtle text-pm-danger',
        'info' => 'border-pm-info bg-pm-info-subtle text-pm-info',
    ];

    $defaultSymbols = [
        'neutral' => '-',
        'success' => '+',
        'warning' => '!',
        'danger' => 'x',
        'info' => 'i',
    ];
@endphp

<span
    {{ $attributes
        ->class(['inline-flex max-w-full items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-semibold leading-4', $variantClasses[$badgeVariant]])
        ->merge([
            'data-pitmetric-component' => 'status-badge',
            'data-pitmetric-variant' => $badgeVariant,
        ]) }}
>
    <span aria-hidden="true" class="shrink-0 font-mono font-bold" data-pitmetric-status-indicator>
        {{ $hasSymbol ? $symbol : $defaultSymbols[$badgeVariant] }}
    </span>

    <span class="min-w-0 break-words">{{ $resolvedLabel }}</span>
</span>
