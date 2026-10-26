<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ButtiDeskBridge
{
    public static function configured(): bool
    {
        $url = (string) config('buttidesk.bridge.url');
        $parts = parse_url($url);

        return config('buttidesk.bridge.enabled')
            && strlen((string) config('buttidesk.bridge.secret')) >= 32
            && is_array($parts) && isset($parts['host'], $parts['scheme'])
            && ($parts['scheme'] === 'https' || (app()->environment(['local', 'testing']) && $parts['scheme'] === 'http'))
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && ! isset($parts['query']) && ! isset($parts['fragment'])
            && in_array($parts['path'] ?? '', ['', '/'], true);
    }

    public static function eligible(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasButtiDeskAccess();
    }

    public static function deskUrl(string $path): string
    {
        abort_unless(self::configured(), 503, __('users.desk_not_configured'));

        return rtrim((string) config('buttidesk.bridge.url'), '/').$path;
    }

    public function authorize(Request $request, string $challenge): string
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail() && self::eligible($user), 403);
        $context = app(WorkspaceContext::class);
        abort_unless($context->isTeamReady(), 503, __('users.desk_not_ready'));
        $workspace = $context->personal($user);
        $this->snapshot($user, (int) $workspace->getKey());
        $binding = $request->session()->get('buttidesk.source_binding');
        if (! is_string($binding)) {
            $binding = bin2hex(random_bytes(32));
            $request->session()->put('buttidesk.source_binding', $binding);
        }
        Cache::put($this->bindingKey($binding), (int) $user->getKey(), $this->lifetime());
        $code = bin2hex(random_bytes(32));
        Cache::put('buttidesk:code:'.hash('sha256', $code), [
            'user_id' => (int) $user->getKey(), 'workspace_id' => (int) $workspace->getKey(),
            'challenge' => $challenge, 'binding' => $binding,
        ], 60);

        return $code;
    }

    /** @return array<string, mixed> */
    public function exchange(string $code, string $verifier): array
    {
        $key = 'buttidesk:code:'.hash('sha256', $code);
        $result = Cache::lock($key.':lock', 5)->get(function () use ($key, $verifier): array {
            $grant = Cache::get($key);
            abort_unless(is_array($grant) && is_int($grant['user_id'] ?? null) && is_int($grant['workspace_id'] ?? null) && is_string($grant['binding'] ?? null) && is_string($grant['challenge'] ?? null), 401);
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            abort_unless(hash_equals((string) $grant['challenge'], $challenge), 401);
            Cache::forget($key);
            $user = User::query()->whereKey($grant['user_id'])->firstOrFail();
            $context = $this->snapshot($user, (int) $grant['workspace_id']);
            abort_unless(Cache::get($this->bindingKey((string) $grant['binding'])) === (int) $user->getKey(), 401);
            $token = bin2hex(random_bytes(32));
            $expires = now()->getTimestamp() + $this->lifetime();
            Cache::put('buttidesk:session:'.hash('sha256', $token), [
                'user_id' => (int) $user->getKey(), 'workspace_id' => (int) $grant['workspace_id'],
                'binding' => (string) $grant['binding'], 'expires_at' => $expires,
            ], $this->lifetime());

            return ['protocol_version' => 1, 'session_token' => $token, 'expires_at' => $expires, 'context' => $context];
        });
        abort_unless(is_array($result), 401);

        return $result;
    }

    /** @return array<string, mixed> */
    public function introspect(string $token): array
    {
        $grant = Cache::get('buttidesk:session:'.hash('sha256', $token));
        abort_unless(is_array($grant) && is_int($grant['user_id'] ?? null) && is_int($grant['workspace_id'] ?? null) && is_string($grant['binding'] ?? null) && is_int($grant['expires_at'] ?? null) && $grant['expires_at'] > now()->getTimestamp(), 401);
        $user = User::query()->whereKey($grant['user_id'])->firstOrFail();
        abort_unless(Cache::get($this->bindingKey((string) $grant['binding'])) === (int) $user->getKey(), 401);

        return ['protocol_version' => 1, 'expires_at' => (int) $grant['expires_at'], 'context' => $this->snapshot($user, (int) $grant['workspace_id'])];
    }

    public function revoke(string $token): void
    {
        Cache::forget('buttidesk:session:'.hash('sha256', $token));
    }

    public function revokeSourceSession(Request $request): void
    {
        if ($request->hasSession() && is_string($binding = $request->session()->get('buttidesk.source_binding'))) {
            Cache::forget($this->bindingKey($binding));
        }
    }

    /** @return array{user: array{id: int, name: string}, workspace: array{id: int, name: string}, role: string} */
    private function snapshot(User $user, int $workspaceId): array
    {
        abort_unless($user->hasVerifiedEmail() && self::eligible($user), 403);
        $membership = DB::table('workspace_user')->join('workspaces', 'workspaces.id', '=', 'workspace_user.workspace_id')
            ->where('workspace_user.user_id', $user->getKey())->where('workspace_user.workspace_id', $workspaceId)
            ->where('workspace_user.status', 'active')->first(['workspace_user.role', 'workspaces.name']);
        abort_unless($membership && in_array($membership->role, WorkspaceMembership::ROLES, true), 403);

        return ['user' => ['id' => (int) $user->getKey(), 'name' => $user->name],
            'workspace' => ['id' => $workspaceId, 'name' => (string) $membership->name], 'role' => (string) $membership->role];
    }

    private function lifetime(): int
    {
        return max(60, min((int) config('session.lifetime', 120) * 60, 7200));
    }

    private function bindingKey(string $binding): string
    {
        return 'buttidesk:source:'.hash('sha256', $binding);
    }
}
