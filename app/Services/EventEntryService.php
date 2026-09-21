<?php

namespace App\Services;

use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventEntryService
{
    public function __construct(private readonly OperationCostService $costService) {}

    /** @param array<string, mixed> $validated */
    public function create(RaceEvent $raceEvent, Workspace $workspace, User $user, array $validated): EventEntry
    {
        $driver = Driver::query()->whereKey((int) $validated['driver_id'])->firstOrFail();
        $vehicle = Vehicle::query()->whereKey((int) $validated['vehicle_id'])->firstOrFail();
        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query
                ->whereNull('configurations.deleted_at')
                ->where('status', 'active')
                ->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))
                ->where('workspace_id', $workspace->getKey())
                ->where('vehicle_id', $vehicle->getKey()))
            ->firstOrFail();

        return DB::transaction(function () use ($raceEvent, $driver, $vehicle, $version, $validated, $user): EventEntry {
            $entry = EventEntry::create([
                'event_id' => $raceEvent->getKey(),
                'driver_id' => $driver->getKey(),
                'vehicle_id' => $vehicle->getKey(),
                'configuration_version_id' => $version->getKey(),
                'entry_number' => $validated['entry_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->costService->record(
                $user,
                isset($validated['entry_cost']) ? (float) $validated['entry_cost'] : null,
                'event_entry',
                __('Event entry').': '.$raceEvent->name.' · '.$driver->display_name,
                'event_entry',
                (int) $entry->getKey(),
                Carbon::parse($raceEvent->start_date)->startOfDay(),
                (int) $raceEvent->getKey(),
            );

            return $entry;
        });
    }

    public function delete(EventEntry $eventEntry): void
    {
        DB::transaction(function () use ($eventEntry): void {
            $entry = EventEntry::query()->whereKey($eventEntry->getKey())->lockForUpdate()->firstOrFail();
            if ($entry->sessions()->exists() || $entry->scheduleItems()->exists()
                || $entry->tasks()->exists() || $entry->eventNotes()->exists()
                || Expense::query()->where('related_type', 'event_entry')->where('related_id', $entry->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'entry' => __('This entry has sessions, activities or costs. Keep it in history; an unused entry can be deleted.'),
                ]);
            }

            $entry->delete();
        });
    }
}
