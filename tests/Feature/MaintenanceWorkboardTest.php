<?php

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\ComponentUsageEntry;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Services\ComponentUsageCalculator;

it('creates, assigns and moves maintenance work orders across the workboard', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $member = User::factory()->create();
    $workspace->users()->attach($member, [
        'role' => 'mechanic_engineer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine #12',
        'status' => 'active',
    ]);
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);
    $schedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Engine inspection',
        'interval_value' => 18000,
        'warning_value' => 3600,
        'is_active' => true,
    ]);

    $this->post(route('maintenance.work-orders.store'), [
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Inspect valve train',
        'assigned_to' => $member->id,
        'priority' => 'high',
        'due_at' => '2026-09-18 09:00:00',
        'notes' => 'Check clearances before the next event.',
    ])->assertRedirect(route('maintenance.index'));

    $workOrder = MaintenanceWorkOrder::query()->firstOrFail();

    expect($workOrder->status)->toBe('todo')
        ->and($workOrder->assigned_to)->toBe($member->id)
        ->and($workOrder->priority)->toBe('high')
        ->and($workOrder->maintenance_schedule_id)->toBe($schedule->id);

    $this->patch(route('maintenance.work-orders.update', $workOrder), [
        'title' => 'Inspect valve train',
        'assigned_to' => $member->id,
        'priority' => 'critical',
        'status' => 'in_progress',
        'due_at' => '2026-09-18 08:30:00',
        'notes' => 'Work started in the garage.',
    ])->assertRedirect(route('maintenance.index'));

    $workOrder->refresh();

    expect($workOrder->status)->toBe('in_progress')
        ->and($workOrder->priority)->toBe('critical')
        ->and($workOrder->started_at)->not->toBeNull();

    $this->get(route('maintenance.index'))
        ->assertOk()
        ->assertSee('Maintenance workboard')
        ->assertSee('Inspect valve train');
});

it('completes a work order through the maintenance ledger and links the cost', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Gearbox']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Gearbox #02',
        'status' => 'active',
    ]);
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);

    $usedAt = now()->subHours(2);
    $batch = UsageBatch::create([
        'source_type' => 'manual',
        'source_id' => 44,
        'usage_metric_type_id' => $metric->id,
        'value' => 5400,
        'occurred_at' => $usedAt,
        'created_by' => $user->id,
    ]);
    ComponentUsageEntry::create([
        'component_tracker_id' => $tracker->id,
        'usage_batch_id' => $batch->id,
        'value' => 5400,
        'occurred_at' => $usedAt,
        'created_by' => $user->id,
    ]);

    $schedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Gearbox oil service',
        'interval_value' => 7200,
        'warning_value' => 1800,
        'is_active' => true,
    ]);
    $workOrder = MaintenanceWorkOrder::create([
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Replace gearbox oil',
        'priority' => 'high',
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(30),
        'created_by' => $user->id,
    ]);

    expect(app(ComponentUsageCalculator::class)->sinceLastService($tracker))->toBe(5400);

    $this->post(route('maintenance.work-orders.complete', $workOrder), [
        'performed_at' => '2026-09-16 12:00:00',
        'description' => 'Gearbox oil replaced',
        'cost' => '84.50',
        'notes' => 'No metal found in the old oil.',
    ])->assertRedirect(route('maintenance.index'));

    $workOrder->refresh();
    $record = MaintenanceRecord::query()->firstOrFail();
    $expense = Expense::query()
        ->where('related_type', 'maintenance_record')
        ->where('related_id', $record->id)
        ->firstOrFail();

    expect($workOrder->status)->toBe('completed')
        ->and($workOrder->maintenance_record_id)->toBe($record->id)
        ->and($workOrder->completed_at)->not->toBeNull()
        ->and(app(ComponentUsageCalculator::class)->lifetime($tracker))->toBe(5400)
        ->and(app(ComponentUsageCalculator::class)->sinceLastService($tracker))->toBe(0)
        ->and($expense->amount_cents)->toBe(8450)
        ->and($expense->category)->toBe('maintenance');
});

it('closes the open work order when maintenance is completed directly from a schedule', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Brakes']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Front brakes',
        'status' => 'active',
    ]);
    $metric = UsageMetricType::query()->where('key', 'cycles')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);
    $schedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Pad inspection',
        'interval_value' => 20,
        'warning_value' => 5,
        'is_active' => true,
    ]);
    $workOrder = MaintenanceWorkOrder::create([
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Inspect front pads',
        'priority' => 'normal',
        'status' => 'blocked',
        'created_by' => $user->id,
    ]);

    $this->post(route('maintenance.complete', $schedule), [
        'performed_at' => '2026-09-16 13:00:00',
        'description' => 'Front pads inspected',
    ])->assertRedirect(route('maintenance.index'));

    $workOrder->refresh();

    expect($workOrder->status)->toBe('completed')
        ->and($workOrder->maintenance_record_id)->not->toBeNull()
        ->and($workOrder->completed_at)->not->toBeNull();
});

it('rejects assignees outside the current team and duplicate open jobs for a schedule', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $outsider = User::factory()->create();
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Cooling']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Radiator',
        'status' => 'active',
    ]);
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);
    $schedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Cooling system check',
        'interval_value' => 14400,
        'warning_value' => 1800,
        'is_active' => true,
    ]);

    $this->post(route('maintenance.work-orders.store'), [
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Pressure test cooling system',
        'assigned_to' => $outsider->id,
        'priority' => 'normal',
    ])->assertSessionHasErrors('assigned_to');

    MaintenanceWorkOrder::create([
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Existing cooling job',
        'priority' => 'normal',
        'status' => 'todo',
        'created_by' => $user->id,
    ]);

    $this->post(route('maintenance.work-orders.store'), [
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Duplicate cooling job',
        'priority' => 'high',
    ])->assertSessionHasErrors('maintenance_schedule_id');

    expect(MaintenanceWorkOrder::query()->count())->toBe(1);
});
