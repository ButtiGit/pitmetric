<?php

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\SessionUsageValue;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CompleteMaintenanceService;
use App\Services\PerformanceIntelligenceService;
use App\Services\WeekendReportService;
use App\Services\WorkspaceContext;
use Database\Seeders\E2EBrowserSeeder;
use Database\Seeders\E2EPrintSeeder;
use Database\Seeders\E2ETracksideSeeder;
use Database\Seeders\E2EWeekendReportSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed([E2EBrowserSeeder::class, E2ETracksideSeeder::class, E2EPrintSeeder::class]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
});

function weekendFixture(): array
{
    $event = RaceEvent::where('name', 'E2E Print Weekend')->firstOrFail();
    $session = $event->sessions()->firstOrFail();
    $session->update(['status' => 'finalized', 'completed_laps' => 10, 'duration_seconds' => 600]);
    $component = Component::where('name', 'E2E Print Engine')->firstOrFail();
    $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Weekend engine service', 'interval_value' => 10000, 'is_active' => true]);

    return compact('event', 'session', 'schedule');
}

function weekendCopy(Session $source, array $attributes): Session
{
    $session = $source->replicate()->fill($attributes);
    $session->save();

    return $session;
}

test('weekend statistics preserve saved usage and explicit zero, distinguish missing data and exclude drafts', function () {
    $f = weekendFixture();
    SessionUsageValue::create(['session_id' => $f['session']->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'value' => 12345, 'source' => 'calculated']);
    weekendCopy($f['session'], ['distance_override_meters' => 0, 'started_at' => '2026-11-01 12:00:00']);
    weekendCopy($f['session'], ['completed_laps' => null, 'duration_seconds' => null, 'circuit_layout_id' => null, 'started_at' => '2026-11-01 13:00:00']);
    weekendCopy($f['session'], ['status' => 'draft', 'completed_laps' => 999, 'distance_override_meters' => 999999, 'started_at' => '2026-11-01 14:00:00']);
    $report = app(WeekendReportService::class)->data($f['event']);
    expect($report['summary']['sessions'])->toBe(3)->and($report['draft_count'])->toBe(1)->and($report['sessions'])->toHaveCount(4)
        ->and($report['summary']['distance_meters'])->toBe(12345)->and($report['summary']['distance_known'])->toBe(2)
        ->and($report['summary']['runtime_known'])->toBe(2)->and($report['summary']['cost_per_km_cents'])->toBeNull()
        ->and(app(PerformanceIntelligenceService::class)->dashboard()['summary']['distance_meters'])->toBe(12345);
    $this->get(route('prints.weekend', $f['event']))->assertOk()->assertSee('12,345 km')->assertSee('0,000 km')->assertSee(__('weekend.provisional'))->assertSee(__('weekend.unknown'));
});

test('the expense ledger reconciles direct and general costs once and keeps other currencies separate', function () {
    $f = weekendFixture();
    $other = RaceEvent::create(['name' => 'Other explicit event', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'created_by' => auth()->id()]);
    foreach ([
        ['amount_cents' => 1200, 'related_type' => 'session', 'related_id' => $f['session']->id],
        ['amount_cents' => 2200, 'related_type' => 'event_entry', 'related_id' => $f['session']->event_entry_id],
        ['amount_cents' => 300, 'event_id' => $f['event']->id],
        ['amount_cents' => 400, 'event_id' => $other->id, 'related_type' => 'session', 'related_id' => $f['session']->id],
        ['amount_cents' => 500],
        ['amount_cents' => 700, 'event_id' => $f['event']->id, 'currency' => 'USD'],
    ] as $data) {
        Expense::create(['currency' => 'EUR', 'category' => 'track', 'description' => 'Ledger cost '.$data['amount_cents'], 'occurred_at' => '2026-11-01 10:00:00', 'created_by' => auth()->id(), ...$data]);
    }
    app(CompleteMaintenanceService::class)->complete($f['schedule'], auth()->user(), Carbon::parse('2026-11-01 15:00:00'), 'Linked engine work', 1500, eventId: $f['event']->id);
    $report = app(WeekendReportService::class)->data($f['event']);
    expect($report['expenses'])->toHaveCount(5)->and($report['summary']['cost_cents'])->toBe(5200)
        ->and($report['direct_cost_cents'])->toBe(3400)->and($report['general_cost_cents'])->toBe(1800)
        ->and($report['currency_totals']['USD'])->toBe(700)->and($report['maintenance']['records'])->toHaveCount(1);
    $this->get(route('prints.weekend', $f['event']))->assertOk()->assertDontSee('Ledger cost 400')->assertDontSee('Ledger cost 500')->assertSee('7,00 USD')->assertSee('52,00 €');
});

test('session details use immutable setup snapshots, show changes and keep recorded weather and pressures', function () {
    $f = weekendFixture();
    $second = weekendCopy($f['session'], ['started_at' => '2026-11-01 12:00:00']);
    $snapshot = $f['session']->setupSnapshot;
    $values = $snapshot->getAttribute('values');
    $values['gear_ratio'] = 5;
    $snapshot->replicate()->fill(['session_id' => $second->id, 'values' => $values])->save();
    $snapshot->technicalSetup->update(['values' => ['gear_ratio' => 999]]);
    Http::preventStrayRequests();
    $auditBefore = DB::table('audit_logs')->count();
    $report = app(WeekendReportService::class)->data($f['event']);
    expect($report['sessions'][0]['best_time'])->toBe('1:00.002')->and($report['sessions'][0]['weather'])->toHaveCount(3)
        ->and($report['sessions'][0]['session']->tyreReadings)->toHaveCount(2)
        ->and($report['sessions'][1]['changes'])->toContain(['label' => __('prints.fields.gear_ratio'), 'before' => '4 :1', 'after' => '5 :1']);
    $this->get(route('insights.events.report', $f['event']))->assertOk()->assertSee('1:00.002')->assertSee('MET Norway')->assertSee('NASA POWER')->assertSee('END-PRINT-NOTES')->assertDontSee('999');
    $this->get(route('prints.weekend', $f['event']))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee(__('prints.history_mismatch'));
    expect(DB::table('audit_logs')->count())->toBe($auditBefore);
    Http::assertNothingSent();
});

test('CSV preserves details and protects user text from spreadsheet formula execution', function () {
    $f = weekendFixture();
    $f['event']->update(['notes' => '=HYPERLINK("https://example.invalid")']);
    $response = $this->get(route('insights.events.report.csv', $f['event']))->assertOk()->assertDownload()->assertHeader('Cache-Control', 'no-store, private');
    $csv = $response->streamedContent();
    expect($csv)->toContain('Europe/Rome')->toContain('2026-11-01 11:00')->toContain('60002')->toContain('Pressure cold')->toContain('Pressure hot')
        ->toContain('END-PRINT-NOTES')->toContain('Weather historical')->toContain("'=HYPERLINK");
});

test('maintenance completion and later event reassignment keep source and generated expenses together', function () {
    $f = weekendFixture();
    $payload = ['performed_at' => '2026-11-01 15:00:00', 'description' => 'Assigned maintenance', 'cost' => 12.50, 'event_id' => $f['event']->id];
    $this->post(route('maintenance.complete', $f['schedule']), $payload)->assertRedirect();
    $record = MaintenanceRecord::where('description', 'Assigned maintenance')->firstOrFail();
    expect($record->event_id)->toBe($f['event']->id)->and(Expense::where('related_type', 'maintenance_record')->where('related_id', $record->id)->firstOrFail()->event_id)->toBe($f['event']->id);
    $this->patch(route('maintenance.records.event', $record), ['event_id' => null])->assertRedirect();
    expect($record->fresh()->event_id)->toBeNull()->and(Expense::where('related_type', 'maintenance_record')->where('related_id', $record->id)->firstOrFail()->event_id)->toBeNull();
    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser);
    $foreign = RaceEvent::create(['name' => 'Foreign weekend', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'created_by' => $otherUser->id]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
    $this->patch(route('maintenance.records.event', $record), ['event_id' => $foreign->id])->assertNotFound();
    $this->post(route('maintenance.complete', $f['schedule']), [...$payload, 'event_id' => $foreign->id])->assertNotFound();
    expect(MaintenanceRecord::where('description', 'Assigned maintenance')->count())->toBe(1);
});

test('manual expenses can be linked and unlinked without bypassing operational source ownership', function () {
    $f = weekendFixture();
    $payload = ['event_id' => $f['event']->id, 'amount' => 20, 'category' => 'travel', 'description' => 'Explicit manual expense', 'occurred_at' => '2026-11-01 12:00:00'];
    $this->post(route('expenses.store'), $payload)->assertRedirect();
    $expense = Expense::where('description', $payload['description'])->firstOrFail();
    expect($expense->event_id)->toBe($f['event']->id);
    $this->put(route('expenses.update', $expense), [...$payload, 'event_id' => null])->assertRedirect();
    expect($expense->fresh()->event_id)->toBeNull();
    $expense->update(['related_type' => 'session', 'related_id' => $f['session']->id]);
    $this->putJson(route('expenses.update', $expense), $payload)->assertUnprocessable();
    expect($expense->fresh()->event_id)->toBeNull();
});

test('all matching tasks expenses and notes are included without pagination or truncation', function () {
    $f = weekendFixture();
    for ($i = 1; $i <= 35; $i++) {
        EventTask::create(['event_id' => $f['event']->id, 'title' => 'Report task '.$i, 'description' => str_repeat('Full task note. ', 50).'TASK-END-'.$i, 'created_by' => auth()->id()]);
        Expense::create(['event_id' => $f['event']->id, 'currency' => 'EUR', 'category' => 'track', 'description' => 'Report expense '.$i, 'amount_cents' => 100, 'occurred_at' => '2026-11-01 12:00:00', 'created_by' => auth()->id()]);
    }
    $response = $this->get(route('prints.weekend', [$f['event'], 'page' => 99]))->assertOk()->assertSee('TASK-END-35')->assertSee('Report expense 35')->assertDontSee('tasks.statuses.');
    expect(substr_count($response->getContent(), 'data-weekend-task='))->toBe(35)->and(substr_count($response->getContent(), 'data-weekend-expense='))->toBe(35);
});

test('reports exports and explicit links respect the selected workspace and viewer permissions', function () {
    $f = weekendFixture();
    $viewer = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($viewer);
    foreach (['prints.weekend', 'insights.events.report', 'insights.events.report.csv'] as $route) {
        $this->get(route($route, $f['event']))->assertNotFound();
    }
    $this->postJson(route('expenses.store'), ['event_id' => $f['event']->id, 'amount' => 20, 'category' => 'travel', 'description' => 'Foreign link', 'occurred_at' => '2026-11-01'])->assertUnprocessable()->assertJsonValidationErrors('event_id');
    $viewer->workspaces()->attach($f['event']->workspace_id, ['role' => 'viewer', 'status' => 'active']);
    app(WorkspaceContext::class)->select($viewer, Workspace::findOrFail($f['event']->workspace_id));
    $this->get(route('prints.weekend', $f['event']))->assertOk()->assertSee($f['event']->name);
    $this->get(route('insights.events.report.csv', $f['event']))->assertOk();
    $this->post(route('maintenance.complete', $f['schedule']), ['event_id' => $f['event']->id])->assertForbidden();
});

test('browser weekend fixture provides a complete event without corrupting other report fixtures', function () {
    $this->seed(E2EWeekendReportSeeder::class);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
    $event = RaceEvent::where('name', E2EWeekendReportSeeder::EVENT)->firstOrFail();
    $report = app(WeekendReportService::class)->data($event);
    expect($report['summary']['sessions'])->toBe(3)->and($report['draft_count'])->toBe(1)->and($report['operations']['tasks_total'])->toBe(6)
        ->and($report['maintenance']['records'])->toHaveCount(3)->and($report['summary']['cost_cents'])->toBe(7350);
    $this->get(route('prints.weekend', $event))->assertOk()->assertSee('END-WEEKEND-NOTES')->assertSee('END-WEEKEND-TASK-6')->assertSee('END-WEEKEND-ISSUE');
});

test('report query count stays bounded as sessions and tasks grow', function () {
    $f = weekendFixture();
    $service = app(WeekendReportService::class);
    $service->data($f['event']);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $service->data($f['event']);
    $baseline = count(DB::getQueryLog());
    DB::disableQueryLog();
    for ($i = 1; $i <= 20; $i++) {
        weekendCopy($f['session'], ['started_at' => '2026-11-01 13:00:00']);
        EventTask::create(['event_id' => $f['event']->id, 'event_entry_id' => $f['session']->event_entry_id, 'title' => 'Query count task '.$i, 'assigned_to' => auth()->id(), 'created_by' => auth()->id()]);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    $service->data($f['event']);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($queries)->toBeLessThanOrEqual($baseline + 4);
});

test('empty events do not collect unrelated unassigned costs or manufacture complete totals', function () {
    $f = weekendFixture();
    $event = RaceEvent::create(['name' => 'Empty report weekend', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'created_by' => auth()->id()]);
    Expense::create(['currency' => 'EUR', 'category' => 'travel', 'description' => 'Unassigned hidden expense', 'amount_cents' => 100, 'occurred_at' => '2026-11-01', 'created_by' => auth()->id()]);
    $response = $this->get(route('prints.weekend', $event))->assertOk()->assertDontSee('Unassigned hidden expense')->assertSee(__('weekend.unknown'));
    expect($response->viewData('report')['expenses'])->toBeEmpty();
});
