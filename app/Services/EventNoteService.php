<?php

namespace App\Services;

use App\Models\EventNote;
use App\Models\RaceEvent;
use App\Models\User;
use Illuminate\Support\Carbon;

class EventNoteService
{
    public function __construct(private readonly EventOperationContextService $context) {}

    /** @param array<string, mixed> $validated */
    public function create(RaceEvent $raceEvent, User $user, array $validated): EventNote
    {
        return EventNote::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $this->context->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null),
            'kind' => $validated['kind'],
            'body' => $validated['body'],
            'occurred_at' => Carbon::parse((string) $validated['occurred_at']),
            'created_by' => $user->getKey(),
        ]);
    }

    /** @param array<string, mixed> $validated */
    public function update(EventNote $eventNote, array $validated): void
    {
        $validated['event_entry_id'] = $this->context->entryIdForEvent(
            $eventNote->raceEvent,
            $validated['event_entry_id'] ?? null,
        );

        $eventNote->update($validated);
    }
}
