<?php

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\DataHubService;

function teamTaskEvent(User $user, string $name = 'Team Weekend'): RaceEvent
{
    return RaceEvent::create([
        'name' => $name, 'start_date' => today(), 'end_date' => today()->addDay(),
        'status' => 'planned', 'created_by' => $user->id,
    ]);
}

function teamTask(RaceEvent $event, array $overrides = []): EventTask
{
    return EventTask::create([
        'event_id' => $event->id, 'title' => 'Check kart', 'priority' => 'normal', 'status' => 'todo',
        'created_by' => $event->created_by, ...$overrides,
    ]);
}

test('the task board separates urgent and completed work and highlights overdue tasks', function () {
    app()->setLocale('it');
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $event = teamTaskEvent($user);
    teamTask($event, ['title' => 'Expired inspection', 'due_at' => now()->subHour()]);
    teamTask($event, ['title' => 'Critical preparation', 'priority' => 'critical', 'status' => 'in_progress']);
    teamTask($event, ['title' => 'Normal preparation']);
    teamTask($event, ['title' => 'Old completed work', 'priority' => 'critical', 'status' => 'done', 'completed_at' => now()]);

    $this->get(route('tasks.index'))->assertOk()->assertSee('Expired inspection')->assertSee('Scaduta')
        ->assertSee('Critical preparation')->assertDontSee('Old completed work')
        ->assertViewHas('counts', ['urgent' => 2, 'todo' => 2, 'in_progress' => 1, 'done' => 1]);
    $this->get(route('tasks.index', ['bucket' => 'done']))->assertOk()->assertSee('Old completed work')->assertDontSee('Expired inspection');
});

test('tasks are created and edited through the board with driver kart and active assignee', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $member = User::factory()->create(['name' => 'Team mechanic']);
    $workspace->users()->attach($member, ['role' => 'mechanic_engineer', 'status' => 'active', 'joined_at' => now()]);
    $this->actingAs($user);
    $event = teamTaskEvent($user);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Task kart']);
    $config = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Task config', 'status' => 'active']);
    $version = ConfigurationVersion::create(['configuration_id' => $config->id, 'version_number' => 1, 'created_by' => $user->id]);
    $driver = Driver::create(['display_name' => 'Task driver', 'status' => 'active']);
    $entry = EventEntry::create(['event_id' => $event->id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'configuration_version_id' => $version->id]);

    $data = ['event_id' => $event->id, 'event_entry_id' => $entry->id, 'title' => 'Pressures before heat',
        'priority' => 'high', 'assigned_to' => $member->id, 'description' => 'Check all four tyres'];
    $this->post(route('tasks.store'), $data)->assertRedirect();
    $task = EventTask::query()->sole();
    expect($task->assigned_to)->toBe($member->id)->and($task->event_entry_id)->toBe($entry->id);
    $this->get(route('tasks.index'))->assertOk()->assertSee('Task driver')->assertSee('Task kart')->assertSee('Team mechanic');

    $this->put(route('tasks.update', $task), [...$data, 'title' => 'Updated tyre check', 'assigned_to' => null])->assertRedirect();
    expect($task->fresh()->title)->toBe('Updated tyre check')->and($task->fresh()->assigned_to)->toBeNull();
    $this->get(route('tasks.index', ['vehicle_id' => $vehicle->id]))->assertSee('Updated tyre check');
});

test('completion records the operator once and reopening preserves the audit history', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $task = teamTask(teamTaskEvent($user));
    $this->patch(route('tasks.status', $task), ['status' => 'done', 'expected_status' => 'todo'])->assertRedirect();
    $when = $task->fresh()->completed_at;
    expect($when)->not->toBeNull()->and($task->fresh()->completed_by)->toBe($user->id);
    $this->travel(10)->minutes();
    $this->patch(route('tasks.status', $task), ['status' => 'done', 'expected_status' => 'todo'])->assertSessionHasErrors('status');
    expect($task->fresh()->completed_at->equalTo($when))->toBeTrue();
    $this->patch(route('tasks.status', $task), ['status' => 'todo', 'expected_status' => 'done'])->assertRedirect();
    expect($task->fresh()->completed_at)->toBeNull()->and($task->fresh()->completed_by)->toBeNull();
    expect(AuditLog::query()->where('subject_type', EventTask::class)->where('subject_id', $task->id)
        ->where('after_values->status', 'done')->where('user_id', $user->id)->exists())->toBeTrue();
    $this->delete(route('events.tasks.destroy', $task))->assertSessionHasErrors('task');
    expect($task->fresh())->not->toBeNull();
});

test('legacy event task updates also record completion and preserve assignment on detail changes', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $task = teamTask(teamTaskEvent($user), ['assigned_to' => $user->id]);
    $this->put(route('events.tasks.details', $task), ['title' => 'From weekend', 'priority' => 'normal'])->assertRedirect();
    expect($task->fresh()->assigned_to)->toBe($user->id);
    $this->patch(route('events.tasks.update', $task), ['status' => 'done'])->assertRedirect();
    expect($task->fresh()->completed_by)->toBe($user->id);
});

test('foreign and inactive assignees and mismatched event entries are rejected', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $other = User::factory()->withDatabaseAccess()->create();
    $inactive = User::factory()->create();
    $user->workspaces()->firstOrFail()->users()->attach($inactive, ['role' => 'viewer', 'status' => 'suspended']);
    $this->actingAs($user);
    $event = teamTaskEvent($user);
    $data = ['event_id' => $event->id, 'title' => 'Protected work', 'priority' => 'normal'];
    foreach ([$other->id, $inactive->id] as $id) {
        $this->post(route('tasks.store'), [...$data, 'assigned_to' => $id])->assertSessionHasErrors('assigned_to');
    }
    expect(EventTask::query()->count())->toBe(0);
    $this->post(route('tasks.store'), [...$data, 'event_entry_id' => 999999])->assertNotFound();
});

test('the board never exposes another workspace and read only members cannot change tasks', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $other = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($other);
    $foreign = teamTask(teamTaskEvent($other), ['title' => 'Foreign secret']);
    $this->actingAs($owner);
    $own = teamTask(teamTaskEvent($owner));
    $this->get(route('tasks.index'))->assertOk()->assertDontSee('Foreign secret');
    $this->get(route('tasks.index', ['event_id' => $foreign->event_id]))->assertNotFound();
    $this->patch(route('tasks.status', $foreign), ['status' => 'done', 'expected_status' => 'todo'])->assertNotFound();
    $viewer = User::factory()->withDatabaseAccess()->create();
    $viewer->workspaces()->detach();
    $owner->workspaces()->firstOrFail()->users()->attach($viewer, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);
    $this->actingAs($viewer)->get(route('tasks.index'))->assertOk()->assertDontSee('create-team-task')->assertDontSee('edit-team-task-'.$own->id);
    $this->patch(route('tasks.status', $own), ['status' => 'done', 'expected_status' => 'todo'])->assertForbidden();
    $this->put(route('tasks.update', $own), ['title' => 'No', 'priority' => 'normal'])->assertForbidden();
    $this->post(route('tasks.store'), ['event_id' => $own->event_id, 'title' => 'No', 'priority' => 'normal'])->assertForbidden();
});

test('personal filters and pagination keep all remaining tasks reachable', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $event = teamTaskEvent($user);
    for ($i = 0; $i < 22; $i++) {
        teamTask($event, ['title' => 'Assigned '.$i, 'assigned_to' => $user->id]);
    }
    teamTask($event, ['title' => 'Unassigned task']);
    $response = $this->get(route('tasks.index', ['mine' => '1', 'bucket' => 'todo']));
    $response->assertOk()->assertDontSee('Unassigned task')->assertViewHas('tasks', fn ($paginator): bool => $paginator->total() === 22 && $paginator->count() === 20);
    $this->get(route('tasks.index', ['mine' => '1', 'bucket' => 'todo', 'page' => 2]))->assertOk()
        ->assertViewHas('tasks', fn ($paginator): bool => $paginator->count() === 2);
});

test('portable backups do not attach source team identities to restored tasks', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $task = teamTask(teamTaskEvent($user), ['assigned_to' => $user->id, 'completed_by' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    $hub = app(DataHubService::class);
    $backup = $hub->export($user->workspaces()->firstOrFail());
    $target = Workspace::factory()->create();
    $hub->import($target, $user, $backup);
    $restored = EventTask::withoutGlobalScopes()->where('workspace_id', $target->id)->sole();
    expect($restored->assigned_to)->toBeNull()->and($restored->completed_by)->toBeNull()
        ->and($restored->completed_at)->not->toBeNull();
});
