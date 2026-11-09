<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class StudioSiteDiagnostics
{
    /** @return list<array{key: string, name: string, status: string, detail: string}> */
    public function run(): array
    {
        return [
            $this->probe('database', 'Database', function (): array {
                DB::select('SELECT 1');

                return ['ok', 'Connessione al database riuscita.'];
            }),
            $this->tableCheck('accounts', 'Account e team', ['users', 'workspaces', 'workspace_user']),
            $this->tableCheck('operations', 'Garage e attività', ['vehicles', 'components', 'events', 'championships']),
            $this->tableCheck('publishing', 'Pubblicazioni', ['updates']),
            $this->tableCheck('mailbox', 'Archivio email Studio', ['studio_mail_messages', 'studio_mail_reads']),
            $this->tableCheck('calendar', 'Calendario', ['calendar_entries']),
            $this->routeCheck('public_routes', 'Pagine pubbliche', ['localized.home', 'localized.about', 'localized.app', 'localized.updates.index', 'localized.updates.show']),
            $this->routeCheck('management_routes', 'Funzioni gestionali', ['dashboard', 'garage.index', 'components.index', 'events.index', 'sessions.index', 'maintenance.index', 'expenses.index', 'calendar.index', 'team.index', 'studio.users.index', 'studio.updates.index', 'studio.mail.index', 'studio.outreach.index']),
            $this->probe('downloads', 'Pulsanti download', function (): array {
                $ready = Route::has('app.download.android') && Route::has('app.open.pwa')
                    && str_starts_with((string) config('pitmetric.android_apk_url'), 'https://github.com/');

                return $ready ? ['ok', 'Link Android e PWA configurati.'] : ['error', 'Controlla i percorsi di download dell’app.'];
            }),
            $this->probe('pwa', 'Build PWA', function (): array {
                $ready = is_file(public_path('mobile/index.html')) && is_file(public_path('mobile/sw.js'));

                return $ready ? ['ok', 'Pagina e service worker PWA presenti.'] : ['warning', 'Build PWA non disponibile su questo server.'];
            }),
            $this->probe('mail', 'Invio e ricezione email', function (): array {
                $ready = filled(config('services.resend.studio_key')) && filled(config('services.resend.webhook_secret'));

                return $ready ? ['ok', 'Credenziali Resend e firma webhook configurate (invio non eseguito).'] : ['warning', 'Resend o la firma del webhook non sono configurati completamente.'];
            }),
            $this->probe('storage', 'Cartelle applicative', function (): array {
                $ready = is_writable(storage_path('logs')) && is_writable(storage_path('framework/cache'));

                return $ready ? ['ok', 'Directory log e cache scrivibili.'] : ['error', 'Verifica i permessi delle directory log e cache.'];
            }),
            $this->probe('ci', 'Test completi GitHub CI', fn (): array => $this->ciStatus()),
        ];
    }

    /**
     * @param  list<string>  $tables
     * @return array{key: string, name: string, status: string, detail: string}
     */
    private function tableCheck(string $key, string $name, array $tables): array
    {
        return $this->probe($key, $name, function () use ($tables): array {
            $missing = array_values(array_filter($tables, fn (string $table): bool => Schema::hasTable($table) === false));

            return $missing === []
                ? ['ok', count($tables).' tabelle presenti.']
                : ['error', 'Tabelle mancanti: '.implode(', ', $missing).'.'];
        });
    }

    /**
     * @param  list<string>  $routes
     * @return array{key: string, name: string, status: string, detail: string}
     */
    private function routeCheck(string $key, string $name, array $routes): array
    {
        return $this->probe($key, $name, function () use ($routes): array {
            $missing = array_values(array_filter($routes, fn (string $route): bool => Route::has($route) === false));

            return $missing === []
                ? ['ok', count($routes).' percorsi applicativi registrati.']
                : ['error', 'Percorsi mancanti: '.implode(', ', $missing).'.'];
        });
    }

    /**
     * @param  callable(): array{string, string}  $resolve
     * @return array{key: string, name: string, status: string, detail: string}
     */
    private function probe(string $key, string $name, callable $resolve): array
    {
        try {
            [$status, $detail] = $resolve();

            return ['key' => $key, 'name' => $name, 'status' => $status, 'detail' => $detail];
        } catch (Throwable $exception) {
            report($exception);

            return ['key' => $key, 'name' => $name, 'status' => 'error', 'detail' => 'Verifica non riuscita. Consulta i log Laravel.'];
        }
    }

    /** @return array{string, string} */
    private function ciStatus(): array
    {
        $response = Http::acceptJson()->timeout(5)->get(
            'https://api.github.com/repos/ButtiGit/pitmetric/actions/workflows/tests.yml/runs',
            ['branch' => 'main', 'per_page' => 1],
        );

        if ($response->successful() === false) {
            return ['warning', 'GitHub Actions non risponde: consulta la CI direttamente su GitHub.'];
        }

        $run = $response->json('workflow_runs.0');
        if (is_array($run) === false) {
            return ['warning', 'Nessuna esecuzione della CI disponibile.'];
        }

        $number = (int) ($run['run_number'] ?? 0);
        if (($run['status'] ?? null) !== 'completed') {
            return ['warning', 'Esecuzione CI #'.$number.' ancora in corso.'];
        }

        return ($run['conclusion'] ?? null) === 'success'
            ? ['ok', 'Suite automatica CI #'.$number.' completata con successo.']
            : ['error', 'Suite automatica CI #'.$number.' non riuscita: controlla GitHub Actions.'];
    }
}
