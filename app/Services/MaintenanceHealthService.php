<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use Illuminate\Support\Collection;

class MaintenanceHealthService
{
    public function __construct(private readonly ComponentUsageCalculator $usageCalculator) {}

    /**
     * @param  Collection<int, MaintenanceSchedule>  $schedules
     * @return array{
     *     states: array<int, array{used: int, lifetime: int, status: string, remaining: int}>,
     *     summary: array{ok: int, due_soon: int, overdue: int, untracked: int, attention: int}
     * }
     */
    public function snapshot(Collection $schedules): array
    {
        $states = [];
        $summary = [
            'ok' => 0,
            'due_soon' => 0,
            'overdue' => 0,
            'untracked' => 0,
            'attention' => 0,
        ];

        foreach ($schedules as $schedule) {
            $used = $this->usageCalculator->sinceLastService($schedule->tracker);
            $status = $this->usageCalculator->status(
                $schedule->tracker,
                $schedule->interval_value,
                $schedule->warning_value,
            );

            $states[$schedule->getKey()] = [
                'used' => $used,
                'lifetime' => $this->usageCalculator->lifetime($schedule->tracker),
                'status' => $status,
                'remaining' => $schedule->interval_value - $used,
            ];

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }

            if (in_array($status, ['due_soon', 'overdue'], true)) {
                $summary['attention']++;
            }
        }

        return [
            'states' => $states,
            'summary' => $summary,
        ];
    }
}
