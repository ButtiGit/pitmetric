<?php

namespace App\Services;

use App\Models\Session;

class SessionReportMetrics
{
    public function distance(Session $session): ?int
    {
        $saved = $session->usageValues->first(fn ($usage) => $usage->metric?->key === 'distance');
        if ($session->status === 'finalized' && $saved !== null) {
            return $saved->value;
        }
        if ($session->distance_override_meters !== null) {
            return $session->distance_override_meters;
        }

        return $session->completed_laps !== null && $session->circuitLayout?->length_meters !== null
            ? $session->completed_laps * $session->circuitLayout->length_meters : null;
    }

    public function runtime(Session $session): ?int
    {
        $saved = $session->usageValues->first(fn ($usage) => $usage->metric?->key === 'runtime');

        return $session->status === 'finalized' && $saved !== null ? $saved->value : $session->duration_seconds;
    }
}
