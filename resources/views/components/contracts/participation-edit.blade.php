@props(['participation'])
@can('update', $participation)
    <x-crud-modal id="participation-{{ $participation->id }}" :title="__('contracts.update_participation')" :trigger="__('contracts.update_participation')" trigger-class="pm-ghost-button">
        <form method="POST" action="{{ route('contracts.participations.update', $participation) }}" class="grid gap-4" onsubmit="return confirm(@js(__('contracts.confirm_change')))">
            @csrf @method('PATCH')<input type="hidden" name="revision" value="{{ $participation->revision }}">
            <p class="text-sm text-pm-muted">{{ __('contracts.no_automatic') }}</p>
            <label class="grid gap-2"><span class="pm-label">{{ __('prints.status') }}</span><select class="pm-input" name="status" required>@foreach (['planned','confirmed','absent','cancelled'] as $state)<option value="{{ $state }}" @selected($participation->status === $state)>{{ __('contracts.states.'.$state) }}</option>@endforeach</select></label>
            <label class="grid gap-2"><span class="pm-label">{{ __('contracts.reason') }}</span><textarea class="pm-input" name="reason" maxlength="1000" required></textarea></label>
            <button class="pm-race-button" type="submit">{{ __('Save') }}</button>
        </form>
    </x-crud-modal>
@endcan
