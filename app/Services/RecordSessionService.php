<?php

namespace App\Services;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\EventEntry;
use App\Models\EventScheduleItem;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSessionService
{
    public function __construct(
        private readonly ConfigurationPhysicalStateService $physicalState,
        private readonly FinalizeSessionService $finalizeSessionService,
        private readonly MaintenanceHealthService $healthService,
        private readonly SetupSnapshotService $snapshotService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{session: Session, raceEvent: RaceEvent|null, maintenanceAttention: int}
     */
    public function record(Workspace $workspace, User $user, array $validated): array
    {
        [$raceEvent, $eventEntry, $scheduleItem] = $this->resolveEventContext($validated);
        $version = $this->resolveConfigurationVersion($workspace, $validated, $eventEntry);
        $technicalSetup = $this->resolveTechnicalSetup($validated, $version);
        $layoutId = $this->resolveLayoutId($workspace, $validated, $raceEvent);

        $session = DB::transaction(function () use ($validated, $version, $layoutId, $user, $technicalSetup, $raceEvent, $eventEntry, $scheduleItem): Session {
            $lockedScheduleItem = $this->lockScheduleItem($scheduleItem);

            $session = Session::create([
                'event_id' => $raceEvent?->getKey(),
                'event_entry_id' => $eventEntry?->getKey(),
                'vehicle_id' => $version->configuration->vehicle_id,
                'configuration_version_id' => $version->getKey(),
                'circuit_layout_id' => $layoutId,
                'session_type' => $validated['session_type'],
                'started_at' => Carbon::parse((string) $validated['started_at']),
                'completed_laps' => $validated['completed_laps'] ?? null,
                'duration_seconds' => isset($validated['duration_minutes'])
                    ? (int) round(((float) $validated['duration_minutes']) * 60)
                    : null,
                'distance_override_meters' => isset($validated['distance_override_km'])
                    ? (int) round(((float) $validated['distance_override_km']) * 1000)
                    : null,
                'status' => 'draft',
                'created_by' => $user->getKey(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $finalizedSession = $this->finalizeSessionService->finalize($session);
            $this->snapshotService->capture($finalizedSession, $technicalSetup, $user);
            $this->recordCost($validated, $finalizedSession, $version, $raceEvent, $user);

            if ($lockedScheduleItem instanceof EventScheduleItem) {
                $lockedScheduleItem->update([
                    'session_id' => $finalizedSession->getKey(),
                    'status' => 'completed',
                ]);
            }

            if ($raceEvent instanceof RaceEvent && $raceEvent->status === 'planned') {
                $raceEvent->update(['status' => 'active']);
            }

            return $finalizedSession;
        });

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->get();
        $health = $this->healthService->snapshot($schedules);

        return [
            'session' => $session,
            'raceEvent' => $raceEvent,
            'maintenanceAttention' => (int) $health['summary']['attention'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{RaceEvent|null, EventEntry|null, EventScheduleItem|null}
     */
    private function resolveEventContext(array $validated): array
    {
        if (empty($validated['event_id'])) {
            return [null, null, null];
        }

        $raceEvent = RaceEvent::query()->whereKey((int) $validated['event_id'])->firstOrFail();
        $eventEntry = EventEntry::query()
            ->whereKey((int) $validated['event_entry_id'])
            ->where('event_id', $raceEvent->getKey())
            ->firstOrFail();

        $startedAt = Carbon::parse((string) $validated['started_at']);
        $eventStartsAt = Carbon::parse($raceEvent->start_date)->startOfDay();
        $eventEndsAt = Carbon::parse($raceEvent->end_date)->endOfDay();

        if ($startedAt->lt($eventStartsAt) || $startedAt->gt($eventEndsAt)) {
            throw ValidationException::withMessages([
                'started_at' => __('The session date must be inside the race weekend.'),
            ]);
        }

        if (empty($validated['schedule_item_id'])) {
            return [$raceEvent, $eventEntry, null];
        }

        $scheduleItem = EventScheduleItem::query()
            ->whereKey((int) $validated['schedule_item_id'])
            ->where('event_id', $raceEvent->getKey())
            ->firstOrFail();

        if ($scheduleItem->session_id !== null) {
            throw ValidationException::withMessages([
                'schedule_item_id' => __('This scheduled session has already been recorded.'),
            ]);
        }

        if ($scheduleItem->event_entry_id !== null && (int) $scheduleItem->event_entry_id !== (int) $eventEntry->getKey()) {
            throw ValidationException::withMessages([
                'event_entry_id' => __('The scheduled session belongs to a different event entry.'),
            ]);
        }

        if ($scheduleItem->session_type !== $validated['session_type']) {
            throw ValidationException::withMessages([
                'session_type' => __('The recorded session type must match the Trackside schedule item.'),
            ]);
        }

        return [$raceEvent, $eventEntry, $scheduleItem];
    }

    /** @param array<string, mixed> $validated */
    private function resolveConfigurationVersion(Workspace $workspace, array $validated, ?EventEntry $eventEntry): ConfigurationVersion
    {
        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', function ($query) use ($workspace, $eventEntry): void {
                $query->whereNull('configurations.deleted_at')
                    ->where('status', 'active')
                    ->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))
                    ->where('workspace_id', $workspace->getKey());

                if ($eventEntry instanceof EventEntry) {
                    $query->where('vehicle_id', $eventEntry->vehicle_id);
                }
            })
            ->with([
                'configuration.vehicle.componentInstallations' => fn ($query) => $query->whereNull('removed_at'),
                'components',
            ])
            ->firstOrFail();

        if (! $this->physicalState->matches($version)) {
            throw ValidationException::withMessages([
                'configuration_version_id' => __('The selected configuration is no longer aligned with the vehicle. Capture a new configuration version before recording the session.'),
            ]);
        }

        return $version;
    }

    /** @param array<string, mixed> $validated */
    private function resolveTechnicalSetup(array $validated, ConfigurationVersion $version): ?TechnicalSetup
    {
        if (! empty($validated['technical_setup_id'])) {
            return TechnicalSetup::query()
                ->whereKey((int) $validated['technical_setup_id'])
                ->where('vehicle_id', $version->configuration->vehicle_id)
                ->where('status', 'active')
                ->firstOrFail();
        }

        return TechnicalSetup::query()
            ->where('vehicle_id', $version->configuration->vehicle_id)
            ->where('status', 'active')
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    /** @param array<string, mixed> $validated */
    private function resolveLayoutId(Workspace $workspace, array $validated, ?RaceEvent $raceEvent): ?int
    {
        $layoutId = $raceEvent?->circuit_layout_id;

        if ($layoutId !== null || empty($validated['circuit_layout_id'])) {
            return $layoutId === null ? null : (int) $layoutId;
        }

        $layoutId = CircuitLayout::query()
            ->whereKey((int) $validated['circuit_layout_id'])
            ->where('is_active', true)
            ->whereHas('circuit', fn ($query) => $query->whereNull('circuits.deleted_at')->where('workspace_id', $workspace->getKey()))
            ->value('id');

        abort_if($layoutId === null, 404);

        return (int) $layoutId;
    }

    private function lockScheduleItem(?EventScheduleItem $scheduleItem): ?EventScheduleItem
    {
        if (! $scheduleItem instanceof EventScheduleItem) {
            return null;
        }

        $locked = EventScheduleItem::query()->whereKey($scheduleItem->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->session_id !== null) {
            throw ValidationException::withMessages([
                'schedule_item_id' => __('This scheduled session has already been recorded.'),
            ]);
        }

        return $locked;
    }

    /** @param array<string, mixed> $validated */
    private function recordCost(
        array $validated,
        Session $session,
        ConfigurationVersion $version,
        ?RaceEvent $raceEvent,
        User $user,
    ): void {
        $sessionCostCents = isset($validated['session_cost']) && (float) $validated['session_cost'] > 0
            ? (int) round(((float) $validated['session_cost']) * 100)
            : null;

        if ($sessionCostCents === null) {
            return;
        }

        Expense::create([
            'event_id' => $raceEvent?->getKey(),
            'amount_cents' => $sessionCostCents,
            'currency' => 'EUR',
            'category' => 'track',
            'description' => $validated['cost_description']
                ?? __('Track session').': '.$version->configuration->vehicle->name.' · '.ucfirst((string) $validated['session_type']),
            'occurred_at' => Carbon::parse((string) $validated['started_at']),
            'related_type' => 'session',
            'related_id' => $session->getKey(),
            'created_by' => $user->getKey(),
        ]);
    }
}
