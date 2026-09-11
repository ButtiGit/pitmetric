<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateStudioController extends Controller
{
    public function index(): View
    {
        $updates = Update::query()
            ->latest('updated_at')
            ->paginate(12);

        return view('studio.updates.index', compact('updates'));
    }

    public function create(): View
    {
        return view('studio.updates.form', [
            'update' => new Update,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $update = new Update;
        $this->persist($request, $update);

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.saved'));
    }

    public function edit(Update $update): View
    {
        return view('studio.updates.form', compact('update'));
    }

    public function update(Request $request, Update $update): RedirectResponse
    {
        $this->persist($request, $update);

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.saved'));
    }

    public function destroy(Update $update): RedirectResponse
    {
        if ($update->media_path !== null && $update->media_path !== '') {
            Storage::disk('public')->delete($update->media_path);
        }

        $update->delete();

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.deleted'));
    }

    private function persist(Request $request, Update $update): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'title_it' => ['nullable', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'excerpt_it' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'content_it' => ['nullable', 'string'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:51200'],
            'media_url' => ['nullable', 'url', 'max:2000'],
            'media_type' => ['nullable', 'in:image,video'],
            'media_alt' => ['nullable', 'string', 'max:255'],
            'media_alt_it' => ['nullable', 'string', 'max:255'],
            'remove_media' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $uploaded = $request->file('media');
        $externalMedia = trim((string) ($data['media_url'] ?? ''));
        $removeMedia = $request->boolean('remove_media');
        $hasExistingMedia = ! $removeMedia
            && $update->exists
            && (($update->media_path !== null && $update->media_path !== '') || ($update->media_url !== null && $update->media_url !== ''));

        if ($externalMedia !== '') {
            $scheme = strtolower((string) parse_url($externalMedia, PHP_URL_SCHEME));

            if (! in_array($scheme, ['http', 'https'], true)) {
                throw ValidationException::withMessages([
                    'media_url' => __('pitmetric.studio.http_media_only'),
                ]);
            }

            if (($data['media_type'] ?? null) === null) {
                throw ValidationException::withMessages([
                    'media_type' => __('pitmetric.studio.media_type_required'),
                ]);
            }
        }

        $englishContent = trim((string) ($data['content'] ?? ''));
        $italianContent = trim((string) ($data['content_it'] ?? ''));
        $hasNewMedia = $uploaded instanceof UploadedFile || $externalMedia !== '';

        if ($englishContent === '' && $italianContent === '' && ! $hasNewMedia && ! $hasExistingMedia) {
            throw ValidationException::withMessages([
                'content' => __('pitmetric.studio.content_or_media'),
            ]);
        }

        $title = trim((string) $data['title']);
        $titleIt = $this->nullableString($data['title_it'] ?? null);
        $excerpt = $this->nullableString($data['excerpt'] ?? null)
            ?? $this->buildExcerpt($englishContent !== '' ? $englishContent : $title);
        $excerptIt = $this->nullableString($data['excerpt_it'] ?? null);

        if ($excerptIt === null && $italianContent !== '') {
            $excerptIt = $this->buildExcerpt($italianContent);
        }

        $slug = $this->uniqueSlug(
            trim((string) ($data['slug'] ?? '')) !== '' ? (string) $data['slug'] : $title,
            $update,
        );

        $update->fill([
            'title' => $title,
            'title_it' => $titleIt,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'excerpt_it' => $excerptIt,
            'content' => $englishContent,
            'content_it' => $italianContent !== '' ? $italianContent : null,
            'media_alt' => $this->nullableString($data['media_alt'] ?? null),
            'media_alt_it' => $this->nullableString($data['media_alt_it'] ?? null),
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($update->published_at ?? now()) : null,
        ]);

        if ($removeMedia) {
            $this->removeStoredMedia($update);
            $update->media_path = null;
            $update->media_url = null;
            $update->media_type = null;
        }

        if ($uploaded instanceof UploadedFile) {
            $this->removeStoredMedia($update);
            $path = $uploaded->store('updates', 'public');

            if ($path === false) {
                throw ValidationException::withMessages([
                    'media' => __('pitmetric.studio.upload_failed'),
                ]);
            }

            $mime = (string) $uploaded->getMimeType();
            $update->media_path = $path;
            $update->media_url = null;
            $update->media_type = str_starts_with($mime, 'video/') ? 'video' : 'image';
        } elseif ($externalMedia !== '') {
            $this->removeStoredMedia($update);
            $update->media_path = null;
            $update->media_url = $externalMedia;
            $update->media_type = (string) $data['media_type'];
        }

        $update->save();
    }

    private function removeStoredMedia(Update $update): void
    {
        if ($update->media_path !== null && $update->media_path !== '') {
            Storage::disk('public')->delete($update->media_path);
        }
    }

    private function uniqueSlug(string $value, Update $update): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'update';
        $slug = $base;
        $suffix = 2;

        while (Update::query()
            ->where('slug', $slug)
            ->when($update->exists, fn ($query) => $query->where('id', '!=', $update->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function buildExcerpt(string $content): string
    {
        $clean = preg_replace('/\s+/', ' ', strip_tags($content));

        return Str::limit(trim($clean ?? $content), 240);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
