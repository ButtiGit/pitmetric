<?php

namespace App\Services;

use App\Models\EventTask;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OperationalNotificationService
{
    public function generate(Workspace $workspace): int
    {
        if (! Schema::hasTable('notifications')
            || ! Schema::hasTable('maintenance_work_orders')
            || ! Schema::hasTable('event_tasks')
            || ! Schema::hasTable('events')) {
            return 0;
        }

        $members = $workspace->users()
            ->wherePivot('status', 'active')
            ->get();

        if ($members->isEmpty()) {
            return 0;
        }

        $created = 0;

        $workOrders = MaintenanceWorkOrder::query()
            ->with('assignee')
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addHours(48))
            ->orderBy('due_at')
            ->get();

        foreach ($workOrders as $workOrder) {
            $recipients = $workOrder->assigned_to === null
                ? $members
                : $members->where('id', $workOrder->assigned_to)->values();

            if ($recipients->isEmpty()) {
                $recipients = $members;
            }

            $dueAt = Carbon::parse($workOrder->due_at);
            $isOverdue = $dueAt->isPast();
            $created += $this->notify(
                $recipients,
                [
                    'workspace_id' => $workspace->getKey(),
                    'alert_key' => 'work-order:'.$workOrder->getKey().':'.$dueAt->toIso8601String(),
                    'category' => 'maintenance',
                    'severity' => $isOverdue ? 'critical' : 'warning',
                    'title' => $isOverdue ? 'Maintenance overdue' : 'Maintenance due soon',
                    'message' => $workOrder->title,
                    'route_name' => 'maintenance.index',
                    'route_params' => [],
                ],
            );
        }

        $eventTasks = EventTask::query()
            ->with('raceEvent')
            ->whereNotIn('status', ['done', 'completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addHours(24))
            ->orderBy('due_at')
            ->get();

        foreach ($eventTasks as $task) {
            $dueAt = Carbon::parse($task->due_at);
            $isOverdue = $dueAt->isPast();
            $created += $this->notify(
                $members,
                [
                    'workspace_id' => $workspace->getKey(),
                    'alert_key' => 'event-task:'.$task->getKey().':'.$dueAt->toIso8601String(),
                    'category' => 'event',
                    'severity' => $isOverdue ? 'critical' : 'warning',
                    'title' => $isOverdue ? 'Event task overdue' : 'Event task due soon',
                    'message' => ($task->raceEvent?->name ? $task->raceEvent->name.' · ' : '').$task->title,
                    'route_name' => 'events.show',
                    'route_params' => ['raceEvent' => $task->event_id],
                ],
            );
        }

        $upcomingEvents = RaceEvent::query()
            ->whereIn('status', ['planned', 'active'])
            ->whereDate('start_date', '>=', today())
            ->whereDate('start_date', '<=', today()->addDays(3))
            ->orderBy('start_date')
            ->get();

        foreach ($upcomingEvents as $event) {
            $startDate = Carbon::parse($event->start_date);
            $created += $this->notify(
                $members,
                [
                    'workspace_id' => $workspace->getKey(),
                    'alert_key' => 'event-start:'.$event->getKey().':'.$startDate->toDateString(),
                    'category' => 'event',
                    'severity' => 'info',
                    'title' => 'Race weekend approaching',
                    'message' => $event->name.' starts '.$startDate->format('d M Y'),
                    'route_name' => 'events.show',
                    'route_params' => ['raceEvent' => $event->getKey()],
                ],
            );
        }

        return $created;
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $payload
     */
    private function notify(Collection $recipients, array $payload): int
    {
        $created = 0;

        foreach ($recipients as $user) {
            if ($this->alreadyExists($user, (string) $payload['alert_key'])) {
                continue;
            }

            $user->notify(new OperationalAlertNotification($payload));
            $created++;
        }

        return $created;
    }

    private function alreadyExists(User $user, string $alertKey): bool
    {
        return $user->notifications()
            ->where('type', OperationalAlertNotification::class)
            ->latest()
            ->limit(250)
            ->get()
            ->contains(fn ($notification): bool => ($notification->data['alert_key'] ?? null) === $alertKey);
    }
}
