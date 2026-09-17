<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventScheduleItem;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OperationCostService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RaceEventOperationsController extends Controller
{
    public function storeDriver(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspaceContext->personal($user);
        $validated = $request->validate($this->driverRules());

        Driver::create([
            ...$validated,
            'status' => 'active',
        ]);

        return to_route('events.index')->with('status', __('Driver added.'));
    }

    public function updateDriver(Request $request, Driver $driver): RedirectResponse
    {
        Gate::authorize('update', $driver);
        $validated = $request->validate($this->driverRules());
        $driver->update($validated);

        return back()->with('status', __('Driver updated.'));
    }

    public function destroyDriver(Driver $driver): RedirectResponse
    {
        Gate::authorize('delete', $driver);

        if ($driver->eventEntries()->exists()) {
            $driver->update(['status' => 'archived']);

            return back()->with('status', __('Driver archived. Existing event history remains available.'));
        }

        $driver->delete();

        return back()->with('status', __('Driver archived.'));
    }

    public function storeEntry(
        Request $request,
        RaceEvent $raceEvent,
        WorkspaceContext $workspaceContext,
        OperationCostService $costService,
    ): RedirectResponse {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            ...$this->entryRules(),
            'entry_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        [$driver, $vehicle, $version] = $this->resolveEntryRelations($validated, (int) $workspace->getKey());

        $entry = EventEntry::create([
            'event_id' => $raceEvent->getKey(),
            'driver_id' => $driver->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'configuration_version_id' => $version->getKey(),
            'entry_number' => $validated['entry_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $costService->record(
            $user,
            isset($validated['entry_cost']) ? (float) $validated['entry_cost'] : null,
            'event_entry',
            __('Event entry').': '.$raceEvent->name.' · '.$driver->display_name,
            'event_entry',
            (int) $entry->getKey(),
            Carbon::parse($raceEvent->start_date)->startOfDay(),
            (int) $raceEvent->getKey(),
        );

        return to_route('events.show', $raceEvent)->with('status', __('Event entry added.'));
    }

    public function updateEntry(Request $request, EventEntry $eventEntry, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $eventEntry->loadMissing('raceEvent');
        Gate::authorize('update', $eventEntry->raceEvent);
        $workspace = $workspaceContext->personal($this->user($request));
        $validated = $request->validate($this->entryRules());
        [$driver, $vehicle, $version] = $this->resolveEntryRelations($validated, (int) $workspace->getKey());

        if ($eventEntry->sessions()->exists() && (int) $vehicle->getKey() !== (int) $eventEntry->vehicle_id) {
            throw ValidationException::withMessages([
                'vehicle_id' => __('The vehicle cannot be changed after sessions have been recorded for this entry.'),
            ]);
        }

        $eventEntry->update([
            'driver_id' => $driver->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'configuration_version_id' => $version->getKey(),
            'entry_number' => $validated['entry_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('events.show', $eventEntry->event_id)->with('status', __('Event entry updated.'));
    }

    public function destroyEntry(EventEntry $eventEntry): RedirectResponse
    {
        $eventEntry->loadMissing('raceEvent');
        Gate::authorize('update', $eventEntry->raceEvent);

        if ($eventEntry->sessions()->exists()
            || $eventEntry->scheduleItems()->exists()
            || $eventEntry->tasks()->exists()
            || $eventEntry->eventNotes()->exists()) {
            return to_route('events.show', $eventEntry->event_id)
                ->with('error', __('This entry already has operational history and cannot be deleted.'));
        }

        $eventId = $eventEntry->event_id;
        $eventEntry->delete();

        return to_route('events.show', $eventId)->with('status', __('Event entry deleted.'));
    }

    public function storeScheduleItem(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:120'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);
        $this->ensureInsideWeekend($raceEvent, $startsAt);
        $entryId = $this->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        EventScheduleItem::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $entryId,
            'label' => $validated['label'] ?? null,
            'session_type' => $validated['session_type'],
            'starts_at' => $startsAt,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'status' => 'planned',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->getKey(),
        ]);

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule updated.'));
    }

    public function updateScheduleItem(Request $request, EventScheduleItem $eventScheduleItem): RedirectResponse
    {
        $eventScheduleItem->loadMissing('raceEvent');
        $raceEvent = $eventScheduleItem->raceEvent;
        Gate::authorize('update', $raceEvent);

        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:120'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['required', 'in:planned,ready,live,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($eventScheduleItem->session_id !== null && $validated['status'] !== 'completed') {
            throw ValidationException::withMessages([
                'status' => __('A schedule item linked to a recorded session must stay completed.'),
            ]);
        }

        $startsAt = Carbon::parse($validated['starts_at']);
        $this->ensureInsideWeekend($raceEvent, $startsAt);
        $entryId = $this->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        $eventScheduleItem->update([
            'event_entry_id' => $entryId,
            'label' => $validated['label'] ?? null,
            'session_type' => $validated['session_type'],
            'starts_at' => $startsAt,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($validated['status'] === 'live' && $raceEvent->status === 'planned') {
            $raceEvent->update(['status' => 'active']);
        }

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule updated.'));
    }

    public function destroyScheduleItem(EventScheduleItem $eventScheduleItem): RedirectResponse
    {
        $eventScheduleItem->loadMissing('raceEvent');
        $raceEvent = $eventScheduleItem->raceEvent;
        Gate::authorize('update', $raceEvent);

        if ($eventScheduleItem->session_id !== null) {
            return to_route('events.show', $raceEvent)
                ->with('error', __('A schedule item linked to a recorded session cannot be deleted.'));
        }

        $eventScheduleItem->delete();

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule item removed.'));
    }

    public function storeTask(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'due_at' => ['nullable', 'date'],
        ]);
        $entryId = $this->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        EventTask::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $entryId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'todo',
            'due_at' => isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
            'created_by' => $user->getKey(),
        ]);

        return to_route('events.show', $raceEvent)->with('status', __('Event task added.'));
    }

    public function updateTask(
        Request $request,
        EventTask $eventTask,
        OperationCostService $costService,
    ): RedirectResponse {
        Gate::authorize('update', $eventTask);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['sometimes', 'nullable', 'integer'],
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'priority' => ['sometimes', 'required', 'in:low,normal,high,critical'],
            'status' => ['sometimes', 'required', 'in:todo,in_progress,done'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
        ]);

        $changes = [];
        foreach (['title', 'description', 'priority'] as $field) {
            if (array_key_exists($field, $validated)) {
                $changes[$field] = $validated[$field];
            }
        }
        if (array_key_exists('due_at', $validated)) {
            $changes['due_at'] = $validated['due_at'] ? Carbon::parse($validated['due_at']) : null;
        }
        if (array_key_exists('event_entry_id', $validated)) {
            $changes['event_entry_id'] = $this->entryIdForEvent($eventTask->raceEvent, $validated['event_entry_id']);
        }

        $status = $validated['status'] ?? $eventTask->status;
        $changes['status'] = $status;
        $changes['completed_at'] = $status === 'done' ? ($eventTask->completed_at ?? now()) : null;
        $eventTask->update($changes);

        if ($status === 'done' && ! $eventTask->wasCompletedBeforeUpdate()) {
            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'event_operations',
                $validated['cost_description'] ?? __('Event task').': '.$eventTask->title,
                'event_task',
                (int) $eventTask->getKey(),
                $eventTask->completed_at ?? now(),
                (int) $eventTask->event_id,
            );
        }

        return to_route('events.show', $eventTask->event_id)->with('status', __('Task updated.'));
    }

    public function destroyTask(EventTask $eventTask): RedirectResponse
    {
        Gate::authorize('delete', $eventTask);

        if ($eventTask->status === 'done' || Expense::query()->where('related_type', 'event_task')->where('related_id', $eventTask->getKey())->exists()) {
            return to_route('events.show', $eventTask->event_id)
                ->with('error', __('Completed tasks with operational history cannot be deleted.'));
        }

        $eventId = $eventTask->event_id;
        $eventTask->delete();

        return to_route('events.show', $eventId)->with('status', __('Task deleted.'));
    }

    public function storeNote(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate($this->noteRules());
        $entryId = $this->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        EventNote::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $entryId,
            'kind' => $validated['kind'],
            'body' => $validated['body'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
            'created_by' => $user->getKey(),
        ]);

        return to_route('events.show', $raceEvent)->with('status', __('Event note added.'));
    }

    public function updateNote(Request $request, EventNote $eventNote): RedirectResponse
    {
        Gate::authorize('update', $eventNote);
        $validated = $request->validate($this->noteRules());
        $eventNote->update([
            'event_entry_id' => $this->entryIdForEvent($eventNote->raceEvent, $validated['event_entry_id'] ?? null),
            'kind' => $validated['kind'],
            'body' => $validated['body'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
        ]);

        return to_route('events.show', $eventNote->event_id)->with('status', __('Event note updated.'));
    }

    public function destroyNote(EventNote $eventNote): RedirectResponse
    {
        Gate::authorize('delete', $eventNote);
        $eventId = $eventNote->event_id;
        $eventNote->delete();

        return to_route('events.show', $eventId)->with('status', __('Event note deleted.'));
    }

    public function storeExpense(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'occurred_at' => ['required', 'date'],
        ]);

        Expense::create([
            'event_id' => $raceEvent->getKey(),
            'amount_cents' => (int) round(((float) $validated['amount']) * 100),
            'currency' => 'EUR',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
            'created_by' => $user->getKey(),
        ]);

        return to_route('events.show', $raceEvent)->with('status', __('Event expense recorded.'));
    }

    /** @return array<string, array<int, string>> */
    private function driverRules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'racing_number' => ['nullable', 'string', 'max:20'],
            'licence_reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function entryRules(): array
    {
        return [
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'configuration_version_id' => ['required', 'integer'],
            'entry_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @param array<string, mixed> $validated */
    private function resolveEntryRelations(array $validated, int $workspaceId): array
    {
        $driver = Driver::query()->whereKey((int) $validated['driver_id'])->where('status', 'active')->firstOrFail();
        $vehicle = Vehicle::query()->whereKey((int) $validated['vehicle_id'])->where('status', 'active')->firstOrFail();
        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query
                ->where('workspace_id', $workspaceId)
                ->where('vehicle_id', $vehicle->getKey()))
            ->firstOrFail();

        return [$driver, $vehicle, $version];
    }

    /** @return array<string, array<int, string>> */
    private function noteRules(): array
    {
        return [
            'event_entry_id' => ['nullable', 'integer'],
            'kind' => ['required', 'in:technical,driver_feedback,incident,operations'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    private function entryIdForEvent(RaceEvent $raceEvent, mixed $entryId): ?int
    {
        if ($entryId === null || $entryId === '') {
            return null;
        }

        $entry = EventEntry::query()
            ->whereKey((int) $entryId)
            ->where('event_id', $raceEvent->getKey())
            ->firstOrFail();

        return (int) $entry->getKey();
    }

    private function ensureInsideWeekend(RaceEvent $raceEvent, Carbon $moment): void
    {
        $startsAt = Carbon::parse($raceEvent->start_date)->startOfDay();
        $endsAt = Carbon::parse($raceEvent->end_date)->endOfDay();

        if ($moment->lt($startsAt) || $moment->gt($endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => __('The scheduled session must be inside the race weekend.'),
            ]);
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
