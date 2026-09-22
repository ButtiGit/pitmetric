<div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <div>
        <x-pitmetric.page-header
            :title="$it ? 'Workboard manutenzione' : 'Maintenance workboard'"
            :description="$it ? 'Pianifica gli interventi, controlla le scadenze e registra i lavori eseguiti.' : 'Plan maintenance, check due dates and record completed work.'"
        />
    </div>

    <div class="hidden flex-wrap gap-2 sm:flex">
        <x-crud-modal id="create-maintenance-work-order" :title="$it ? 'Nuovo lavoro manutenzione' : 'New maintenance work order'" :description="$it ? 'Pianifica un intervento su un piano esistente e assegnalo al team.' : 'Plan work against an existing schedule and assign it to the team.'" :trigger="$it ? '+ Lavoro' : '+ Work order'">
            @can('team-write')<form method="POST" action="{{ route('maintenance.work-orders.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Piano manutenzione' : 'Maintenance schedule' }}</span><select class="pm-input" name="maintenance_schedule_id" required><option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>@foreach ($schedules as $schedule)<option value="{{ $schedule->id }}" @selected((string) old('maintenance_schedule_id') === (string) $schedule->id)>{{ $schedule->tracker->component->name }} · {{ $schedule->name }}</option>@endforeach</select></label>
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Titolo lavoro' : 'Work title' }}</span><input class="pm-input" name="title" required maxlength="180" value="{{ old('title') }}" placeholder="{{ $it ? 'Controllo e revisione' : 'Inspection and service' }}"></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Assegnato a' : 'Assignee' }}</span><select class="pm-input" name="assigned_to"><option value="">{{ $it ? 'Non assegnato' : 'Unassigned' }}</option>@foreach ($members as $member)<option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>{{ $member->name }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Priorità' : 'Priority' }}</span><select class="pm-input" name="priority" required>@foreach (['low', 'normal', 'high', 'critical'] as $priority)<option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>@endforeach</select></label>
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Scadenza' : 'Due date' }}</span><input class="pm-input" name="due_at" type="datetime-local" value="{{ old('due_at') }}"></label>
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note operative' : 'Work notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit" @disabled($schedules->isEmpty())>{{ $it ? 'Aggiungi al board' : 'Add to board' }}</button></div>
            </form>@endcan
        </x-crud-modal>

        <x-crud-modal id="create-maintenance-schedule" :title="$it ? 'Nuovo piano manutenzione' : 'New maintenance schedule'" :description="$it ? 'Definisci la soglia di utilizzo che alimenterà gli alert e il workboard.' : 'Define the usage threshold that will feed alerts and the workboard.'" :trigger="$it ? '+ Piano' : '+ Schedule'" trigger-class="pm-ghost-button">
            @can('team-write')<form method="POST" action="{{ route('maintenance.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Componente / metrica' : 'Component / metric' }}</span><select class="pm-input" name="component_tracker_id" required><option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>@foreach ($trackers as $tracker)<option value="{{ $tracker->id }}" @selected((string) old('component_tracker_id') === (string) $tracker->id)>{{ $tracker->component->name }} · {{ $tracker->metric->name }} ({{ $tracker->metric->display_unit }})</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome intervento' : 'Service name' }}</span><input class="pm-input" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Engine rebuild"></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Intervallo' : 'Interval' }}</span><input class="pm-input" name="interval_display" type="number" inputmode="decimal" required min="0.01" step="0.01" value="{{ old('interval_display') }}"></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Preavviso' : 'Warning before' }}</span><input class="pm-input" name="warning_display" type="number" inputmode="decimal" min="0" step="0.01" value="{{ old('warning_display') }}"></label>
                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit" @disabled($trackers->isEmpty())>{{ $it ? 'Crea piano' : 'Create schedule' }}</button></div>
            </form>@endcan
        </x-crud-modal>
    </div>
</div>

@if (session('status'))
    <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="pm-mobile-stat-strip grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ $it ? 'Riepilogo manutenzione' : 'Maintenance summary' }}">
    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Lavori aperti' : 'Open jobs' }}</p><p class="mt-3 text-3xl font-black text-pm-text">{{ $workSummary['open'] }}</p><p class="mt-1 text-xs text-pm-muted">{{ $workSummary['in_progress'] }} {{ $it ? 'in lavorazione' : 'in progress' }}</p></article>
    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Overdue</p><p class="mt-3 text-3xl font-black text-pm-danger">{{ $summary['overdue'] }}</p><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'piani oltre il limite' : 'schedules past limit' }}</p></article>
    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Due soon</p><p class="mt-3 text-3xl font-black text-pm-warning">{{ $summary['due_soon'] }}</p><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'richiedono pianificazione' : 'need planning' }}</p></article>
    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Bloccati' : 'Blocked' }}</p><p class="mt-3 text-3xl font-black {{ $workSummary['blocked'] > 0 ? 'text-pm-danger' : 'text-pm-success' }}">{{ $workSummary['blocked'] }}</p><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'lavori da sbloccare' : 'jobs needing attention' }}</p></article>
</section>
