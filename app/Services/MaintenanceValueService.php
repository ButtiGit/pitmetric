<?php

namespace App\Services;

use App\Models\ComponentTracker;

class MaintenanceValueService
{
    public function toStorageValue(ComponentTracker $tracker, float $value): int
    {
        return match ($tracker->metric->key) {
            'distance' => (int) round($value * 1000),
            'runtime' => (int) round($value * 3600),
            default => (int) round($value),
        };
    }
}
