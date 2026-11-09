<?php

namespace App\Http\Controllers;

use App\Models\StudioSiteCheckRun;
use App\Models\StudioSiteMetric;
use App\Services\StudioAppActivations;
use App\Services\StudioSiteDiagnostics;
use App\Services\StudioSiteReleaseDownloads;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

final class StudioSiteManagementController extends Controller
{
    public function index(StudioSiteReleaseDownloads $releases, StudioAppActivations $activations): View
    {
        if (! $this->isReady()) {
            return view('studio.site.setup');
        }

        $since = now('Europe/Rome')->subDays(29)->startOfDay();
        $metrics = StudioSiteMetric::query()
            ->whereDate('day', '>=', $since->toDateString())
            ->get(['day', 'kind', 'subject', 'value']);

        $totals = [
            'page_view' => (int) $metrics->where('kind', 'page_view')->sum('value'),
            'apk_download' => (int) $metrics->where('kind', 'apk_download')->sum('value'),
            'pwa_open' => (int) $metrics->where('kind', 'pwa_open')->sum('value'),
            'http_error' => (int) $metrics->where('kind', 'http_error')->sum('value'),
        ];

        $dailyPageViews = $metrics->where('kind', 'page_view')->groupBy(fn (StudioSiteMetric $metric): string => Carbon::parse($metric->day)->toDateString())
            ->map(fn ($items): int => (int) $items->sum('value'));
        $trend = collect(range(13, 0))->map(function (int $daysAgo) use ($dailyPageViews): array {
            $day = now('Europe/Rome')->subDays($daysAgo);

            return ['date' => $day->toDateString(), 'label' => $day->format('d/m'), 'value' => (int) $dailyPageViews->get($day->toDateString(), 0)];
        });
        $trendMaximum = max(1, (int) $trend->max('value'));

        $topPages = $metrics->where('kind', 'page_view')->groupBy('subject')
            ->map(fn ($items, string $path): array => ['path' => $path, 'views' => (int) $items->sum('value')])
            ->sortByDesc('views')->take(8)->values();

        $httpErrors = $metrics->where('kind', 'http_error')->groupBy('subject')
            ->map(fn ($items, string $route): array => ['route' => $route, 'count' => (int) $items->sum('value')])
            ->sortByDesc('count')->take(6)->values();

        $runs = StudioSiteCheckRun::query()->with('author:id,name')->latest()->limit(8)->get();
        $githubApkDownloads = $releases->count();
        $activationReady = Schema::hasTable('studio_app_activations');
        $appActivations = $activationReady ? $activations->totals() : null;

        return view('studio.site.index', compact('totals', 'trend', 'trendMaximum', 'topPages', 'httpErrors', 'runs', 'githubApkDownloads', 'activationReady', 'appActivations'));
    }

    public function run(Request $request, StudioSiteDiagnostics $diagnostics): RedirectResponse
    {
        if (! $this->isReady()) {
            return redirect()->route('studio.site.index')->with('status', 'Applica le migrazioni Laravel per attivare il monitoraggio.');
        }

        $results = $diagnostics->run();
        $passed = count(array_filter($results, fn (array $result): bool => $result['status'] === 'ok'));
        $warnings = count(array_filter($results, fn (array $result): bool => $result['status'] === 'warning'));
        $failed = count(array_filter($results, fn (array $result): bool => $result['status'] === 'error'));

        StudioSiteCheckRun::query()->create([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'status' => $failed > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'ok'),
            'passed' => $passed,
            'warnings' => $warnings,
            'failed' => $failed,
            'results' => $results,
        ]);

        return redirect()->route('studio.site.index')->with('status', 'Verifiche completate. Il report è disponibile qui sotto.');
    }

    private function isReady(): bool
    {
        return Schema::hasTable('studio_site_metrics') && Schema::hasTable('studio_site_check_runs');
    }
}
