<section>
    <div class="mb-3 flex items-end justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'SALUTE COMPONENTI' : 'COMPONENT HEALTH' }}</p>
            <h2 class="mt-1 text-lg font-black text-pm-text">{{ $it ? 'Piani e soglie di utilizzo' : 'Schedules and usage thresholds' }}</h2>
        </div>
        <p class="hidden text-xs text-pm-muted sm:block">{{ $it ? 'I piani sul board restano sempre legati a questi contatori.' : 'Board jobs stay linked to these counters.' }}</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($schedules as $schedule)
            @php($state = $states[$schedule->id])
            <article class="pm-panel p-5 sm:p-6 {{ $state['status'] === 'overdue' ? 'border-pm-danger/30' : ($state['status'] === 'due_soon' ? 'border-pm-warning/30' : '') }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-pm-accent">{{ $schedule->tracker->component->name }}</p>
                        <h2 class="mt-1 font-black text-pm-text">{{ $schedule->name }}</h2>
                        <p class="mt-1 text-sm text-pm-text-secondary">{{ $schedule->tracker->metric->name }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <x-pitmetric.status-badge :label="$state['status']" :variant="match ($state['status']) { 'overdue' => 'danger', 'due_soon' => 'warning', 'ok' => 'success', default => 'neutral' }" />
                        @if (in_array($schedule->id, $activeWorkOrderScheduleIds, true))
                            <x-pitmetric.status-badge :label="$it ? 'Sul board' : 'On board'" variant="info" />
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Da service' : 'Since service' }}</p><p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($state['used'], $schedule->tracker->metric) }}</p></div>
                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Limite' : 'Limit' }}</p><p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($schedule->interval_value, $schedule->tracker->metric) }}</p></div>
                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $state['remaining'] < 0 ? ($it ? 'Oltre limite' : 'Over limit') : ($it ? 'Residuo' : 'Remaining') }}</p><p class="mt-2 font-mono font-bold {{ $state['remaining'] < 0 ? 'text-pm-danger' : 'text-pm-text' }}">{{ $formatUsage(abs($state['remaining']), $schedule->tracker->metric) }}</p></div>
                </div>

                <p class="mt-3 text-xs text-pm-muted">Lifetime: {{ $formatUsage($state['lifetime'], $schedule->tracker->metric) }}</p>

                <div class="mt-5 border-t border-pm-border pt-4">
                    <x-crud-modal id="complete-maintenance-{{ $schedule->id }}" :title="$it ? 'Registra intervento completato' : 'Record completed maintenance'" :description="$schedule->tracker->component->name.' · '.$schedule->name" :trigger="$it ? 'Completa direttamente' : 'Complete directly'" trigger-class="pm-ghost-button">
                        @can('team-write')<form method="POST" action="{{ route('maintenance.complete', $schedule) }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" name="performed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span><input class="pm-input" name="cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato automaticamente nei Costi' : 'Optional · automatically recorded in Expenses' }}</span></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Intervento' : 'Work performed' }}</span><input class="pm-input" name="description" required maxlength="180" value="{{ $schedule->name }}"></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                            <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Completa e registra' : 'Complete and record' }}</button></div>
                        </form>@endcan
                    </x-crud-modal>
                    <x-pitmetric.record-editor id="edit-schedule-{{ $schedule->id }}" :title="$it ? 'Modifica piano' : 'Edit schedule'" :action="route('maintenance.update', $schedule)">
                        @php($unitScale = match ($schedule->tracker->metric->key) { 'distance' => 1000, 'runtime' => 3600, default => 1 })
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome piano' : 'Schedule name' }}</span><input class="pm-input" name="name" type="text" value="{{ $schedule->name }}" required maxlength="120"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Intervallo' : 'Interval' }} ({{ $schedule->tracker->metric->display_unit }})</span><input class="pm-input" name="interval_display" type="number" value="{{ $schedule->interval_value / $unitScale }}" required min="0.001" max="1000000" step="any"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Preavviso' : 'Warning' }} ({{ $schedule->tracker->metric->display_unit }})</span><input class="pm-input" name="warning_display" type="number" value="{{ $schedule->warning_value === null ? '' : $schedule->warning_value / $unitScale }}" min="0" max="1000000" step="any"></label>
                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">Note</span><textarea class="pm-input min-h-24" name="notes" maxlength="2000">{{ $schedule->notes }}</textarea></label>
                    </x-pitmetric.record-editor>
                    <x-pitmetric.record-delete :action="route('maintenance.destroy', $schedule)" :label="__('crud.archive')" :message="__('crud.confirm_archive')" />
                </div>
            </article>
        @empty
            <x-pitmetric.empty-state :title="$it ? 'Nessun piano manutenzione' : 'No maintenance schedules'" :description="$it ? 'Crea un piano su una metrica tracciata per ricevere uno stato affidabile.' : 'Create a schedule on a tracked metric to get a reliable maintenance status.'" />
        @endforelse
    </div>
</section>

@if ($archivedSchedules->isNotEmpty())
    <details class="pm-panel pm-archive-list p-5"><summary>{{ $it ? 'Piani archiviati' : 'Archived schedules' }} ({{ $archivedSchedules->count() }})</summary>
        @foreach ($archivedSchedules as $schedule)<div class="pm-record-heading mt-3"><div><p>{{ $schedule->name }}</p><p class="text-sm text-pm-muted">{{ $schedule->tracker->component->name }}</p></div>@can('team-write')<form method="POST" action="{{ route('maintenance.restore', $schedule) }}">@csrf @method('PATCH')<button class="pm-row-action" type="submit">{{ __('crud.restore') }}</button></form>@endcan</div>@endforeach
    </details>
@endif

@if ($records->isNotEmpty())
    <section class="pm-panel p-5 sm:p-6">
        <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Storico recente' : 'Recent history' }}</h2>
        <div class="mt-4 space-y-3">
            @foreach ($records as $record)
                <div class="flex flex-col gap-1 rounded-xl border border-pm-border bg-pm-subtle p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-bold text-pm-text">{{ $record->component->name }} · {{ $record->description }}</p><p class="mt-1 text-xs text-pm-muted">{{ $record->performed_at->format('d/m/Y H:i') }}</p></div>
                    @if ($record->cost_cents !== null)
                        <div class="text-right"><p class="font-mono font-bold text-pm-text">€ {{ number_format($record->cost_cents / 100, 2, ',', '.') }}</p><p class="mt-1 text-[10px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'collegato ai costi' : 'linked to expenses' }}</p></div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
