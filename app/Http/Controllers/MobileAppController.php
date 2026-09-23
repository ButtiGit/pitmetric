<?php

namespace App\Http\Controllers;

use App\Models\GalleryAsset;
use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class MobileAppController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user instanceof User || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        return $this->issueToken($user, $validated['device_name'] ?? null);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return $this->issueToken($user, $validated['device_name'] ?? null, 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['session' => $this->sessionPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->attributes->get('mobile_access_token');

        if ($accessToken instanceof MobileAccessToken) {
            $accessToken->forceFill(['revoked_at' => now()])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function galleryIndex(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasDatabaseAccess()) {
            return response()->json(['message' => 'Database access is not enabled. Keep using local storage until it is enabled.'], 403);
        }

        $items = GalleryAsset::query()
            ->where('user_id', $user->getKey())
            ->latest('captured_at')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (GalleryAsset $asset): array => $this->galleryPayload($asset));

        return response()->json(['data' => $items]);
    }

    public function galleryStore(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasDatabaseAccess()) {
            return response()->json(['message' => 'Database access is not enabled. Data remains on this device.'], 403);
        }

        $validated = $request->validate([
            'client_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'captured_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:15360'],
        ]);

        $workspaceId = $user->workspaces()
            ->wherePivot('status', 'active')
            ->orderBy('workspaces.id')
            ->value('workspaces.id');

        $asset = GalleryAsset::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'client_id' => $validated['client_id'],
        ]);

        $asset->fill([
            'workspace_id' => is_numeric($workspaceId) ? (int) $workspaceId : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'captured_at' => $validated['captured_at'] ?? now(),
        ]);

        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            $storedPath = $photo->store('gallery/'.$user->getKey(), 'public');

            if ($storedPath === false) {
                throw new RuntimeException('Unable to store gallery photo.');
            }

            if ($asset->path !== null) {
                Storage::disk('public')->delete($asset->path);
            }

            $asset->path = $storedPath;
        }

        $asset->save();

        return response()->json($this->galleryPayload($asset), $asset->wasRecentlyCreated ? 201 : 200);
    }

    private function issueToken(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $plainToken = Str::random(80);

        MobileAccessToken::query()->create([
            'user_id' => $user->getKey(),
            'device_name' => $deviceName,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'token' => $plainToken,
            'session' => $this->sessionPayload($user),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function sessionPayload(User $user): array
    {
        $workspace = $user->workspaces()
            ->wherePivot('status', 'active')
            ->orderBy('workspaces.id')
            ->first();

        return [
            'authenticated' => true,
            'cloudEnabled' => $user->hasDatabaseAccess(),
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
            ],
            'workspace' => $workspace === null ? null : [
                'id' => $workspace->getKey(),
                'name' => $workspace->name,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function galleryPayload(GalleryAsset $asset): array
    {
        return [
            'id' => $asset->getKey(),
            'client_id' => $asset->client_id,
            'title' => $asset->title,
            'description' => $asset->description,
            'captured_at' => $asset->captured_at?->toIso8601String(),
            'url' => $asset->path === null ? null : Storage::disk('public')->url($asset->path),
        ];
    }
}
