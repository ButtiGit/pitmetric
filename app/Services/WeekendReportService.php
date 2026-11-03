<?php

namespace App\Services;

use App\Models\EventEntry;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class WeekendReportService
{
    public function __construct(private readonly PrintDocumentService $documents, private readonly MaintenanceReportService $maintenance, private readonly SessionReportMetrics $metrics) {}

    /** @return array<string, mixed> */
    public function data(RaceEvent $event): array
    {
        $event->load([
            'circuitLayout.circuit', 'entries.driver', 'entries.vehicle',
            'sessions.vehicle', 'sessions.circuitLayout', 'sessions.eventEntry.driver', 'sessions.setupSnapshot',
            'sessions.usageValues.metric', 'sessions.tyreReadings.creator', 'sessions.weatherObservations',
            'sessions.timingLaps' => fn ($q) => $q->orderBy('lap_number')->orderBy('id'),
            'tasks.assignee', 'tasks.completedBy', 'tasks.eventEntry.driver', 'tasks.eventEntry.vehicle', 'eventNotes.eventEntry.driver',
        ]);
        $workspace = Workspace::query()->findOrFail($event->workspace_id);
        $maintenance = $this->maintenance->data($workspace, ['event_id' => $event->id]);
        $sessions = $event->sessions->sortBy([['started_at', 'asc'], ['id', 'asc']])->values();
        $finalized = $sessions->where('status', 'finalized')->values();
        $sources = ['session' => $sessions->modelKeys(), 'event_entry' => $event->entries->modelKeys(),
            'event_task' => $event->tasks->modelKeys(), 'maintenance_record' => $maintenance['records']->modelKeys()];
        $expenses = Expense::query()->where('workspace_id', $event->workspace_id)
            ->where(function ($q) use ($event, $sources): void {
                $q->where('event_id', $event->id)->orWhere(function ($linked) use ($sources): void {
                    $linked->whereNull('event_id')->where(function ($source) use ($sources): void {
                        foreach ($sources as $type => $ids) {
                            $source->orWhere(fn ($match) => $match->where('related_type', $type)->whereIn('related_id', $ids));
                        }
                    });
                });
            })->orderBy('occurred_at')->orderBy('id')->get();
        $eur = $expenses->where('currency', 'EUR');
        $costByEntry = [];
        $costBySession = [];
        foreach ($eur as $expense) {
            $entryId = match ($expense->related_type) {
                'event_entry' => $event->entries->firstWhere('id', $expense->related_id)?->id,
                'session' => $sessions->firstWhere('id', $expense->related_id)?->event_entry_id,
                'event_task' => $event->tasks->firstWhere('id', $expense->related_id)?->event_entry_id,
                default => null,
            };
            if ($entryId !== null && $event->entries->contains('id', $entryId)) {
                $costByEntry[$entryId] = ($costByEntry[$entryId] ?? 0) + $expense->amount_cents;
            }
            if ($expense->related_type === 'session' && $sessions->contains('id', $expense->related_id)) {
                $costBySession[$expense->related_id] = ($costBySession[$expense->related_id] ?? 0) + $expense->amount_cents;
            }
        }
        $previous = [];
        $sessionRows = $sessions->map(function (Session $session) use (&$previous, $costBySession): array {
            $values = $session->setupSnapshot?->getAttribute('values');
            $setupRows = $this->documents->setupRows(is_array($values) ? $values : [], $session->vehicle?->category === 'kart');
            $pair = $session->vehicle_id.':'.($session->eventEntry->driver_id ?? 'unknown');
            $changes = [];
            $baseline = $previous[$pair] ?? null;
            if ($session->eventEntry !== null && $session->setupSnapshot !== null && $baseline !== null) {
                $before = collect($baseline['rows'])->pluck('value', 'label');
                $after = collect($setupRows)->pluck('value', 'label');
                foreach ($before->keys()->merge($after->keys())->unique() as $label) {
                    if ($before->get($label, '—') !== $after->get($label, '—')) {
                        $changes[] = ['label' => $label, 'before' => $before->get($label, '—'), 'after' => $after->get($label, '—')];
                    }
                }
            }
            $previous[$pair] = $session->setupSnapshot === null ? null : ['id' => $session->id, 'rows' => $setupRows];
            $best = $session->timingLaps->where('is_valid', true)->where('lap_time_ms', '>', 0)->sortBy('lap_time_ms')->first();

            return ['session' => $session, 'distance_meters' => $this->metrics->distance($session), 'runtime_seconds' => $this->metrics->runtime($session),
                'distance_source' => $session->status === 'finalized' && $session->usageValues->contains(fn ($usage) => $usage->metric?->key === 'distance') ? 'saved' : ($session->distance_override_meters !== null ? 'distance_explicit' : 'derived'),
                'cost_cents' => $costBySession[$session->id] ?? 0, 'best_lap' => $best, 'best_time' => $this->documents->lapTime($best?->lap_time_ms),
                'setup_rows' => $setupRows, 'changes' => $changes, 'baseline_id' => $baseline['id'] ?? null, 'weather' => $this->documents->weather($session)];
        });
        $entries = $event->entries->map(function (EventEntry $entry) use ($finalized, $costByEntry, $sessionRows): array {
            $entrySessions = $finalized->where('event_entry_id', $entry->id)->values();
            $laps = $sessionRows->filter(fn ($row) => $row['session']->event_entry_id === $entry->id && $row['session']->status === 'finalized')->pluck('best_lap')->filter();

            return ['entry' => $entry, ...$this->summary($entrySessions, $costByEntry[$entry->id] ?? 0),
                'direct_cost_cents' => $costByEntry[$entry->id] ?? 0,
                'best_lap_ms' => $entrySessions->pluck('circuit_layout_id')->filter()->unique()->count() === 1 && $entrySessions->whereNull('circuit_layout_id')->isEmpty() ? $laps->min('lap_time_ms') : null];
        });
        $currencies = $expenses->groupBy('currency')->map(fn ($items) => (int) $items->sum('amount_cents'));

        return ['event' => $event, 'workspace' => $workspace, 'documents' => $this->documents,
            'generated_at' => now()->timezone('Europe/Rome'), 'summary' => $this->summary($finalized, (int) $eur->sum('amount_cents')),
            'entries' => $entries, 'sessions' => $sessionRows, 'draft_count' => $sessions->where('status', '!=', 'finalized')->count(),
            'expenses' => $expenses, 'currency_totals' => $currencies, 'eur_expense_count' => $eur->count(),
            'direct_cost_cents' => array_sum($costByEntry), 'general_cost_cents' => (int) $eur->sum('amount_cents') - array_sum($costByEntry),
            'expense_categories' => $eur->groupBy('category')->map(fn ($items, $category) => ['category' => $category, 'count' => $items->count(), 'amount_cents' => (int) $items->sum('amount_cents')])->values(),
            'maintenance' => $maintenance,
            'operations' => ['tasks_total' => $event->tasks->count(), 'tasks_done' => $event->tasks->where('status', 'done')->count(),
                'tasks_open' => $event->tasks->where('status', '!=', 'done')->count(), 'notes' => $event->eventNotes->count(), 'maintenance_records' => $maintenance['records']->count()]];
    }

    /** @param Collection<int, Session> $sessions
     * @return array<string, int|null>
     */
    private function summary(Collection $sessions, int $cost): array
    {
        $distance = $sessions->map(fn (Session $s) => $this->metrics->distance($s));
        $runtime = $sessions->map(fn (Session $s) => $this->metrics->runtime($s));
        $meters = (int) $distance->sum();
        $seconds = (int) $runtime->sum();

        return ['sessions' => $sessions->count(), 'laps' => (int) $sessions->sum('completed_laps'), 'laps_known' => $sessions->whereNotNull('completed_laps')->count(),
            'distance_meters' => $meters, 'distance_known' => $distance->filter(fn ($v) => $v !== null)->count(),
            'duration_seconds' => $seconds, 'runtime_known' => $runtime->filter(fn ($v) => $v !== null)->count(), 'cost_cents' => $cost,
            'cost_per_km_cents' => $meters > 0 && $distance->every(fn ($v) => $v !== null) ? (int) round($cost / ($meters / 1000)) : null,
            'cost_per_hour_cents' => $seconds > 0 && $runtime->every(fn ($v) => $v !== null) ? (int) round($cost / ($seconds / 3600)) : null];
    }
}
