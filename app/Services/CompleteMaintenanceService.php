<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\TrackerResetEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteMaintenanceService
{
    public function complete(
        MaintenanceSchedule $schedule,
        User $user,
        Carbon $performedAt,
        string $description,
        ?int $costCents = null,
        ?string $notes = null,
    ): MaintenanceRecord {
        return DB::transaction(function () use ($schedule, $user, $performedAt, $description, $costCents, $notes): MaintenanceRecord {
            $lockedSchedule = MaintenanceSchedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSchedule->load('tracker.component');

            $record = MaintenanceRecord::create([
                'component_id' => $lockedSchedule->tracker->component_id,
                'maintenance_schedule_id' => $lockedSchedule->getKey(),
                'performed_at' => $performedAt,
                'description' => $description,
                'cost_cents' => $costCents,
                'created_by' => $user->getKey(),
                'notes' => $notes,
            ]);

            TrackerResetEvent::create([
                'component_tracker_id' => $lockedSchedule->component_tracker_id,
                'maintenance_record_id' => $record->getKey(),
                'reset_at' => $performedAt,
                'reason' => $description,
                'created_by' => $user->getKey(),
            ]);

            MaintenanceWorkOrder::withoutGlobalScope('workspace')
                ->where('workspace_id', $lockedSchedule->workspace_id)
                ->where('maintenance_schedule_id', $lockedSchedule->getKey())
                ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
                ->update([
                    'status' => 'completed',
                    'completed_at' => $performedAt,
                    'maintenance_record_id' => $record->getKey(),
                ]);

            if ($costCents !== null && $costCents > 0) {
                Expense::create([
                    'amount_cents' => $costCents,
                    'currency' => 'EUR',
                    'category' => 'maintenance',
                    'description' => $lockedSchedule->tracker->component->name.' · '.$description,
                    'occurred_at' => $performedAt,
                    'related_type' => 'maintenance_record',
                    'related_id' => $record->getKey(),
                    'created_by' => $user->getKey(),
                ]);
            }

            return $record->load('component', 'schedule.tracker.metric');
        });
    }
}
