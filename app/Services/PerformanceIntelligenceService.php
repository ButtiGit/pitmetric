<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\Session;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PerformanceIntelligenceService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $sessions = Session::query()
            ->with([
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
        $event->loadMissing([
            'circuitLayout.circuit',
            'entries.driver',
            'entries.vehicle',
            'sessions.vehicle',
            'sessions.circuitLayout',
            'sessions.eventEntry.driver',
            'sessions.setupSnapshot.technicalSetup',
            'tasks',
            'eventNotes',
            'expenses',
            'maintenanceRecords.schedule.tracker.component',
        ]);

        $sessions = $event->sessions
            ->where('status', 'finalized')
            ->sortBy('started_at')
            ->values();
        $expenses = $event->expenses->sortBy('occurred_at')->values();
        $summary = $this->summarizeSessions($sessions, (int) $expenses->sum('amount_cents'));

        $taskCosts = $this->relatedCostsById($expenses, 'event_task');
        $entryCosts = $this->relatedCostsById($expenses, 'event_entry');
        $sessionCosts = $this->relatedCostsById($expenses, 'session');

        $entries = $event->entries->map(function ($entry) use ($sessions, $entryCosts, $sessionCosts, $taskCosts, $event): array {
            $entrySessions = $sessions->where('event_entry_id', $entry->getKey())->values();
            $entryTaskIds = $event->tasks
                ->where('event_entry_id', $entry->getKey())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $directCostCents = (int) $entryCosts->get((int) $entry->getKey(), 0)
                + (int) $entrySessions->sum(fn (Session $session) => (int) $sessionCosts->get((int) $session->getKey(), 0))
                + (int) $entryTaskIds->sum(fn (int $taskId) => (int) $taskCosts->get($taskId, 0));

            return [
                'entry' => $entry,
                'direct_cost_cents' => $directCostCents,
                ...$this->summarizeSessions($entrySessions, $directCostCents),
            ];
        })->sortByDesc('distance_meters')->values();

        $sessionRows = $sessions->map(function (Session $session) use ($sessionCosts): array {
            return [
                'session' => $session,
                'distance_meters' => $this->sessionDistanceMeters($session),
                'cost_cents' => (int) $sessionCosts->get((int) $session->getKey(), 0),
            ];
        });

        return [
            'event' => $event,
            'summary' => $summary,
            'entries' => $entries,
            'sessions' => $sessionRows,
            'expense_categories' => $this->expenseCategories($expenses),
            'expenses' => $expenses,
            'operations' => [
                'tasks_total' => $event->tasks->count(),
                'tasks_done' => $event->tasks->where('status', 'done')->count(),
                'tasks_open' => $event->tasks->where('status', '!=', 'done')->count(),
                'notes' => $event->eventNotes->count(),
                'maintenance_records' => $event->maintenanceRecords->count(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<string, int|null>
     */
    private function summarizeSessions(Collection $sessions, int $costCents = 0): array
    {
        $distanceMeters = (int) $sessions->sum(fn (Session $session) => $this->sessionDistanceMeters($session));
        $durationSeconds = (int) $sessions->sum(fn (Session $session) => max(0, (int) ($session->duration_seconds ?? 0)));
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
        $override = max(0, (int) ($session->distance_override_meters ?? 0));

        if ($override > 0) {
            return $override;
        }

        $laps = max(0, (int) ($session->completed_laps ?? 0));
        $layoutMeters = max(0, (int) ($session->circuitLayout?->length_meters ?? 0));

        return $laps * $layoutMeters;
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return Collection<int, int>
     */
    private function relatedCostsById(Collection $expenses, string $relatedType): Collection
    {
        return $expenses
            ->where('related_type', $relatedType)
            ->groupBy(fn (Expense $expense) => (int) $expense->related_id)
            ->map(fn (Collection $relatedExpenses): int => (int) $relatedExpenses->sum('amount_cents'));
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
