<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Database\Seeders\E2EBrowserSeeder;
use Database\Seeders\E2EPrintSeeder;
use Database\Seeders\E2ETracksideSeeder;

beforeEach(function () {
    $this->seed([E2EBrowserSeeder::class, E2ETracksideSeeder::class, E2EPrintSeeder::class]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
});

function openWorkFixture(): array
{
    $entry = EventEntry::whereHas('vehicle', fn ($q) => $q->where('name', 'E2E Print Kart'))->firstOrFail();
    $component = $entry->vehicle->componentInstallations()->firstOrFail()->component;
    $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Worklist service', 'interval_value' => 10000, 'is_active' => true]);
    $order = MaintenanceWorkOrder::create(['maintenance_schedule_id' => $schedule->id, 'title' => 'Blocked engine job', 'status' => 'blocked', 'priority' => 'high', 'created_by' => auth()->id()]);

    return compact('entry', 'component', 'order');
}

function openWorkTask(EventEntry $entry, array $attributes = []): EventTask
{
    return EventTask::create(['event_id' => $entry->event_id, 'event_entry_id' => $entry->id, 'title' => 'Routine kart task', 'status' => 'todo', 'created_by' => auth()->id(), ...$attributes]);
}

test('work list prints all open records across pages with stable source identities and urgency ordering', function () {
    $f = openWorkFixture();
    $urgent = openWorkTask($f['entry'], ['title' => 'Urgent first', 'priority' => 'critical']);
    $progress = openWorkTask($f['entry'], ['title' => 'Progress second', 'status' => 'in_progress']);
    for ($i = 0; $i < 25; $i++) {
        openWorkTask($f['entry'], ['title' => 'Routine '.$i]);
    }
    openWorkTask($f['entry'], ['title' => 'Completed hidden', 'status' => 'done']);
    MaintenanceWorkOrder::create(['maintenance_schedule_id' => $f['order']->maintenance_schedule_id, 'title' => 'Cancelled hidden', 'status' => 'cancelled', 'created_by' => auth()->id()]);
    $response = $this->get(route('prints.worklist', ['page' => 99, 'bucket' => 'done']))->assertOk()->assertSeeInOrder(['Urgent first', 'Blocked engine job', 'Progress second', 'Routine 24'])
        ->assertDontSee('Completed hidden')->assertDontSee('Cancelled hidden')->assertHeader('Cache-Control', 'no-store, private');
    expect(substr_count($response->getContent(), 'data-work-row='))->toBe(28)
        ->and($urgent->fresh()->status)->toBe('todo')->and($progress->fresh()->status)->toBe('in_progress')->and($f['order']->fresh()->status)->toBe('blocked');
    $response->assertSee('data-work-row="task:'.$urgent->id.'"', false)->assertSee('data-work-row="maintenance:'.$f['order']->id.'"', false);
});

test('event and kart filters apply to task links and current component installations', function () {
    $f = openWorkFixture();
    openWorkTask($f['entry'], ['title' => 'Selected kart task']);
    openWorkTask($f['entry'], ['title' => 'General event task', 'event_entry_id' => null]);
    $other = EventEntry::where('id', '!=', $f['entry']->id)->firstOrFail();
    openWorkTask($other, ['title' => 'Other event hidden']);
    $component = Component::create(['component_type_id' => $f['component']->component_type_id, 'name' => 'Unmounted worklist part', 'status' => 'active']);
    $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Unmounted service', 'interval_value' => 10000, 'is_active' => true]);
    MaintenanceWorkOrder::create(['maintenance_schedule_id' => $schedule->id, 'title' => 'Unlinked maintenance', 'created_by' => auth()->id()]);
    $this->get(route('prints.worklist', ['event_id' => $f['entry']->event_id]))->assertOk()->assertSee('General event task')->assertSee('Blocked engine job')->assertDontSee('Other event hidden')->assertDontSee('Unlinked maintenance');
    $this->get(route('prints.worklist', ['vehicle_id' => $f['entry']->vehicle_id]))->assertOk()->assertSee('Selected kart task')->assertSee('Blocked engine job')->assertDontSee('General event task');
    $f['entry']->vehicle->componentInstallations()->update(['removed_at' => now()]);
    $this->get(route('prints.worklist', ['vehicle_id' => $f['entry']->vehicle_id]))->assertOk()->assertDontSee('Blocked engine job');
    $this->get(route('prints.worklist'))->assertOk()->assertSee('Blocked engine job')->assertSee('Unlinked maintenance');
    $replacement = Vehicle::factory()->create(['workspace_id' => $f['entry']->workspace_id]);
    ComponentInstallation::create(['component_id' => $f['component']->id, 'vehicle_id' => $replacement->id, 'installed_at' => now(), 'created_by' => auth()->id()]);
    $this->get(route('prints.worklist', ['vehicle_id' => $replacement->id]))->assertOk()->assertSee('Blocked engine job')->assertDontSee('Selected kart task');
    $this->get(route('prints.worklist', ['event_id' => $f['entry']->event_id]))->assertOk()->assertDontSee('Blocked engine job');
});

test('assignee mine and search filters cover both kinds without leaking foreign member identities', function () {
    $f = openWorkFixture();
    $user = auth()->user();
    openWorkTask($f['entry'], ['title' => 'Search matching task', 'assigned_to' => $user->id]);
    $f['order']->update(['title' => 'Search matching order', 'assigned_to' => $user->id]);
    openWorkTask($f['entry'], ['title' => 'Other title', 'assigned_to' => $user->id]);
    $this->get(route('prints.worklist', ['mine' => '1', 'q' => 'Search matching']))->assertOk()->assertSee('Search matching task')->assertSee('Search matching order')->assertDontSee('Other title');
    $foreign = User::factory()->create();
    $this->get(route('prints.worklist', ['assigned_to' => $foreign->id]))->assertNotFound();
    $this->get(route('prints.worklist', ['q' => 'No match anywhere']))->assertOk()->assertSee(__('worklist.empty'));
});

test('work lists stay scoped to the selected workspace and remain available to read only members', function () {
    $f = openWorkFixture();
    openWorkTask($f['entry'], ['title' => 'Private work title']);
    $viewer = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($viewer)->get(route('prints.worklist'))->assertOk()->assertDontSee('Private work title');
    $this->get(route('prints.worklist', ['event_id' => $f['entry']->event_id]))->assertNotFound();
    $this->get(route('prints.worklist', ['vehicle_id' => $f['entry']->vehicle_id]))->assertNotFound();
    $viewer->workspaces()->attach($f['entry']->workspace_id, ['role' => 'viewer', 'status' => 'active']);
    app(WorkspaceContext::class)->select($viewer, Workspace::findOrFail($f['entry']->workspace_id));
    $this->get(route('prints.worklist'))->assertOk()->assertSee('Private work title');
    auth()->logout();
    $this->get(route('prints.worklist'))->assertRedirect(route('login'));
});
