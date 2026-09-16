<?php

namespace App\Notifications;

use App\Models\MaintenanceWorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MaintenanceWorkOrderAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly MaintenanceWorkOrder $workOrder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'maintenance_assignment',
            'work_order_id' => $this->workOrder->getKey(),
            'title' => $this->workOrder->title,
            'priority' => $this->workOrder->priority,
            'due_at' => $this->workOrder->due_at === null
                ? null
                : Carbon::parse((string) $this->workOrder->due_at)->toIso8601String(),
            'url' => route('maintenance.index'),
        ];
    }
}
