<?php

use App\Models\StudioErpCheckRun;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\StudioErpTechnical;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
});

test('ERP technical management and diagnostic actions are accessible only to Studio editors', function () {
    $ordinary = User::factory()->create(['database_access_enabled' => true]);
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test', 'database_access_enabled' => false]);

    $this->get(route('studio.erp.index'))->assertRedirect(route('login'));
    $this->post(route('studio.erp.checks'))->assertRedirect(route('login'));

    $this->actingAs($ordinary)->get(route('studio.erp.index'))->assertForbidden();
    $this->post(route('studio.erp.checks'))->assertForbidden();
    $this->get(route('dashboard'))->assertDontSee('Gestione tecnica ERP');

    $this->actingAs($editor)->get(route('studio.erp.index'))
        ->assertOk()
        ->assertSee('Gestione tecnica ERP')
        ->assertSee('Esegui diagnostica ERP')
        ->assertSee('Garage e componenti');

    $this->post(route('studio.erp.checks'))->assertRedirect(route('studio.erp.index'));

    $run = StudioErpCheckRun::query()->sole();
    expect($run->user_id)->toBe($editor->id)
        ->and(collect($run->results)->pluck('key'))->toContain('database', 'migrations', 'module_garage', 'tenant_integrity', 'queue');

    $this->get(route('studio.erp.index'))->assertOk()->assertSee('Storico report ERP');
});

test('ERP overview shows aggregate counts without changing operational records', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $workspace = Workspace::factory()->create();
    $vehicle = Vehicle::factory()->for($workspace)->create();

    $this->actingAs($editor)->get(route('studio.erp.index'))
        ->assertOk()->assertSee('Team / Workspace')->assertSee('Veicoli');

    expect(app(StudioErpTechnical::class)->totals())
        ->toMatchArray(['workspaces' => 1, 'vehicles' => 1, 'sessions' => 0, 'open_orders' => 0]);

    $this->post(route('studio.erp.checks'))->assertRedirect(route('studio.erp.index'));

    expect(Vehicle::query()->find($vehicle->id))->not->toBeNull()
        ->and(Workspace::query()->count())->toBe(1)
        ->and(DB::table('technical_setups')->count())->toBe(0)
        ->and(StudioErpCheckRun::query()->count())->toBe(1);
});

test('ERP integrity diagnostics detect cross-workspace references without repairing them', function () {
    $first = Workspace::factory()->create();
    $second = Workspace::factory()->create();
    $vehicle = Vehicle::factory()->for($first)->create();

    DB::table('technical_setups')->insert([
        'workspace_id' => $second->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Inconsistent setup',
        'values' => json_encode([], JSON_THROW_ON_ERROR),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $results = collect(app(StudioErpTechnical::class)->run());
    $integrity = $results->firstWhere('key', 'tenant_integrity');

    expect($integrity['status'])->toBe('error')
        ->and($integrity['detail'])->toContain('1 riferimenti')
        ->and(DB::table('technical_setups')->where('workspace_id', $second->id)->count())->toBe(1);
});
