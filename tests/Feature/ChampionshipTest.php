<?php

use App\Models\Championship;
use App\Models\ChampionshipParticipant;
use App\Models\ChampionshipResult;
use App\Models\ChampionshipRound;
use App\Models\Driver;
use App\Models\RaceEvent;
use App\Models\User;
use App\Services\ChampionshipRecords;
use App\Services\ChampionshipSheetReader;
use App\Services\ChampionshipStandings;
use App\Services\DataHubService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user);
    $this->championship = Championship::factory()->create(['name' => 'Trofeo Kart 2026']);
});

function championshipDriver(Championship $championship, string $name, string $number): ChampionshipParticipant
{
    return app(ChampionshipRecords::class)->participant($championship, ['name' => $name, 'number' => $number, 'status' => 'active']);
}

function championshipRound(Championship $championship, int $number = 1): ChampionshipRound
{
    return app(ChampionshipRecords::class)->round($championship, ['number' => $number, 'name' => 'Round '.$number, 'status' => 'planned']);
}

function championshipUpload($test, Championship $championship, string $kind, string $contents): string
{
    return $test->post(route('championships.imports.upload', $championship), [
        'kind' => $kind, 'file' => UploadedFile::fake()->createWithContent('import.csv', $contents),
    ], ['Accept' => 'application/json'])->assertOk()->json('token');
}

function championshipPreview($test, Championship $championship, string $token, array $mapping)
{
    return $test->postJson(route('championships.imports.preview', $championship), ['token' => $token, 'sheet' => 0, 'header_row' => 1, 'mapping' => $mapping]);
}

test('championships use modal forms and support editing archiving and restoration', function () {
    $this->get(route('championships.index'))->assertOk()->assertSee('Trofeo Kart 2026')->assertSee('id="new-championship"', false);
    $response = $this->post(route('championships.store'), ['name' => 'Coppa 2027', 'season' => '2027', 'category' => 'KZ', 'status' => 'planned']);
    $created = Championship::query()->where('name', 'Coppa 2027')->firstOrFail();
    $response->assertRedirect(route('championships.show', $created));
    $this->get(route('championships.show', $created))->assertOk()->assertSee('id="new-championship-round"', false)->assertSee('id="championship-import"', false)->assertSee('data-import-upload', false);
    $this->put(route('championships.update', $created), ['name' => 'Coppa aggiornata', 'season' => '2027', 'status' => 'active'])->assertSessionHasNoErrors();
    expect($created->fresh()->name)->toBe('Coppa aggiornata');
    $this->delete(route('championships.archive', $created))->assertRedirect(route('championships.index'));
    expect($created->fresh()->trashed())->toBeTrue();
    $this->get(route('championships.show', $created))->assertNotFound();
    $this->get(route('championships.index', ['status' => 'archived']))->assertOk()->assertSee('Coppa aggiornata');
    $this->patch(route('championships.restore', $created))->assertRedirect(route('championships.show', $created));
    expect($created->fresh()->trashed())->toBeFalse();
});

test('rounds validate dates retain links to operational weekends and reject nested mismatches', function () {
    $event = RaceEvent::create(['name' => 'Weekend Busca', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'created_by' => $this->user->id]);
    $this->post(route('championships.rounds.store', $this->championship), ['number' => 1, 'name' => 'Busca', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-02', 'status' => 'planned', 'event_id' => $event->id])->assertSessionHasNoErrors();
    $round = $this->championship->rounds()->firstOrFail();
    expect($round->event_id)->toBe($event->id);
    $this->get(route('events.show', $event))->assertOk()->assertSee(route('championships.show', $this->championship), false);
    $this->post(route('championships.rounds.store', $this->championship), ['number' => 1, 'name' => 'Duplicate', 'status' => 'planned'])->assertSessionHasErrors('number');
    $this->put(route('championships.rounds.update', [$this->championship, $round->id]), ['number' => 1, 'name' => 'Busca', 'starts_on' => '2026-11-02', 'ends_on' => '2026-11-01', 'status' => 'planned'])->assertSessionHasErrors('ends_on');
    $other = Championship::factory()->create();
    $this->put(route('championships.rounds.update', [$other, $round->id]), [])->assertNotFound();
});

test('participants reuse roster drivers reject duplicates and keep results after withdrawal', function () {
    $driver = Driver::create(['display_name' => 'Alice Rossi', 'racing_number' => '007']);
    $this->post(route('championships.participants.store', $this->championship), ['driver_id' => $driver->id, 'number' => '007', 'status' => 'active'])->assertSessionHasNoErrors();
    $participant = $this->championship->participants()->firstOrFail();
    $this->post(route('championships.participants.store', $this->championship), ['driver_id' => $driver->id, 'status' => 'active'])->assertSessionHasErrors('driver_id');
    expect(Driver::query()->count())->toBe(1);
    $round = championshipRound($this->championship);
    app(ChampionshipRecords::class)->result($this->championship, $round, ['participant_id' => $participant->id, 'position' => 1, 'status' => 'classified']);
    $this->put(route('championships.participants.update', [$this->championship, $participant->id]), ['number' => '007', 'team_name' => 'PN', 'status' => 'withdrawn'])->assertSessionHasNoErrors();
    expect($participant->fresh()->status)->toBe('withdrawn')->and(app(ChampionshipStandings::class)->table($this->championship->fresh())[0]['points'])->toBe(2500);
});

test('scores use exact hundredths bonuses adjustments countback and shared ties', function () {
    $records = app(ChampionshipRecords::class);
    $records->scoring($this->championship, ['points' => [1 => '25,50', 2 => '25.50'], 'pole_bonus' => '0.50', 'fastest_lap_bonus' => '1.25']);
    $alice = championshipDriver($this->championship, 'Alice', '1');
    $bob = championshipDriver($this->championship, 'Bob', '2');
    $round = championshipRound($this->championship);
    $result = $records->result($this->championship, $round, ['participant_id' => $alice->id, 'position' => 1, 'status' => 'classified', 'pole' => true, 'fastest_lap' => true, 'adjustment' => '-0.25']);
    expect(app(ChampionshipStandings::class)->points($this->championship, $result))->toBe(2700);
    $records->result($this->championship, $round, ['participant_id' => $alice->id, 'position' => 1, 'status' => 'classified']);
    $records->result($this->championship, $round, ['participant_id' => $bob->id, 'position' => 2, 'status' => 'classified']);
    $table = app(ChampionshipStandings::class)->table($this->championship->fresh());
    expect($table[0]['participant']->id)->toBe($alice->id)->and($table[1]['rank'])->toBe(2);
    $round2 = championshipRound($this->championship, 2);
    $records->result($this->championship, $round2, ['participant_id' => $alice->id, 'position' => 2, 'status' => 'classified']);
    $records->result($this->championship, $round2, ['participant_id' => $bob->id, 'position' => 1, 'status' => 'classified']);
    $table = app(ChampionshipStandings::class)->table($this->championship->fresh());
    expect(array_column($table, 'rank'))->toBe([1, 1])->and($table[0]['points'])->toBe(5100);
    $round2->update(['status' => 'cancelled']);
    expect(app(ChampionshipStandings::class)->table($this->championship->fresh())[0]['points'])->toBe(2550);
});

test('result corrections reject duplicate positions and removal can be safely reentered', function () {
    $alice = championshipDriver($this->championship, 'Alice', '1');
    $bob = championshipDriver($this->championship, 'Bob', '2');
    $round = championshipRound($this->championship);
    $url = route('championships.results.store', [$this->championship, $round->id]);
    $this->post($url, ['participant_id' => $alice->id, 'position' => 1, 'status' => 'classified'])->assertSessionHasNoErrors();
    $result = $round->results()->firstOrFail();
    $this->post($url, ['participant_id' => $bob->id, 'position' => 1, 'status' => 'classified'])->assertSessionHasErrors('position');
    $this->delete(route('championships.results.remove', [$this->championship, $round->id, $result->id]))->assertSessionHasNoErrors();
    $this->post($url, ['participant_id' => $alice->id, 'position' => 99, 'status' => 'dnf', 'adjustment' => '-2.50'])->assertSessionHasNoErrors();
    expect($round->results()->first()->id)->toBe($result->id)->and($result->fresh()->position)->toBeNull()->and($result->fresh()->trashed())->toBeFalse();
    expect(app(ChampionshipStandings::class)->table($this->championship->fresh())[0]['points'])->toBe(0);
    expect(app(ChampionshipStandings::class)->points($this->championship, $result->fresh()))->toBe(-250);
});

test('other workspace identifiers and same workspace other championship children are rejected', function () {
    $otherUser = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $otherUser->workspaces()->firstOrFail();
    $foreign = Championship::factory()->create(['workspace_id' => $workspace->id]);
    $foreignDriver = Driver::forceCreate(['workspace_id' => $workspace->id, 'display_name' => 'Private driver']);
    $this->get(route('championships.show', $foreign))->assertNotFound();
    $this->put(route('championships.update', $foreign), [])->assertNotFound();
    $this->postJson(route('championships.participants.store', $this->championship), ['driver_id' => $foreignDriver->id, 'status' => 'active'])->assertUnprocessable()->assertJsonValidationErrors('driver_id');
    $otherChamp = Championship::factory()->create();
    $participant = championshipDriver($otherChamp, 'Other entrant', '30');
    $round = championshipRound($this->championship);
    $this->post(route('championships.results.store', [$this->championship, $round->id]), ['participant_id' => $participant->id, 'position' => 1, 'status' => 'classified'])->assertSessionHasErrors('participant_id');
    expect(fn () => ChampionshipResult::create(['round_id' => $round->id, 'championship_id' => $this->championship->id, 'participant_id' => $participant->id, 'status' => 'classified', 'position' => 1]))->toThrow(QueryException::class);
});

test('viewers can read but cannot create edit archive or import', function () {
    $viewer = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $viewer->workspaces()->attach($this->championship->workspace_id, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);
    $this->actingAs($viewer)->withSession(['pitmetric.current_workspace_id' => $this->championship->workspace_id]);
    $this->get(route('championships.show', $this->championship))->assertOk()->assertDontSee('id="new-championship-round"', false);
    $this->post(route('championships.store'), [])->assertForbidden();
    $this->put(route('championships.update', $this->championship), [])->assertForbidden();
    $this->delete(route('championships.archive', $this->championship))->assertForbidden();
    $this->post(route('championships.imports.upload', $this->championship), [])->assertForbidden();
});

test('csv wizard previews without writes imports atomically and consumes its token', function () {
    $token = championshipUpload($this, $this->championship, 'participants', "\xEF\xBB\xBFNome;Numero;Licenza;Team\nAlice Rossì;007;LIC-7;Team Uno\nBob Bianchi;12;LIC-12;Team Due\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1, 'licence' => 2, 'team_name' => 3])->assertOk()->assertJsonPath('new', 2)->assertJsonPath('errors', 0);
    expect(Driver::query()->count())->toBe(0);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertOk()->assertJsonPath('imported', 2);
    expect($this->championship->participants()->count())->toBe(2)->and(Driver::query()->where('display_name', 'Alice Rossì')->first()->racing_number)->toBe('007');
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
});

test('existing entries are explicitly skipped and duplicate source rows block all writes', function () {
    championshipDriver($this->championship, 'Alice', '1');
    $token = championshipUpload($this, $this->championship, 'participants', "Name,Number\nAlice,1\nBob,2\nBob,2\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1])->assertOk()->assertJsonPath('skipped', 1)->assertJsonPath('errors', 1);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    expect(Driver::query()->count())->toBe(1);
});

test('round and scoring imports handle local dates decimal commas and exact points', function () {
    $token = championshipUpload($this, $this->championship, 'rounds', "Numero;Tappa;Data;Fine;Circuito\n1;Busca;01/11/2026;02/11/2026;Busca\n2;Lonato;2026-12-01;2026-12-02;Lonato\n");
    championshipPreview($this, $this->championship, $token, ['number' => 0, 'name' => 1, 'starts_on' => 2, 'ends_on' => 3, 'venue' => 4])->assertOk()->assertJsonPath('errors', 0);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertOk()->assertJsonPath('imported', 2);
    expect($this->championship->rounds()->first()->starts_on->format('Y-m-d'))->toBe('2026-11-01');
    $token = championshipUpload($this, $this->championship, 'scoring', "Posizione;Punti\n1;25,5\n2;18,25\n");
    championshipPreview($this, $this->championship, $token, ['position' => 0, 'points' => 1])->assertOk()->assertJsonPath('errors', 0);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertOk();
    expect($this->championship->fresh()->points)->toBe([1 => 2550, 2 => 1825]);
});

test('results import resolves enrolled drivers and rounds and rejects conflicting positions', function () {
    championshipDriver($this->championship, 'Alice', '007');
    championshipDriver($this->championship, 'Bob', '12');
    championshipRound($this->championship);
    $token = championshipUpload($this, $this->championship, 'results', "Tappa;Numero;Posizione;Stato;Giro;Rettifica\n1;007;1;classified;1;-0,5\n1;12;;dnf;0;0\n");
    championshipPreview($this, $this->championship, $token, ['round_number' => 0, 'number' => 1, 'position' => 2, 'status' => 3, 'fastest_lap' => 4, 'adjustment' => 5])->assertOk()->assertJsonPath('errors', 0);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertOk()->assertJsonPath('imported', 2);
    expect(ChampionshipResult::query()->count())->toBe(2)->and(ChampionshipResult::query()->where('position', 1)->first()->adjustment)->toBe(-50);
});

test('import tokens are scoped to the uploader workspace and championship and require preview', function () {
    $token = championshipUpload($this, $this->championship, 'participants', "Name,Number\nAlice,1\n");
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    $other = Championship::factory()->create();
    championshipPreview($this, $other, $token, ['name' => 0])->assertUnprocessable();
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'licence' => 0])->assertUnprocessable();
    $coworker = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $coworker->workspaces()->attach($this->championship->workspace_id, ['role' => 'manager', 'status' => 'active', 'joined_at' => now()]);
    $this->actingAs($coworker)->withSession(['pitmetric.current_workspace_id' => $this->championship->workspace_id]);
    championshipPreview($this, $this->championship, $token, ['name' => 0])->assertUnprocessable();
});

test('import rechecks live data after preview instead of overwriting changes', function () {
    $token = championshipUpload($this, $this->championship, 'participants', "Name,Number\nAlice,7\nBob,8\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1])->assertOk()->assertJsonPath('new', 2);
    championshipDriver($this->championship, 'Someone else', '8');
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    expect(Driver::query()->count())->toBe(1)->and($this->championship->participants()->count())->toBe(1);
});

test('xlsx reader supports multiple worksheets shared strings and Excel dates without evaluating formulas', function () {
    $path = tempnam(sys_get_temp_dir(), 'pm-xlsx-');
    $zipPath = $path.'.zip';
    try {
        $archive = new PharData($zipPath);
        $archive['xl/workbook.xml'] = '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Piloti" sheetId="1" r:id="rId1"/><sheet name="Calendario" sheetId="2" r:id="rId2"/></sheets></workbook>';
        $archive['xl/_rels/workbook.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Target="worksheets/sheet2.xml"/></Relationships>';
        $archive['xl/sharedStrings.xml'] = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Nome</t></si><si><r><t>Alice </t></r><r><t>Rossi</t></r></si></sst>';
        $archive['xl/styles.xml'] = '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cellXfs><xf numFmtId="0"/><xf numFmtId="14"/></cellXfs></styleSheet>';
        $archive['xl/worksheets/sheet1.xml'] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c></row><row r="2"><c r="A2" t="s"><v>1</v></c></row></sheetData></worksheet>';
        $archive['xl/worksheets/sheet2.xml'] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Data</t></is></c></row><row r="2"><c r="A2" s="1"><v>46327</v></c><c r="B2"><f>SUM(1,2)</f><v>3</v></c></row></sheetData></worksheet>';
        unset($archive);
        $file = UploadedFile::fake()->createWithContent('campionato.xlsx', file_get_contents($zipPath));
        $sheets = app(ChampionshipSheetReader::class)->read($file);
        expect($sheets[0]['name'])->toBe('Piloti')->and($sheets[0]['rows'][1][0])->toBe('Alice Rossi')
            ->and($sheets[1]['name'])->toBe('Calendario')->and($sheets[1]['rows'][1][0])->toMatch('/^2026-/')->and($sheets[1]['rows'][1][1])->toBe('3');
    } finally {
        @unlink($zipPath);
        @unlink($path);
    }
});

test('malformed files invalid dates and missing columns produce reviewable validation errors', function () {
    $this->post(route('championships.imports.upload', $this->championship), ['kind' => 'rounds', 'file' => UploadedFile::fake()->createWithContent('invalid.xlsx', 'not a zip')], ['Accept' => 'application/json'])->assertUnprocessable();
    $token = championshipUpload($this, $this->championship, 'rounds', "Numero;Nome;Data\n1;Busca;31/02/2026\n");
    championshipPreview($this, $this->championship, $token, ['number' => 0, 'name' => 1, 'starts_on' => 2])->assertOk()->assertJsonPath('errors', 1);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    $this->travel(21)->minutes();
    championshipPreview($this, $this->championship, $token, ['number' => 0])->assertUnprocessable();
});

test('workspace backup preserves and remaps championship relationships and scoring', function () {
    $participant = championshipDriver($this->championship, 'Alice', '7');
    $round = championshipRound($this->championship);
    app(ChampionshipRecords::class)->result($this->championship, $round, ['participant_id' => $participant->id, 'position' => 1, 'status' => 'classified']);
    $service = app(DataHubService::class);
    $event = RaceEvent::create(['name' => 'Linked weekend', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'created_by' => $this->user->id]);
    $round->update(['event_id' => $event->id]);
    $sourceWorkspace = $this->user->workspaces()->firstOrFail();
    $backup = $service->export($sourceWorkspace);
    expect($backup['tables']['championship_results'])->toHaveCount(1);
    $targetUser = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $targetUser->workspaces()->firstOrFail();
    $this->actingAs($targetUser)->withSession(['pitmetric.current_workspace_id' => $workspace->id]);
    $service->import($workspace, $targetUser, $backup);
    $copy = Championship::query()->firstOrFail();
    expect($copy->id)->not->toBe($this->championship->id)->and($copy->workspace_id)->toBe($workspace->id);
    expect(app(ChampionshipStandings::class)->table($copy)[0]['points'])->toBe(2500);
    expect($copy->rounds()->first()->event_id)->not->toBe($event->id)->and($copy->rounds()->first()->event->workspace_id)->toBe($workspace->id);
});

test('zero race numbers stay distinct and duplicate numbers in an import are visible before saving', function () {
    $token = championshipUpload($this, $this->championship, 'participants', "Name,Number\nAlice,0\nBob,0\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1])->assertOk()->assertJsonPath('errors', 1);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    expect(Driver::query()->count())->toBe(0);
    championshipDriver($this->championship, 'Alice', '0');
    $this->post(route('championships.participants.store', $this->championship), ['name' => 'Bob', 'number' => '0', 'status' => 'active'])->assertSessionHasErrors('number');
    expect(Driver::query()->count())->toBe(1);
});

test('reader handles UTF16 tab separated files and rejects XML entities and oversized archive entries', function () {
    $file = UploadedFile::fake()->createWithContent('piloti.tsv', "\xFF\xFE".mb_convert_encoding("Nome\tNumero\nAndré\t007\n", 'UTF-16LE', 'UTF-8'));
    $rows = app(ChampionshipSheetReader::class)->read($file)[0]['rows'];
    expect($rows[0])->toBe(['Nome', 'Numero'])->and($rows[1])->toBe(['André', '007']);
    foreach ([
        '<!DOCTYPE workbook [<!ENTITY external SYSTEM "file:///not-readable">]><workbook>&external;</workbook>',
        str_repeat(' ', 8 * 1024 * 1024 + 1),
    ] as $xml) {
        $temporary = tempnam(sys_get_temp_dir(), 'pm-hostile-');
        $path = $temporary.'.zip';
        try {
            $archive = new PharData($path);
            $archive['xl/workbook.xml'] = $xml;
            $archive->compressFiles(Phar::GZ);
            unset($archive);
            $file = UploadedFile::fake()->createWithContent('unsafe.xlsx', file_get_contents($path));
            $this->post(route('championships.imports.upload', $this->championship), ['kind' => 'rounds', 'file' => $file], ['Accept' => 'application/json'])->assertUnprocessable();
        } finally {
            @unlink($path);
            @unlink($temporary);
        }
    }
});

test('foreign weekend links malformed scoring and nonactivated accounts cannot change championship data', function () {
    $other = User::factory()->withDatabaseAccess()->create();
    $foreignEvent = RaceEvent::forceCreate(['workspace_id' => $other->workspaces()->firstOrFail()->id, 'name' => 'Private weekend', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'created_by' => $other->id]);
    $this->post(route('championships.rounds.store', $this->championship), ['number' => 1, 'name' => 'Attempt', 'status' => 'planned', 'event_id' => $foreignEvent->id])->assertSessionHasErrors('event_id');
    $this->put(route('championships.scoring', $this->championship), ['points' => [1 => '-25'], 'pole_bonus' => '0', 'fastest_lap_bonus' => '0'])->assertSessionHasErrors('points.1');
    expect($this->championship->fresh()->points)->toBe(Championship::DEFAULT_POINTS);
    $normal = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($normal)->get(route('championships.index'))->assertForbidden();
    $this->post(route('championships.store'), [])->assertForbidden();
});

test('a licence cannot silently associate an imported name with another person', function () {
    Driver::create(['display_name' => 'Alice', 'licence_reference' => 'LIC-7']);
    $token = championshipUpload($this, $this->championship, 'participants', "Name,Licence\nBob,LIC-7\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'licence' => 1])->assertOk()->assertJsonPath('errors', 1);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertUnprocessable();
    expect($this->championship->participants()->count())->toBe(0)->and(Driver::query()->count())->toBe(1);
});

test('guided import accepts one thousand records and rejects a larger batch without partial writes', function () {
    $rows = "Name,Number,Licence\n";
    for ($i = 1; $i <= 1000; $i++) {
        $rows .= "Driver {$i},{$i},LIC-{$i}\n";
    }
    $token = championshipUpload($this, $this->championship, 'participants', $rows."Driver 1001,1001,LIC-1001\n");
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1, 'licence' => 2])->assertUnprocessable();
    expect(Driver::query()->count())->toBe(0);
    $token = championshipUpload($this, $this->championship, 'participants', $rows);
    championshipPreview($this, $this->championship, $token, ['name' => 0, 'number' => 1, 'licence' => 2])->assertOk()->assertJsonPath('new', 1000)->assertJsonPath('errors', 0);
    $this->postJson(route('championships.imports.confirm', $this->championship), ['token' => $token])->assertOk()->assertJsonPath('imported', 1000);
    expect($this->championship->participants()->count())->toBe(1000)->and(Driver::query()->count())->toBe(1000);
});

test('legacy version one backups remain readable without championship tables', function () {
    Driver::create(['display_name' => 'Legacy driver']);
    $service = app(DataHubService::class);
    $backup = $service->export($this->user->workspaces()->firstOrFail());
    $backup['version'] = 1;
    foreach (['championships', 'championship_rounds', 'championship_participants', 'championship_results'] as $table) {
        unset($backup['tables'][$table]);
    }
    $target = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $target->workspaces()->firstOrFail();
    $this->actingAs($target)->withSession(['pitmetric.current_workspace_id' => $workspace->id]);
    $service->import($workspace, $target, $backup);
    expect(Driver::query()->first()->display_name)->toBe('Legacy driver')->and(Championship::query()->count())->toBe(0);
});
