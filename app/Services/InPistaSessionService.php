<?php

namespace App\Services;

use App\Models\ConfigurationVersion;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\TimingLap;
use App\Models\TrackCapture;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InPistaSessionService
{
    private const LAST_USED_FIELDS = [
        'camber_front_deg',
        'caster_front_deg',
        'track_width_front_mm',
        'track_width_rear_mm',
        'pinion_teeth',
        'crown_teeth',
        'final_drive',
    ];

    public function __construct(
        private readonly ConfigurationPhysicalStateService $physicalState,
        private readonly SetupSnapshotService $snapshotService,
        private readonly FinalizeSessionService $finalizeSessionService,
        private readonly TrackCaptureService $captureService,
        private readonly AuditTrailService $auditTrail,
    ) {}

    public function currentEventEntry(Vehicle $kart): ?EventEntry
    {
        return EventEntry::query()
            ->where('vehicle_id', $kart->getKey())
            ->whereHas('raceEvent', fn ($query) => $query
                ->whereIn('status', ['planned', 'active'])
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today()))
            ->with(['driver', 'raceEvent', 'configurationVersion'])
            ->latest('id')
            ->first();
    }

    public function recommendedVersion(Vehicle $kart, ?EventEntry $entry): ?ConfigurationVersion
    {
        if ($entry?->configurationVersion?->configuration?->vehicle_id !== null
            && (int) $entry->configurationVersion->configuration->vehicle_id === (int) $kart->getKey()) {
            return $entry->configurationVersion;
        }

        return ConfigurationVersion::query()
            ->whereHas('configuration', fn ($query) => $query->where('vehicle_id', $kart->getKey())
                ->where('status', 'active')->whereNull('deleted_at'))
            ->with('configuration')
            ->latest('id')
            ->first();
    }

    /**
     * @return array{session_id: int, session_date: string, setup_name: string, values: array<string, int|float|string>}|null
     */
    public function latestUsedSettings(Vehicle $kart): ?array
    {
        $lastSession = Session::query()
            ->where('workspace_id', $kart->workspace_id)
            ->where('vehicle_id', $kart->getKey())
            ->where('status', 'finalized')
            ->with([
                'setupSnapshot',
                'trackCaptures' => fn ($query) => $query->where('kind', 'setup_adjustment')
                    ->orderBy('occurred_at')->orderBy('id'),
            ])
            ->latest('started_at')
            ->latest('id')
            ->first();

        if (! $lastSession instanceof Session) {
            return null;
        }

        $snapshotValues = $lastSession->setupSnapshot->values ?? [];
        $values = is_array($snapshotValues) ? $snapshotValues : [];

        foreach ($lastSession->trackCaptures as $capture) {
            $adjustments = data_get($capture->payload, 'values', []);

            if (! is_array($adjustments)) {
                continue;
            }

            foreach ($adjustments as $field => $value) {
                if (is_string($field) && array_key_exists($field, TechnicalSetup::FIELD_DEFINITIONS) && is_scalar($value)) {
                    $values[$field] = $value;
                }
            }
        }

        $recentValues = [];

        foreach (self::LAST_USED_FIELDS as $field) {
            $value = $values[$field] ?? null;

            if ((is_int($value) || is_float($value) || is_string($value)) && $value !== '') {
                $recentValues[$field] = $value;
            }
        }

        if ($recentValues === []) {
            return null;
        }

        return [
            'session_id' => (int) $lastSession->getKey(),
            'session_date' => Carbon::parse($lastSession->started_at)->format('d/m/Y H:i'),
            'setup_name' => $lastSession->setupSnapshot->name ?? __('Unspecified setup'),
            'values' => $recentValues,
        ];
    }

    public function start(User $user, Vehicle $kart, ConfigurationVersion $version, ?EventEntry $entry, string $type): Session
    {
        if ($kart->category !== 'kart' || $kart->status !== 'active'
            || (int) $version->configuration->vehicle_id !== (int) $kart->getKey()
            || $version->configuration->status !== 'active'
            || $version->configuration->trashed()
            || (int) $version->configuration->workspace_id !== (int) $kart->workspace_id
            || ! $this->physicalState->matches($version)) {
            throw ValidationException::withMessages(['kart' => __('Create an up-to-date kart configuration before starting a session.')]);
        }

        if ($entry !== null && (int) $entry->vehicle_id !== (int) $kart->getKey()) {
            throw ValidationException::withMessages(['kart' => __('The event entry belongs to a different kart.')]);
        }

        return DB::transaction(function () use ($user, $kart, $version, $entry, $type): Session {
            Vehicle::query()->whereKey($kart->getKey())->lockForUpdate()->firstOrFail();

            $existing = Session::query()->where('vehicle_id', $kart->getKey())
                ->where('status', 'draft')->latest('id')->first();

            if ($existing instanceof Session) {
                return $existing;
            }

            $event = $entry?->raceEvent;
            $session = Session::create([
                'event_id' => $event?->getKey(),
                'event_entry_id' => $entry?->getKey(),
                'vehicle_id' => $kart->getKey(),
                'configuration_version_id' => $version->getKey(),
                'circuit_layout_id' => $event?->circuit_layout_id,
                'session_type' => $type,
                'started_at' => now(),
                'status' => 'draft',
                'created_by' => $user->getKey(),
            ]);

            $setup = TechnicalSetup::query()->where('vehicle_id', $kart->getKey())
                ->where('status', 'active')->latest('updated_at')->latest('id')->first();
            $this->snapshotService->capture($session, $setup, $user);

            if ($event instanceof RaceEvent && $event->status === 'planned') {
                $event->update(['status' => 'active']);
            }

            return $session;
        });
    }

    public function addLap(Session $session, string $input, LapTimeParser $parser): TimingLap
    {
        $milliseconds = $parser->parse($input);

        return DB::transaction(function () use ($session, $milliseconds): TimingLap {
            $current = Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $this->assertDraft($current);

            $nextLap = (int) TimingLap::query()->where('session_id', $current->getKey())->max('lap_number') + 1;

            return TimingLap::create([
                'session_id' => $current->getKey(),
                'driver_id' => $current->eventEntry?->driver_id,
                'vehicle_id' => $current->vehicle_id,
                'circuit_layout_id' => $current->circuit_layout_id,
                'lap_number' => $nextLap,
                'lap_time_ms' => $milliseconds,
                'source' => 'manual',
                'is_valid' => true,
            ]);
        });
    }

    public function correctManualLap(
        Session $session,
        TimingLap $selectedLap,
        string $input,
        int $expectedMilliseconds,
        ?string $reason,
        LapTimeParser $parser,
    ): TimingLap {
        $correctedMilliseconds = $parser->parse($input);

        return DB::transaction(function () use ($session, $selectedLap, $correctedMilliseconds, $expectedMilliseconds, $reason): TimingLap {
            $current = Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $lap = TimingLap::query()->where('session_id', $current->getKey())
                ->whereKey($selectedLap->getKey())->lockForUpdate()->firstOrFail();

            if ($lap->source !== 'manual' || $lap->telemetry_import_id !== null) {
                throw ValidationException::withMessages(['lap_time' => __('Only manually recorded laps can be corrected here.')]);
            }

            $previousMilliseconds = (int) $lap->lap_time_ms;

            if ($previousMilliseconds !== $expectedMilliseconds) {
                throw ValidationException::withMessages(['lap_time' => __('This lap was already changed. Refresh to see the latest time.')]);
            }

            if ($previousMilliseconds === $correctedMilliseconds) {
                throw ValidationException::withMessages(['lap_time' => __('The corrected time must be different from the current one.')]);
            }

            $lap->updateQuietly(['lap_time_ms' => $correctedMilliseconds]);
            $this->auditTrail->custom(
                (int) $current->workspace_id,
                'lap_time_corrected',
                TimingLap::class,
                (int) $lap->getKey(),
                'Giro #'.$lap->lap_number,
                ['lap_time_ms' => $previousMilliseconds],
                [
                    'lap_time_ms' => $correctedMilliseconds,
                    'session_id' => (int) $current->getKey(),
                    'lap_number' => (int) $lap->lap_number,
                    'reason' => $reason,
                ],
            );

            return $lap;
        });
    }

    /** @param array<string, mixed> $payload */
    public function addCapture(Session $session, User $user, string $kind, array $payload, ?string $notes): TrackCapture
    {
        $this->assertDraft($session);
        $session->loadMissing('vehicle', 'eventEntry.driver', 'circuitLayout.circuit', 'configurationVersion.configuration', 'setupSnapshot');

        return $this->captureService->create($user, [
            'kind' => $kind,
            'session_id' => $session->getKey(),
            'circuit_name' => $session->circuitLayout?->circuit?->name,
            'payload' => $payload,
            'notes' => $notes,
            'context' => [
                'vehicle_name' => $session->vehicle?->name,
                'driver_name' => $session->eventEntry?->driver?->display_name,
                'configuration_name' => $session->configurationVersion?->configuration?->name,
                'technical_setup_name' => $session->setupSnapshot?->technical_setup_id ? $session->setupSnapshot->name : null,
            ],
        ]);
    }

    public function finish(Session $session): Session
    {
        return DB::transaction(function () use ($session): Session {
            $current = Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $this->assertDraft($current);

            $lapCount = $current->timingLaps()->count();
            $current->update([
                'completed_laps' => max($lapCount, (int) ($current->completed_laps ?? 0)),
                'duration_seconds' => (int) max(0, Carbon::parse($current->started_at)->diffInSeconds(now())),
            ]);

            return $this->finalizeSessionService->finalize($current);
        });
    }

    public function assertDraft(Session $session): void
    {
        if ($session->status !== 'draft') {
            throw ValidationException::withMessages(['session' => __('This session is closed. Start a new one to record trackside changes.')]);
        }
    }
}
