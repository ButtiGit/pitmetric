<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class StudioErpTechnical
{
    private const MODULES = [
        ['key' => 'teams', 'name' => 'Team e accessi', 'description' => 'Workspace, membri e autorizzazioni', 'tables' => ['workspaces', 'workspace_user', 'users'], 'primary' => 'workspaces', 'route' => 'team.index'],
        ['key' => 'garage', 'name' => 'Garage e componenti', 'description' => 'Veicoli, ricambi e installazioni', 'tables' => ['vehicles', 'components', 'component_installations'], 'primary' => 'vehicles', 'route' => 'garage.index'],
        ['key' => 'configurations', 'name' => 'Configurazioni e setup', 'description' => 'Versioni, regolazioni e snapshot', 'tables' => ['configurations', 'configuration_versions', 'technical_setups', 'setup_snapshots'], 'primary' => 'technical_setups', 'route' => 'setups.index'],
        ['key' => 'sessions', 'name' => 'Eventi e sessioni', 'description' => 'Calendario gare, turni e utilizzo', 'tables' => ['events', 'event_entries', 'track_sessions', 'usage_batches'], 'primary' => 'track_sessions', 'route' => 'sessions.index'],
        ['key' => 'maintenance', 'name' => 'Manutenzione', 'description' => 'Piani, interventi e ordini di lavoro', 'tables' => ['maintenance_schedules', 'maintenance_records', 'maintenance_work_orders'], 'primary' => 'maintenance_work_orders', 'route' => 'maintenance.index'],
        ['key' => 'finance', 'name' => 'Costi e documenti', 'description' => 'Spese registrate e allegati', 'tables' => ['expenses', 'documents'], 'primary' => 'expenses', 'route' => 'expenses.index'],
    ];

    /** @return list<array{key: string, name: string, description: string, status: string, count: ?int, missing: list<string>, route: string}> */
    public function modules(): array
    {
        return array_map(function (array $module): array {
            try {
                $missing = array_values(array_filter($module['tables'], fn (string $table): bool => ! Schema::hasTable($table)));
                $routeReady = Route::has($module['route']);

                return [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'status' => $missing === [] && $routeReady ? 'ok' : 'warning',
                    'count' => Schema::hasTable($module['primary']) ? (int) DB::table($module['primary'])->count() : null,
                    'missing' => $missing,
                    'route' => $module['route'],
                ];
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'status' => 'error',
                    'count' => null,
                    'missing' => [],
                    'route' => $module['route'],
                ];
            }
        }, self::MODULES);
    }

    /** @return array{workspaces: ?int, vehicles: ?int, sessions: ?int, open_orders: ?int} */
    public function totals(): array
    {
        return [
            'workspaces' => $this->count('workspaces'),
            'vehicles' => $this->count('vehicles'),
            'sessions' => $this->count('track_sessions'),
            'open_orders' => $this->count('maintenance_work_orders', ['todo', 'in_progress', 'blocked']),
        ];
    }

    /** @return list<array{key: string, name: string, status: string, detail: string}> */
    public function run(): array
    {
        $results = [
            $this->probe('database', 'Connessione al database', function (): array {
                DB::select('SELECT 1');

                return ['ok', 'Il database ERP risponde correttamente.'];
            }),
            $this->probe('migrations', 'Migrazioni del database', fn (): array => $this->migrationStatus()),
        ];

        foreach ($this->modules() as $module) {
            $results[] = $this->probe('module_'.$module['key'], $module['name'], static function () use ($module): array {
                if ($module['status'] === 'error') {
                    return ['error', 'Impossibile leggere il modulo. Verifica i log Laravel.'];
                }

                if ($module['missing'] !== []) {
                    return ['error', 'Tabelle mancanti: '.implode(', ', $module['missing']).'.'];
                }

                if (! Route::has($module['route'])) {
                    return ['error', 'Il percorso del modulo non è registrato.'];
                }

                return ['ok', 'Tabelle e percorso disponibili; '.($module['count'] ?? 0).' record principali.'];
            });
        }

        $results[] = $this->probe('tenant_integrity', 'Coerenza tra workspace', fn (): array => $this->workspaceIntegrity());
        $results[] = $this->probe('queue', 'Coda di elaborazione', fn (): array => $this->queueStatus());
        $results[] = $this->probe('storage', 'Scrittura di log e cache', function (): array {
            return is_writable(storage_path('logs')) && is_writable(storage_path('framework/cache'))
                ? ['ok', 'Le directory di log e cache sono scrivibili.']
                : ['error', 'Verifica i permessi di log e cache sul server.'];
        });

        return $results;
    }

    /** @param list<string>|null $statuses */
    private function count(string $table, ?array $statuses = null): ?int
    {
        try {
            if (! Schema::hasTable($table)) {
                return null;
            }

            $query = DB::table($table);

            return (int) ($statuses === null ? $query->count() : $query->whereIn('status', $statuses)->count());
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /** @return array{string, string} */
    private function migrationStatus(): array
    {
        if (! Schema::hasTable('migrations')) {
            return ['error', 'La tabella di registro delle migrazioni non esiste.'];
        }

        $applied = DB::table('migrations')->pluck('migration')->all();
        $pending = collect(File::files(database_path('migrations')))
            ->filter(fn (\SplFileInfo $file): bool => $file->getExtension() === 'php')
            ->map(fn (\SplFileInfo $file): string => $file->getBasename('.php'))
            ->diff($applied)
            ->count();

        return $pending === 0
            ? ['ok', 'Tutte le migrazioni applicative risultano eseguite.']
            : ['warning', $pending.' migrazioni ancora da applicare: esegui php artisan migrate --force --no-interaction.'];
    }

    /** @return array{string, string} */
    private function workspaceIntegrity(): array
    {
        $relations = [
            ['track_sessions', 'vehicles', 'vehicle_id'],
            ['component_installations', 'vehicles', 'vehicle_id'],
            ['component_installations', 'components', 'component_id'],
            ['maintenance_records', 'components', 'component_id'],
            ['technical_setups', 'vehicles', 'vehicle_id'],
            ['event_entries', 'events', 'event_id'],
            ['setup_snapshots', 'track_sessions', 'session_id'],
        ];

        $inconsistent = 0;
        foreach ($relations as [$child, $parent, $column]) {
            if (! Schema::hasTable($child) || ! Schema::hasTable($parent)) {
                return ['warning', 'Alcune tabelle ERP mancano: verifica la struttura prima del controllo di integrità.'];
            }

            $inconsistent += DB::table($child)
                ->join($parent, $child.'.'.$column, '=', $parent.'.id')
                ->whereColumn($child.'.workspace_id', '!=', $parent.'.workspace_id')
                ->count();
        }

        return $inconsistent === 0
            ? ['ok', 'Nessun riferimento tra workspace diversi nei 7 collegamenti verificati.']
            : ['error', $inconsistent.' riferimenti tra workspace diversi trovati. Intervento manuale necessario.'];
    }

    /** @return array{string, string} */
    private function queueStatus(): array
    {
        $driver = (string) config('queue.default', 'sync');
        if ($driver !== 'database') {
            return $driver === 'sync'
                ? ['ok', 'Coda sincrona: nessun worker separato richiesto.']
                : ['warning', 'Driver '.$driver.' configurato; la disponibilità del worker non è verificabile con questo controllo.'];
        }

        if (! Schema::hasTable('jobs') || ! Schema::hasTable('failed_jobs')) {
            return ['error', 'Tabelle per la coda database mancanti.'];
        }

        $pending = (int) DB::table('jobs')->count();
        $failed = (int) DB::table('failed_jobs')->count();

        return $failed > 0 || $pending > 100
            ? ['warning', $pending.' job in attesa e '.$failed.' job falliti: controlla il worker e i log.']
            : ['ok', $pending.' job in attesa e nessun job fallito.'];
    }

    /**
     * @param  callable(): array{string, string}  $resolve
     * @return array{key: string, name: string, status: string, detail: string}
     */
    private function probe(string $key, string $name, callable $resolve): array
    {
        try {
            [$status, $detail] = $resolve();

            return compact('key', 'name', 'status', 'detail');
        } catch (Throwable $exception) {
            report($exception);

            return ['key' => $key, 'name' => $name, 'status' => 'error', 'detail' => 'Verifica non riuscita. Consulta i log Laravel.'];
        }
    }
}
