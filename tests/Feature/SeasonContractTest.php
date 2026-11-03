<?php

use App\Models\Championship;
use App\Models\ChampionshipRound;
use App\Models\ContractChange;
use App\Models\ContractExtension;
use App\Models\ContractParticipation;
use App\Models\Document;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\SeasonContract;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\DataHubService;
use App\Services\SeasonContractService;
use App\Services\WorkspaceContext;
use Database\Seeders\E2EBrowserSeeder;
use Database\Seeders\E2EPrintSeeder;
use Database\Seeders\E2ESeasonContractSeeder;
use Database\Seeders\E2ETracksideSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([E2EBrowserSeeder::class, E2ETracksideSeeder::class, E2EPrintSeeder::class]);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
});

function contractFixture(int $races = 2): array
{
    $entry = EventEntry::whereHas('vehicle', fn ($q) => $q->where('name', 'E2E Print Kart'))->firstOrFail();
    $service = app(SeasonContractService::class);
    $contract = $service->create(Workspace::findOrFail($entry->workspace_id), auth()->user(), [
        'driver_id' => $entry->driver_id, 'vehicle_id' => $entry->vehicle_id, 'season' => '2026',
        'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'initial_races' => $races,
    ]);

    return compact('entry', 'contract', 'service');
}

function associateContract(array $f, ?EventEntry $entry = null): ContractParticipation
{
    return $f['service']->attach($entry ?? $f['entry'], auth()->user(), ['season_contract_id' => $f['contract']->id, 'revision' => 0, 'notes' => 'Kart substitution if needed', 'reason' => 'Race booking']);
}

function anotherContractEntry(EventEntry $source, string $name = 'Another contract weekend'): EventEntry
{
    $event = RaceEvent::create(['name' => $name, 'start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'created_by' => auth()->id()]);
    $copy = $source->replicate()->fill(['event_id' => $event->id]);
    $copy->save();

    return $copy;
}

test('creating and editing contracts preserve original terms and block overlaps', function () {
    $f = contractFixture();
    $this->get(route('contracts.index'))->assertOk()->assertSee('E2E Print Driver');
    $this->get(route('contracts.show', $f['contract']))->assertOk()->assertSee(__('contracts.rule'));
    $payload = ['driver_id' => $f['entry']->driver_id, 'vehicle_id' => $f['entry']->vehicle_id, 'season' => '2026', 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'initial_races' => 3];
    $this->postJson(route('contracts.store'), $payload)->assertUnprocessable();
    $this->put(route('contracts.update', $f['contract']), ['revision' => 1, 'status' => 'suspended', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Temporary pause', 'initial_races' => 999])->assertRedirect();
    expect($f['contract']->fresh()->initial_races)->toBe(2)->and($f['contract']->fresh()->status)->toBe('suspended');
    $this->postJson(route('contracts.store'), $payload)->assertUnprocessable();
    expect(ContractChange::where('kind', 'updated')->firstOrFail()->reason)->toBe('Temporary pause');
});

test('a weekend consumes exactly one race only after explicit confirmation and corrections retain history', function () {
    $f = contractFixture();
    $row = associateContract($f);
    $again = associateContract($f);
    expect($again->id)->toBe($row->id)->and(ContractParticipation::count())->toBe(1)->and($f['contract']->participations()->where('status', 'confirmed')->count())->toBe(0);
    $this->patch(route('contracts.participations.update', $row), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Race took place'])->assertRedirect();
    expect($row->fresh()->confirmed_by)->toBe(auth()->id())->and($row->fresh()->confirmed_name)->toBe(auth()->user()->name);
    $this->patchJson(route('contracts.participations.update', $row), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Duplicate stale click'])->assertUnprocessable();
    expect(ContractChange::where('delta', 1)->count())->toBe(1);
    $this->patch(route('contracts.participations.update', $row), ['revision' => 2, 'status' => 'cancelled', 'reason' => 'Race cancelled afterwards'])->assertRedirect();
    expect($row->fresh()->confirmed_at)->toBeNull()->and(ContractChange::where('delta', -1)->firstOrFail()->reason)->toBe('Race cancelled afterwards')
        ->and(ContractChange::where('delta', 1)->firstOrFail()->after_values['confirmed_name'])->toBe(auth()->user()->name);
});

test('the final remaining place cannot be consumed twice and an extension unlocks another race', function () {
    $f = contractFixture(1);
    $first = associateContract($f);
    $second = associateContract($f, anotherContractEntry($f['entry']));
    $this->patch(route('contracts.participations.update', $first), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'First operator'])->assertRedirect();
    $this->patchJson(route('contracts.participations.update', $second), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Second operator'])->assertUnprocessable();
    expect($second->fresh()->status)->toBe('planned');
    $this->post(route('contracts.extend', $f['contract']), ['revision' => $f['contract']->fresh()->revision, 'added_races' => 1, 'ends_on' => '2026-12-31', 'reason' => 'One more race agreed'])->assertRedirect();
    $this->patch(route('contracts.participations.update', $second), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Race after extension'])->assertRedirect();
    expect($f['service']->total($f['contract']))->toBe(2)->and($f['contract']->participations()->where('status', 'confirmed')->count())->toBe(2);
    $extension = ContractExtension::firstOrFail();
    expect(fn () => $extension->update(['added_races' => 9]))->toThrow(LogicException::class);
});

test('suspension cancellation absence and dates are checked without silently changing consumption', function () {
    $f = contractFixture();
    $row = associateContract($f);
    $f['contract']->update(['status' => 'suspended']);
    $this->patchJson(route('contracts.participations.update', $row), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Not allowed'])->assertUnprocessable();
    $this->patch(route('contracts.participations.update', $row), ['revision' => 1, 'status' => 'absent', 'reason' => 'Driver absent'])->assertRedirect();
    expect($f['contract']->participations()->where('status', 'confirmed')->count())->toBe(0);
    $f['contract']->update(['status' => 'active']);
    $f['entry']->raceEvent->update(['status' => 'cancelled']);
    $this->patchJson(route('contracts.participations.update', $row), ['revision' => 2, 'status' => 'confirmed', 'reason' => 'Cancelled event'])->assertUnprocessable();
    $this->putJson(route('contracts.update', $f['contract']), ['revision' => $f['contract']->fresh()->revision, 'status' => 'active', 'starts_on' => '2026-11-02', 'ends_on' => '2026-12-31', 'reason' => 'Invalid historical range'])->assertUnprocessable();
});

test('replacement karts require notes and do not rewrite the primary assignment', function () {
    $f = contractFixture();
    $replacement = Vehicle::factory()->create(['workspace_id' => $f['entry']->workspace_id, 'category' => 'kart']);
    $f['entry']->update(['vehicle_id' => $replacement->id]);
    $payload = ['season_contract_id' => $f['contract']->id, 'revision' => 0, 'reason' => 'Replacement'];
    $this->postJson(route('contracts.attach', $f['entry']), $payload)->assertUnprocessable();
    $this->post(route('contracts.attach', $f['entry']), [...$payload, 'notes' => 'Primary kart engine failure'])->assertRedirect();
    $row = ContractParticipation::firstOrFail();
    expect($row->vehicle_id)->toBe($replacement->id)->and($f['contract']->fresh()->vehicle_id)->not->toBe($replacement->id);
    $this->get(route('prints.contract', $f['contract']))->assertOk()->assertSee('Primary kart engine failure');
    $this->delete(route('events.entries.destroy', $f['entry']))->assertRedirect()->assertSessionHasErrors();
});

test('championship participation requires an explicit matching linked round and prevents repeated round consumption', function () {
    $f = contractFixture();
    $championship = Championship::factory()->create(['workspace_id' => $f['entry']->workspace_id]);
    $f['contract']->update(['championship_id' => $championship->id]);
    $round = ChampionshipRound::create(['championship_id' => $championship->id, 'event_id' => $f['entry']->event_id, 'number' => 1, 'name' => 'Contract round']);
    $this->postJson(route('contracts.attach', $f['entry']), ['season_contract_id' => $f['contract']->id, 'revision' => 0, 'reason' => 'Missing round'])->assertUnprocessable();
    $this->post(route('contracts.attach', $f['entry']), ['season_contract_id' => $f['contract']->id, 'round_id' => $round->id, 'revision' => 0, 'reason' => 'Correct round'])->assertRedirect();
    $other = anotherContractEntry($f['entry']);
    $this->postJson(route('contracts.attach', $other), ['season_contract_id' => $f['contract']->id, 'round_id' => $round->id, 'revision' => 0, 'reason' => 'Wrong event'])->assertUnprocessable();
    expect(ContractParticipation::count())->toBe(1);
});

test('read only members can read and print while foreign workspace records and files remain inaccessible', function () {
    $f = contractFixture();
    $row = associateContract($f);
    Storage::fake('local');
    $this->post(route('control-center.documents.store'), ['attachable' => 'season_contract:'.$f['contract']->id, 'document' => UploadedFile::fake()->create('signed.pdf', 10, 'application/pdf')])->assertRedirect(route('contracts.show', $f['contract']));
    $document = Document::firstOrFail();
    $this->get(route('control-center.documents.download', $document))->assertOk();
    $viewer = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($viewer);
    $this->get(route('contracts.show', $f['contract']))->assertNotFound();
    $this->get(route('prints.contract', $f['contract']))->assertNotFound();
    $this->get(route('control-center.documents.download', $document))->assertNotFound();
    $this->post(route('contracts.attach', $f['entry']), ['season_contract_id' => $f['contract']->id])->assertNotFound();
    $viewer->workspaces()->attach($f['entry']->workspace_id, ['role' => 'viewer', 'status' => 'active']);
    app(WorkspaceContext::class)->select($viewer, Workspace::findOrFail($f['entry']->workspace_id));
    $this->get(route('contracts.show', $f['contract']))->assertOk();
    $this->get(route('prints.contract', $f['contract']))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->patch(route('contracts.participations.update', $row), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Forbidden'])->assertForbidden();
    $this->post(route('control-center.documents.store'), ['attachable' => 'season_contract:'.$f['contract']->id, 'document' => UploadedFile::fake()->create('other.pdf', 10, 'application/pdf')])->assertForbidden();
});

test('backup export and restore retain balances and original operator names', function () {
    $f = contractFixture();
    $row = associateContract($f);
    $f['service']->change($row, auth()->user(), ['revision' => 1, 'status' => 'confirmed', 'reason' => 'Original confirmation']);
    $backup = app(DataHubService::class)->export(Workspace::findOrFail($f['entry']->workspace_id));
    $name = auth()->user()->name;
    $newUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($newUser);
    $target = $newUser->workspaces()->firstOrFail();
    app(DataHubService::class)->import($target, $newUser, $backup);
    $restored = SeasonContract::firstOrFail();
    expect($restored->participations()->where('status', 'confirmed')->count())->toBe(1)
        ->and($restored->changes()->where('delta', 1)->firstOrFail()->actor_name)->toBe($name)
        ->and($restored->participations()->firstOrFail()->confirmed_name)->toBe($name);
});

test('printing all history is read only and does not truncate long notes or paginate changes', function () {
    $f = contractFixture();
    $row = associateContract($f);
    for ($i = 0; $i < 28; $i++) {
        $f['service']->change($row->fresh(), auth()->user(), ['revision' => $row->fresh()->revision, 'status' => $i % 2 ? 'planned' : 'absent', 'reason' => str_repeat('Full historical reason. ', 20).'CONTRACT-HISTORY-'.$i]);
    }
    $before = DB::table('audit_logs')->count();
    $response = $this->get(route('prints.contract', [$f['contract'], 'page' => 99]))->assertOk()->assertSee('CONTRACT-HISTORY-27');
    expect(substr_count($response->getContent(), 'data-contract-change='))->toBe(30)->and(DB::table('audit_logs')->count())->toBe($before);
});

test('new entry association is transactional and stale metadata forms cannot overwrite newer changes', function () {
    $f = contractFixture();
    $event = RaceEvent::create(['name' => 'New contract entry event', 'start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'created_by' => auth()->id()]);
    $this->post(route('events.entries.store', $event), ['driver_id' => $f['entry']->driver_id, 'vehicle_id' => $f['entry']->vehicle_id,
        'configuration_version_id' => $f['entry']->configuration_version_id, 'season_contract_id' => $f['contract']->id, 'entry_cost' => 50])->assertRedirect();
    expect(ContractParticipation::where('event_id', $event->id)->count())->toBe(1);
    $this->postJson(route('contracts.extend', $f['contract']), ['revision' => 1, 'added_races' => 2, 'reason' => 'Stale contract form'])->assertUnprocessable();
    $new = SeasonContract::factory()->create();
    $rollback = RaceEvent::create(['name' => 'Rollback weekend', 'start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'created_by' => auth()->id()]);
    $this->postJson(route('events.entries.store', $rollback), ['driver_id' => $new->driver_id, 'vehicle_id' => $f['entry']->vehicle_id,
        'configuration_version_id' => $f['entry']->configuration_version_id, 'season_contract_id' => $f['contract']->id, 'entry_cost' => 50])->assertUnprocessable();
    expect($rollback->entries()->count())->toBe(0)->and($rollback->expenses()->count())->toBe(0);
});

test('changing an unconfirmed association preserves history on both contracts and confirmed ones require reversal', function () {
    $f = contractFixture();
    $row = associateContract($f);
    $f['service']->update($f['contract']->fresh(), auth()->user(), ['revision' => $f['contract']->fresh()->revision, 'status' => 'completed', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'New agreement']);
    $new = $f['service']->create(Workspace::findOrFail($f['entry']->workspace_id), auth()->user(), ['driver_id' => $f['entry']->driver_id, 'vehicle_id' => $f['entry']->vehicle_id, 'season' => '2026 replacement', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'initial_races' => 3]);
    $payload = ['season_contract_id' => $new->id, 'revision' => $row->revision, 'reason' => 'Correct agreement selected'];
    $this->post(route('contracts.attach', $f['entry']), $payload)->assertRedirect();
    expect($row->fresh()->season_contract_id)->toBe($new->id)->and($f['contract']->changes()->where('kind', 'transferred')->count())->toBe(1);
    $f['service']->change($row->fresh(), auth()->user(), ['revision' => $row->fresh()->revision, 'status' => 'confirmed', 'reason' => 'Confirmed']);
    $this->postJson(route('contracts.attach', $f['entry']), [...$payload, 'revision' => $row->fresh()->revision])->assertUnprocessable();
});

test('contract factories create valid workspace relationships and database keys reject cross team participation', function () {
    $factory = ContractParticipation::factory()->create();
    expect($factory->contract->workspace_id)->toBe($factory->workspace_id);
    $extension = ContractExtension::factory()->create(['season_contract_id' => $factory->season_contract_id]);
    expect($extension->workspace_id)->toBe($factory->workspace_id);
    $foreign = SeasonContract::factory()->create(['workspace_id' => Workspace::factory()]);
    expect(fn () => DB::table('contract_participations')->where('id', $factory->id)->update(['season_contract_id' => $foreign->id]))->toThrow(QueryException::class);
});

test('browser fixture preserves a complete history and a separate last race allowance', function () {
    $this->seed(E2ESeasonContractSeeder::class);
    $this->actingAs(User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail());
    $contract = SeasonContract::where('season', 'E2E Main 2026')->firstOrFail();
    $this->get(route('contracts.show', $contract))->assertOk()->assertSee('END-CONTRACT-HISTORY');
    expect($contract->participations()->count())->toBe(8)->and($contract->participations()->where('status', 'confirmed')->count())->toBe(2);
    $last = SeasonContract::where('season', 'E2E Last Place')->firstOrFail();
    expect($last->participations()->count())->toBe(2)->and(app(SeasonContractService::class)->total($last))->toBe(1);
});

test('foreign driver kart championship and participation ids cannot cross workspace boundaries', function () {
    $f = contractFixture();
    $foreign = SeasonContract::factory()->create(['workspace_id' => Workspace::factory()]);
    $payload = ['driver_id' => $f['entry']->driver_id, 'vehicle_id' => $f['entry']->vehicle_id, 'season' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'initial_races' => 2];
    $this->postJson(route('contracts.store'), [...$payload, 'driver_id' => $foreign->driver_id])->assertNotFound();
    $this->postJson(route('contracts.store'), [...$payload, 'vehicle_id' => $foreign->vehicle_id])->assertNotFound();
    $championship = Championship::factory()->create(['workspace_id' => $foreign->workspace_id]);
    $this->postJson(route('contracts.store'), [...$payload, 'championship_id' => $championship->id])->assertNotFound();
    $this->postJson(route('contracts.attach', $f['entry']), ['season_contract_id' => $foreign->id, 'revision' => 0, 'reason' => 'Foreign agreement'])->assertNotFound();
    $this->get(route('contracts.index', ['driver_id' => $foreign->driver_id]))->assertNotFound();
    expect($f['contract']->participations()->count())->toBe(0);
});

test('the new migration can roll back and existing events remain usable before it is installed', function () {
    $entry = EventEntry::whereHas('vehicle', fn ($q) => $q->where('name', 'E2E Print Kart'))->firstOrFail();
    $migration = require database_path('migrations/2026_11_03_162909_create_season_contract_domain.php');
    $migration->down();
    try {
        expect(app(WorkspaceContext::class)->isContractsReady())->toBeFalse();
        $this->get(route('contracts.index'))->assertStatus(503);
        $this->get(route('events.show', $entry->event_id))->assertOk();
        $this->get(route('garage.index'))->assertOk();
    } finally {
        $migration->up();
    }
    expect(app(WorkspaceContext::class)->isContractsReady())->toBeTrue();
    $this->get(route('contracts.index'))->assertOk();
});

test('archived driver and kart remain available for historical contract filters and printing', function () {
    $f = contractFixture();
    $f['contract']->driver->delete();
    $f['contract']->vehicle->delete();
    $this->get(route('contracts.index', ['driver_id' => $f['entry']->driver_id, 'vehicle_id' => $f['entry']->vehicle_id]))->assertOk()
        ->assertSee('E2E Print Driver')->assertSee('E2E Print Kart');
    $this->get(route('prints.contract', $f['contract']))->assertOk()->assertSee('E2E Print Driver')->assertSee('E2E Print Kart');
});
