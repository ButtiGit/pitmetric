<?php

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\ComponentUsageEntry;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Services\CompleteMaintenanceService;
use App\Services\ComponentUsageCalculator;
use Illuminate\Support\Carbon;

it('resets usage since service without erasing lifetime history and links service cost', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Engine #01', 'status' => 'active']);
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);

    $usedAt = now()->subHour();
    $batch = UsageBatch::create([
        'source_type' => 'manual',
        'source_id' => 1,
        'usage_metric_type_id' => $metric->id,
        'value' => 7200,
        'occurred_at' => $usedAt,
        'created_by' => $user->id,
    ]);
    ComponentUsageEntry::create([
        'component_tracker_id' => $tracker->id,
        'usage_batch_id' => $batch->id,
        'value' => 7200,
        'occurred_at' => $usedAt,
        'created_by' => $user->id,
    ]);

    $schedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Engine rebuild',
        'interval_value' => 18000,
        'warning_value' => 3600,
        'is_active' => true,
    ]);

    $calculator = app(ComponentUsageCalculator::class);

    expect($calculator->lifetime($tracker))->toBe(7200)
        ->and($calculator->sinceLastService($tracker))->toBe(7200);

    $record = app(CompleteMaintenanceService::class)->complete(
        $schedule,
        $user,
        Carbon::now(),
        'Engine rebuild',
        12500,
    );

    $expense = Expense::query()->where('related_type', 'maintenance_record')->where('related_id', $record->id)->firstOrFail();

    expect($tracker->resetEvents()->count())->toBe(1)
        ->and($calculator->lifetime($tracker))->toBe(7200)
        ->and($calculator->sinceLastService($tracker))->toBe(0)
        ->and($expense->amount_cents)->toBe(12500)
        ->and($expense->category)->toBe('maintenance');
});
