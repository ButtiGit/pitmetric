<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\ComponentUsageEntry;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\TrackerResetEvent;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CompleteMaintenanceService;
use App\Services\ComponentUsageCalculator;
use App\Services\CreateConfigurationVersionService;
use App\Services\VehicleReadinessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

function readinessKart(User $user, string $name = 'Readiness kart'): array
{
    $vehicle = Vehicle::factory()->create(['workspace_id' => $user->workspaces()->firstOrFail()->id, 'name' => $name, 'category' => 'kart']);
    $type = ComponentType::firstOrCreate(['name' => 'Readiness engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => $name.' engine', 'status' => 'active']);
    $installation = ComponentInstallation::create(['vehicle_id' => $vehicle->id, 'component_id' => $component->id, 'created_by' => $user->id, 'installed_at' => now()->subDays(2)]);
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => $metric->id, 'is_active' => true]);
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Readiness rebuild', 'interval_value' => 10000, 'warning_value' => 2000, 'is_active' => true]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => $name.' configuration', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);

    return compact('vehicle', 'component', 'installation', 'tracker', 'schedule', 'configuration', 'version');
}

function readinessUsage(User $user, ComponentTracker $tracker, int $value, string $when): void
{
    $batch = UsageBatch::create(['source_type' => 'manual', 'source_id' => fake()->unique()->numberBetween(1, 1000000), 'usage_metric_type_id' => $tracker->usage_metric_type_id,
        'value' => $value, 'occurred_at' => $when, 'created_by' => $user->id]);
    ComponentUsageEntry::create(['component_tracker_id' => $tracker->id, 'usage_batch_id' => $batch->id,
        'value' => $value, 'occurred_at' => $when, 'created_by' => $user->id]);
}

function readinessCard(User $user, Vehicle $vehicle): array
{
    return app(VehicleReadinessService::class)->data($user->workspaces()->firstOrFail(), ['vehicle_id' => $vehicle->id])['cards'][0];
}

test('missing preparation data is never treated as an all clear vehicle', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $user->workspaces()->firstOrFail()->id]);
    expect(readinessCard($user, $vehicle)['state'])->toBe('incomplete');
    $kart = readinessKart($user);
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('clear');
    $kart['tracker']->update(['is_active' => false]);
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('incomplete');
    $kart['tracker']->update(['is_active' => true]);
    $kart['configuration']->update(['status' => 'archived']);
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('incomplete');
});

test('maintenance thresholds and completion use the existing usage ledger and reset rules', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = readinessKart($user);
    readinessUsage($user, $kart['tracker'], 8500, now()->subHour()->toDateTimeString());
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('pending');
    readinessUsage($user, $kart['tracker'], 1500, now()->subMinute()->toDateTimeString());
    $card = readinessCard($user, $kart['vehicle']);
    expect($card['state'])->toBe('attention')->and($card['health']['overdue'])->toBe(1);
    app(CompleteMaintenanceService::class)->complete($kart['schedule'], $user, Carbon::now(), 'Rebuild completed');
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('clear')
        ->and(app(ComponentUsageCalculator::class)->lifetime($kart['tracker']))->toBe(10000);
});

test('usage batches match individual calculations including multiple resets and exact reset timestamps', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = readinessKart($user);
    readinessUsage($user, $kart['tracker'], 500, '2026-10-28 09:00:00');
    readinessUsage($user, $kart['tracker'], 1000, '2026-10-29 09:00:00');
    readinessUsage($user, $kart['tracker'], 2000, '2026-10-30 09:00:00');
    readinessUsage($user, $kart['tracker'], 3000, '2026-10-31 09:00:00');
    foreach (['2026-10-28 09:00:00', '2026-10-30 09:00:00'] as $when) {
        TrackerResetEvent::create(['component_tracker_id' => $kart['tracker']->id, 'reset_at' => $when, 'created_by' => $user->id]);
    }
    $calculator = app(ComponentUsageCalculator::class);
    $totals = $calculator->snapshot(collect([$kart['tracker']]))[$kart['tracker']->id];
    expect($totals)->toBe(['used' => 3000, 'lifetime' => 6500])
        ->and($totals['used'])->toBe($calculator->sinceLastService($kart['tracker']))
        ->and($totals['lifetime'])->toBe($calculator->lifetime($kart['tracker']));
});

test('explicit tracker limits are used when no active schedule exists and missing limits remain incomplete', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = readinessKart($user);
    $kart['schedule']->update(['is_active' => false]);
    $kart['tracker']->update(['service_limit' => 10000, 'warning_threshold' => 2000]);
    readinessUsage($user, $kart['tracker'], 8500, now()->subHour()->toDateTimeString());
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('pending');
    $kart['tracker']->update(['service_limit' => null]);
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('incomplete');
});

test('open maintenance follows currently installed components and excludes completed orders', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $first = readinessKart($user, 'Kart A');
    $second = readinessKart($user, 'Kart B');
    $order = MaintenanceWorkOrder::create(['maintenance_schedule_id' => $first['schedule']->id,
        'title' => 'Engine inspection blocked', 'priority' => 'normal', 'status' => 'blocked', 'created_by' => $user->id, 'due_at' => now()->subHour()]);
    $this->get(route('tasks.index'))->assertOk()->assertSee('Engine inspection blocked')->assertDontSee('readiness.order_blocked');
    expect(readinessCard($user, $first['vehicle'])['state'])->toBe('attention')
        ->and(readinessCard($user, $second['vehicle'])['urgent_orders'])->toBe(0);
    $first['installation']->update(['removed_at' => now()]);
    ComponentInstallation::create(['vehicle_id' => $second['vehicle']->id, 'component_id' => $first['component']->id, 'created_by' => $user->id, 'installed_at' => now()]);
    expect(readinessCard($user, $first['vehicle'])['work_orders'])->toHaveCount(0)
        ->and(readinessCard($user, $second['vehicle'])['urgent_orders'])->toBe(1);
    $order->update(['status' => 'completed', 'completed_at' => now()]);
    expect(readinessCard($user, $second['vehicle'])['work_orders'])->toHaveCount(0);
});

test('colleagues urgent tasks remain visible in preparation when the task list is filtered to mine', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $member = User::factory()->create();
    $user->workspaces()->firstOrFail()->users()->attach($member, ['role' => 'mechanic_engineer', 'status' => 'active']);
    $this->actingAs($user);
    $kart = readinessKart($user);
    $event = RaceEvent::create(['name' => 'Readiness event', 'start_date' => today(), 'end_date' => today(), 'status' => 'active', 'created_by' => $user->id]);
    $driver = Driver::create(['display_name' => 'Readiness driver', 'status' => 'active']);
    $entry = EventEntry::create(['event_id' => $event->id, 'vehicle_id' => $kart['vehicle']->id, 'driver_id' => $driver->id, 'configuration_version_id' => $kart['version']->id]);
    $task = EventTask::create(['event_id' => $event->id, 'event_entry_id' => $entry->id, 'title' => 'Colleague work', 'priority' => 'critical', 'status' => 'todo', 'assigned_to' => $member->id, 'created_by' => $user->id]);
    $this->get(route('tasks.index', ['mine' => '1', 'q' => 'other search', 'event_id' => $event->id]))->assertOk()
        ->assertViewHas('tasks', fn ($tasks): bool => $tasks->total() === 0)
        ->assertViewHas('vehicleReadiness', fn ($data): bool => $data['cards'][0]['urgent_tasks'] === 1 && $data['cards'][0]['state'] === 'attention');
    $this->patch(route('tasks.status', $task), ['status' => 'done', 'expected_status' => 'todo'])->assertRedirect();
    expect(readinessCard($user, $kart['vehicle'])['state'])->toBe('clear');
});

test('unlinked team tasks are shown separately and do not get assigned to an arbitrary kart', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = readinessKart($user);
    $event = RaceEvent::create(['name' => 'Team event', 'start_date' => today(), 'end_date' => today(), 'status' => 'active', 'created_by' => $user->id]);
    EventTask::create(['event_id' => $event->id, 'title' => 'Prepare paddock', 'priority' => 'high', 'status' => 'todo', 'created_by' => $user->id]);
    $this->get(route('tasks.index'))->assertOk()->assertViewHas('vehicleReadiness', fn ($data): bool => $data['general_tasks'] === 1 && $data['cards'][0]['open_tasks'] === 0);
});

test('vehicle readiness stays isolated from foreign workspaces and from archived vehicles', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $other = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($other);
    $foreign = readinessKart($other, 'Foreign readiness secret');
    $this->actingAs($owner);
    $own = readinessKart($owner, 'Own kart');
    $this->get(route('tasks.index'))->assertOk()->assertDontSee('Foreign readiness secret');
    $this->get(route('tasks.index', ['vehicle_id' => $foreign['vehicle']->id]))->assertNotFound();
    $own['vehicle']->delete();
    $data = app(VehicleReadinessService::class)->data($owner->workspaces()->firstOrFail(), []);
    expect($data['cards'])->toBeEmpty();
});

test('preparation pagination stays independent from the task list pagination', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    Vehicle::factory()->count(14)->create(['workspace_id' => $user->workspaces()->firstOrFail()->id]);
    $this->get(route('tasks.index', ['readiness_page' => 2]))->assertOk()
        ->assertViewHas('vehicleReadiness', fn ($data): bool => $data['vehicles']->total() === 14 && count($data['cards']) === 2);
});

test('the number of preparation queries does not grow with each kart and tracker', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    readinessKart($user, 'Kart 0');
    $service = app(VehicleReadinessService::class);
    $workspace = $user->workspaces()->firstOrFail();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $service->data($workspace, []);
    $single = count(DB::getQueryLog());
    DB::disableQueryLog();
    for ($i = 1; $i <= 5; $i++) {
        readinessKart($user, 'Kart '.$i);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    $data = $service->data($workspace, []);
    $multiple = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($data['cards'])->toHaveCount(6)->and($multiple)->toBeLessThanOrEqual($single + 1);
});
