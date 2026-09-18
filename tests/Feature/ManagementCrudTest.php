<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\Configuration;
use App\Models\Document;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CompleteMaintenanceService;
use App\Services\CreateConfigurationVersionService;
use App\Services\OperationCostService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
    $this->circuit = Circuit::create(['name' => 'Test circuit']);
    $this->layout = $this->circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1200, 'is_active' => true]);
    $this->event = RaceEvent::create(['name' => 'Test weekend', 'circuit_layout_id' => $this->layout->id, 'start_date' => now(), 'end_date' => now()->addDay(), 'status' => 'planned', 'created_by' => $this->operator->id]);
});

test('circuit deletion and restoration preserve history and remove active choices', function () {
    $this->delete(route('circuits.destroy', $this->circuit))->assertSessionHasNoErrors();
    expect(Circuit::query()->count())->toBe(0)->and($this->event->fresh()->circuitLayout->circuit->name)->toBe('Test circuit');
    $this->get(route('events.index'))->assertSuccessful()->assertViewHas('layouts', fn ($layouts) => $layouts->isEmpty());
    $this->get(route('sessions.index'))->assertSuccessful()->assertViewHas('layouts', fn ($layouts) => $layouts->isEmpty());
    $this->get(route('circuits.index'))->assertSuccessful()->assertSee('Removed circuits')->assertSee('Restore');
    $this->post(route('events.store'), ['name' => 'Invalid', 'circuit_layout_id' => $this->layout->id, 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString()])->assertNotFound();
    $this->patch(route('circuits.restore', $this->circuit))->assertSessionHasNoErrors();
    expect($this->circuit->fresh()->trashed())->toBeFalse();
});

test('layout deletion keeps historical references and restoration checks its parent', function () {
    $other = Circuit::create(['name' => 'Other']);
    $this->delete(route('circuits.layouts.destroy', [$other, $this->layout]))->assertNotFound();
    $this->delete(route('circuits.layouts.destroy', [$this->circuit, $this->layout]))->assertSessionHasNoErrors();
    expect($this->event->fresh()->circuitLayout->length_meters)->toBe(1200)->and($this->circuit->layouts()->count())->toBe(0);
    $this->patch(route('circuits.layouts.restore', [$other, $this->layout]))->assertNotFound();
    $this->patch(route('circuits.layouts.restore', [$this->circuit, $this->layout]))->assertSessionHasNoErrors();
    expect($this->circuit->layouts()->count())->toBe(1);
});

test('weekends can be archived and restored without losing notes', function () {
    $note = EventNote::create(['event_id' => $this->event->id, 'kind' => 'technical', 'body' => 'Preserve', 'occurred_at' => now(), 'created_by' => $this->operator->id]);
    $this->delete(route('events.destroy', $this->event))->assertSessionHasNoErrors();
    expect($note->fresh()->raceEvent->trashed())->toBeTrue();
    $this->get(route('events.index'))->assertSuccessful()->assertSee('Archived weekends');
    $this->patch(route('events.restore', $this->event))->assertSessionHasNoErrors();
    expect($note->fresh()->raceEvent->trashed())->toBeFalse();
});

test('weekend notes and task details can be corrected and removed', function () {
    $note = EventNote::create(['event_id' => $this->event->id, 'kind' => 'technical', 'body' => 'Before', 'occurred_at' => now(), 'created_by' => $this->operator->id]);
    $task = EventTask::create(['event_id' => $this->event->id, 'title' => 'Before', 'priority' => 'normal', 'status' => 'todo', 'created_by' => $this->operator->id]);
    $this->put(route('events.notes.update', $note), ['kind' => 'driver_feedback', 'body' => 'After', 'occurred_at' => now()->toDateTimeString()])->assertSessionHasNoErrors();
    $this->put(route('events.tasks.details', $task), ['title' => 'After', 'priority' => 'high', 'description' => 'Check brakes'])->assertSessionHasNoErrors();
    expect($note->fresh()->body)->toBe('After')->and($task->fresh()->priority)->toBe('high');
    $this->get(route('events.show', $this->event))->assertSuccessful()->assertSee('edit-note-'.$note->id)->assertSee('edit-task-'.$task->id);
    $this->delete(route('events.notes.destroy', $note))->assertSessionHasNoErrors();
    $this->delete(route('events.tasks.destroy', $task))->assertSessionHasNoErrors();
    expect(EventNote::query()->count())->toBe(0)->and(EventTask::query()->count())->toBe(0);
});

test('a task linked to a cost cannot be removed', function () {
    $task = EventTask::create(['event_id' => $this->event->id, 'title' => 'Brakes', 'priority' => 'normal', 'status' => 'todo', 'created_by' => $this->operator->id]);
    $this->patch(route('events.tasks.update', $task), ['status' => 'done', 'operation_cost' => 12.34])->assertSessionHasNoErrors();
    $this->delete(route('events.tasks.destroy', $task))->assertSessionHasErrors('task');
    expect($task->fresh()->status)->toBe('done')->and((int) Expense::query()->sum('amount_cents'))->toBe(1234);
});

test('a failed task cost rolls back its completion', function () {
    $task = EventTask::create(['event_id' => $this->event->id, 'title' => 'Brakes', 'priority' => 'normal', 'status' => 'todo', 'created_by' => $this->operator->id]);
    $this->mock(OperationCostService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Cost unavailable'));
    $this->patch(route('events.tasks.update', $task), ['status' => 'done', 'operation_cost' => 12.34])->assertServerError();
    expect($task->fresh()->status)->toBe('todo')->and($task->fresh()->completed_at)->toBeNull()->and(Expense::query()->count())->toBe(0);
});

test('entries without history can be corrected and removed', function () {
    $vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $driver = Driver::create(['display_name' => 'Driver']);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Build', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $this->operator, []);
    $entry = EventEntry::create(['event_id' => $this->event->id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'configuration_version_id' => $version->id]);
    $this->get(route('events.show', $this->event))->assertSuccessful()->assertSee('edit-entry-'.$entry->id);
    $this->put(route('events.entries.update', $entry), ['entry_number' => '27', 'notes' => 'Corrected'])->assertSessionHasNoErrors();
    expect($entry->fresh()->entry_number)->toBe('27');
    $note = EventNote::create(['event_id' => $this->event->id, 'event_entry_id' => $entry->id, 'kind' => 'technical', 'body' => 'History', 'occurred_at' => now(), 'created_by' => $this->operator->id]);
    $this->delete(route('events.entries.destroy', $entry))->assertSessionHasErrors('entry');
    $note->delete();
    $this->delete(route('events.entries.destroy', $entry))->assertSessionHasNoErrors();
    expect($entry->fresh())->toBeNull();
});

test('schedule corrections archive and restore retain completed maintenance', function () {
    $this->post(route('components.store'), ['name' => 'Engine', 'type_name' => 'Engine', 'metric_key' => 'runtime'])->assertSessionHasNoErrors();
    $tracker = Component::query()->firstOrFail()->trackers()->firstOrFail();
    $this->post(route('maintenance.store'), ['component_tracker_id' => $tracker->id, 'name' => 'Service', 'interval_display' => 10, 'warning_display' => 2])->assertSessionHasNoErrors();
    $schedule = MaintenanceSchedule::query()->firstOrFail();
    $this->put(route('maintenance.update', $schedule), ['name' => 'Service revised', 'interval_display' => 12.5, 'warning_display' => 2])->assertSessionHasNoErrors();
    expect($schedule->fresh()->interval_value)->toBe(45000);
    $record = app(CompleteMaintenanceService::class)->complete($schedule, $this->operator, Carbon::now(), 'Service done', 2000);
    $this->delete(route('maintenance.destroy', $schedule))->assertSessionHasNoErrors();
    $this->get(route('maintenance.index'))->assertSuccessful()->assertSee('Archived schedules')->assertSee('Service done');
    $this->post(route('maintenance.complete', $schedule), ['performed_at' => now()->toDateTimeString(), 'description' => 'Blocked'])->assertSessionHasErrors('schedule');
    $this->patch(route('maintenance.restore', $schedule))->assertSessionHasNoErrors();
    expect($schedule->fresh()->is_active)->toBeTrue()->and($record->fresh()->cost_cents)->toBe(2000);
    $this->get(route('maintenance.index'))->assertSuccessful()->assertSee('edit-schedule-'.$schedule->id);
});

test('open maintenance work must be cancelled before archiving and cannot be completed afterwards', function () {
    $this->post(route('components.store'), ['name' => 'Engine', 'type_name' => 'Engine', 'metric_key' => 'runtime']);
    $tracker = Component::query()->firstOrFail()->trackers()->firstOrFail();
    $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'Service', 'interval_value' => 36000, 'is_active' => true]);
    $order = MaintenanceWorkOrder::create(['maintenance_schedule_id' => $schedule->id, 'title' => 'Service', 'priority' => 'normal', 'status' => 'todo', 'created_by' => $this->operator->id]);
    $this->get(route('maintenance.index'))->assertSuccessful()->assertSee('Cancel work');
    $this->delete(route('maintenance.destroy', $schedule))->assertSessionHasErrors('schedule');
    $this->delete(route('maintenance.work-orders.destroy', $order))->assertSessionHasNoErrors();
    expect(fn () => app(CompleteMaintenanceService::class)->complete($schedule, $this->operator, Carbon::now(), 'Duplicate', null, null, $order))->toThrow(ValidationException::class);
    $this->delete(route('maintenance.destroy', $schedule))->assertSessionHasNoErrors();
    expect($order->fresh()->status)->toBe('cancelled');
});

test('maintenance intervals cannot round to zero or have a warning larger than the interval', function () {
    $this->post(route('components.store'), ['name' => 'Engine', 'type_name' => 'Engine', 'metric_key' => 'runtime']);
    $tracker = Component::query()->firstOrFail()->trackers()->firstOrFail();
    $base = ['component_tracker_id' => $tracker->id, 'name' => 'Service'];
    $this->post(route('maintenance.store'), [...$base, 'interval_display' => 0.00001])->assertSessionHasErrors('interval_display');
    $this->post(route('maintenance.store'), [...$base, 'interval_display' => 1, 'warning_display' => 2])->assertSessionHasErrors('warning_display');
});

test('session notes can change without rewriting finalized usage', function () {
    $vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Build', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $this->operator, []);
    $this->post(route('sessions.store'), ['configuration_version_id' => $version->id, 'session_type' => 'test', 'started_at' => now()->toDateTimeString(), 'duration_minutes' => 10])->assertSessionHasNoErrors();
    $session = Session::query()->firstOrFail();
    $this->put(route('sessions.update', $session), ['notes' => 'New notes', 'duration_seconds' => 1, 'status' => 'draft'])->assertSessionHasNoErrors();
    expect($session->fresh()->notes)->toBe('New notes')->and($session->fresh()->duration_seconds)->toBe(600)->and($session->fresh()->status)->toBe('finalized');
});

test('document metadata editing preserves the stored file and association', function () {
    $document = Document::create(['name' => 'Manual', 'original_name' => 'manual.pdf', 'disk' => 'local', 'path' => 'private/manual.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 123, 'uploaded_by' => $this->operator->id]);
    $this->put(route('control-center.documents.update', $document), ['name' => 'Engine manual', 'notes' => 'Revision B', 'path' => 'other'])->assertSessionHasNoErrors();
    expect($document->fresh()->name)->toBe('Engine manual')->and($document->fresh()->path)->toBe('private/manual.pdf');
    $this->get(route('control-center.index'))->assertSuccessful()->assertSee('edit-document-'.$document->id);
});

test('team name editing does not alter memberships', function () {
    $workspace = $this->operator->workspaces()->firstOrFail();
    $this->put(route('team.update'), ['name' => 'Racing team'])->assertSessionHasNoErrors();
    expect($workspace->fresh()->name)->toBe('Racing team')->and($workspace->users()->count())->toBe(1);
    $this->get(route('team.index'))->assertSuccessful()->assertSee('edit-team');
});

test('telemetry removal deletes imported children and source file', function () {
    Storage::fake('local');
    Storage::disk('local')->put('telemetry/test.csv', 'time,speed');
    $vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Build', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $this->operator, []);
    $this->post(route('sessions.store'), ['configuration_version_id' => $version->id, 'session_type' => 'test', 'started_at' => now()->toDateTimeString(), 'duration_minutes' => 10])->assertSessionHasNoErrors();
    $session = Session::query()->firstOrFail();
    $import = TelemetryImport::create(['session_id' => $session->id, 'vehicle_id' => $vehicle->id, 'source_vendor' => 'generic', 'source_format' => 'csv', 'original_filename' => 'test.csv', 'storage_path' => 'telemetry/test.csv', 'sha256' => str_repeat('a', 64), 'imported_by' => $this->operator->id, 'imported_at' => now(), 'sample_count' => 1, 'lap_count' => 0]);
    $import->samples()->create(['sequence' => 0, 'elapsed_ms' => 0, 'channels' => ['speed' => 20]]);
    $import->laps()->create(['session_id' => $session->id, 'vehicle_id' => $vehicle->id, 'lap_number' => 1, 'lap_time_ms' => 60000]);
    $this->get(route('telemetry.index'))->assertSuccessful()->assertSee('Delete acquisition');
    $this->delete(route('telemetry.destroy', $import))->assertSessionHasNoErrors();
    expect($import->fresh())->toBeNull()->and($import->samples()->count())->toBe(0);
    expect($session->fresh()->status)->toBe('finalized')->and($import->laps()->count())->toBe(0);
    Storage::disk('local')->assertMissing('telemetry/test.csv');
});

test('new destructive routes reject foreign workspaces and readers', function () {
    $note = EventNote::create(['event_id' => $this->event->id, 'kind' => 'technical', 'body' => 'Safe', 'occurred_at' => now(), 'created_by' => $this->operator->id]);
    $routes = [['circuits.destroy', $this->circuit], ['circuits.layouts.destroy', [$this->circuit, $this->layout]], ['events.destroy', $this->event], ['events.notes.destroy', $note]];
    $team = $this->operator->workspaces()->firstOrFail();
    $reader = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($reader);
    foreach ($routes as [$route, $model]) {
        $this->delete(route($route, $model))->assertNotFound();
    }
    $team->users()->attach($reader, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);
    $this->post(route('team.switch', $team))->assertRedirect();
    foreach ($routes as [$route, $model]) {
        $this->delete(route($route, $model))->assertForbidden();
    }
    $this->put(route('team.update'), ['name' => 'Intrusion'])->assertForbidden();
    $this->get(route('circuits.index'))->assertSuccessful()->assertDontSee('edit-circuit-'.$this->circuit->id)->assertDontSee('Delete circuit');
});

test('removed circuits remain scoped and readers cannot restore them', function () {
    $team = $this->operator->workspaces()->firstOrFail();
    $this->circuit->delete();
    $reader = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($reader)->patch(route('circuits.restore', $this->circuit))->assertNotFound();
    $team->users()->attach($reader, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);
    $this->post(route('team.switch', $team))->assertRedirect();
    $this->patch(route('circuits.restore', $this->circuit))->assertForbidden();
    expect($this->circuit->fresh()->trashed())->toBeTrue();
    $this->get(route('circuits.index'))->assertSuccessful()->assertSee('Removed circuits')->assertDontSee('>Restore<', false);
});
