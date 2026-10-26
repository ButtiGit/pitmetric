<?php

namespace App\Http\Middleware;

use App\Services\ButtiDeskBridge;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateButtiDeskBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(ButtiDeskBridge::configured(), 404);
        $timestamp = $request->header('X-ButtiDesk-Timestamp', '');
        $nonce = $request->header('X-ButtiDesk-Nonce', '');
        $signature = $request->header('X-ButtiDesk-Signature', '');
        abort_unless(preg_match('/^\d{10}$/D', $timestamp) && abs(time() - (int) $timestamp) <= 30
            && preg_match('/^[a-f0-9]{32}$/D', $nonce) && preg_match('/^[a-f0-9]{64}$/D', $signature), 401);
        $canonical = "v1\n{$timestamp}\n{$nonce}\nPOST\n".$request->getPathInfo()."\n".hash('sha256', $request->getContent());
        abort_unless(hash_equals(hash_hmac('sha256', $canonical, (string) config('buttidesk.bridge.secret')), $signature), 401);
        abort_unless(Cache::add('buttidesk:nonce:'.hash('sha256', $nonce), true, 90), 401);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
