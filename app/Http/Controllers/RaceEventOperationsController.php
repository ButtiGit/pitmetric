<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
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

        Driver::create([
            ...$validated,
            'status' => 'active',
        ]);

        return to_route('events.index')->with('status', __('Driver added.'));
    }

    public function storeEntry(Request $request, RaceEvent $raceEvent, WorkspaceContext $workspaceContext): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $workspace = $workspaceContext->personal($this->user($request));
        $validated = $request->validate([
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'configuration_version_id' => ['required', 'integer'],
            'entry_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $driver = Driver::query()->whereKey((int) $validated['driver_id'])->firstOrFail();
        $vehicle = Vehicle::query()->whereKey((int) $validated['vehicle_id'])->firstOrFail();
        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query
                ->where('workspace_id', $workspace->getKey())
                ->where('vehicle_id', $vehicle->getKey()))
            ->firstOrFail();

        EventEntry::create([
            'event_id' => $raceEvent->getKey(),
            'driver_id' => $driver->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'configuration_version_id' => $version->getKey(),
            'entry_number' => $validated['entry_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('events.show', $raceEvent)->with('status', __('Event entry added.'));
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

    public function updateTask(Request $request, EventTask $eventTask): RedirectResponse
    {
        Gate::authorize('update', $eventTask);
        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
        ]);

        $eventTask->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        return to_route('events.show', $eventTask->event_id)->with('status', __('Task status updated.'));
    }

    public function storeNote(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $user = $this->user($request);
        $validated = $request->validate([
            'event_entry_id' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);
        $entryId = $this->entryIdForEvent($raceEvent, $validated['event_entry_id'] ?? null);

        EventNote::create([
            'event_id' => $raceEvent->getKey(),
            'event_entry_id' => $entryId,
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

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
