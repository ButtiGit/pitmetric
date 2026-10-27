@props(['championship' => null])
<label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.name') }}</span><input class="pm-input" name="name" value="{{ $championship?->name }}" maxlength="140" required></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.season') }}</span><input class="pm-input" name="season" value="{{ $championship?->season ?? now()->year }}" maxlength="40" required></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.category') }}</span><input class="pm-input" name="category" value="{{ $championship?->category }}" maxlength="80"></label>
<label class="grid gap-2"><span class="pm-label">{{ __('championship.status') }}</span><select class="pm-input" name="status">@foreach (['planned','active','completed'] as $status)<option value="{{ $status }}" @selected(($championship?->status ?? 'planned') === $status)>{{ __('championship.'.$status) }}</option>@endforeach</select></label>
<label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.notes') }}</span><textarea class="pm-input min-h-24" name="notes" maxlength="4000">{{ $championship?->notes }}</textarea></label>
<div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
