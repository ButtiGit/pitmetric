<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class StudioSiteReleaseDownloads
{
    public function count(): ?int
    {
        try {
            /** @var array{count: int|null} $result */
            $result = Cache::remember('studio-site-android-release-downloads', now()->addMinutes(15), function (): array {
                $response = Http::acceptJson()->timeout(3)->get(
                    'https://api.github.com/repos/ButtiGit/pitmetric/releases/tags/android-latest',
                );

                if (! $response->successful()) {
                    return ['count' => null];
                }

                $assets = $response->json('assets');
                if (! is_array($assets)) {
                    return ['count' => null];
                }

                foreach ($assets as $asset) {
                    if (is_array($asset) && ($asset['name'] ?? null) === 'PitMetric.apk' && isset($asset['download_count'])) {
                        return ['count' => (int) $asset['download_count']];
                    }
                }

                return ['count' => null];
            });

            return $result['count'];
        } catch (Throwable) {
            return null;
        }
    }
}
