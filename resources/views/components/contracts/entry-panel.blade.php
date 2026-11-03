@props(['entry','contracts','participation','rounds'])
@php($candidates = $contracts->where('driver_id', $entry->driver_id))
<div class="mt-4 min-w-0 space-y-3 rounded-xl border border-pm-border p-3 [overflow-wrap:anywhere]" data-entry-contract="{{ $entry->id }}">
    @if ($participation)
        @php($c = $participation->contract)
        @php($total = $c->initial_races + (int) $c->extra_races)
        <a class="text-sm font-bold text-pm-accent underline" href="{{ route('contracts.show', $c) }}">{{ __('contracts.title') }} #{{ $c->id }} · {{ $c->season }}</a>
        <p class="text-sm text-pm-text">{{ __('contracts.states.'.$participation->status) }} · {{ __('contracts.remaining') }}: {{ max(0, $total - (int) $c->consumed) }}/{{ $total }}</p>
        @if ($c->consumed >= $total)<p class="text-sm text-pm-warning">{{ __('contracts.exhausted') }}</p>@endif
        @if ((int) $participation->vehicle_id !== (int) $c->vehicle_id)<p class="text-sm text-pm-warning">{{ __('contracts.substitute') }} · {{ $participation->notes }}</p>@endif
        <x-contracts.participation-edit :$participation />
    @else
        <p class="text-sm text-pm-muted">{{ __('contracts.none') }}</p>
    @endif
    @if (!$participation || $participation->status !== 'confirmed')
        @can('update', $entry)
            <x-crud-modal id="entry-contract-{{ $entry->id }}" :title="__('contracts.attach')" :trigger="__('contracts.attach')" trigger-class="pm-ghost-button">
                <form method="POST" action="{{ route('contracts.attach', $entry) }}" class="grid gap-4">@csrf
                    <input type="hidden" name="revision" value="{{ $participation->revision ?? 0 }}">
                    <p class="text-sm text-pm-muted">{{ __('contracts.association_help') }}</p>
                    <label class="grid gap-2"><span class="pm-label">{{ __('contracts.title') }}</span><select name="season_contract_id" class="pm-input" required><option value="">{{ __('contracts.choose') }}</option>@foreach ($candidates as $candidate)<option value="{{ $candidate->id }}" @selected(($participation && $participation->season_contract_id === $candidate->id) || (!$participation && $candidates->count() === 1))>#{{ $candidate->id }} · {{ $candidate->season }} · {{ $candidate->vehicle?->name }} · {{ $candidate->championship?->name ?? __('contracts.no_championship') }} · {{ __('contracts.remaining') }} {{ max(0, $candidate->initial_races + (int) $candidate->extra_races - (int) $candidate->consumed) }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ __('contracts.round') }}</span><select name="round_id" class="pm-input"><option value="">{{ __('contracts.optional') }}</option>@foreach ($rounds as $round)<option value="{{ $round->id }}" @selected($participation?->round_id === $round->id)>{{ $round->championship?->name }} · {{ $round->name }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ __('contracts.notes') }}</span><textarea name="notes" class="pm-input" maxlength="2000">{{ $participation->notes ?? '' }}</textarea></label>
                    <label class="grid gap-2"><span class="pm-label">{{ __('contracts.reason') }}</span><input name="reason" class="pm-input" maxlength="1000" value="{{ __('contracts.associated') }}" required></label>
                    <button class="pm-race-button" type="submit" @disabled($candidates->isEmpty())>{{ __('Save') }}</button>
                </form>
            </x-crud-modal>
        @endcan
    @endif
</div>
