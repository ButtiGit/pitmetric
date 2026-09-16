<?php

namespace App\Observers;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Notifications\MaintenanceWorkOrderAssignedNotification;

class MaintenanceWorkOrderObserver
{
    public function created(MaintenanceWorkOrder $workOrder): void
    {
        $this->notifyAssignee($workOrder);
    }

    public function updated(MaintenanceWorkOrder $workOrder): void
    {
        if ($workOrder->wasChanged('assigned_to')) {
            $this->notifyAssignee($workOrder);
        }
    }

    private function notifyAssignee(MaintenanceWorkOrder $workOrder): void
    {
        if ($workOrder->assigned_to === null) {
            return;
        }

        $assignee = User::query()->find($workOrder->assigned_to);

        if ($assignee instanceof User) {
            $assignee->notify((new MaintenanceWorkOrderAssignedNotification($workOrder))->afterCommit());
        }
    }
}
