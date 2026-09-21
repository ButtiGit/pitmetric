<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventScheduleItem;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Services\EventEntryService;
use App\Services\EventNoteService;
use App\Services\EventScheduleService;
use App\Services\EventTaskService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

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

        Driver::create([...$validated, 'status' => 'active']);

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
        EventEntryService $entryService,
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

        $entryService->create($raceEvent, $workspace, $user, $validated);

        return to_route('events.show', $raceEvent)->with('status', __('Event entry added.'));
    }

    public function updateEntry(Request $request, EventEntry $eventEntry): RedirectResponse
    {
        Gate::authorize('update', $eventEntry);
        $eventEntry->update($request->validate([
            'entry_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return to_route('events.show', $eventEntry->event_id)->with('status', __('Entry updated.'));
    }

    public function destroyEntry(EventEntry $eventEntry, EventEntryService $entryService): RedirectResponse
    {
        Gate::authorize('delete', $eventEntry);
        $eventId = $eventEntry->event_id;
        $entryService->delete($eventEntry);

        return to_route('events.show', $eventId)->with('status', __('Entry deleted.'));
    }

    public function storeScheduleItem(
        Request $request,
        RaceEvent $raceEvent,
        EventScheduleService $scheduleService,
    ): RedirectResponse {
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

        $scheduleService->create($raceEvent, $user, $validated);

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule updated.'));
    }

    public function updateScheduleItem(
        Request $request,
        EventScheduleItem $eventScheduleItem,
        EventScheduleService $scheduleService,
    ): RedirectResponse {
        $eventScheduleItem->loadMissing('raceEvent');
        Gate::authorize('update', $eventScheduleItem->raceEvent);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:120'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['required', 'in:planned,ready,live,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $raceEvent = $scheduleService->update($eventScheduleItem, $validated);

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule updated.'));
    }

    public function destroyScheduleItem(
        EventScheduleItem $eventScheduleItem,
        EventScheduleService $scheduleService,
    ): RedirectResponse {
        $eventScheduleItem->loadMissing('raceEvent');
        $raceEvent = $eventScheduleItem->raceEvent;
        Gate::authorize('update', $raceEvent);

        if (! $scheduleService->delete($eventScheduleItem)) {
            return to_route('events.show', $raceEvent)
                ->with('error', __('A schedule item linked to a recorded session cannot be deleted.'));
        }

        return to_route('events.show', $raceEvent)->with('status', __('Trackside schedule item removed.'));
    }

    public function storeTask(
        Request $request,
        RaceEvent $raceEvent,
        EventTaskService $taskService,
    ): RedirectResponse {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'due_at' => ['nullable', 'date'],
        ]);

        $taskService->create($raceEvent, $user, $validated);

        return to_route('events.show', $raceEvent)->with('status', __('Event task added.'));
    }

    public function updateTask(Request $request, EventTask $eventTask, EventTaskService $taskService): RedirectResponse
    {
        Gate::authorize('update', $eventTask);
        $user = $this->user($request);
        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
        ]);

        $taskService->updateStatus($eventTask, $user, $validated);

        return to_route('events.show', $eventTask->event_id)->with('status', __('Task status updated.'));
    }

    public function updateTaskDetails(Request $request, EventTask $eventTask, EventTaskService $taskService): RedirectResponse
    {
        Gate::authorize('update', $eventTask);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'due_at' => ['nullable', 'date'],
        ]);

        $taskService->updateDetails($eventTask, $validated);

        return to_route('events.show', $eventTask->event_id)->with('status', __('Task updated.'));
    }

    public function destroyTask(EventTask $eventTask, EventTaskService $taskService): RedirectResponse
    {
        Gate::authorize('delete', $eventTask);
        $eventId = $eventTask->event_id;
        $taskService->delete($eventTask);

        return to_route('events.show', $eventId)->with('status', __('Task deleted.'));
    }

    public function updateNote(Request $request, EventNote $eventNote, EventNoteService $noteService): RedirectResponse
    {
        Gate::authorize('update', $eventNote);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'kind' => ['required', 'in:technical,driver_feedback,incident,operations'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);

        $noteService->update($eventNote, $validated);

        return to_route('events.show', $eventNote->event_id)->with('status', __('Note updated.'));
    }

    public function destroyNote(EventNote $eventNote): RedirectResponse
    {
        Gate::authorize('delete', $eventNote);
        $eventId = $eventNote->event_id;
        $eventNote->delete();

        return to_route('events.show', $eventId)->with('status', __('Note deleted.'));
    }

    public function storeNote(Request $request, RaceEvent $raceEvent, EventNoteService $noteService): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'kind' => ['required', 'in:technical,driver_feedback,incident,operations'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);

        $noteService->create($raceEvent, $user, $validated);

        return to_route('events.show', $raceEvent)->with('status', __('Event note added.'));
    }

    public function storeExpense(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:1000000'],
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

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
