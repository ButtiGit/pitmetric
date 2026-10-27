@props(['round' => null, 'events', 'nextNumber' => 1])
<input type="hidden" name="_championship_tab" value="rounds">
<label class="grid gap-2"><span class="pm-label">{{ __('championship.number') }}</span><input class="pm-input" type="number" name="number" min="1" max="1000" value="{{ $round?->number ?? $nextNumber }}" required></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.round_name') }}</span><input class="pm-input" name="name" maxlength="140" value="{{ $round?->name }}" required></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.starts_on') }}</span><input class="pm-input" type="date" name="starts_on" value="{{ $round?->starts_on?->format('Y-m-d') }}"></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.ends_on') }}</span><input class="pm-input" type="date" name="ends_on" value="{{ $round?->ends_on?->format('Y-m-d') }}"></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.venue') }}</span><input class="pm-input" name="venue" maxlength="140" value="{{ $round?->venue }}"></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.status') }}</span><select class="pm-input" name="status">@foreach (['planned','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(($round?->status ?? 'planned') === $status)>{{ __('championship.'.$status) }}</option>@endforeach</select></label>
<label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.event') }}</span><select class="pm-input" name="event_id"><option value="">{{ __('championship.no_event') }}</option>@foreach ($events as $event)<option value="{{ $event->id }}" @selected($round?->event_id === $event->id)>{{ $event->name }} / {{ $event->start_date->format('d/m/Y') }}</option>@endforeach</select></label>
<label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.notes') }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000">{{ $round?->notes }}</textarea></label>
<div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
