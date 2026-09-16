<x-layouts::app :title="__('Maintenance')">
    @php
        $it = app()->getLocale() === 'it';
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            $absolute = abs($value);
            $formatted = match ($metric->key) {
                'distance' => number_format($absolute / 1000, 1, ',', '.').' km',
                'runtime' => number_format($absolute / 3600, 1, ',', '.').' h',
                default => number_format($absolute, 0, ',', '.').' '.$metric->display_unit,
            };

            return $value < 0 ? '-'.$formatted : $formatted;
        };
        $boardColumns = [
            'todo' => $it ? 'Da fare' : 'To do',
            'in_progress' => $it ? 'In lavorazione' : 'In progress',
            'blocked' => $it ? 'Bloccati' : 'Blocked',
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1440px] space-y-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">MAINTENANCE</p>
                    <x-pitmetric.page-header
                        :title="$it ? 'Workboard manutenzione' : 'Maintenance workboard'"
                        :description="$it ? 'Trasforma gli alert di utilizzo in lavori assegnati, seguili fino al completamento e registra service e costi senza perdere lo storico.' : 'Turn usage alerts into assigned jobs, track them through completion, and record service and costs without losing history.'"
                    />
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-crud-modal
                        id="create-maintenance-work-order"
                        :title="$it ? 'Nuovo lavoro manutenzione' : 'New maintenance work order'"
                        :description="$it ? 'Pianifica un intervento su un piano esistente e assegnalo al team.' : 'Plan work against an existing schedule and assign it to the team.'"
                        :trigger="$it ? '+ Lavoro' : '+ Work order'"
                    >
                        <form method="POST" action="{{ route('maintenance.work-orders.store') }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Piano manutenzione' : 'Maintenance schedule' }}</span>
                                <select class="pm-input" name="maintenance_schedule_id" required>
                                    <option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id }}" @selected((string) old('maintenance_schedule_id') === (string) $schedule->id)>
                                            {{ $schedule->tracker->component->name }} · {{ $schedule->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Titolo lavoro' : 'Work title' }}</span>
                                <input class="pm-input" name="title" required maxlength="180" value="{{ old('title') }}" placeholder="{{ $it ? 'Controllo e revisione' : 'Inspection and service' }}">
                            </label>
                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Assegnato a' : 'Assignee' }}</span>
                                <select class="pm-input" name="assigned_to">
                                    <option value="">{{ $it ? 'Non assegnato' : 'Unassigned' }}</option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Priorità' : 'Priority' }}</span>
                                <select class="pm-input" name="priority" required>
                                    @foreach (['low', 'normal', 'high', 'critical'] as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Scadenza' : 'Due date' }}</span>
                                <input class="pm-input" name="due_at" type="datetime-local" value="{{ old('due_at') }}">
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Note operative' : 'Work notes' }}</span>
                                <textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                            </label>
                            <div class="md:col-span-2 flex justify-end">
                                <button class="pm-race-button" type="submit" @disabled($schedules->isEmpty())>{{ $it ? 'Aggiungi al board' : 'Add to board' }}</button>
                            </div>
                        </form>
                    </x-crud-modal>

                    <x-crud-modal
                        id="create-maintenance-schedule"
                        :title="$it ? 'Nuovo piano manutenzione' : 'New maintenance schedule'"
                        :description="$it ? 'Definisci la soglia di utilizzo che alimenterà gli alert e il workboard.' : 'Define the usage threshold that will feed alerts and the workboard.'"
                        :trigger="$it ? '+ Piano' : '+ Schedule'"
                        trigger-class="pm-ghost-button"
                    >
                        <form method="POST" action="{{ route('maintenance.store') }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Componente / metrica' : 'Component / metric' }}</span>
                                <select class="pm-input" name="component_tracker_id" required>
                                    <option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>
                                    @foreach ($trackers as $tracker)
                                        <option value="{{ $tracker->id }}" @selected((string) old('component_tracker_id') === (string) $tracker->id)>
                                            {{ $tracker->component->name }} · {{ $tracker->metric->name }} ({{ $tracker->metric->display_unit }})
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Nome intervento' : 'Service name' }}</span>
                                <input class="pm-input" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Engine rebuild">
                            </label>
                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Intervallo' : 'Interval' }}</span>
                                <input class="pm-input" name="interval_display" type="number" required min="0.01" step="0.01" value="{{ old('interval_display') }}">
                            </label>
                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Preavviso' : 'Warning before' }}</span>
                                <input class="pm-input" name="warning_display" type="number" min="0" step="0.01" value="{{ old('warning_display') }}">
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span>
                                <textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                            </label>
                            <div class="md:col-span-2 flex justify-end">
                                <button class="pm-race-button" type="submit" @disabled($trackers->isEmpty())>{{ $it ? 'Crea piano' : 'Create schedule' }}</button>
                            </div>
                        </form>
                    </x-crud-modal>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="pm-stat-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Lavori aperti' : 'Open jobs' }}</p>
                    <p class="mt-3 text-3xl font-black text-pm-text">{{ $workSummary['open'] }}</p>
                    <p class="mt-1 text-xs text-pm-muted">{{ $workSummary['in_progress'] }} {{ $it ? 'in lavorazione' : 'in progress' }}</p>
                </article>
                <article class="pm-stat-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Overdue</p>
                    <p class="mt-3 text-3xl font-black text-pm-danger">{{ $summary['overdue'] }}</p>
                    <p class="mt-1 text-xs text-pm-muted">{{ $it ? 'piani oltre il limite' : 'schedules past limit' }}</p>
                </article>
                <article class="pm-stat-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Due soon</p>
                    <p class="mt-3 text-3xl font-black text-pm-warning">{{ $summary['due_soon'] }}</p>
                    <p class="mt-1 text-xs text-pm-muted">{{ $it ? 'richiedono pianificazione' : 'need planning' }}</p>
                </article>
                <article class="pm-stat-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Bloccati' : 'Blocked' }}</p>
                    <p class="mt-3 text-3xl font-black {{ $workSummary['blocked'] > 0 ? 'text-pm-danger' : 'text-pm-success' }}">{{ $workSummary['blocked'] }}</p>
                    <p class="mt-1 text-xs text-pm-muted">{{ $it ? 'lavori da sbloccare' : 'jobs needing attention' }}</p>
                </article>
            </section>

            <section class="pm-panel p-4 sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">WORKBOARD</p>
                        <h2 class="mt-1 text-lg font-black text-pm-text">{{ $it ? 'Coda operativa manutenzione' : 'Maintenance operations queue' }}</h2>
                    </div>
                    <p class="text-xs text-pm-muted">{{ $it ? 'Il completamento chiude il lavoro e resetta il contatore del piano.' : 'Completion closes the job and resets the schedule counter.' }}</p>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-3">
                    @foreach ($boardColumns as $statusKey => $columnLabel)
                        @php
                            $columnOrders = $workOrders->where('status', $statusKey);
                            $columnVariant = match ($statusKey) {
                                'blocked' => 'danger',
                                'in_progress' => 'info',
                                default => 'neutral',
                            };
                        @endphp
                        <div class="rounded-2xl border border-pm-border bg-pm-subtle/40 p-3">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <x-pitmetric.status-badge :label="$columnLabel" :variant="$columnVariant" />
                                    <span class="font-mono text-xs font-bold text-pm-muted">{{ $columnOrders->count() }}</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @forelse ($columnOrders as $workOrder)
                                    @php
                                        $workState = $states[$workOrder->maintenance_schedule_id] ?? null;
                                        $priorityVariant = match ($workOrder->priority) {
                                            'critical' => 'danger',
                                            'high' => 'warning',
                                            'normal' => 'info',
                                            default => 'neutral',
                                        };
                                        $dueLate = $workOrder->due_at !== null && $workOrder->due_at->isPast();
                                    @endphp
                                    <article class="rounded-xl border border-pm-border bg-pm-panel p-4 shadow-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-[11px] font-bold uppercase tracking-[0.1em] text-pm-accent">{{ $workOrder->schedule->tracker->component->name }}</p>
                                                <h3 class="mt-1 break-words font-black text-pm-text">{{ $workOrder->title }}</h3>
                                                <p class="mt-1 text-xs text-pm-muted">{{ $workOrder->schedule->name }}</p>
                                            </div>
                                            <x-pitmetric.status-badge :label="$workOrder->priority" :variant="$priorityVariant" />
                                        </div>

                                        @if ($workState !== null)
                                            <div class="mt-3 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2">
                                                <div class="flex items-center justify-between gap-3 text-xs">
                                                    <span class="text-pm-muted">{{ $it ? 'Utilizzo' : 'Usage' }}</span>
                                                    <span class="font-mono font-bold {{ $workState['status'] === 'overdue' ? 'text-pm-danger' : ($workState['status'] === 'due_soon' ? 'text-pm-warning' : 'text-pm-text') }}">
                                                        {{ $formatUsage($workState['used'], $workOrder->schedule->tracker->metric) }} / {{ $formatUsage($workOrder->schedule->interval_value, $workOrder->schedule->tracker->metric) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif

                                        <dl class="mt-3 grid gap-2 text-xs">
                                            <div class="flex items-center justify-between gap-3">
                                                <dt class="text-pm-muted">{{ $it ? 'Assegnato' : 'Assignee' }}</dt>
                                                <dd class="font-semibold text-pm-text-secondary">{{ $workOrder->assignee?->name ?? ($it ? 'Non assegnato' : 'Unassigned') }}</dd>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <dt class="text-pm-muted">{{ $it ? 'Scadenza' : 'Due' }}</dt>
                                                <dd class="font-mono font-semibold {{ $dueLate ? 'text-pm-danger' : 'text-pm-text-secondary' }}">
                                                    {{ $workOrder->due_at?->format('d/m/Y H:i') ?? '-' }}
                                                </dd>
                                            </div>
                                        </dl>

                                        @if ($workOrder->notes)
                                            <p class="mt-3 line-clamp-3 text-xs leading-5 text-pm-text-secondary">{{ $workOrder->notes }}</p>
                                        @endif

                                        <div class="mt-4 flex flex-wrap gap-2 border-t border-pm-border pt-3">
                                            <x-crud-modal
                                                id="edit-maintenance-work-order-{{ $workOrder->id }}"
                                                :title="$it ? 'Aggiorna lavoro' : 'Update work order'"
                                                :description="$workOrder->schedule->tracker->component->name.' · '.$workOrder->schedule->name"
                                                :trigger="$it ? 'Aggiorna' : 'Update'"
                                                trigger-class="pm-ghost-button"
                                            >
                                                <form method="POST" action="{{ route('maintenance.work-orders.update', $workOrder) }}" class="grid gap-4 md:grid-cols-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="grid gap-2 md:col-span-2">
                                                        <span class="pm-label">{{ $it ? 'Titolo' : 'Title' }}</span>
                                                        <input class="pm-input" name="title" required maxlength="180" value="{{ $workOrder->title }}">
                                                    </label>
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Stato' : 'Status' }}</span>
                                                        <select class="pm-input" name="status" required>
                                                            <option value="todo" @selected($workOrder->status === 'todo')>{{ $it ? 'Da fare' : 'To do' }}</option>
                                                            <option value="in_progress" @selected($workOrder->status === 'in_progress')>{{ $it ? 'In lavorazione' : 'In progress' }}</option>
                                                            <option value="blocked" @selected($workOrder->status === 'blocked')>{{ $it ? 'Bloccato' : 'Blocked' }}</option>
                                                            <option value="cancelled">{{ $it ? 'Annullato' : 'Cancelled' }}</option>
                                                        </select>
                                                    </label>
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Priorità' : 'Priority' }}</span>
                                                        <select class="pm-input" name="priority" required>
                                                            @foreach (['low', 'normal', 'high', 'critical'] as $priority)
                                                                <option value="{{ $priority }}" @selected($workOrder->priority === $priority)>{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Assegnato a' : 'Assignee' }}</span>
                                                        <select class="pm-input" name="assigned_to">
                                                            <option value="">{{ $it ? 'Non assegnato' : 'Unassigned' }}</option>
                                                            @foreach ($members as $member)
                                                                <option value="{{ $member->id }}" @selected((string) $workOrder->assigned_to === (string) $member->id)>{{ $member->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Scadenza' : 'Due date' }}</span>
                                                        <input class="pm-input" name="due_at" type="datetime-local" value="{{ $workOrder->due_at?->format('Y-m-d\TH:i') }}">
                                                    </label>
                                                    <label class="grid gap-2 md:col-span-2">
                                                        <span class="pm-label">{{ $it ? 'Note operative' : 'Work notes' }}</span>
                                                        <textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $workOrder->notes }}</textarea>
                                                    </label>
                                                    <div class="md:col-span-2 flex justify-end">
                                                        <button class="pm-race-button" type="submit">{{ $it ? 'Salva' : 'Save' }}</button>
                                                    </div>
                                                </form>
                                            </x-crud-modal>

                                            <x-crud-modal
                                                id="complete-maintenance-work-order-{{ $workOrder->id }}"
                                                :title="$it ? 'Completa lavoro e registra service' : 'Complete work and record service'"
                                                :description="$workOrder->schedule->tracker->component->name.' · '.$workOrder->title"
                                                :trigger="$it ? 'Completa' : 'Complete'"
                                            >
                                                <form method="POST" action="{{ route('maintenance.work-orders.complete', $workOrder) }}" class="grid gap-4 sm:grid-cols-2">
                                                    @csrf
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span>
                                                        <input class="pm-input" name="performed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}">
                                                    </label>
                                                    <label class="grid gap-2">
                                                        <span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span>
                                                        <input class="pm-input" name="cost" type="number" min="0" max="1000000" step="0.01">
                                                        <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · verrà registrato nei Costi' : 'Optional · will be recorded in Expenses' }}</span>
                                                    </label>
                                                    <label class="grid gap-2 sm:col-span-2">
                                                        <span class="pm-label">{{ $it ? 'Intervento eseguito' : 'Work performed' }}</span>
                                                        <input class="pm-input" name="description" required maxlength="180" value="{{ $workOrder->title }}">
                                                    </label>
                                                    <label class="grid gap-2 sm:col-span-2">
                                                        <span class="pm-label">{{ $it ? 'Note finali' : 'Completion notes' }}</span>
                                                        <textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea>
                                                    </label>
                                                    <div class="sm:col-span-2 flex justify-end">
                                                        <button class="pm-race-button" type="submit">{{ $it ? 'Completa e registra' : 'Complete and record' }}</button>
                                                    </div>
                                                </form>
                                            </x-crud-modal>
                                        </div>
                                    </article>
                                @empty
                                    <div class="rounded-xl border border-dashed border-pm-border px-4 py-8 text-center">
                                        <p class="text-sm font-semibold text-pm-text-secondary">{{ $it ? 'Nessun lavoro in questa colonna' : 'No jobs in this column' }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

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
                                    <x-pitmetric.status-badge
                                        :label="$state['status']"
                                        :variant="match ($state['status']) { 'overdue' => 'danger', 'due_soon' => 'warning', 'ok' => 'success', default => 'neutral' }"
                                    />
                                    @if (in_array($schedule->id, $activeWorkOrderScheduleIds, true))
                                        <x-pitmetric.status-badge :label="$it ? 'Sul board' : 'On board'" variant="info" />
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-3">
                                <div class="rounded-xl border border-pm-border bg-pm-subtle p-3">
                                    <p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Da service' : 'Since service' }}</p>
                                    <p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($state['used'], $schedule->tracker->metric) }}</p>
                                </div>
                                <div class="rounded-xl border border-pm-border bg-pm-subtle p-3">
                                    <p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Limite' : 'Limit' }}</p>
                                    <p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($schedule->interval_value, $schedule->tracker->metric) }}</p>
                                </div>
                                <div class="rounded-xl border border-pm-border bg-pm-subtle p-3">
                                    <p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $state['remaining'] < 0 ? ($it ? 'Oltre limite' : 'Over limit') : ($it ? 'Residuo' : 'Remaining') }}</p>
                                    <p class="mt-2 font-mono font-bold {{ $state['remaining'] < 0 ? 'text-pm-danger' : 'text-pm-text' }}">{{ $formatUsage(abs($state['remaining']), $schedule->tracker->metric) }}</p>
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-pm-muted">Lifetime: {{ $formatUsage($state['lifetime'], $schedule->tracker->metric) }}</p>

                            <div class="mt-5 border-t border-pm-border pt-4">
                                <x-crud-modal
                                    id="complete-maintenance-{{ $schedule->id }}"
                                    :title="$it ? 'Registra intervento completato' : 'Record completed maintenance'"
                                    :description="$schedule->tracker->component->name.' · '.$schedule->name"
                                    :trigger="$it ? 'Completa direttamente' : 'Complete directly'"
                                    trigger-class="pm-ghost-button"
                                >
                                    <form method="POST" action="{{ route('maintenance.complete', $schedule) }}" class="grid gap-4 sm:grid-cols-2">
                                        @csrf
                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span>
                                            <input class="pm-input" name="performed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}">
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span>
                                            <input class="pm-input" name="cost" type="number" min="0" max="1000000" step="0.01">
                                            <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato automaticamente nei Costi' : 'Optional · automatically recorded in Expenses' }}</span>
                                        </label>
                                        <label class="grid gap-2 sm:col-span-2">
                                            <span class="pm-label">{{ $it ? 'Intervento' : 'Work performed' }}</span>
                                            <input class="pm-input" name="description" required maxlength="180" value="{{ $schedule->name }}">
                                        </label>
                                        <label class="grid gap-2 sm:col-span-2">
                                            <span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span>
                                            <textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea>
                                        </label>
                                        <div class="sm:col-span-2 flex justify-end">
                                            <button class="pm-race-button" type="submit">{{ $it ? 'Completa e registra' : 'Complete and record' }}</button>
                                        </div>
                                    </form>
                                </x-crud-modal>
                            </div>
                        </article>
                    @empty
                        <x-pitmetric.empty-state
                            :title="$it ? 'Nessun piano manutenzione' : 'No maintenance schedules'"
                            :description="$it ? 'Crea un piano su una metrica tracciata per ricevere uno stato affidabile.' : 'Create a schedule on a tracked metric to get a reliable maintenance status.'"
                        />
                    @endforelse
                </div>
            </section>

            @if ($records->isNotEmpty())
                <section class="pm-panel p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Storico recente' : 'Recent history' }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($records as $record)
                            <div class="flex flex-col gap-1 rounded-xl border border-pm-border bg-pm-subtle p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold text-pm-text">{{ $record->component->name }} · {{ $record->description }}</p>
                                    <p class="mt-1 text-xs text-pm-muted">{{ $record->performed_at->format('d/m/Y H:i') }}</p>
                                </div>
                                @if ($record->cost_cents !== null)
                                    <div class="text-right">
                                        <p class="font-mono font-bold text-pm-text">€ {{ number_format($record->cost_cents / 100, 2, ',', '.') }}</p>
                                        <p class="mt-1 text-[10px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'collegato ai costi' : 'linked to expenses' }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
