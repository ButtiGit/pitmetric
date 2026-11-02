<?php

namespace App\Services;

use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;
use App\Models\TrackerResetEvent;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

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

        return $this->statusForUsage($tracker, $this->sinceLastService($tracker), $interval, $warning);
    }

    public function statusForUsage(ComponentTracker $tracker, int $used, ?int $interval = null, ?int $warning = null): string
    {
        $limit = $interval ?? $tracker->service_limit;

        if ($limit === null || $limit <= 0) {
            return 'untracked';
        }

        if ($used >= $limit) {
            return 'overdue';
        }

        $warningAt = max(0, $limit - ($warning ?? $tracker->warning_threshold ?? 0));

        return $used >= $warningAt ? 'due_soon' : 'ok';
    }

    /**
     * @param  Collection<int, ComponentTracker>  $trackers
     * @return array<int, array{used: int, lifetime: int}>
     */
    public function snapshot(Collection $trackers): array
    {
        if ($trackers->isEmpty()) {
            return [];
        }
        $ids = $trackers->pluck('id')->all();
        $table = (new ComponentUsageEntry)->getTable();
        $resets = TrackerResetEvent::query()->select('component_tracker_id')
            ->selectRaw('MAX(reset_at) as reset_at')->whereIn('component_tracker_id', $ids)->groupBy('component_tracker_id');
        $totals = ComponentUsageEntry::query()->from($table, 'usage_entries')->whereIn('usage_entries.component_tracker_id', $ids)
            ->leftJoinSub($resets, 'latest_resets', fn (JoinClause $join) => $join
                ->on('usage_entries.component_tracker_id', '=', 'latest_resets.component_tracker_id'))
            ->select('usage_entries.component_tracker_id')
            ->selectRaw('SUM(usage_entries.value) as lifetime')
            ->selectRaw('SUM(CASE WHEN latest_resets.reset_at IS NULL OR usage_entries.occurred_at > latest_resets.reset_at THEN usage_entries.value ELSE 0 END) as used')
            ->groupBy('usage_entries.component_tracker_id')->get()->keyBy('component_tracker_id');
        $result = [];
        foreach ($ids as $id) {
            $total = $totals->get($id);
            $result[(int) $id] = [
                'used' => (int) ($total?->getAttribute('used') ?? 0),
                'lifetime' => (int) ($total?->getAttribute('lifetime') ?? 0),
            ];
        }

        return $result;
    }
}
