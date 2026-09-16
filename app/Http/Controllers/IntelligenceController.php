<?php

namespace App\Http\Controllers;

use App\Models\RaceEvent;
use App\Models\User;
use App\Services\PerformanceIntelligenceService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
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
    ): View {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('view', $raceEvent);

        return view('insights.weekend-report', [
            'report' => $intelligence->weekend($raceEvent),
        ]);
    }

    public function weekendReportCsv(
        Request $request,
        RaceEvent $raceEvent,
        WorkspaceContext $workspaceContext,
        PerformanceIntelligenceService $intelligence,
    ): StreamedResponse {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('view', $raceEvent);

        $report = $intelligence->weekend($raceEvent);
        $eventDate = Carbon::parse($raceEvent->start_date)->format('Y-m-d');
        $filename = 'pitmetric-weekend-'.Str::slug($raceEvent->name).'-'.$eventDate.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['PitMetric Weekend Report']);
            fputcsv($stream, ['Event', $report['event']->name]);
            fputcsv($stream, ['Start', $report['event']->start_date->toDateString()]);
            fputcsv($stream, ['End', $report['event']->end_date->toDateString()]);
            fputcsv($stream, ['Sessions', $report['summary']['sessions']]);
            fputcsv($stream, ['Laps', $report['summary']['laps']]);
            fputcsv($stream, ['Distance km', number_format($report['summary']['distance_meters'] / 1000, 3, '.', '')]);
            fputcsv($stream, ['Runtime hours', number_format($report['summary']['duration_seconds'] / 3600, 3, '.', '')]);
            fputcsv($stream, ['Cost EUR', number_format($report['summary']['cost_cents'] / 100, 2, '.', '')]);
            fputcsv($stream, ['Cost per km EUR', $this->csvMoney($report['summary']['cost_per_km_cents'])]);
            fputcsv($stream, ['Cost per hour EUR', $this->csvMoney($report['summary']['cost_per_hour_cents'])]);
            fputcsv($stream, []);

            fputcsv($stream, ['Entries']);
            fputcsv($stream, ['Driver', 'Vehicle', 'Sessions', 'Laps', 'Distance km', 'Runtime hours', 'Direct cost EUR']);

            foreach ($report['entries'] as $row) {
                fputcsv($stream, [
                    $row['entry']->driver->display_name,
                    $row['entry']->vehicle->name,
                    $row['sessions'],
                    $row['laps'],
                    number_format($row['distance_meters'] / 1000, 3, '.', ''),
                    number_format($row['duration_seconds'] / 3600, 3, '.', ''),
                    number_format($row['direct_cost_cents'] / 100, 2, '.', ''),
                ]);
            }

            fputcsv($stream, []);
            fputcsv($stream, ['Sessions']);
            fputcsv($stream, ['Date', 'Type', 'Driver', 'Vehicle', 'Laps', 'Distance km', 'Runtime min', 'Direct cost EUR', 'Setup']);

            foreach ($report['sessions'] as $row) {
                $session = $row['session'];
                fputcsv($stream, [
                    $session->started_at->format('Y-m-d H:i'),
                    $session->session_type,
                    $session->eventEntry->driver->display_name ?? '',
                    $session->vehicle->name ?? '',
                    $session->completed_laps ?? '',
                    number_format($row['distance_meters'] / 1000, 3, '.', ''),
                    $session->duration_seconds !== null ? number_format($session->duration_seconds / 60, 1, '.', '') : '',
                    number_format($row['cost_cents'] / 100, 2, '.', ''),
                    $session->setupSnapshot->name ?? $session->setupSnapshot->technicalSetup->name ?? '',
                ]);
            }

            fputcsv($stream, []);
            fputcsv($stream, ['Expenses']);
            fputcsv($stream, ['Date', 'Category', 'Description', 'Amount EUR']);

            foreach ($report['expenses'] as $expense) {
                fputcsv($stream, [
                    $expense->occurred_at->format('Y-m-d H:i'),
                    $expense->category,
                    $expense->description,
                    number_format($expense->amount_cents / 100, 2, '.', ''),
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    private function csvMoney(?int $cents): string
    {
        return $cents === null ? '' : number_format($cents / 100, 2, '.', '');
    }
}
