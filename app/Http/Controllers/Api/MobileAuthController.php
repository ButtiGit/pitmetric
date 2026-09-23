<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileTokenService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request, MobileTokenService $tokens): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', mb_strtolower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'code' => 'invalid_credentials',
            ], 422);
        }

        return $this->authenticated($user, $tokens->issue($user, $validated['device_name'] ?? null));
    }

    public function register(Request $request, CreateNewUser $createNewUser, MobileTokenService $tokens): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $createNewUser->create([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
            'password' => $input['password'],
            'password_confirmation' => $input['password_confirmation'],
            'newsletter_opt_in' => false,
            'newsletter_locale' => 'it',
        ]);

        event(new Registered($user));

        return $this->authenticated($user, $tokens->issue($user, $input['device_name'] ?? null), 201);
    }

    public function logout(Request $request, MobileTokenService $tokens): JsonResponse
    {
        $plainTextToken = $request->bearerToken();
        if (is_string($plainTextToken) && $plainTextToken !== '') {
            $tokens->revoke($plainTextToken);
        }

        return response()->json(['ok' => true]);
    }

    private function authenticated(User $user, string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'cloud_enabled' => $user->hasDatabaseAccess() && $user->hasVerifiedEmail(),
                'cloud_reason' => $user->hasVerifiedEmail()
                    ? ($user->hasDatabaseAccess() ? null : 'database_access_required')
                    : 'email_verification_required',
            ],
        ], $status);
    }
}
