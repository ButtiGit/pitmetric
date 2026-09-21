<?php

namespace App\Services;

use App\Models\EventEntry;
use App\Models\RaceEvent;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EventOperationContextService
{
    public function entryIdForEvent(RaceEvent $raceEvent, mixed $entryId): ?int
    {
        if ($entryId === null || $entryId === '') {
            return null;
        }

        $entry = EventEntry::query()
            ->whereKey((int) $entryId)
            ->where('event_id', $raceEvent->getKey())
            ->firstOrFail();

        return (int) $entry->getKey();
    }

    public function ensureInsideWeekend(RaceEvent $raceEvent, Carbon $moment): void
    {
        $startsAt = Carbon::parse($raceEvent->start_date)->startOfDay();
        $endsAt = Carbon::parse($raceEvent->end_date)->endOfDay();

        if ($moment->lt($startsAt) || $moment->gt($endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => __('The scheduled session must be inside the race weekend.'),
            ]);
        }
    }
}
