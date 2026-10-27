@props(['result' => null, 'participants'])
<input type="hidden" name="_championship_tab" value="rounds">
<div class="grid gap-4 sm:grid-cols-2" x-data="{ status: @js($result?->status ?? 'classified') }">
    @if ($result)
        <input type="hidden" name="participant_id" value="{{ $result->participant_id }}"><p class="font-bold sm:col-span-2">{{ $result->participant->driver->display_name }}</p>
    @else
        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.driver') }}</span><select class="pm-input" name="participant_id" required><option value="">—</option>@foreach ($participants as $participant)<option value="{{ $participant->id }}">{{ filled($participant->entry_number) ? '#'.$participant->entry_number.' ' : '' }}{{ $participant->driver->display_name }}</option>@endforeach</select></label>
    @endif
    <label class="grid gap-2"><span class="pm-label">{{ __('championship.status') }}</span><select class="pm-input" name="status" x-model="status">@foreach (['classified','dnf','dns','dsq'] as $status)<option value="{{ $status }}">{{ __('championship.'.$status) }}</option>@endforeach</select></label>
    <label class="grid gap-2"><span class="pm-label">{{ __('championship.position') }}</span><input class="pm-input" type="number" name="position" min="1" max="500" value="{{ $result?->position }}" :required="status === 'classified'" :disabled="status !== 'classified'"></label>
    <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.adjustment') }}</span><input class="pm-input" name="adjustment" inputmode="decimal" value="{{ \App\Services\ChampionshipRecords::display((int) ($result?->adjustment ?? 0)) }}"></label>
    <label class="flex items-center gap-3"><input type="checkbox" name="pole" value="1" @checked($result?->pole)><span class="pm-label">{{ __('championship.pole') }}</span></label>
    <label class="flex items-center gap-3"><input type="checkbox" name="fastest_lap" value="1" @checked($result?->fastest_lap)><span class="pm-label">{{ __('championship.fastest_lap') }}</span></label>
    <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.notes') }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $result?->notes }}</textarea></label>
    <div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
</div>
