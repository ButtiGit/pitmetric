<?php

namespace App\Http\Controllers;

use App\Services\SysPilot\ChecklistGenerator;
use App\Services\SysPilot\ChecklistEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use stdClass;

class SysPilotController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $base = DB::table('syspilot_interventions')->where('user_id', $userId);

        return $this->privateJson([
            'user' => ['name' => $request->user()->name],
            'model' => config('syspilot.openai_model'),
            'configured' => filled(config('syspilot.openai_api_key')),
            'csrf_token' => csrf_token(),
            'counts' => [
                'total' => (clone $base)->count(),
                'open' => (clone $base)->where('status', 'open')->count(),
                'closed' => (clone $base)->where('status', 'closed')->count(),
            ],
            'interventions' => (clone $base)->orderByDesc('id')->limit(40)->get(),
        ]);
    }

    public function show(Request $request, int $intervention): JsonResponse
    {
        $record = $this->owned($request, $intervention);

        return $this->privateJson([
            'intervention' => $record,
            'steps' => DB::table('syspilot_steps')
                ->where('intervention_id', $record->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(function (stdClass $step): stdClass {
                    $step->revision = $this->stepRevision($step);

                    return $step;
                }),
            'events' => DB::table('syspilot_events')
                ->where('intervention_id', $record->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(Request $request, ChecklistGenerator $generator): JsonResponse
    {
        $input = $request->validate([
            'request' => ['required', 'string', 'min:15', 'max:2500'],
            'client' => ['nullable', 'string', 'max:120'],
            'asset' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $plan = $generator->generate(trim($input['request']));
        } catch (RuntimeException $e) {
            return $this->privateJson(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            report($e);

            return $this->privateJson(['message' => 'Connessione all’IA non disponibile. Riprova tra poco.'], 503);
        }

        $steps = [];
        foreach (array_slice($plan['phases'], 0, 5) as $phase) {
            if (! is_array($phase)) {
                continue;
            }

            $name = Str::limit(trim((string) ($phase['name'] ?? 'Attività')), 120, '');
            foreach (array_slice(is_array($phase['steps'] ?? null) ? $phase['steps'] : [], 0, 14 - count($steps)) as $step) {
                if (! is_array($step) || trim((string) ($step['title'] ?? '')) === '') {
                    continue;
                }

                $steps[] = [
                    'phase' => $name !== '' ? $name : 'Attività',
                    'title' => Str::limit(trim((string) $step['title']), 250, ''),
                    'detail' => Str::limit(trim((string) ($step['detail'] ?? '')), 750, ''),
                    'state' => 'todo',
                    'note' => '',
                    'position' => count($steps) + 1,
                ];
            }
        }

        if ($steps === []) {
            return $this->privateJson(['message' => 'L’IA ha restituito una checklist vuota. Riprova.'], 422);
        }

        $id = DB::transaction(function () use ($request, $input, $plan, $steps): int {
            $now = now();
            $id = DB::table('syspilot_interventions')->insertGetId([
                'user_id' => $request->user()->id,
                'title' => Str::limit(trim((string) $plan['title']), 150, '') ?: 'Intervento IT',
                'request' => trim($input['request']),
                'client' => trim($input['client'] ?? ''),
                'asset' => trim($input['asset'] ?? ''),
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($steps as $step) {
                DB::table('syspilot_steps')->insert($step + [
                    'intervention_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->event($id, (int) $request->user()->id, 'Checklist proposta dall’IA. Nessuna attività è stata eseguita automaticamente.');

            return $id;
        });

        return $this->privateJson(['id' => $id], 201);
    }

    public function updateStep(Request $request, int $intervention, int $step): JsonResponse
    {
        $input = $request->validate([
            'state' => ['required', 'in:todo,done,blocked,skipped'],
            'note' => ['nullable', 'string', 'max:3000'],
            'revision' => ['sometimes', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
        ]);

        $note = trim($input['note'] ?? '');
        if ($input['state'] === 'skipped' && $note === '') {
            throw ValidationException::withMessages(['note' => 'Motiva il passaggio saltato.']);
        }

        $newRevision = DB::transaction(function () use ($request, $intervention, $step, $input, $note): string {
            $record = $this->owned($request, $intervention);
            abort_if($record->status !== 'open', 409, 'L’intervento è già chiuso.');

            $previous = DB::table('syspilot_steps')
                ->where('intervention_id', $record->id)
                ->where('id', $step)
                ->lockForUpdate()->first();
            abort_unless($previous, 404);
            if (array_key_exists('revision', $input)) {
                abort_if(! hash_equals($this->stepRevision($previous), $input['revision']), 409,
                    'Il passaggio è stato modificato altrove. Ricarica la checklist prima di salvare.');
            }

            DB::table('syspilot_steps')
                ->where('id', $step)
                ->update([
                    'state' => $input['state'],
                    'note' => $note,
                    'updated_at' => now(),
                ]);

            DB::table('syspilot_interventions')
                ->where('id', $record->id)
                ->update(['updated_at' => now()]);

            if ($previous->state !== $input['state'] || ($previous->note ?? '') !== $note) {
                // Autosave may produce many note revisions. Audit the action,
                // not the whole note: the step always holds the current text.
                $description = $previous->state !== $input['state']
                    ? 'Stato di «'.$previous->title.'» aggiornato a '.$input['state'].'.'
                    : 'Note aggiornate su «'.$previous->title.'».';

                $this->event($record->id, (int) $request->user()->id, $description);
            }

            $updated = clone $previous;
            $updated->state = $input['state'];
            $updated->note = $note;

            return $this->stepRevision($updated);
        });

        return $this->privateJson(['ok' => true, 'revision' => $newRevision]);
    }

    public function proposeRevision(Request $request, int $intervention, ChecklistEditor $editor): JsonResponse
    {
        $input = $request->validate(['instruction' => ['required', 'string', 'min:8', 'max:750']]);
        $record = $this->owned($request, $intervention);
        abort_if($record->status !== 'open', 409, 'Non puoi modificare un intervento chiuso.');

        $steps = DB::table('syspilot_steps')->where('intervention_id', $record->id)
            ->orderBy('position')->orderBy('id')->get()->all();

        try {
            $result = $editor->propose((int) $request->user()->id, $record, $steps, trim($input['instruction']));
        } catch (RuntimeException $e) {
            return $this->privateJson(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            report($e);

            return $this->privateJson(['message' => 'Connessione all’IA non disponibile. Riprova.'], 503);
        }

        return $this->privateJson($result);
    }

    public function applyRevision(Request $request, int $intervention, ChecklistEditor $editor): JsonResponse
    {
        $input = $request->validate(['token' => ['required', 'string', 'max:40000']]);

        return $this->privateJson($editor->apply((int) $request->user()->id, $intervention, $input['token']));
    }

    public function close(Request $request, int $intervention): JsonResponse
    {
        DB::transaction(function () use ($request, $intervention): void {
            $record = $this->owned($request, $intervention);
            abort_if($record->status !== 'open', 409, 'Intervento già chiuso.');

            $pending = DB::table('syspilot_steps')
                ->where('intervention_id', $record->id)
                ->whereIn('state', ['todo', 'blocked'])
                ->exists();

            abort_if($pending, 422, 'Completa o salta con motivazione tutte le attività prima di chiudere.');

            DB::table('syspilot_interventions')
                ->where('id', $record->id)
                ->update(['status' => 'closed', 'updated_at' => now()]);

            $this->event($record->id, (int) $request->user()->id, 'Intervento chiuso dal tecnico.');
        });

        return $this->privateJson(['ok' => true]);
    }

    private function stepRevision(stdClass $step): string
    {
        return hash('sha256', json_encode([
            $step->state, $step->note, $step->phase, $step->title, $step->detail,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function owned(Request $request, int $id): stdClass
    {
        $record = DB::table('syspilot_interventions')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($record, 404);

        return $record;
    }

    private function event(int $interventionId, int $userId, string $message): void
    {
        DB::table('syspilot_events')->insert([
            'intervention_id' => $interventionId,
            'user_id' => $userId,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    private function privateJson(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }
}
