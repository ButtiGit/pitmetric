<?php

namespace App\Services;

use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;

class ComponentUsageCalculator
{
    public function lifetime(ComponentTracker $tracker): int
    {
        return (int) ComponentUsageEntry::query()
            ->where('component_tracker_id', $tracker->getKey())
            ->sum('value');
    }

    public function sinceLastService(ComponentTracker $tracker): int
    {
        $resetAt = $tracker->resetEvents()
            ->latest('reset_at')
            ->value('reset_at');

        $query = ComponentUsageEntry::query()
            ->where('component_tracker_id', $tracker->getKey());

        if ($resetAt !== null) {
            $query->where('occurred_at', '>', $resetAt);
        }

        return (int) $query->sum('value');
    }

    public function status(ComponentTracker $tracker, ?int $interval = null, ?int $warning = null): string
    {
        $limit = $interval ?? $tracker->service_limit;

        if ($limit === null || $limit <= 0) {
            return 'untracked';
        }

        $used = $this->sinceLastService($tracker);

        if ($used >= $limit) {
            return 'overdue';
        }

        $warningAt = max(0, $limit - ($warning ?? $tracker->warning_threshold ?? 0));

        return $used >= $warningAt ? 'due_soon' : 'ok';
    }
}
