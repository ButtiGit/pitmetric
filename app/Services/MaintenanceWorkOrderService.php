<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceWorkOrderService
{
    /** @param array<string, mixed> $validated */
    public function create(Workspace $workspace, User $user, array $validated): MaintenanceWorkOrder
    {
        return DB::transaction(function () use ($validated, $workspace, $user): MaintenanceWorkOrder {
            $schedule = MaintenanceSchedule::query()
                ->whereKey((int) $validated['maintenance_schedule_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if (MaintenanceWorkOrder::query()
                ->where('maintenance_schedule_id', $schedule->getKey())
                ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
                ->exists()) {
                throw ValidationException::withMessages([
                    'maintenance_schedule_id' => __('This maintenance schedule already has an open work order.'),
                ]);
            }

            return MaintenanceWorkOrder::create([
                'maintenance_schedule_id' => $schedule->getKey(),
                'assigned_to' => $this->activeAssigneeId($workspace, $validated['assigned_to'] ?? null),
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'status' => 'todo',
                'due_at' => isset($validated['due_at']) ? Carbon::parse((string) $validated['due_at']) : null,
                'created_by' => $user->getKey(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });
    }

    /** @param array<string, mixed> $validated */
    public function update(MaintenanceWorkOrder $workOrder, Workspace $workspace, array $validated): void
    {
        if (! in_array($workOrder->status, MaintenanceWorkOrder::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => __('Closed maintenance work orders cannot be changed.'),
            ]);
        }

        $assignedTo = $this->activeAssigneeId($workspace, $validated['assigned_to'] ?? null);

        DB::transaction(function () use ($workOrder, $validated, $assignedTo): void {
            MaintenanceSchedule::query()->whereKey($workOrder->maintenance_schedule_id)->lockForUpdate()->firstOrFail();
            $order = MaintenanceWorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, MaintenanceWorkOrder::OPEN_STATUSES, true)) {
                throw ValidationException::withMessages(['status' => __('Closed maintenance work orders cannot be changed.')]);
            }

            $startedAt = $order->started_at;
            if ($validated['status'] === 'in_progress' && $startedAt === null) {
                $startedAt = now();
            }

            $order->update([
                'title' => $validated['title'],
                'assigned_to' => $assignedTo,
                'priority' => $validated['priority'],
                'status' => $validated['status'],
                'due_at' => isset($validated['due_at']) ? Carbon::parse((string) $validated['due_at']) : null,
                'started_at' => $startedAt,
                'notes' => $validated['notes'] ?? null,
            ]);
        });
    }

    public function cancel(MaintenanceWorkOrder $workOrder): void
    {
        DB::transaction(function () use ($workOrder): void {
            MaintenanceSchedule::query()->whereKey($workOrder->maintenance_schedule_id)->lockForUpdate()->firstOrFail();
            $order = MaintenanceWorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();

            if ($order->maintenance_record_id !== null || $order->status === 'completed') {
                throw ValidationException::withMessages(['work_order' => __('Completed maintenance must remain in history.')]);
            }

            $order->update(['status' => 'cancelled']);
        });
    }

    private function activeAssigneeId(Workspace $workspace, mixed $assignedTo): ?int
    {
        if ($assignedTo === null || $assignedTo === '') {
            return null;
        }

        $assigneeId = (int) $assignedTo;
        $exists = $workspace->users()
            ->where('users.id', $assigneeId)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'assigned_to' => __('The assignee must be an active member of the current team.'),
            ]);
        }

        return $assigneeId;
    }
}
