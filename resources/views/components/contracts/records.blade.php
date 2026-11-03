@props(['contract', 'editable' => false])
<section><h2 class="mb-3 text-lg font-black">{{ __('contracts.participations') }}</h2>
    <p class="mb-3 text-sm">{{ __('contracts.no_automatic') }}</p>
    @forelse ($contract->participations->sortBy([['event_id','asc'],['id','asc']]) as $row)
        <article class="pm-panel mb-3 space-y-3 p-4 [overflow-wrap:anywhere] print-contract-record" data-contract-participation="{{ $row->id }}">
            <h3 class="font-bold">{{ $row->raceEvent?->name ?? '—' }} · {{ $row->round?->name ?? '—' }}</h3>
            <p class="text-sm">{{ $row->raceEvent?->start_date?->format('d/m/Y') }} · {{ $row->raceEvent?->end_date?->format('d/m/Y') }}</p>
            <dl class="grid gap-3 text-sm sm:grid-cols-2 print-facts">
                <div><dt>{{ __('prints.status') }}</dt><dd>{{ __('contracts.states.'.$row->status) }}</dd></div>
                <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $row->vehicle?->name ?? '—' }}@if ($row->vehicle_id !== $contract->vehicle_id) · {{ __('contracts.substitute') }}@endif</dd></div>
                <div><dt>{{ __('contracts.actor') }}</dt><dd>{{ $row->confirmed_name ?? '—' }}</dd></div>
                <div><dt>{{ __('prints.measured') }}</dt><dd>{{ $row->confirmed_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</dd></div>
            </dl>
            @if ($row->status === 'confirmed' && $row->raceEvent?->status === 'cancelled')<p class="font-bold">{{ __('contracts.event_cancelled') }}</p>@endif
            @if ($row->notes)<p class="whitespace-pre-wrap print-note">{{ $row->notes }}</p>@endif
            @if ($editable)
                <a class="pm-ghost-button" href="{{ route('events.show', $row->event_id) }}">{{ __('prints.event') }}</a>
                <x-contracts.participation-edit :participation="$row" />
            @endif
        </article>
    @empty
        <p>{{ __('contracts.empty_history') }}</p>
    @endforelse
</section>
<section><h2 class="mb-3 text-lg font-black">{{ __('contracts.extensions') }}</h2>
    @forelse ($contract->extensions->sortBy('id') as $extension)
        <article class="pm-panel mb-3 space-y-2 p-4 print-contract-record">
            <h3 class="font-bold">+{{ $extension->added_races }} · {{ $extension->ends_on?->format('d/m/Y') }}</h3>
            <p>{{ $extension->actor_name }} · {{ $extension->created_at->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') }}</p>
            <p class="whitespace-pre-wrap print-note">{{ $extension->reason }}</p>
        </article>
    @empty
        <p>{{ __('contracts.empty_history') }}</p>
    @endforelse
</section>
<section><h2 class="mb-3 text-lg font-black">{{ __('contracts.history') }}</h2>
    @foreach ($contract->changes->sortBy('id') as $change)
        <article class="pm-panel mb-3 space-y-2 p-4 [overflow-wrap:anywhere] print-contract-record" data-contract-change="{{ $change->id }}">
            <h3 class="font-bold">{{ $change->created_at->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') }} · {{ __('contracts.kinds.'.$change->kind) }}</h3>
            <p>{{ $change->actor_name }} · {{ __('contracts.delta') }}: {{ $change->delta > 0 ? '+' : '' }}{{ $change->delta }}</p>
            @if ($change->participation)<p>{{ $change->participation->raceEvent?->name }}</p>@endif
            @if (isset($change->before_values['status']))<p>{{ __('contracts.before') }}: {{ __('contracts.states.'.$change->before_values['status']) }}</p>@endif
            @if (isset($change->after_values['status']))<p>{{ __('contracts.after') }}: {{ __('contracts.states.'.$change->after_values['status']) }}</p>@endif
            @if (isset($change->after_values['added_races']))<p>{{ __('contracts.added') }}: {{ $change->after_values['added_races'] }}</p>@endif
            <p class="whitespace-pre-wrap print-note">{{ $change->reason }}</p>
        </article>
    @endforeach
</section>
