@props(['events', 'entries', 'members', 'task' => null])

<div class="grid gap-4" x-data="{ eventId: {{ \Illuminate\Support\Js::from((string) ($task?->event_id ?? old('event_id', ''))) }} }">
    @if ($task)
        <p class="text-sm font-semibold text-pm-text-secondary">{{ $task->raceEvent->name }}</p>
    @else
        <label class="grid gap-2"><span class="pm-label">{{ __('tasks.event') }}</span>
            <select class="pm-input" name="event_id" required x-model="eventId" @change="$refs.entry.value = ''">
                <option value="">{{ __('tasks.choose_event') }}</option>
                @foreach ($events as $event)<option value="{{ $event->id }}">{{ $event->name }}</option>@endforeach
            </select>
        </label>
    @endif
    <label class="grid gap-2"><span class="pm-label">{{ __('tasks.name') }}</span><input class="pm-input" name="title" maxlength="160" required value="{{ $task?->title }}"></label>
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-2"><span class="pm-label">{{ __('tasks.priority') }}</span><select class="pm-input" name="priority">@foreach (['low', 'normal', 'high', 'critical'] as $priority)<option value="{{ $priority }}" @selected(($task?->priority ?? 'normal') === $priority)>{{ __('tasks.'.$priority) }}</option>@endforeach</select></label>
        <label class="grid gap-2"><span class="pm-label">{{ __('tasks.due') }}</span><input class="pm-input" name="due_at" type="datetime-local" value="{{ $task?->due_at?->format('Y-m-d\TH:i') }}"></label>
        <label class="grid gap-2"><span class="pm-label">{{ __('tasks.assignee') }}</span><select class="pm-input" name="assigned_to"><option value="">{{ __('tasks.unassigned') }}</option>
            @if ($task?->assigned_to && ! $members->contains('id', $task->assigned_to))<option value="{{ $task->assigned_to }}" selected>{{ $task->assignee?->name ?? __('tasks.unknown') }} ({{ __('tasks.inactive') }})</option>@endif
            @foreach ($members as $member)<option value="{{ $member->id }}" @selected($task?->assigned_to === $member->id)>{{ $member->name }}</option>@endforeach
        </select></label>
        <label class="grid gap-2"><span class="pm-label">{{ __('tasks.entry') }}</span><select class="pm-input" name="event_entry_id" x-ref="entry"><option value="">{{ __('tasks.whole_team') }}</option>
            @foreach ($entries as $entry)
                @if (! $task || $entry->event_id === $task->event_id)
                    <option value="{{ $entry->id }}" x-bind:disabled="eventId !== '{{ $entry->event_id }}'" x-show="eventId === '{{ $entry->event_id }}'" @selected($task?->event_entry_id === $entry->id)>{{ $entry->driver?->display_name }} · {{ $entry->vehicle?->name }}</option>
                @endif
            @endforeach
        </select></label>
    </div>
    <label class="grid gap-2"><span class="pm-label">{{ __('tasks.description') }}</span><textarea class="pm-input min-h-24" name="description" maxlength="3000">{{ $task?->description }}</textarea></label>
    <button class="pm-race-button justify-self-end" type="submit">{{ __('tasks.save') }}</button>
</div>
