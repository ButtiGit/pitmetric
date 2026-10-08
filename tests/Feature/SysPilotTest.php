<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    config()->set('syspilot.openai_api_key', 'fake-test-key');
    config()->set('syspilot.openai_model', 'gpt-4o-mini');
});

function fakeSysPilotChecklist(): void
{
    Http::fake([
        'api.openai.com/*' => Http::response([
            'output' => [
                ['content' => [
                    ['type' => 'output_text', 'text' => json_encode([
                        'title' => 'Controllo server di prova',
                        'phases' => [
                            ['name' => 'Verifiche', 'steps' => [
                                ['title' => 'Controlla lo spazio disco', 'detail' => 'Verifica capacità e stato.'],
                            ]],
                        ],
                    ], JSON_THROW_ON_ERROR)],
                ]],
            ],
        ], 200),
    ]);
}

test('only verified allowlisted users may access syspilot API', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    $this->getJson('/api/syspilot/bootstrap')->assertUnauthorized();

    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')->assertForbidden();

    config()->set('syspilot.allowed_emails', 'tecnico@example.test');
    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')
        ->assertOk()
        ->assertJsonPath('counts.total', 0)
        ->assertJsonPath('configured', true);
});

test('creates a checklist then closes an intervention after the steps are documented', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    config()->set('syspilot.allowed_emails', $user->email);
    fakeSysPilotChecklist();

    $response = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Devo verificare lo stato del server Ubuntu dopo un riavvio programmato.',
        'client' => 'Azienda demo',
        'asset' => 'SRV-01',
    ]);
    $response->assertCreated();
    $id = $response->json('id');
    $this->assertDatabaseHas('syspilot_interventions', ['id' => $id, 'user_id' => $user->id]);
    $this->assertDatabaseHas('syspilot_steps', ['intervention_id' => $id, 'state' => 'todo']);

    $this->actingAs($user)->postJson('/api/syspilot/interventions/'.$id.'/close', [])
        ->assertStatus(422);
    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');

    $this->actingAs($user)->patchJson('/api/syspilot/interventions/'.$id.'/steps/'.$stepId, [
        'state' => 'done',
        'note' => 'Spazio controllato e verificato dal tecnico.',
    ])->assertOk();

    $this->actingAs($user)->postJson('/api/syspilot/interventions/'.$id.'/close', [])
        ->assertOk();

    $this->assertDatabaseHas('syspilot_interventions', ['id' => $id, 'status' => 'closed']);
    $this->assertDatabaseHas('syspilot_events', ['intervention_id' => $id, 'message' => 'Intervento chiuso dal tecnico.']);
    $this->actingAs($user)->patchJson('/api/syspilot/interventions/'.$id.'/steps/'.$stepId, [
        'state' => 'todo',
        'note' => '',
    ])->assertStatus(409);
});

test('cannot read or modify another technicians intervention', function (): void {
    $owner = User::factory()->create(['email' => 'owner@example.test']);
    $other = User::factory()->create(['email' => 'other@example.test']);
    config()->set('syspilot.allowed_emails', 'owner@example.test,other@example.test');
    fakeSysPilotChecklist();

    $id = $this->actingAs($owner)->postJson('/api/syspilot/interventions', [
        'request' => 'Controllare i servizi del server e verificare che tutto sia operativo.',
    ])->assertCreated()->json('id');

    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');

    $this->actingAs($other)->getJson('/api/syspilot/interventions/'.$id)->assertNotFound();
    $this->actingAs($other)->patchJson('/api/syspilot/interventions/'.$id.'/steps/'.$stepId, [
        'state' => 'done',
    ])->assertNotFound();
    $this->actingAs($other)->postJson('/api/syspilot/interventions/'.$id.'/close', [])
        ->assertNotFound();
});

test('skipped steps require an explanation', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    config()->set('syspilot.allowed_emails', $user->email);
    fakeSysPilotChecklist();
    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Verificare tutti i servizi del server Linux di staging prima di andare online.',
    ])->assertCreated()->json('id');
    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');

    $this->actingAs($user)->patchJson('/api/syspilot/interventions/'.$id.'/steps/'.$stepId, [
        'state' => 'skipped', 'note' => '',
    ])->assertUnprocessable();
    $this->assertDatabaseHas('syspilot_steps', ['id' => $stepId, 'state' => 'todo']);
});
