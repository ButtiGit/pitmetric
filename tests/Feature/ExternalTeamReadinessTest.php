<?php

use App\Models\AuditLog;
use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkspaceAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('records workspace audit entries with the authenticated actor', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['name' => 'Audited Kart']);
    $vehicle->update(['status' => 'inactive']);

    $created = AuditLog::query()->where('entity_type', 'Vehicle')->where('entity_id', $vehicle->id)->where('action', 'created')->firstOrFail();
    $updated = AuditLog::query()->where('entity_type', 'Vehicle')->where('entity_id', $vehicle->id)->where('action', 'updated')->firstOrFail();

    expect($created->actor_id)->toBe($user->id)
        ->and($updated->actor_id)->toBe($user->id)
        ->and($updated->metadata['changed_fields'])->toContain('status');
});

it('imports CSV atomically and exports portable workspace data', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $csv = "name,category,manufacturer,model,year,identifier,status,notes\nImport Kart,kart,OTK,Exprit,2026,K-01,active,Imported from spreadsheet\n";

    $this->post(route('data-hub.import'), [
        'dataset' => 'vehicles',
        'file' => UploadedFile::fake()->createWithContent('vehicles.csv', $csv),
    ])->assertRedirect(route('data-hub.index'));

    expect(Vehicle::query()->where('name', 'Import Kart')->exists())->toBeTrue();

    $this->get(route('data-hub.export', 'vehicles'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();

    $this->get(route('data-hub.backup'))
        ->assertOk()
        ->assertHeader('content-type', 'application/json; charset=UTF-8')
        ->assertDownload();

    $before = Vehicle::query()->count();
    $invalidCsv = "name,category\nBroken Kart,spaceship\nValid Kart,kart\n";

    $this->from(route('data-hub.index'))->post(route('data-hub.import'), [
        'dataset' => 'vehicles',
        'file' => UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv),
    ])->assertRedirect(route('data-hub.index'))->assertSessionHasErrors('file');

    expect(Vehicle::query()->count())->toBe($before);
});

it('stores attachments privately and keeps them isolated by workspace', function () {
    Storage::fake('local');

    $owner = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($owner);

    $event = RaceEvent::create([
        'name' => 'Document Weekend',
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-20',
        'status' => 'planned',
        'created_by' => $owner->id,
    ]);

    $this->post(route('data-hub.attachments.store'), [
        'attachable_type' => 'event',
        'attachable_id' => $event->id,
        'label' => 'Tyre invoice',
        'file' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
    ])->assertRedirect(route('data-hub.index'));

    $attachment = WorkspaceAttachment::query()->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);

    $this->get(route('data-hub.attachments.download', $attachment))->assertOk();

    $outsider = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($outsider);
    $this->get('/data-hub/attachments/'.$attachment->id)->assertNotFound();
});

it('notifies a teammate when a maintenance work order is assigned', function () {
    $owner = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $owner->workspaces()->firstOrFail();
    $member = User::factory()->create(['email_verified_at' => now()]);
    $workspace->users()->attach($member, [
        'role' => 'mechanic_engineer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($owner);

    $type = ComponentType::create(['name' => 'Notification Engine']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine N1',
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
        'name' => 'Pre-event inspection',
        'interval_value' => 7200,
        'is_active' => true,
    ]);

    $this->post(route('maintenance.work-orders.store'), [
        'maintenance_schedule_id' => $schedule->id,
        'title' => 'Inspect engine before event',
        'assigned_to' => $member->id,
        'priority' => 'high',
        'due_at' => '2026-09-18 08:00:00',
    ])->assertRedirect(route('maintenance.index'));

    $workOrder = MaintenanceWorkOrder::query()->firstOrFail();
    $notification = $member->notifications()->firstOrFail();

    expect($notification->data['work_order_id'])->toBe($workOrder->id)
        ->and($notification->data['title'])->toBe('Inspect engine before event');
});

it('shows the external-team readiness hub to database users', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $this->get(route('data-hub.index'))
        ->assertOk()
        ->assertSee('Data Hub & Readiness')
        ->assertSee('TEAM ONBOARDING')
        ->assertSee('AUDIT TRAIL')
        ->assertSee('IMPORT')
        ->assertSee('EXPORT & BACKUP');
});
