@props(['total', 'consumed', 'remaining'])
<dl class="grid gap-3 sm:grid-cols-3">
    @foreach (['total' => $total, 'consumed' => $consumed, 'remaining' => $remaining] as $key => $value)
        <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><dt class="text-xs text-pm-muted">{{ __('contracts.'.$key) }}</dt><dd class="mt-1 text-xl font-black text-pm-text" data-contract-count="{{ $key }}">{{ $value }}</dd></div>
    @endforeach
</dl>
<progress class="mt-3 h-3 w-full accent-[#FF5A36]" value="{{ min($consumed, $total) }}" max="{{ $total }}" aria-label="{{ __('contracts.consumed') }}">{{ $consumed }}/{{ $total }}</progress>
@if ($remaining === 0)
    <p class="mt-2 text-sm font-bold text-pm-warning">{{ __('contracts.exhausted') }}</p>
@elseif ($remaining <= 2)
    <p class="mt-2 text-sm font-bold text-pm-warning">{{ __('contracts.near') }}</p>
@endif
