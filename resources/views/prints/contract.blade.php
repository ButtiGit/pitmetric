<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <dl class="print-facts">
        <div><dt>{{ __('prints.driver') }}</dt><dd>{{ $contract->driver?->display_name }}</dd></div>
        <div><dt>{{ __('contracts.primary') }}</dt><dd>{{ $contract->vehicle?->name }}</dd></div>
        <div><dt>{{ __('contracts.season') }}</dt><dd>{{ $contract->season }} · {{ $contract->championship?->name ?? __('contracts.no_championship') }}</dd></div>
        <div><dt>{{ __('prints.status') }}</dt><dd>{{ __('contracts.states.'.$contract->status) }}</dd></div>
        <div><dt>{{ __('contracts.start') }} / {{ __('contracts.end') }}</dt><dd>{{ $contract->starts_on->format('d/m/Y') }} · {{ $contract->ends_on->format('d/m/Y') }}</dd></div>
        <div><dt>{{ __('contracts.initial') }}</dt><dd>{{ $contract->initial_races }}</dd></div>
        <div><dt>{{ __('contracts.total') }}</dt><dd>{{ $total }}</dd></div>
        <div><dt>{{ __('contracts.consumed') }} / {{ __('contracts.remaining') }}</dt><dd>{{ $consumed }} / {{ $remaining }}</dd></div>
    </dl>
    <p>{{ __('contracts.rule') }}</p>
    <p>{{ __('contracts.terms') }}</p>
    @if ($contract->notes)<p class="print-note">{{ $contract->notes }}</p>@endif
    <x-contracts.records :$contract />
</x-prints.layout>
