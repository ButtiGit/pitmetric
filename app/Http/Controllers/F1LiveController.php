<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The browser never sees the Python service URL or its shared secret.
 * Route-level can:manage-updates is mandatory on all endpoints.
 */
final class F1LiveController extends Controller
{
    public function index(): View
    {
        return view('studio.f1-live');
    }

    public function live(Request $request): JsonResponse
    {
        $values = $request->validate([
            'drivers' => ['nullable', 'string', 'max:100', 'regex:/^(\d{1,3})(,\d{1,3}){0,2}$/'],
        ]);

        return $this->proxy('live', $values, 8);
    }

    public function events(Request $request): JsonResponse
    {
        $values = $request->validate([
            'year' => ['required', 'integer', 'between:2018,2100'],
        ]);

        return $this->proxy('events', $values, 20);
    }

    public function history(Request $request): JsonResponse
    {
        $values = $request->validate([
            'year' => ['required', 'integer', 'between:2018,2100'],
            'round' => ['required', 'integer', 'between:1,30'],
            'session' => ['required', 'in:FP1,FP2,FP3,Q,SQ,S,R'],
            'drivers' => ['required', 'string', 'max:30', 'regex:/^([A-Z0-9]{1,3})(,[A-Z0-9]{1,3}){0,2}$/i'],
            'lap' => ['nullable', 'regex:/^(fastest|latest|[1-9][0-9]?)$/'],
        ]);

        return $this->proxy('history', $values, 55);
    }

    /** @param array<string, mixed> $query */
    private function proxy(string $endpoint, array $query, int $timeout): JsonResponse
    {
        $baseUrl = rtrim((string) config('services.fastf1.url', ''), '/');
        $token = (string) config('services.fastf1.token', '');

        if ($baseUrl === '' || $token === ''
            || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)
            || ! parse_url($baseUrl, PHP_URL_HOST)) {
            return $this->unavailable('FastF1 non configurato in Coolify.');
        }

        try {
            $upstream = Http::acceptJson()
                ->withHeaders(['X-PitMetric-Token' => $token])
                ->withoutRedirecting()
                ->connectTimeout(4)
                ->timeout($timeout)
                ->get($baseUrl.'/api/'.$endpoint, $query);

            if (! $upstream->successful()) {
                return $this->unavailable('Il servizio FastF1 non è disponibile (HTTP '.$upstream->status().').');
            }

            $data = $upstream->json();

            if (! is_array($data)) {
                return $this->unavailable('Risposta FastF1 non valida.');
            }

            return response()->json($data)->header('Cache-Control', 'no-store, private');
        } catch (ConnectionException $exception) {
            report($exception);

            return $this->unavailable('Connessione al servizio FastF1 non disponibile.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailable('Il servizio FastF1 ha restituito un errore.');
        }
    }

    private function unavailable(string $message): JsonResponse
    {
        return response()->json([
            'live' => false,
            'available' => false,
            'message' => $message,
        ], 503)->header('Cache-Control', 'no-store, private');
    }
}
