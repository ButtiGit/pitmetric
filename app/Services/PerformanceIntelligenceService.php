<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\Session;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PerformanceIntelligenceService
{
    public function __construct(private readonly SessionReportMetrics $metrics) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $sessions = Session::query()
            ->with([
                'usageValues.metric',
                'vehicle',
                'circuitLayout',
                'eventEntry.driver',
                'raceEvent',
            ])
            ->where('status', 'finalized')
            ->when($from, fn ($query, CarbonInterface $date) => $query->where('started_at', '>=', $date))
            ->when($to, fn ($query, CarbonInterface $date) => $query->where('started_at', '<=', $date))
            ->orderBy('started_at')
            ->get();

        $expenses = Expense::query()
            ->when($from, fn ($query, CarbonInterface $date) => $query->where('occurred_at', '>=', $date))
            ->when($to, fn ($query, CarbonInterface $date) => $query->where('occurred_at', '<=', $date))
            ->orderBy('occurred_at')
            ->get();

        $summary = $this->summarizeSessions($sessions, (int) $expenses->sum('amount_cents'));

        $drivers = $sessions
            ->filter(fn (Session $session) => $session->eventEntry?->driver !== null)
            ->groupBy(fn (Session $session) => (int) $session->eventEntry->driver_id)
            ->map(function (Collection $driverSessions): array {
                /** @var Session $first */
                $first = $driverSessions->first();
                $driver = $first->eventEntry->driver;
                $stats = $this->summarizeSessions($driverSessions);

                return [
                    'id' => $driver->getKey(),
                    'name' => $driver->display_name,
                    'racing_number' => $driver->racing_number,
                    'weekends' => $driverSessions->pluck('event_id')->filter()->unique()->count(),
                    ...$stats,
                ];
            })
            ->sortByDesc('distance_meters')
            ->values();

        $vehicles = $sessions
            ->filter(fn (Session $session) => $session->vehicle !== null)
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehicleSessions): array {
                /** @var Session $first */
                $first = $vehicleSessions->first();
                $stats = $this->summarizeSessions($vehicleSessions);

                return [
                    'id' => $first->vehicle->getKey(),
                    'name' => $first->vehicle->name,
                    'weekends' => $vehicleSessions->pluck('event_id')->filter()->unique()->count(),
                    ...$stats,
                ];
            })
            ->sortByDesc('distance_meters')
            ->values();

        $weekends = RaceEvent::query()
            ->with([
                'circuitLayout.circuit',
                'sessions.circuitLayout',
                'sessions.usageValues.metric',
                'expenses',
            ])
            ->when($from, fn ($query, CarbonInterface $date) => $query->whereDate('end_date', '>=', $date->toDateString()))
            ->when($to, fn ($query, CarbonInterface $date) => $query->whereDate('start_date', '<=', $date->toDateString()))
            ->latest('start_date')
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(function (RaceEvent $event): array {
                $eventSessions = $event->sessions->where('status', 'finalized');
                $eventSummary = $this->summarizeSessions($eventSessions, (int) $event->expenses->sum('amount_cents'));

                return [
                    'event' => $event,
                    ...$eventSummary,
                ];
            });

        return [
            'summary' => $summary,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'weekends' => $weekends,
            'expense_categories' => $this->expenseCategories($expenses),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function weekend(RaceEvent $event): array
    {
        return app(WeekendReportService::class)->data($event);
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<string, int|null>
     */
    private function summarizeSessions(Collection $sessions, int $costCents = 0): array
    {
        $distanceMeters = (int) $sessions->sum(fn (Session $session) => $this->sessionDistanceMeters($session));
        $durationSeconds = (int) $sessions->sum(fn (Session $session) => max(0, (int) ($this->metrics->runtime($session) ?? 0)));
        $laps = (int) $sessions->sum(fn (Session $session) => max(0, (int) ($session->completed_laps ?? 0)));

        return [
            'sessions' => $sessions->count(),
            'laps' => $laps,
            'distance_meters' => $distanceMeters,
            'duration_seconds' => $durationSeconds,
            'cost_cents' => $costCents,
            'cost_per_km_cents' => $distanceMeters > 0
                ? (int) round($costCents / ($distanceMeters / 1000))
                : null,
            'cost_per_hour_cents' => $durationSeconds > 0
                ? (int) round($costCents / ($durationSeconds / 3600))
                : null,
        ];
    }

    private function sessionDistanceMeters(Session $session): int
    {
        return max(0, (int) ($this->metrics->distance($session) ?? 0));
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return Collection<int, array{category: string, amount_cents: int, count: int}>
     */
    private function expenseCategories(Collection $expenses): Collection
    {
        return $expenses
            ->groupBy(fn (Expense $expense) => $expense->category ?: 'other')
            ->map(fn (Collection $categoryExpenses, string $category): array => [
                'category' => $category,
                'amount_cents' => (int) $categoryExpenses->sum('amount_cents'),
                'count' => $categoryExpenses->count(),
            ])
            ->sortByDesc('amount_cents')
            ->values();
    }
}
