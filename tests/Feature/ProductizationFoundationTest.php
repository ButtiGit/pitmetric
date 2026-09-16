<?php

use App\Models\AuditLog;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\EventTask;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DataHubService;
use App\Services\OperationalNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('records workspace model changes in an isolated audit trail', function () {
    $first = User::factory()->withDatabaseAccess()->create();
    $firstWorkspace = $first->workspaces()->firstOrFail();
    $this->actingAs($first);

    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $firstWorkspace->id,
        'name' => 'Audit Kart',
    ]);
    $vehicle->update(['name' => 'Audit Kart Evo']);

    expect(AuditLog::query()->where('workspace_id', $firstWorkspace->id)->where('action', 'created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('workspace_id', $firstWorkspace->id)->where('action', 'updated')->exists())->toBeTrue();

    $update = AuditLog::query()
        ->where('workspace_id', $firstWorkspace->id)
        ->where('subject_type', Vehicle::class)
        ->where('subject_id', $vehicle->id)
        ->where('action', 'updated')
        ->latest('id')
        ->firstOrFail();

    expect($update->before_values['name'])->toBe('Audit Kart')
        ->and($update->after_values['name'])->toBe('Audit Kart Evo')
        ->and($update->user_id)->toBe($first->id);

    $second = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($second)
        ->get(route('control-center.index'))
        ->assertOk()
        ->assertDontSee('Audit Kart Evo');
});

it('exports and restores a portable workspace backup with remapped relationships', function () {
    $sourceUser = User::factory()->withDatabaseAccess()->create();
    $sourceWorkspace = $sourceUser->workspaces()->firstOrFail();
    $this->actingAs($sourceUser);

    Vehicle::factory()->create([
        'workspace_id' => $sourceWorkspace->id,
        'name' => 'Backup Kart',
    ]);
    $type = ComponentType::create([
        'name' => 'Engine',
        'category' => 'powertrain',
    ]);
    Component::create([
        'component_type_id' => $type->id,
        'name' => 'Backup Engine',
        'status' => 'active',
    ]);

    $backup = app(DataHubService::class)->export($sourceWorkspace);

    expect($backup['format'])->toBe(DataHubService::FORMAT)
        ->and($backup['version'])->toBe(DataHubService::VERSION)
        ->and($backup['tables']['vehicles'])->toHaveCount(1)
        ->and($backup['tables']['components'])->toHaveCount(1);

    $targetUser = User::factory()->withDatabaseAccess()->create();
    $targetWorkspace = $targetUser->workspaces()->firstOrFail();
    $this->actingAs($targetUser);

    $counts = app(DataHubService::class)->import($targetWorkspace, $targetUser, $backup);

    expect($counts['vehicles'])->toBe(1)
        ->and($counts['component_types'])->toBe(1)
        ->and($counts['components'])->toBe(1)
        ->and(DB::table('vehicles')->where('workspace_id', $targetWorkspace->id)->where('name', 'Backup Kart')->exists())->toBeTrue();

    $restoredTypeId = DB::table('component_types')
        ->where('workspace_id', $targetWorkspace->id)
        ->where('name', 'Engine')
        ->value('id');
    $restoredComponent = DB::table('components')
        ->where('workspace_id', $targetWorkspace->id)
        ->where('name', 'Backup Engine')
        ->first();

    expect($restoredComponent)->not->toBeNull()
        ->and((int) $restoredComponent->component_type_id)->toBe((int) $restoredTypeId)
        ->and((int) $restoredTypeId)->not->toBe((int) $type->id);
});

it('refuses to import a backup into a workspace that already contains operational data', function () {
    $sourceUser = User::factory()->withDatabaseAccess()->create();
    $sourceWorkspace = $sourceUser->workspaces()->firstOrFail();
    $this->actingAs($sourceUser);
    Vehicle::factory()->create(['workspace_id' => $sourceWorkspace->id, 'name' => 'Source Kart']);
    $backup = app(DataHubService::class)->export($sourceWorkspace);

    $targetUser = User::factory()->withDatabaseAccess()->create();
    $targetWorkspace = $targetUser->workspaces()->firstOrFail();
    $this->actingAs($targetUser);
    Vehicle::factory()->create(['workspace_id' => $targetWorkspace->id, 'name' => 'Existing Kart']);

    expect(fn () => app(DataHubService::class)->import($targetWorkspace, $targetUser, $backup))
        ->toThrow(ValidationException::class);
});

it('stores private documents and prevents another workspace from downloading them', function () {
    Storage::fake('local');

    $owner = User::factory()->withDatabaseAccess()->create();
    $workspace = $owner->workspaces()->firstOrFail();
    $this->actingAs($owner);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Document Kart']);

    $this->post(route('control-center.documents.store'), [
        'document' => UploadedFile::fake()->create('engine-sheet.pdf', 120, 'application/pdf'),
        'name' => 'Engine Sheet',
        'attachable' => 'vehicle:'.$vehicle->id,
    ])->assertRedirect(route('control-center.index'));

    $document = DB::table('documents')->where('workspace_id', $workspace->id)->first();

    expect($document)->not->toBeNull();
    Storage::disk('local')->assertExists($document->path);

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser)
        ->get(route('control-center.documents.download', $document->id))
        ->assertNotFound();
});

it('generates operational alerts once for the same deadline', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $event = RaceEvent::create([
        'name' => 'Alert Weekend',
        'start_date' => today()->addDays(10),
        'end_date' => today()->addDays(11),
        'status' => 'planned',
        'created_by' => $user->id,
    ]);
    EventTask::create([
        'event_id' => $event->id,
        'title' => 'Prepare wet tyres',
        'priority' => 'high',
        'status' => 'todo',
        'due_at' => now()->addHours(2),
        'created_by' => $user->id,
    ]);

    $service = app(OperationalNotificationService::class);

    expect($service->generate($workspace))->toBe(1)
        ->and($service->generate($workspace))->toBe(0)
        ->and($user->fresh()->notifications()->count())->toBe(1)
        ->and($user->fresh()->notifications()->first()->data['message'])->toContain('Prepare wet tyres');
});

it('renders the control center with readiness, data hub, documents, alerts and audit trail', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user)
        ->get(route('control-center.index'))
        ->assertOk()
        ->assertSee('Control Center')
        ->assertSee('DATA HUB')
        ->assertSee('PRIVATE DOCUMENTS')
        ->assertSee('OPERATIONAL ALERTS')
        ->assertSee('AUDIT TRAIL');
});
