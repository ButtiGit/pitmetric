<?php

namespace App\Http\Controllers;

use App\Services\ButtiDeskBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ButtiDeskBridgeController extends Controller
{
    public function launch(): RedirectResponse
    {
        Gate::authorize('access-buttidesk');

        return redirect()->away(ButtiDeskBridge::deskUrl('/auth/pitmetric/start'));
    }

    public function authorizeConnection(Request $request, ButtiDeskBridge $bridge): RedirectResponse
    {
        Gate::authorize('access-buttidesk');
        ButtiDeskBridge::deskUrl('/auth/pitmetric/callback');
        $data = $request->validate([
            'state' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'code_challenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/D'],
        ]);
        $code = $bridge->authorize($request, $data['code_challenge']);

        return redirect()->away(ButtiDeskBridge::deskUrl('/auth/pitmetric/callback').'?'.http_build_query(['state' => $data['state'], 'code' => $code]))
            ->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer']);
    }

    public function token(Request $request, ButtiDeskBridge $bridge): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'code_verifier' => ['required', 'string', 'regex:/^[A-Za-z0-9._~-]{43,128}$/D'],
        ]);

        return response()->json($bridge->exchange($data['code'], $data['code_verifier']));
    }

    public function context(Request $request, ButtiDeskBridge $bridge): JsonResponse
    {
        $data = $request->validate(['session_token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']]);

        return response()->json($bridge->introspect($data['session_token']));
    }

    public function revoke(Request $request, ButtiDeskBridge $bridge): JsonResponse
    {
        $data = $request->validate(['session_token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']]);
        $bridge->revoke($data['session_token']);

        return response()->json(['revoked' => true]);
    }
}
