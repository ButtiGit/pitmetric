@props(['events', 'selected' => null])
<label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('prints.event') }}</span>
    <select name="event_id" class="pm-input"><option value="">{{ __('weekend.no_event') }}</option>
        @foreach ($events as $event)
            <option value="{{ $event->id }}" @selected((string) $selected === (string) $event->id)>{{ $event->name }} · {{ $event->start_date->format('d/m/Y') }} #{{ $event->id }}</option>
        @endforeach
    </select>
</label>
