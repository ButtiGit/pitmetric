<?php

namespace App\Services;

use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;
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

    /**
     * @return array{distance: int, runtime: int, cycles: int, sessions: int}
     */
    private function calculateMetrics(Session $session): array
    {
        $distance = (int) ($session->distance_override_meters ?? 0);

        if ($distance === 0 && $session->circuitLayout !== null && $session->completed_laps !== null) {
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
