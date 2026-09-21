<?php

namespace App\Services;

use App\Models\EventScheduleItem;
use App\Models\RaceEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EventScheduleService
{
    public function __construct(private readonly EventOperationContextService $context) {}

    /** @param array<string, mixed> $validated */
    public function create(RaceEvent $raceEvent, User $user, array $validated): EventScheduleItem
    {
        $startsAt = Carbon::parse((string) $validated['starts_at']);
        $this->context->ensureInsideWeekend($raceEvent, $startsAt);
        $entryId = $this->context->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        return EventScheduleItem::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $entryId,
            'label' => $validated['label'] ?? null,
            'session_type' => $validated['session_type'],
            'starts_at' => $startsAt,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'status' => 'planned',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->getKey(),
        ]);
    }

    /** @param array<string, mixed> $validated */
    public function update(EventScheduleItem $scheduleItem, array $validated): RaceEvent
    {
        $scheduleItem->loadMissing('raceEvent');
        $raceEvent = $scheduleItem->raceEvent;

        if ($scheduleItem->session_id !== null && $validated['status'] !== 'completed') {
            throw ValidationException::withMessages([
                'status' => __('A schedule item linked to a recorded session must stay completed.'),
            ]);
        }

        $startsAt = Carbon::parse((string) $validated['starts_at']);
        $this->context->ensureInsideWeekend($raceEvent, $startsAt);
        $entryId = $this->context->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        $scheduleItem->update([
            'event_entry_id' => $entryId,
            'label' => $validated['label'] ?? null,
            'session_type' => $validated['session_type'],
            'starts_at' => $startsAt,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($validated['status'] === 'live' && $raceEvent->status === 'planned') {
            $raceEvent->update(['status' => 'active']);
        }

        return $raceEvent;
    }

    public function delete(EventScheduleItem $scheduleItem): bool
    {
        if ($scheduleItem->session_id !== null) {
            return false;
        }

        $scheduleItem->delete();

        return true;
    }
}
