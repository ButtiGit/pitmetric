<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\Component;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventScheduleItem;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DataManagerController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! (Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess())) {
            return view('demo.workspace', ['initialSection' => 'garage']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        return view('data-manager.index', [
            'vehicles' => Vehicle::query()->withTrashed()->orderBy('name')->get(),
            'parts' => Component::query()->withTrashed()->with(['type', 'activeInstallation.vehicle'])->orderBy('name')->get(),
            'configurations' => Configuration::query()->withTrashed()->with(['vehicle', 'versions' => fn ($query) => $query->latest('version_number')])->orderBy('name')->get(),
            'setups' => TechnicalSetup::query()->withTrashed()->with('vehicle')->withCount('snapshots')->orderBy('name')->get(),
            'circuits' => Circuit::query()->with('layouts')->orderBy('name')->get(),
            'sessions' => Session::query()->with(['vehicle', 'configurationVersion.configuration', 'circuitLayout.circuit', 'raceEvent', 'eventEntry.driver', 'setupSnapshot'])->latest('started_at')->get(),
            'sessionExpenses' => Expense::query()->where('related_type', 'session')->get()->keyBy('related_id'),
            'schedules' => MaintenanceSchedule::query()->with(['tracker.component', 'tracker.metric'])->orderBy('name')->get(),
            'workOrders' => MaintenanceWorkOrder::query()->with(['schedule.tracker.component', 'assignee'])->latest()->get(),
            'maintenanceRecords' => MaintenanceRecord::query()->with(['component', 'schedule'])->latest('performed_at')->limit(100)->get(),
            'expenses' => Expense::query()->withTrashed()->latest('occurred_at')->limit(200)->get(),
            'events' => RaceEvent::query()->withTrashed()->with('circuitLayout.circuit')->latest('start_date')->get(),
            'drivers' => Driver::query()->withTrashed()->orderBy('display_name')->get(),
            'entries' => EventEntry::query()->with(['raceEvent', 'driver', 'vehicle', 'configurationVersion.configuration'])->latest()->get(),
            'eventTasks' => EventTask::query()->withTrashed()->with(['raceEvent', 'eventEntry.driver'])->latest()->get(),
            'eventNotes' => EventNote::query()->withTrashed()->with(['raceEvent', 'eventEntry.driver'])->latest('occurred_at')->get(),
            'scheduleItems' => EventScheduleItem::query()->with(['raceEvent', 'eventEntry.driver'])->latest('starts_at')->get(),
            'activeVehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'activeDrivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'activeLayouts' => \App\Models\CircuitLayout::query()->where('is_active', true)->whereHas('circuit', fn ($query) => $query->where('is_active', true))->with('circuit')->orderBy('name')->get(),
            'versions' => \App\Models\ConfigurationVersion::query()->whereHas('configuration', fn ($query) => $query->where('status', 'active'))->with('configuration.vehicle')->latest()->get(),
            'trackers' => \App\Models\ComponentTracker::query()->where('is_active', true)->with(['component', 'metric'])->whereHas('component', fn ($query) => $query->where('status', 'active'))->get(),
            'members' => $workspace->users()->wherePivot('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
