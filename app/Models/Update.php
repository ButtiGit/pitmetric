<?php

namespace App\Models;

use Database\Factories\UpdateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property Carbon|null $published_at
 * @property string $title
 * @property string|null $title_it
 * @property string $excerpt
 * @property string|null $excerpt_it
 * @property string $content
 * @property string|null $content_it
 * @property string|null $media_type
 * @property string|null $media_path
 * @property string|null $media_url
 * @property string|null $media_alt
 * @property string|null $media_alt_it
 */
class Update extends Model
{
    /** @use HasFactory<UpdateFactory> */
    use HasFactory;

    protected $fillable = [
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

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Update>  $query
     * @return Builder<Update>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());
    }

    public function titleForLocale(?string $locale = null): string
    {
        return $this->localizedValue(
            $this->stringAttribute('title'),
            $this->stringAttribute('title_it'),
            $locale,
        );
    }

    public function excerptForLocale(?string $locale = null): string
    {
        return $this->localizedValue(
            $this->stringAttribute('excerpt'),
            $this->stringAttribute('excerpt_it'),
            $locale,
        );
    }

    public function contentForLocale(?string $locale = null): string
    {
        return $this->localizedValue(
            $this->stringAttribute('content'),
            $this->stringAttribute('content_it'),
            $locale,
        );
    }

    public function mediaAltForLocale(?string $locale = null): string
    {
        $alt = $this->localizedValue(
            $this->stringAttribute('media_alt'),
            $this->stringAttribute('media_alt_it'),
            $locale,
        );

        return $alt !== '' ? $alt : $this->titleForLocale($locale);
    }

    public function mediaSource(): ?string
    {
        $mediaPath = $this->stringAttribute('media_path');
        $mediaUrl = $this->stringAttribute('media_url');

        if ($mediaPath !== null && $mediaPath !== '') {
            return Storage::disk('public')->url($mediaPath);
        }

        if ($mediaUrl !== null && $mediaUrl !== '') {
            return $mediaUrl;
        }

        return null;
    }

    public function videoEmbedUrl(): ?string
    {
        $mediaType = $this->stringAttribute('media_type');
        $mediaUrl = $this->stringAttribute('media_url');

        if ($mediaType !== 'video' || $mediaUrl === null || $mediaUrl === '') {
            return null;
        }

        $host = strtolower((string) parse_url($mediaUrl, PHP_URL_HOST));
        $path = trim((string) parse_url($mediaUrl, PHP_URL_PATH), '/');

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str((string) parse_url($mediaUrl, PHP_URL_QUERY), $query);
            $videoId = $query['v'] ?? null;

            if (is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId) === 1) {
                return 'https://www.youtube-nocookie.com/embed/'.$videoId;
            }
        }

        if ($host === 'youtu.be' && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $path) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$path;
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true) && preg_match('/^\d+$/', $path) === 1) {
            return 'https://player.vimeo.com/video/'.$path;
        }

        return null;
    }

    private function localizedValue(?string $english, ?string $italian, ?string $locale): string
    {
        $activeLocale = $locale ?? app()->getLocale();

        if ($activeLocale === 'it' && $italian !== null && trim($italian) !== '') {
            return $italian;
        }

        return $english ?? '';
    }

    private function stringAttribute(string $key): ?string
    {
        $value = $this->getAttributes()[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
