@props([
    'text',
    'label' => null,
    'position' => 'top',
])

<flux:tooltip toggleable :position="$position">
    <button
        type="button"
        class="inline-flex size-6 shrink-0 items-center justify-center rounded-full border border-pm-border bg-pm-subtle text-xs font-black text-pm-text-secondary transition hover:border-pm-accent/60 hover:text-pm-accent focus:outline-none focus:ring-2 focus:ring-pm-accent/50"
        aria-label="{{ $label ?? __('help.open') }}"
        data-pitmetric-help-tooltip
    >?</button>

    <flux:tooltip.content class="max-w-[20rem] text-sm leading-5">
        {{ $text }}
    </flux:tooltip.content>
</flux:tooltip>
