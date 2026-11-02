<?php

use App\Models\EventEntry;
use App\Models\EventTask;
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

function checklistEntry(): EventEntry
{
    return EventEntry::whereHas('vehicle', fn ($q) => $q->where('name', 'E2E Print Kart'))->firstOrFail();
}

test('checklist uses event context and current readiness despite unrelated pagination and personal filters', function () {
    $entry = checklistEntry();
    $task = EventTask::create(['created_by' => auth()->id(), 'event_id' => $entry->event_id, 'event_entry_id' => $entry->id, 'title' => 'Inspect kart before race', 'priority' => 'critical', 'status' => 'todo']);
    $response = $this->get(route('prints.checklist', [$entry, 'readiness_page' => 99, 'mine' => '1', 'q' => 'unrelated']))->assertOk()
        ->assertSee('E2E Print Kart')->assertSee('E2E Print Driver')->assertSee('E2E Print Weekend')->assertSee('E2E Print Configuration')
        ->assertSee('Inspect kart before race')->assertSee(__('readiness.attention'))->assertSee(__('checklist.aligned'))->assertSee('print-check-box', false)
        ->assertHeader('Cache-Control', 'no-store, private');
    expect($task->fresh()->status)->toBe('todo')->and($response->getContent())->not->toContain('checked=');
});

test('checklist excludes other karts and events and separates shared tasks from kart tasks', function () {
    $entry = checklistEntry();
    EventTask::create(['created_by' => auth()->id(), 'event_id' => $entry->event_id, 'title' => 'Shared paddock work', 'status' => 'todo']);
    EventTask::create(['created_by' => auth()->id(), 'event_id' => $entry->event_id, 'event_entry_id' => $entry->id, 'title' => 'Completed kart task', 'status' => 'done']);
    $other = EventEntry::where('id', '!=', $entry->id)->firstOrFail();
    EventTask::create(['created_by' => auth()->id(), 'event_id' => $other->event_id, 'event_entry_id' => $other->id, 'title' => 'Other event secret task', 'status' => 'todo']);
    $this->get(route('prints.checklist', $entry))->assertOk()->assertSee('Shared paddock work')->assertDontSee('Completed kart task')->assertDontSee('Other event secret task');
    $vehicle = Vehicle::factory()->create(['workspace_id' => $entry->workspace_id]);
    $otherEntry = EventEntry::create(['event_id' => $entry->event_id, 'vehicle_id' => $vehicle->id, 'driver_id' => $entry->driver_id, 'configuration_version_id' => $entry->configuration_version_id]);
    EventTask::create(['created_by' => auth()->id(), 'event_id' => $entry->event_id, 'event_entry_id' => $otherEntry->id, 'title' => 'Other kart task', 'status' => 'todo']);
    $this->get(route('prints.checklist', $entry))->assertOk()->assertDontSee('Other kart task');
});

test('inactive vehicles and configuration mismatches require verification rather than an all clear', function () {
    $entry = checklistEntry();
    $entry->vehicle->componentInstallations()->update(['removed_at' => now()]);
    $this->get(route('prints.checklist', $entry))->assertOk()->assertSee(__('checklist.not_aligned'))->assertSee(__('readiness.incomplete'));
    $entry->vehicle->update(['status' => 'inactive']);
    $this->get(route('prints.checklist', $entry))->assertOk()->assertSee(__('checklist.inactive'))->assertDontSee(__('readiness.clear'));
});

test('checklists protect workspaces and allow read only members in the selected workspace', function () {
    $entry = checklistEntry();
    $viewer = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($viewer)->get(route('prints.checklist', $entry))->assertNotFound();
    $viewer->workspaces()->attach($entry->workspace_id, ['role' => 'viewer', 'status' => 'active']);
    app(WorkspaceContext::class)->select($viewer, Workspace::findOrFail($entry->workspace_id));
    $this->get(route('prints.checklist', $entry))->assertOk();
    auth()->logout();
    $this->get(route('prints.checklist', $entry))->assertRedirect(route('login'));
});
