<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\RaceEvent;
use Illuminate\Support\Facades\DB;

class AssignMaintenanceEventService
{
    public function assign(MaintenanceRecord $record, ?int $eventId): void
    {
        DB::transaction(function () use ($record, $eventId): void {
            $locked = MaintenanceRecord::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($eventId !== null) {
                RaceEvent::withTrashed()->where('workspace_id', $locked->workspace_id)->findOrFail($eventId);
            }
            $locked->update(['event_id' => $eventId]);
            $expenses = Expense::query()->where('workspace_id', $locked->workspace_id)
                ->where('related_type', 'maintenance_record')->where('related_id', $locked->id)->lockForUpdate()->get();
            foreach ($expenses as $expense) {
                $expense->update(['event_id' => $eventId]);
            }
        });
    }
}
