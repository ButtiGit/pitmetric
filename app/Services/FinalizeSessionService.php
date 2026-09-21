<?php

namespace App\Services;

use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;
use App\Models\EventEntry;
use App\Models\Session;
use App\Models\SessionUsageValue;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSessionService
{
    public function finalize(Session $session): Session
    {
        return DB::transaction(function () use ($session): Session {
            $lockedSession = Session::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status === 'finalized') {
                return $lockedSession->load([
                    'configurationVersion.components.trackers.metric',
                    'circuitLayout.circuit',
                    'usageValues.metric',
                ]);
            }

            if ($lockedSession->status !== 'draft') {
                throw ValidationException::withMessages([
                    'session' => __('Only draft sessions can be finalized.'),
                ]);
            }

            $lockedSession->load([
                'configurationVersion.configuration',
                'configurationVersion.components',
                'circuitLayout',
            ]);

            if ($lockedSession->configurationVersion->configuration->vehicle_id !== $lockedSession->vehicle_id) {
                throw ValidationException::withMessages([
                    'configuration_version_id' => __('The selected configuration does not belong to this vehicle.'),
                ]);
            }

            $this->assertConfigurationMatchesPhysicalState($lockedSession);

            $metrics = $this->calculateMetrics($lockedSession);
            $metricTypes = UsageMetricType::query()
                ->whereIn('key', array_keys($metrics))
                ->get()
                ->keyBy('key');

            $componentIds = $lockedSession->configurationVersion->components->modelKeys();
            $occurredAt = $lockedSession->started_at ?? now();

            foreach ($metrics as $key => $value) {
                if ($value <= 0) {
                    continue;
                }

                $metric = $metricTypes->get($key);

                if (! $metric instanceof UsageMetricType) {
                    continue;
                }

                SessionUsageValue::firstOrCreate(
                    [
                        'session_id' => $lockedSession->getKey(),
                        'usage_metric_type_id' => $metric->getKey(),
                        'source' => 'calculated',
                    ],
                    ['value' => $value],
                );

                $batch = UsageBatch::firstOrCreate(
                    [
                        'source_type' => 'session',
                        'source_id' => $lockedSession->getKey(),
                        'usage_metric_type_id' => $metric->getKey(),
                    ],
                    [
                        'value' => $value,
                        'occurred_at' => $occurredAt,
                        'created_by' => $lockedSession->created_by,
                    ],
                );

                $trackers = ComponentTracker::query()
                    ->whereIn('component_id', $componentIds)
                    ->where('usage_metric_type_id', $metric->getKey())
                    ->where('is_active', true)
                    ->get();

                foreach ($trackers as $tracker) {
                    ComponentUsageEntry::firstOrCreate(
                        [
                            'component_tracker_id' => $tracker->getKey(),
                            'usage_batch_id' => $batch->getKey(),
                        ],
                        [
                            'value' => $value,
                            'occurred_at' => $occurredAt,
                            'created_by' => $lockedSession->created_by,
                        ],
                    );
                }
            }

            if ($lockedSession->configurationVersion->locked_at === null) {
                $lockedSession->configurationVersion->update(['locked_at' => now()]);
            }

            $this->syncEventEntryConfiguration($lockedSession);

            $lockedSession->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            return $lockedSession->fresh([
                'configurationVersion.components.trackers.metric',
                'circuitLayout.circuit',
                'usageValues.metric',
            ]) ?? $lockedSession;
        });
    }

    private function assertConfigurationMatchesPhysicalState(Session $session): void
    {
        $configuredComponentIds = array_values(array_map(
            'intval',
            $session->configurationVersion->components->modelKeys(),
        ));
        sort($configuredComponentIds);

        $installedComponentIds = ComponentInstallation::query()
            ->where('vehicle_id', $session->vehicle_id)
            ->whereNull('removed_at')
            ->lockForUpdate()
            ->pluck('component_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($configuredComponentIds === $installedComponentIds) {
            return;
        }

        throw ValidationException::withMessages([
            'configuration_version_id' => __('The vehicle components changed after this configuration was created. Create a new configuration version before recording the session so usage is assigned to the components actually installed.'),
        ]);
    }

    private function syncEventEntryConfiguration(Session $session): void
    {
        if ($session->event_entry_id === null) {
            return;
        }

        $entry = EventEntry::query()
            ->whereKey($session->event_entry_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $entry->vehicle_id !== (int) $session->vehicle_id) {
            throw ValidationException::withMessages([
                'event_entry_id' => __('The event entry vehicle does not match the recorded session.'),
            ]);
        }

        if ((int) $entry->configuration_version_id === (int) $session->configuration_version_id) {
            return;
        }

        $entry->update([
            'configuration_version_id' => $session->configuration_version_id,
        ]);
    }

    /**
     * @return array{distance: int, runtime: int, cycles: int, sessions: int}
     */
    private function calculateMetrics(Session $session): array
    {
        $distance = (int) ($session->distance_override_meters ?? 0);

        if ($session->distance_override_meters === null && $session->circuitLayout !== null && $session->completed_laps !== null) {
            $distance = $session->circuitLayout->length_meters * $session->completed_laps;
        }

        return [
            'distance' => max(0, $distance),
            'runtime' => max(0, (int) ($session->duration_seconds ?? 0)),
            'cycles' => max(0, (int) ($session->completed_laps ?? 0)),
            'sessions' => 1,
        ];
    }
}
