<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileGalleryController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): JsonResponse
    {
        $user = $this->user($request);
        if (! $user->hasDatabaseAccess() || ! $user->hasVerifiedEmail()) {
            return response()->json(['photos' => [], 'cloud_enabled' => false]);
        }

        $workspaceId = $workspaceContext->currentId($user);
        abort_if($workspaceId === null, 409, 'No active workspace is available.');

        $photos = GalleryPhoto::query()
            ->where('workspace_id', $workspaceId)
            ->latest('taken_at')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (GalleryPhoto $photo): array => $this->serialize($photo))
            ->values();

        return response()->json(['photos' => $photos, 'cloud_enabled' => true]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): JsonResponse
    {
        $user = $this->user($request);
        if (! $user->hasDatabaseAccess() || ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Cloud database access is not active. Keep this photo on the device until sync is available.',
                'code' => $user->hasVerifiedEmail() ? 'database_access_required' : 'email_verification_required',
            ], 403);
        }

        if (! $workspaceContext->canWrite($user)) {
            abort(403, 'Your team role cannot upload gallery photos.');
        }

        $workspaceId = $workspaceContext->currentId($user);
        abort_if($workspaceId === null, 409, 'No active workspace is available.');

        $validated = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'taken_at' => ['nullable', 'date'],
            'photo' => ['required', 'file', 'image', 'max:15360'],
        ]);

        $existing = GalleryPhoto::query()
            ->where('workspace_id', $workspaceId)
            ->where('client_uuid', $validated['client_uuid'])
            ->first();

        if ($existing !== null) {
            return response()->json(['photo' => $this->serialize($existing), 'duplicate' => true]);
        }

        $file = $request->file('photo');
        abort_if($file === null, 422, 'A photo is required.');

        $extension = strtolower($file->guessExtension() ?: 'jpg');
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs('mobile-gallery/'.$workspaceId, $filename, 'local');

        $photo = GalleryPhoto::create([
            'user_id' => $user->getKey(),
            'client_uuid' => $validated['client_uuid'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'original_filename' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'taken_at' => $validated['taken_at'] ?? now(),
        ]);

        return response()->json(['photo' => $this->serialize($photo), 'duplicate' => false], 201);
    }

    public function show(
        Request $request,
        GalleryPhoto $galleryPhoto,
        WorkspaceContext $workspaceContext,
    ): BinaryFileResponse {
        $user = $this->user($request);
        $workspaceId = $workspaceContext->currentId($user);

        abort_unless(
            $workspaceId !== null && (int) $galleryPhoto->workspace_id === $workspaceId,
            404,
        );
        abort_unless(Storage::disk('local')->exists($galleryPhoto->path), 404);

        return response()->file(
            Storage::disk('local')->path($galleryPhoto->path),
            [
                'Content-Type' => $galleryPhoto->mime_type,
                'Cache-Control' => 'private, max-age=3600',
            ],
        );
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    /** @return array<string, mixed> */
    private function serialize(GalleryPhoto $photo): array
    {
        return [
            'id' => $photo->getKey(),
            'client_uuid' => $photo->client_uuid,
            'title' => $photo->title,
            'description' => $photo->description,
            'mime_type' => $photo->mime_type,
            'size_bytes' => $photo->size_bytes,
            'taken_at' => $photo->taken_at?->toIso8601String(),
            'created_at' => $photo->created_at?->toIso8601String(),
            'image_url' => route('api.mobile.gallery.show', $photo),
        ];
    }
}
