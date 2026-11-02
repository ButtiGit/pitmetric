<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventTaskService
{
    public function __construct(
        private readonly EventOperationContextService $context,
        private readonly OperationCostService $costService,
    ) {}

    /** @param array<string, mixed> $validated */
    public function create(RaceEvent $raceEvent, User $user, array $validated): EventTask
    {
        return EventTask::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $this->context->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'todo',
            'due_at' => isset($validated['due_at']) ? Carbon::parse((string) $validated['due_at']) : null,
            'created_by' => $user->getKey(),
            'assigned_to' => $this->assigneeId((int) $raceEvent->workspace_id, $validated['assigned_to'] ?? null),
        ]);
    }

    /** @param array<string, mixed> $validated */
    public function updateStatus(EventTask $eventTask, User $user, array $validated): void
    {
        DB::transaction(function () use ($eventTask, $validated, $user): void {
            $task = EventTask::query()->whereKey($eventTask->getKey())->lockForUpdate()->firstOrFail();
            if (isset($validated['expected_status']) && $task->status !== $validated['expected_status']) {
                throw ValidationException::withMessages(['status' => __('tasks.conflict')]);
            }

            $completedAt = $validated['status'] === 'done'
                ? ($task->completed_at === null ? now() : Carbon::parse($task->completed_at))
                : null;

            $task->update([
                'status' => $validated['status'],
                'completed_at' => $completedAt,
                'completed_by' => $validated['status'] === 'done'
                    ? ($task->status === 'done' ? $task->completed_by : $user->getKey()) : null,
            ]);

            if ($completedAt !== null) {
                $this->costService->record(
                    $user,
                    isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                    'event_operations',
                    $validated['cost_description'] ?? __('Event task').': '.$task->title,
                    'event_task',
                    (int) $task->getKey(),
                    $completedAt,
                    (int) $task->event_id,
                );
            }
        });
    }

    /** @param array<string, mixed> $validated */
    public function updateDetails(EventTask $eventTask, array $validated): void
    {
        DB::transaction(function () use ($eventTask, $validated): void {
            $task = EventTask::query()->whereKey($eventTask->getKey())->lockForUpdate()->firstOrFail();
            $validated['event_entry_id'] = $this->context->entryIdForEvent($task->raceEvent, $validated['event_entry_id'] ?? null);
            if (array_key_exists('assigned_to', $validated)) {
                $validated['assigned_to'] = $this->assigneeId((int) $task->workspace_id, $validated['assigned_to']);
            }

            $task->update($validated);
        });
    }

    public function delete(EventTask $eventTask): void
    {
        DB::transaction(function () use ($eventTask): void {
            $task = EventTask::query()->whereKey($eventTask->getKey())->lockForUpdate()->firstOrFail();

            if ($task->completed_at !== null || AuditLog::query()->where('workspace_id', $task->workspace_id)
                ->where('subject_type', EventTask::class)->where('subject_id', $task->getKey())
                ->where('after_values->status', 'done')->exists()) {
                throw ValidationException::withMessages(['task' => __('tasks.delete_error')]);
            }

            if (Expense::query()->where('related_type', 'event_task')->where('related_id', $task->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'task' => __('This task has a recorded cost and must remain in history.'),
                ]);
            }

            $task->delete();
        });
    }

    private function assigneeId(int $workspaceId, mixed $userId): ?int
    {
        if ($userId === null || $userId === '') {
            return null;
        }
        $active = DB::table('workspace_user')->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)->where('status', 'active')->exists();
        if (! $active) {
            throw ValidationException::withMessages(['assigned_to' => __('tasks.member_error')]);
        }

        return (int) $userId;
    }
}
