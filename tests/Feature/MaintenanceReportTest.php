<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\TrackerResetEvent;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\CompleteMaintenanceService;
use App\Services\MaintenanceReportService;
use App\Services\WorkspaceContext;
use Database\Seeders\E2EBrowserSeeder;
use Database\Seeders\E2EMaintenanceReportSeeder;
use Database\Seeders\E2EPrintSeeder;
use Database\Seeders\E2ETracksideSeeder;
use Database\Seeders\E2EWorklistSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([E2EBrowserSeeder::class, E2ETracksideSeeder::class, E2EPrintSeeder::class]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
});

function maintenanceReportFixture(): array
{
    $component = Component::where('name', 'E2E Print Engine')->firstOrFail();
    $vehicle = Vehicle::where('name', 'E2E Print Kart')->firstOrFail();
    $vehicle->componentInstallations()->update(['installed_at' => '2026-01-01 00:00:00']);
    $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Report service', 'interval_value' => 10000, 'is_active' => true]);
    $record = app(CompleteMaintenanceService::class)->complete($schedule, auth()->user(), Carbon::parse('2026-11-01 10:00:00', 'UTC'), 'Completed report service', 1250, str_repeat('Full maintenance note. ', 80).'END-MAINTENANCE-NOTES');

    return compact('component', 'vehicle', 'tracker', 'schedule', 'record');
}

test('maintenance report prints executed records once with canonical cost and full notes without changing history', function () {
    $f = maintenanceReportFixture();
    for ($i = 0; $i < 26; $i++) {
        MaintenanceRecord::create(['component_id' => $f['component']->id, 'performed_at' => '2026-11-01 11:00:00', 'description' => 'Historical record '.$i, 'created_by' => auth()->id()]);
    }
    foreach (['completed', 'completed', 'blocked', 'cancelled'] as $status) {
        MaintenanceWorkOrder::create(['maintenance_schedule_id' => $f['schedule']->id, 'maintenance_record_id' => $status === 'completed' ? $f['record']->id : null, 'status' => $status, 'title' => $status.' order', 'created_by' => auth()->id()]);
    }
    $before = [MaintenanceRecord::count(), Expense::count(), TrackerResetEvent::count(), DB::table('audit_logs')->count()];
    $response = $this->get(route('prints.maintenance', ['page' => 100]))->assertOk()->assertSee('END-MAINTENANCE-NOTES')->assertSee('Historical record 25')->assertSee('€ 12,50')->assertSee('completed order')->assertDontSee('blocked order')->assertDontSee('cancelled order')->assertHeader('Cache-Control', 'no-store, private');
    expect(substr_count($response->getContent(), 'data-maintenance-record='))->toBe(27)
        ->and($response->viewData('costTotal'))->toBe(1250)->and($response->viewData('costCount'))->toBe(1)
        ->and([MaintenanceRecord::count(), Expense::count(), TrackerResetEvent::count(), DB::table('audit_logs')->count()])->toBe($before);
    $this->get(route('maintenance.index'))->assertOk()->assertSee(route('prints.maintenance'));
});

test('kart filter uses historic installation including archived kart instead of current placement', function () {
    $f = maintenanceReportFixture();
    $f['vehicle']->componentInstallations()->update(['removed_at' => '2026-11-02 00:00:00']);
    $replacement = Vehicle::factory()->create(['workspace_id' => $f['vehicle']->workspace_id, 'name' => 'Current different kart']);
    ComponentInstallation::create(['component_id' => $f['component']->id, 'vehicle_id' => $replacement->id, 'installed_at' => '2026-11-02 00:00:00', 'created_by' => auth()->id()]);
    $f['vehicle']->delete();
    $f['component']->delete();
    $this->get(route('prints.maintenance', ['vehicle_id' => $f['vehicle']->id, 'component_id' => $f['component']->id]))->assertOk()->assertSee('Completed report service')->assertViewHas('historicalVehicles', fn ($vehicles) => $vehicles->has($f['vehicle']->id));
    $this->get(route('prints.maintenance', ['vehicle_id' => $replacement->id]))->assertOk()->assertSee(__('maintenance-report.empty'));
});

test('overlapping or absent installations remain unknown and are not attributed to a kart', function () {
    $f = maintenanceReportFixture();
    ComponentInstallation::create(['component_id' => $f['component']->id, 'vehicle_id' => $f['vehicle']->id, 'installed_at' => '2026-01-01 00:00:00', 'created_by' => auth()->id()]);
    $data = app(MaintenanceReportService::class)->data(Workspace::findOrFail($f['vehicle']->workspace_id), []);
    expect($data['records']->first()->report_vehicle_id)->toBeNull();
    $this->get(route('prints.maintenance', ['vehicle_id' => $f['vehicle']->id]))->assertOk()->assertSee(__('maintenance-report.empty'));
    ComponentInstallation::query()->where('component_id', $f['component']->id)->update(['installed_at' => '2026-11-02 00:00:00']);
    $this->get(route('prints.maintenance'))->assertOk()->assertViewHas('historicalVehicles', fn ($vehicles) => $vehicles->isEmpty());
});

test('usage at work excludes future entries and current reset while preserving lifetime and earlier reset boundary', function () {
    $f = maintenanceReportFixture();
    foreach (['2026-10-01 10:00:00' => 1000, '2026-10-15 10:00:00' => 2000, '2026-10-20 10:00:00' => 3000, '2026-11-01 10:00:00' => 4000, '2026-11-02 10:00:00' => 90000] as $date => $value) {
        $batch = UsageBatch::create(['source_type' => 'maintenance_report_fixture', 'source_id' => $value, 'usage_metric_type_id' => $f['tracker']->usage_metric_type_id, 'value' => $value, 'occurred_at' => $date, 'created_by' => auth()->id()]);
        ComponentUsageEntry::create(['usage_batch_id' => $batch->id, 'component_tracker_id' => $f['tracker']->id, 'value' => $value, 'occurred_at' => $date, 'created_by' => auth()->id()]);
    }
    TrackerResetEvent::create(['component_tracker_id' => $f['tracker']->id, 'reset_at' => '2026-10-15 10:00:00', 'created_by' => auth()->id()]);
    TrackerResetEvent::create(['component_tracker_id' => $f['tracker']->id, 'reset_at' => '2026-11-02 10:00:00', 'created_by' => auth()->id()]);
    $response = $this->get(route('prints.maintenance'))->assertOk();
    $record = $response->viewData('records')->first();
    expect((int) $record->report_lifetime)->toBe(10000)->and((int) $record->report_since_service)->toBe(7000)->and((int) $record->report_ambiguous_reset)->toBe(0);
    $response->assertSee('10,')->assertSee('7,');
    TrackerResetEvent::create(['component_tracker_id' => $f['tracker']->id, 'reset_at' => $f['record']->performed_at, 'created_by' => auth()->id()]);
    $this->get(route('prints.maintenance'))->assertOk()->assertViewHas('records', fn ($records) => (int) $records->first()->report_ambiguous_reset === 1);
});

test('date filters cover the entire local day including both DST hours and combine with explicit event links', function () {
    $f = maintenanceReportFixture();
    $event = RaceEvent::where('name', 'E2E Print Weekend')->firstOrFail();
    foreach (['2026-10-24 21:59:59', '2026-10-24 22:00:00', '2026-10-25 00:30:00', '2026-10-25 01:30:00', '2026-10-25 22:59:59', '2026-10-25 23:00:00'] as $i => $date) {
        MaintenanceRecord::create(['component_id' => $f['component']->id, 'event_id' => $event->id, 'performed_at' => $date, 'description' => 'DST record '.$i, 'created_by' => auth()->id()]);
    }
    $response = $this->get(route('prints.maintenance', ['from' => '2026-10-25', 'to' => '2026-10-25', 'event_id' => $event->id]))->assertOk()->assertDontSee('DST record 0')->assertDontSee('DST record 5')->assertDontSee('Completed report service');
    expect($response->viewData('records'))->toHaveCount(4);
    $this->getJson(route('prints.maintenance', ['from' => '2026-11-02', 'to' => '2026-11-01']))->assertUnprocessable()->assertJsonValidationErrors('to');
    $this->getJson(route('prints.maintenance', ['from' => '2026-02-30']))->assertUnprocessable()->assertJsonValidationErrors('from');
});

test('unknown usage and cost stay missing instead of becoming invented zeroes', function () {
    $f = maintenanceReportFixture();
    $f['record']->update(['cost_cents' => null]);
    $response = $this->get(route('prints.maintenance'))->assertOk()->assertSee(__('maintenance-report.unknown'));
    expect($response->viewData('records')->first()->report_lifetime)->toBeNull()->and($response->viewData('costCount'))->toBe(0);
});

test('reports and filter options stay isolated and read only workspace members can print', function () {
    $f = maintenanceReportFixture();
    $eventId = RaceEvent::where('name', 'E2E Print Weekend')->firstOrFail()->id;
    $viewer = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($viewer)->get(route('prints.maintenance'))->assertOk()->assertDontSee('E2E Print Engine')->assertDontSee('Completed report service');
    foreach (['component_id' => $f['component']->id, 'vehicle_id' => $f['vehicle']->id, 'event_id' => $eventId] as $key => $id) {
        $this->get(route('prints.maintenance', [$key => $id]))->assertNotFound();
    }
    $viewer->workspaces()->attach($f['vehicle']->workspace_id, ['role' => 'viewer', 'status' => 'active']);
    app(WorkspaceContext::class)->select($viewer, Workspace::findOrFail($f['vehicle']->workspace_id));
    $this->get(route('prints.maintenance'))->assertOk()->assertSee('Completed report service');
    auth()->logout();
    $this->get(route('prints.maintenance'))->assertRedirect(route('login'));
});

test('browser maintenance fixture preserves the open workboard and supplies complete printable history', function () {
    $this->seed([E2EWorklistSeeder::class, E2EMaintenanceReportSeeder::class]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
    $this->get(route('prints.maintenance'))->assertOk()->assertSee('E2E Maintenance item 18')->assertSee('END-MAINTENANCE-NOTES')
        ->assertViewHas('records', fn ($records) => $records->count() === 18)->assertViewHas('costTotal', 22500);
    expect(MaintenanceWorkOrder::where('title', 'E2E Worklist blocked service')->firstOrFail()->status)->toBe('blocked');
});
