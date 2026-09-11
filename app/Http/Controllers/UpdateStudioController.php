<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateStudioController extends Controller
{
    /** @var list<string> */
    private const REQUIRED_COLUMNS = [
        'title',
        'title_it',
        'slug',
        'excerpt',
        'excerpt_it',
        'content',
        'content_it',
        'media_type',
        'media_path',
        'media_url',
        'media_alt',
        'media_alt_it',
        'status',
        'published_at',
    ];

    public function index(): View
    {
        if (! $this->schemaReady()) {
            return $this->setupView();
        }

        $updates = Update::query()
            ->latest('updated_at')
            ->paginate(12);

        return view('studio.updates.index', compact('updates'));
    }

    public function create(): View
    {
        if (! $this->schemaReady()) {
            return $this->setupView();
        }

        return view('studio.updates.form', [
            'update' => new Update,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->schemaReady()) {
            return redirect()
                ->route('studio.updates.index')
                ->with('studio_setup_error', __('pitmetric.studio.setup_not_ready'));
        }

        $update = new Update;
        $this->persist($request, $update);

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.saved'));
    }

    public function edit(Update $update): View
    {
        if (! $this->schemaReady()) {
            return $this->setupView();
        }

        return view('studio.updates.form', compact('update'));
    }

    public function update(Request $request, Update $update): RedirectResponse
    {
        if (! $this->schemaReady()) {
            return redirect()
                ->route('studio.updates.index')
                ->with('studio_setup_error', __('pitmetric.studio.setup_not_ready'));
        }

        $this->persist($request, $update);

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.saved'));
    }

    public function destroy(Update $update): RedirectResponse
    {
        if (! $this->schemaReady()) {
            return redirect()
                ->route('studio.updates.index')
                ->with('studio_setup_error', __('pitmetric.studio.setup_not_ready'));
        }

        $this->removeStoredMedia($update);
        $update->delete();

        return redirect()
            ->route('studio.updates.index')
            ->with('status', __('pitmetric.studio.deleted'));
    }

    private function persist(Request $request, Update $update): void
    {
        $this->normalizeLegacyComposerInput($request);

        $data = $request->validate([
            'primary_locale' => ['required', 'in:en,it'],
            'primary_title' => ['required', 'string', 'max:180'],
            'primary_excerpt' => ['nullable', 'string', 'max:500'],
            'primary_content' => ['nullable', 'string'],
            'translation_title' => ['nullable', 'string', 'max:180'],
            'translation_excerpt' => ['nullable', 'string', 'max:500'],
            'translation_content' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:51200'],
            'media_url' => ['nullable', 'url', 'max:2000'],
            'media_type' => ['nullable', 'in:image,video'],
            'remove_media' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $uploaded = $request->file('media');
        $externalMedia = trim((string) ($data['media_url'] ?? ''));
        $removeMedia = $request->boolean('remove_media');
        $hasExistingMedia = ! $removeMedia
            && $update->exists
            && (($update->getAttribute('media_path') !== null && $update->getAttribute('media_path') !== '')
                || ($update->getAttribute('media_url') !== null && $update->getAttribute('media_url') !== ''));

        $externalMediaType = null;

        if ($externalMedia !== '') {
            $scheme = strtolower((string) parse_url($externalMedia, PHP_URL_SCHEME));

            if (! in_array($scheme, ['http', 'https'], true)) {
                throw ValidationException::withMessages([
                    'media_url' => __('pitmetric.studio.http_media_only'),
                ]);
            }

            $externalMediaType = $this->inferExternalMediaType($externalMedia, $data['media_type'] ?? null);

            if ($externalMediaType === null) {
                throw ValidationException::withMessages([
                    'media_url' => __('pitmetric.studio.media_detect_failed'),
                ]);
            }
        }

        $primaryLocale = (string) $data['primary_locale'];
        $primaryTitle = trim((string) $data['primary_title']);
        $primaryContent = trim((string) ($data['primary_content'] ?? ''));
        $primaryExcerpt = $this->nullableString($data['primary_excerpt'] ?? null)
            ?? $this->buildExcerpt($primaryContent !== '' ? $primaryContent : $primaryTitle);

        $translationTitle = $this->nullableString($data['translation_title'] ?? null);
        $translationContent = $this->nullableString($data['translation_content'] ?? null);
        $translationExcerpt = $this->nullableString($data['translation_excerpt'] ?? null);

        if ($translationExcerpt === null && $translationContent !== null) {
            $translationExcerpt = $this->buildExcerpt($translationContent);
        }

        $hasNewMedia = $uploaded instanceof UploadedFile || $externalMedia !== '';

        if ($primaryContent === '' && ! $hasNewMedia && ! $hasExistingMedia) {
            throw ValidationException::withMessages([
                'primary_content' => __('pitmetric.studio.content_or_media'),
            ]);
        }

        if ($primaryLocale === 'it') {
            $titleIt = $primaryTitle;
            $excerptIt = $primaryExcerpt;
            $contentIt = $primaryContent;

            $title = $translationTitle ?? $this->existingString($update, 'title') ?? $primaryTitle;
            $content = $translationContent ?? $this->existingString($update, 'content') ?? $primaryContent;
            $excerpt = $translationExcerpt
                ?? $this->existingString($update, 'excerpt')
                ?? $this->buildExcerpt($content !== '' ? $content : $title);
        } else {
            $title = $primaryTitle;
            $excerpt = $primaryExcerpt;
            $content = $primaryContent;

            $titleIt = $translationTitle ?? $this->existingString($update, 'title_it');
            $contentIt = $translationContent ?? $this->existingString($update, 'content_it');
            $excerptIt = $translationExcerpt
                ?? $this->existingString($update, 'excerpt_it');
        }

        $slug = $this->uniqueSlug(
            trim((string) ($data['slug'] ?? '')) !== '' ? (string) $data['slug'] : $primaryTitle,
            $update,
        );

        $update->fill([
            'title' => $title,
            'title_it' => $titleIt,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'excerpt_it' => $excerptIt,
            'content' => $content,
            'content_it' => $contentIt,
            'media_alt' => $title,
            'media_alt_it' => $titleIt,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($update->published_at ?? now()) : null,
        ]);

        if ($removeMedia) {
            $this->removeStoredMedia($update);
            $update->setAttribute('media_path', null);
            $update->setAttribute('media_url', null);
            $update->setAttribute('media_type', null);
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
            $update->setAttribute('media_path', $path);
            $update->setAttribute('media_url', null);
            $update->setAttribute('media_type', str_starts_with($mime, 'video/') ? 'video' : 'image');
        } elseif ($externalMedia !== '') {
            $this->removeStoredMedia($update);
            $update->setAttribute('media_path', null);
            $update->setAttribute('media_url', $externalMedia);
            $update->setAttribute('media_type', $externalMediaType);
        }

        $update->save();
    }

    private function normalizeLegacyComposerInput(Request $request): void
    {
        if (! $request->filled('primary_title') && $request->filled('title')) {
            $request->merge([
                'primary_locale' => 'en',
                'primary_title' => $request->input('title'),
                'primary_excerpt' => $request->input('excerpt'),
                'primary_content' => $request->input('content'),
                'translation_title' => $request->input('title_it'),
                'translation_excerpt' => $request->input('excerpt_it'),
                'translation_content' => $request->input('content_it'),
            ]);
        }
    }

    private function inferExternalMediaType(string $url, mixed $providedType): ?string
    {
        if (is_string($providedType) && in_array($providedType, ['image', 'video'], true)) {
            return $providedType;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com'], true)) {
            return 'video';
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'webm', 'mov'], true)) {
            return 'video';
        }

        return null;
    }

    private function removeStoredMedia(Update $update): void
    {
        $mediaPath = $update->getAttribute('media_path');

        if (is_string($mediaPath) && $mediaPath !== '') {
            Storage::disk('public')->delete($mediaPath);
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

    private function existingString(Update $update, string $attribute): ?string
    {
        if (! $update->exists) {
            return null;
        }

        return $this->nullableString($update->getAttribute($attribute));
    }

    private function schemaReady(): bool
    {
        if (! Schema::hasTable('updates')) {
            return false;
        }

        return Schema::hasColumns('updates', self::REQUIRED_COLUMNS);
    }

    private function setupView(): View
    {
        return view('studio.updates.setup');
    }
}
