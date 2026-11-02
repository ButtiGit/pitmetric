<?php

namespace App\Http\Controllers;

use App\Models\EventTask;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\EventTaskService;
use App\Services\TeamTaskBoardService;
use App\Services\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class TeamTaskController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, TeamTaskBoardService $board): View
    {
        Gate::authorize('viewAny', EventTask::class);
        if (! Schema::hasColumn('event_tasks', 'assigned_to')) {
            return view('garage.unavailable');
        }
        $filters = $request->validate([
            'bucket' => ['nullable', 'in:urgent,todo,in_progress,done'],
            'event_id' => ['nullable', 'integer'], 'vehicle_id' => ['nullable', 'integer'],
            'mine' => ['nullable', 'in:1'], 'q' => ['nullable', 'string', 'max:120'],
        ]);
        if (! empty($filters['event_id'])) {
            RaceEvent::query()->findOrFail($filters['event_id']);
        }
        if (! empty($filters['vehicle_id'])) {
            Vehicle::query()->findOrFail($filters['vehicle_id']);
        }
        $user = $this->user($request);

        return view('tasks.index', $board->data($context->personal($user), $user, $filters));
    }

    public function store(Request $request, EventTaskService $service): RedirectResponse
    {
        $validated = $request->validate(['event_id' => ['required', 'integer'], ...$this->detailRules()]);
        $event = RaceEvent::query()->findOrFail((int) $validated['event_id']);
        Gate::authorize('update', $event);
        $service->create($event, $this->user($request), $validated);

        return to_route('tasks.index', ['event_id' => $event->getKey(), 'bucket' => 'todo'])->with('status', __('tasks.created_notice'));
    }

    public function update(Request $request, EventTask $eventTask, EventTaskService $service): RedirectResponse
    {
        Gate::authorize('update', $eventTask);
        $service->updateDetails($eventTask, $request->validate($this->detailRules()));

        return back()->with('status', __('tasks.updated_notice'));
    }

    public function status(Request $request, EventTask $eventTask, EventTaskService $service): RedirectResponse
    {
        Gate::authorize('update', $eventTask);
        $service->updateStatus($eventTask, $this->user($request), $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
            'expected_status' => ['required', 'in:todo,in_progress,done'],
        ]));

        return back()->with('status', __('tasks.status_notice'));
    }

    /** @return array<string, list<string>> */
    private function detailRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,high,critical'], 'due_at' => ['nullable', 'date'],
            'event_entry_id' => ['nullable', 'integer'], 'assigned_to' => ['nullable', 'integer'],
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
