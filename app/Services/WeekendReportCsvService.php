<?php

namespace App\Services;

use Carbon\CarbonInterface;

class WeekendReportCsvService
{
    /** @param resource $stream
     * @param  array<string, mixed>  $report
     */
    public function write($stream, array $report): void
    {
        $this->row($stream, ['PitMetric Weekend Report', $report['event']->name, $report['event']->id]);
        $this->row($stream, ['Start', $report['event']->start_date->toDateString(), 'End', $report['event']->end_date->toDateString()]);
        $this->row($stream, ['Generated Europe/Rome', $this->date($report['generated_at'])]);
        $this->row($stream, ['Finalized sessions', 'Laps', 'Distance meters', 'Runtime seconds', 'EUR ledger cost', 'Direct EUR', 'General EUR', 'Draft sessions', 'EUR per km', 'EUR per hour']);
        $summary = $report['summary'];
        $this->row($stream, [$summary['sessions'], $summary['laps_known'] ? $summary['laps'] : null, $summary['distance_known'] ? $summary['distance_meters'] : null,
            $summary['runtime_known'] ? $summary['duration_seconds'] : null, $report['eur_expense_count'] ? $summary['cost_cents'] / 100 : null,
            $report['direct_cost_cents'] / 100, $report['general_cost_cents'] / 100, $report['draft_count'], $report['eur_expense_count'] && $summary['cost_per_km_cents'] !== null ? $summary['cost_per_km_cents'] / 100 : null, $report['eur_expense_count'] && $summary['cost_per_hour_cents'] !== null ? $summary['cost_per_hour_cents'] / 100 : null]);
        $this->row($stream, ['Known distance sessions', $summary['distance_known'], 'Known runtime sessions', $summary['runtime_known'], 'Known lap sessions', $summary['laps_known']]);
        $this->row($stream, ['Entries']);
        $this->row($stream, ['Entry ID', 'Driver', 'Vehicle', 'Finalized sessions', 'Best valid lap ms (same layout)', 'Direct cost EUR', 'Laps', 'Distance meters', 'Runtime seconds']);
        foreach ($report['entries'] as $row) {
            $this->row($stream, [$row['entry']->id, $row['entry']->driver?->display_name, $row['entry']->vehicle?->name, $row['sessions'], $row['best_lap_ms'], $row['direct_cost_cents'] / 100, $row['laps_known'] ? $row['laps'] : null, $row['distance_known'] ? $row['distance_meters'] : null, $row['runtime_known'] ? $row['duration_seconds'] : null]);
        }
        $this->row($stream, ['Sessions']);
        $this->row($stream, ['Session ID', 'Date Europe/Rome', 'Status', 'Driver', 'Vehicle', 'Laps', 'Distance meters', 'Distance source', 'Runtime seconds', 'Best valid lap ms', 'Setup snapshot', 'Direct cost EUR', 'Notes']);
        foreach ($report['sessions'] as $row) {
            $s = $row['session'];
            $this->row($stream, [$s->id, $this->date($s->started_at), $s->status, $s->eventEntry?->driver?->display_name, $s->vehicle?->name,
                $s->completed_laps, $row['distance_meters'], $row['distance_source'], $row['runtime_seconds'], $row['best_lap']?->lap_time_ms,
                $s->setupSnapshot?->name, $row['cost_cents'] / 100, $s->notes]);
        }
        $this->row($stream, ['Session details']);
        $this->row($stream, ['Session ID', 'Section', 'Reference', 'Field', 'Value', 'Previous value', 'Notes']);
        foreach ($report['sessions'] as $row) {
            $s = $row['session'];
            foreach ($s->timingLaps as $lap) {
                $this->row($stream, [$s->id, 'Timing', $lap->lap_number, $lap->is_valid ? 'valid_ms' : 'invalid_ms', $lap->lap_time_ms, null, $lap->source]);
            }
            foreach ($row['setup_rows'] as $value) {
                $this->row($stream, [$s->id, 'Setup snapshot', $s->setupSnapshot?->id, $value['label'], $value['value']]);
            }
            foreach ($row['changes'] as $change) {
                $this->row($stream, [$s->id, 'Setup change', $row['baseline_id'], $change['label'], $change['after'], $change['before']]);
            }
            foreach ($s->tyreReadings as $reading) {
                foreach (['fl', 'fr', 'rl', 'rr'] as $corner) {
                    $this->row($stream, [$s->id, 'Pressure '.$reading->phase, $this->date($reading->measured_at), $corner.' bar', $reading->pressures[$corner] ?? null, null, $reading->notes]);
                }
            }
            foreach ($row['weather'] as $weather) {
                foreach ($weather['rows'] as $value) {
                    $this->row($stream, [$s->id, 'Weather '.$weather['kind'], $weather['valid_at'], $value['label'], $value['value'], null, $weather['mismatch'] ? __('prints.history_mismatch') : $weather['notes']]);
                }
            }
        }
        $this->row($stream, ['Tasks']);
        $this->row($stream, ['ID', 'Title', 'Status', 'Priority', 'Assignee', 'Due Europe/Rome', 'Completed Europe/Rome', 'Completed by', 'Notes']);
        foreach ($report['event']->tasks as $task) {
            $this->row($stream, [$task->id, $task->title, $task->status, $task->priority, $task->assignee?->name,
                $this->date($task->due_at), $this->date($task->completed_at), $task->completedBy?->name, $task->description]);
        }
        $this->row($stream, ['Maintenance']);
        $this->row($stream, ['ID', 'Performed Europe/Rome', 'Component', 'Serial', 'Historical vehicle', 'Recorded by', 'Description', 'Recorded cost EUR (not added to ledger)', 'Lifetime', 'Since previous reset', 'Resets', 'Notes']);
        $maintenance = $report['maintenance'];
        foreach ($maintenance['records'] as $record) {
            $metric = $record->schedule?->tracker?->metric;
            $this->row($stream, [$record->id, $this->date($record->performed_at), $record->component?->name, $record->component?->serial_number,
                $maintenance['historicalVehicles']->get($record->report_vehicle_id)?->name, $record->creator?->name, $record->description,
                $record->cost_cents === null ? null : $record->cost_cents / 100, $maintenance['report']->usage($record->report_lifetime, $metric),
                $maintenance['report']->usage($record->report_lifetime === null || $record->report_ambiguous_reset > 0 ? null : ($record->report_since_service ?? 0), $metric),
                $record->resetEvents->map(fn ($reset) => $this->date($reset->reset_at).' '.$reset->reason)->implode(' | '), $record->notes]);
        }
        $this->row($stream, ['Expenses']);
        $this->row($stream, ['ID', 'Date Europe/Rome', 'Category', 'Description', 'Amount', 'Currency', 'Source', 'Source ID']);
        foreach ($report['expenses'] as $expense) {
            $this->row($stream, [$expense->id, $this->date($expense->occurred_at), $expense->category, $expense->description, $expense->amount_cents / 100, $expense->currency, $expense->related_type, $expense->related_id]);
        }
        $this->row($stream, ['Notes', $report['event']->notes]);
        foreach ($report['event']->eventNotes as $note) {
            $this->row($stream, [$note->id, $this->date($note->occurred_at), $note->kind, $note->eventEntry?->driver?->display_name, $note->body]);
        }
    }

    private function date(?CarbonInterface $date): ?string
    {
        return $date?->copy()->timezone('Europe/Rome')->format('Y-m-d H:i');
    }

    /** @param resource $stream
     * @param  list<mixed>  $values
     */
    private function row($stream, array $values): void
    {
        $safe = array_map(static fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/u', $value) ? "'".$value : $value, $values);
        fputcsv($stream, $safe, ',', '"', '');
    }
}
