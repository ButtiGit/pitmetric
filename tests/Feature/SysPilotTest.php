<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

test('syspilot uses the same database access permission as PitMetric', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    $user->forceFill(['database_access_enabled' => false])->save();

    $this->getJson('/api/syspilot/bootstrap')->assertUnauthorized();
    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')->assertForbidden();

    $user->forceFill(['database_access_enabled' => true])->save();
    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')
        ->assertOk()
        ->assertJsonPath('counts.total', 0)
        ->assertJsonPath('configured', true);

    $user->forceFill(['database_access_enabled' => false])->save();
    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')->assertForbidden();
});

test('existing PitMetric update editors retain their DB access override', function (): void {
    $user = User::factory()->create(['email' => 'editor@example.test']);
    $user->forceFill(['database_access_enabled' => false])->save();
    config()->set('pitmetric.update_editor_emails', ['editor@example.test']);

    $this->actingAs($user)->getJson('/api/syspilot/bootstrap')->assertOk();
});

test('revoked database access prevents further AI requests and access to existing work', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    $user->forceFill(['database_access_enabled' => true])->save();
    fakeSysPilotChecklist();

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Controllare i servizi su un server Linux dopo un aggiornamento programmato.',
    ])->assertCreated()->json('id');

    $user->forceFill(['database_access_enabled' => false])->save();
    $this->actingAs($user)->getJson('/api/syspilot/interventions/'.$id)->assertForbidden();
    $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Pianificare le verifiche necessarie sulla macchina dopo il riavvio.',
    ])->assertForbidden();

    Http::assertSentCount(1);
});

test('creates a checklist then closes an intervention after the steps are documented', function (): void {
    $user = User::factory()->create(['email' => 'tecnico@example.test']);
    $user->forceFill(['database_access_enabled' => true])->save();
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
    $owner->forceFill(['database_access_enabled' => true])->save();
    $other->forceFill(['database_access_enabled' => true])->save();
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
    $user->forceFill(['database_access_enabled' => true])->save();
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

/*
|--------------------------------------------------------------------------
| SysPilot checklist revisions & autosave — no real OpenAI calls.
|--------------------------------------------------------------------------
*/
function sysPilotOpenAiResponse(array $object): array
{
    return [
        'output' => [['content' => [
            ['type' => 'output_text', 'text' => json_encode($object, JSON_THROW_ON_ERROR)],
        ]]],
    ];
}

function sysPilotAiChanges(array $changes): array
{
    return ['summary' => 'Aggiornamento operativo proposto', 'changes' => $changes];
}

function sysPilotChange(string $action, int $stepId, int $anchorId = 0, string $placement = 'end'): array
{
    return [
        'action' => $action,
        'step_id' => $stepId,
        'anchor_id' => $anchorId,
        'placement' => $placement,
        'phase' => 'Verifiche pre-riavvio',
        'title' => 'Controllare il backup prima del riavvio',
        'detail' => 'Verificare che esista una copia recuperabile.',
        'reason' => 'Riduce il rischio di un riavvio senza punto di ripristino.',
    ];
}

test('AI changes are previewed then approved and never modify completed steps or technician notes', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();

    $initial = ['title' => 'Controllo server', 'phases' => [
        ['name' => 'Verifiche', 'steps' => [
            ['title' => 'Controllare spazio disco', 'detail' => 'Verificare il disco prima del riavvio.'],
        ]],
    ]];
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse($initial))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([sysPilotChange('add', 0, 1, 'before')])))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Verificare il server di prova e documentare i controlli prima del riavvio.',
    ])->assertCreated()->json('id');

    $step = DB::table('syspilot_steps')->where('intervention_id', $id)->first();
    expect($step)->not->toBeNull();

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/$step->id", [
        'state' => 'done',
        'note' => 'Controllato il disco: spazio sufficiente.',
    ])->assertOk();

    $proposal = $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Aggiungi un passaggio per il backup prima del controllo del disco.',
    ])->assertOk()->assertJsonCount(1, 'changes')->json();

    expect($proposal['token'])->toBeString();
    expect(DB::table('syspilot_steps')->where('intervention_id', $id)->count())->toBe(1);

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/apply", [
        'token' => $proposal['token'],
    ])->assertOk()->assertJsonPath('applied', 1);

    $rows = DB::table('syspilot_steps')->where('intervention_id', $id)->orderBy('position')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->title)->toBe('Controllare il backup prima del riavvio')
        ->and($rows[0]->state)->toBe('todo')
        ->and($rows[1]->id)->toBe($step->id)
        ->and($rows[1]->state)->toBe('done')
        ->and($rows[1]->note)->toBe('Controllato il disco: spazio sufficiente.');
});

test('autosave revisions reject stale updates and retain the newer note', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();
    fakeSysPilotChecklist();

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Controllare le risorse e registrare le informazioni del server di test.',
    ])->assertCreated()->json('id');

    $step = $this->actingAs($user)->getJson("/api/syspilot/interventions/$id")
        ->assertOk()->json('steps.0');
    expect($step['revision'])->toHaveLength(64);

    $updated = $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/".$step['id'], [
        'state' => 'todo', 'note' => 'Prima nota registrata', 'revision' => $step['revision'],
    ])->assertOk();

    expect($updated->json('revision'))->not->toBe($step['revision']);

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/".$step['id'], [
        'state' => 'done', 'note' => 'Tentativo da tab obsoleta', 'revision' => $step['revision'],
    ])->assertStatus(409);

    $this->assertDatabaseHas('syspilot_steps', [
        'id' => $step['id'], 'note' => 'Prima nota registrata', 'state' => 'todo',
    ]);
});

test('a proposed checklist edit is refused after notes change', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();
    $initial = ['title' => 'Verifica server', 'phases' => [
        ['name' => 'Controlli', 'steps' => [
            ['title' => 'Verifica disco', 'detail' => 'Controlla il disco.'],
        ]],
    ]];
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse($initial))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([sysPilotChange('edit', 1)])))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Verificare il server e annotare gli esiti dei controlli di sistema.',
    ])->assertCreated()->json('id');

    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');
    $proposal = $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Migliora il titolo e dettaglio del controllo del disco.',
    ])->assertOk()->json();

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/$stepId", [
        'state' => 'todo', 'note' => 'Nota aggiornata mentre la proposta era aperta.',
    ])->assertOk();

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/apply", [
        'token' => $proposal['token'],
    ])->assertStatus(409);

    $this->assertDatabaseHas('syspilot_steps', [
        'id' => $stepId, 'title' => 'Verifica disco',
        'note' => 'Nota aggiornata mentre la proposta era aperta.',
    ]);
});

test('an AI proposal cannot remove a completed step', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();
    $initial = ['title' => 'Verifica server', 'phases' => [
        ['name' => 'Controlli', 'steps' => [
            ['title' => 'Verifica disco', 'detail' => 'Controlla il disco.'],
        ]],
    ]];
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse($initial))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([sysPilotChange('remove', 1)])))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Documentare controlli del server e verificare il funzionamento dei dischi.',
    ])->assertCreated()->json('id');
    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/$stepId", [
        'state' => 'done', 'note' => 'Eseguito',
    ])->assertOk();

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Rimuovi la verifica del disco dalla checklist.',
    ])->assertUnprocessable();

    $this->assertDatabaseHas('syspilot_steps', ['id' => $stepId, 'state' => 'done', 'note' => 'Eseguito']);
});

test('an approved AI token cannot be reused for another technician or intervention', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->forceFill(['database_access_enabled' => true])->save();
    $other->forceFill(['database_access_enabled' => true])->save();
    $initial = ['title' => 'Server', 'phases' => [
        ['name' => 'Controlli', 'steps' => [
            ['title' => 'Verifica disco', 'detail' => 'Controlla disco.'],
        ]],
    ]];
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse($initial))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([sysPilotChange('add', 0)])))]);

    $id = $this->actingAs($owner)->postJson('/api/syspilot/interventions', [
        'request' => 'Eseguire la verifica del disco sul server e preparare il backup.',
    ])->assertCreated()->json('id');

    $preview = $this->actingAs($owner)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Aggiungi un ulteriore controllo prima di chiudere il lavoro.',
    ])->assertOk()->json();

    $this->actingAs($other)->postJson("/api/syspilot/interventions/$id/ai/apply", [
        'token' => $preview['token'],
    ])->assertUnprocessable();

    $this->actingAs($owner)->postJson("/api/syspilot/interventions/$id/ai/apply", [
        'token' => $preview['token'].'tampered',
    ])->assertUnprocessable();

    $this->assertDatabaseCount('syspilot_steps', 1);
});

test('initial checklist respects explicitly completed work and pending projector network setup', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();

    $request = 'Oggi ho sistemato un proiettore nella sala riunioni. Devo ancora connetterlo alla rete.';
    Http::fake(['api.openai.com/*' => Http::response(sysPilotOpenAiResponse([
        'title' => 'Installazione proiettore',
        'phases' => [
            ['name' => 'Installazione', 'steps' => [
                [
                    'title' => 'Montaggio e posizionamento del proiettore',
                    'detail' => 'Controllare l’installazione dichiarata.',
                    'state' => 'done',
                    'source_quote' => 'ho sistemato un proiettore',
                ],
                [
                    'title' => 'Connessione alla rete aziendale',
                    'detail' => 'Configurare rete e verificare il collegamento.',
                    'state' => 'todo',
                    'source_quote' => '',
                ],
            ]],
        ],
    ]))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => $request,
    ])->assertCreated()->json('id');

    $steps = DB::table('syspilot_steps')->where('intervention_id', $id)
        ->orderBy('position')->get();

    expect($steps)->toHaveCount(2)
        ->and($steps[0]->state)->toBe('done')
        ->and($steps[0]->note)->toContain('Dichiarato già svolto')
        ->and($steps[1]->state)->toBe('todo')
        ->and($steps[1]->note)->toBe('');

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/close", [])
        ->assertStatus(422);
});

test('unsupported or pending text is never accepted as evidence of completed work', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();
    $request = 'Devo ancora collegare alla rete il proiettore già montato.';
    Http::fake(['api.openai.com/*' => Http::response(sysPilotOpenAiResponse([
        'title' => 'Connessione proiettore',
        'phases' => [
            ['name' => 'Rete', 'steps' => [
                [
                    'title' => 'Connettere proiettore alla rete',
                    'detail' => 'Configurare indirizzo e verificare la connessione.',
                    'state' => 'done',
                    'source_quote' => 'Devo ancora collegare alla rete',
                ],
                [
                    'title' => 'Controllare il DNS',
                    'detail' => 'Verificare risoluzione.',
                    'state' => 'done',
                    'source_quote' => 'il DNS è stato configurato correttamente',
                ],
            ]],
        ],
    ]))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => $request,
    ])->assertCreated()->json('id');

    $steps = DB::table('syspilot_steps')->where('intervention_id', $id)->get();

    expect($steps)->toHaveCount(2)
        ->and($steps[0]->state)->toBe('todo')
        ->and($steps[1]->state)->toBe('todo');
});

test('AI can add real nested diagnostic checks without changing parent state or notes', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();

    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse([
            'title' => 'Diagnosi rete',
            'phases' => [['name' => 'Rete', 'steps' => [
                ['title' => 'Diagnosi rete del proiettore', 'detail' => 'Verificare la connettività.'],
            ]]],
        ]))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([
            [
                ...sysPilotChange('add', 0, 1, 'after'),
                'title' => 'Verifica IP e gateway',
                'parent_step_id' => 1,
            ],
            [
                ...sysPilotChange('add', 0, 1, 'after'),
                'title' => 'Test DNS',
                'parent_step_id' => 1,
            ],
        ])))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'La rete del proiettore non funziona; servono verifiche di connettività.',
    ])->assertCreated()->json('id');

    $parentId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');
    // Keep model-produced fixture IDs deterministic even if migrations seed data.
    expect($parentId)->toBe(1);

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/$parentId", [
        'state' => 'blocked',
        'note' => 'Il gateway non risponde.',
    ])->assertOk();

    $preview = $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Spezza il controllo della rete in due sotto-attività: verifica gateway e DNS.',
    ])->assertOk()->assertJsonCount(2, 'changes')->json();

    expect(DB::table('syspilot_steps')->where('intervention_id', $id)->count())->toBe(1);

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/apply", [
        'token' => $preview['token'],
    ])->assertOk()->assertJsonPath('applied', 2);

    $rows = DB::table('syspilot_steps')->where('intervention_id', $id)
        ->orderBy('position')->get();

    expect($rows)->toHaveCount(3)
        ->and($rows[0]->state)->toBe('blocked')
        ->and($rows[0]->note)->toBe('Il gateway non risponde.')
        ->and($rows[1]->parent_step_id)->toBe($parentId)
        ->and($rows[2]->parent_step_id)->toBe($parentId)
        ->and($rows[1]->state)->toBe('todo')
        ->and($rows[2]->state)->toBe('todo');
});

test('AI refuses nested checks under a completed parent', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['database_access_enabled' => true])->save();

    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(sysPilotOpenAiResponse([
            'title' => 'Rete proiettore',
            'phases' => [['name' => 'Rete', 'steps' => [
                ['title' => 'Verificare la rete', 'detail' => 'Controllare il collegamento.'],
            ]]],
        ]))
        ->push(sysPilotOpenAiResponse(sysPilotAiChanges([
            [
                ...sysPilotChange('add', 0, 1, 'after'),
                'parent_step_id' => 1,
            ],
        ])))]);

    $id = $this->actingAs($user)->postJson('/api/syspilot/interventions', [
        'request' => 'Verificare rete e collegamento del proiettore nella sala riunioni.',
    ])->assertCreated()->json('id');

    $stepId = DB::table('syspilot_steps')->where('intervention_id', $id)->value('id');

    $this->actingAs($user)->patchJson("/api/syspilot/interventions/$id/steps/$stepId", [
        'state' => 'done',
        'note' => 'Verificato da tecnico.',
    ])->assertOk();

    $this->actingAs($user)->postJson("/api/syspilot/interventions/$id/ai/propose", [
        'instruction' => 'Aggiungi verifiche come sotto-attività del passaggio completato.',
    ])->assertUnprocessable();

    $this->assertDatabaseCount('syspilot_steps', 1);
});
