<?php

namespace App\Http\Controllers;

use App\Models\RaceEvent;
use App\Models\User;
use App\Services\PerformanceIntelligenceService;
use App\Services\WeekendReportCsvService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntelligenceController extends Controller
{
    public function index(
        Request $request,
        WorkspaceContext $workspaceContext,
        PerformanceIntelligenceService $intelligence,
    ): View {
        $user = $this->user($request);
        $workspaceContext->personal($user);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        return view('insights.index', [
            'intelligence' => $intelligence->dashboard($from, $to),
            'filters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }

    public function weekendReport(
        Request $request,
        RaceEvent $raceEvent,
        WorkspaceContext $workspaceContext,
        PerformanceIntelligenceService $intelligence,
    ): Response {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('view', $raceEvent);

        return response()->view('insights.weekend-report', [
            'report' => $intelligence->weekend($raceEvent),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function weekendReportCsv(
        Request $request,
        RaceEvent $raceEvent,
        WorkspaceContext $workspaceContext,
        PerformanceIntelligenceService $intelligence,
        WeekendReportCsvService $csv,
    ): StreamedResponse {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('view', $raceEvent);

        $report = $intelligence->weekend($raceEvent);
        $eventDate = Carbon::parse($raceEvent->start_date)->format('Y-m-d');
        $filename = 'pitmetric-weekend-'.Str::slug($raceEvent->name).'-'.$eventDate.'.csv';

        return response()->streamDownload(function () use ($report, $csv): void {
            $stream = fopen('php://output', 'w');
            if ($stream !== false) {
                $csv->write($stream, $report);
                fclose($stream);
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
