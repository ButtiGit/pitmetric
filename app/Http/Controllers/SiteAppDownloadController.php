<?php

namespace App\Http\Controllers;

use App\Services\StudioSiteTraffic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SiteAppDownloadController extends Controller
{
    public function android(StudioSiteTraffic $traffic): RedirectResponse
    {
        $this->record($traffic, 'apk_download', 'android');

        return redirect()->away((string) config('pitmetric.android_apk_url'))
            ->header('Cache-Control', 'no-store');
    }

    public function pwa(StudioSiteTraffic $traffic): RedirectResponse
    {
        if (! is_file(public_path('mobile/index.html')) || ! is_file(public_path('mobile/sw.js'))) {
            abort(404);
        }

        $this->record($traffic, 'pwa_open', 'pwa');

        return redirect('/mobile/')->header('Cache-Control', 'no-store');
    }

    private function record(StudioSiteTraffic $traffic, string $kind, string $subject): void
    {
        try {
            $traffic->record($kind, $subject);
        } catch (Throwable $exception) {
            Log::warning('Download tracking could not record a click.', ['error_type' => class_basename($exception)]);
        }
    }
}
