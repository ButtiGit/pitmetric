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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RaceEventOperationsController extends Controller
{
    public function storeDriver(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspaceContext->personal($user);
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'racing_number' => ['nullable', 'string', 'max:20'],
            'licence_reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Driver::create([
            ...$validated,
            'status' => 'active',
        ]);

        return to_route('events.index')->with('status', __('Driver added.'));
    }

    public function updateDriver(Request $request, Driver $driver): RedirectResponse
    {
        Gate::authorize('update', $driver);
        $driver->update($request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'racing_number' => ['nullable', 'string', 'max:20'],
            'licence_reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return to_route('events.index')->with('status', __('Driver updated.'));
    }

    public function destroyDriver(Driver $driver): RedirectResponse
    {
        Gate::authorize('delete', $driver);
        $driver->delete();

        return to_route('events.index')->with('status', __('Driver archived. History preserved.'));
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
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'configuration_version_id' => ['required', 'integer'],
            'entry_number' => ['nullable', 'string', 'max:20'],
            'entry_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $driver = Driver::query()->whereKey((int) $validated['driver_id'])->firstOrFail();
        $vehicle = Vehicle::query()->whereKey((int) $validated['vehicle_id'])->firstOrFail();
        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query->whereNull('configurations.deleted_at')->where('status', 'active')->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))
                ->where('workspace_id', $workspace->getKey())
                ->where('vehicle_id', $vehicle->getKey()))
            ->firstOrFail();

        DB::transaction(function () use ($raceEvent, $driver, $vehicle, $version, $validated, $costService, $user): void {
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
        });

        return to_route('events.show', $raceEvent)->with('status', __('Event entry added.'));
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
            'status' => ['required', 'in:todo,in_progress,done'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
        ]);

        $completedAt = $validated['status'] === 'done' ? now() : null;
        $eventTask->update([
            'status' => $validated['status'],
            'completed_at' => $completedAt,
        ]);

        if ($completedAt !== null) {
            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'event_operations',
                $validated['cost_description'] ?? __('Event task').': '.$eventTask->title,
                'event_task',
                (int) $eventTask->getKey(),
                $completedAt,
                (int) $eventTask->event_id,
            );
        }

        return to_route('events.show', $eventTask->event_id)->with('status', __('Task status updated.'));
    }

    public function storeNote(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'kind' => ['required', 'in:technical,driver_feedback,incident,operations'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);
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
