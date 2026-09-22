<section class="pm-panel p-4 sm:p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Interventi' : 'Jobs' }}</p>
            <h2 class="mt-1 text-lg font-black text-pm-text">{{ $it ? 'Coda operativa manutenzione' : 'Maintenance operations queue' }}</h2>
        </div>
        <p class="text-xs text-pm-muted">{{ $it ? 'Scorri le corsie e completa il lavoro direttamente dal telefono.' : 'Swipe through lanes and complete work directly from your phone.' }}</p>
    </div>

    <div class="pm-mobile-lanes mt-4 grid gap-4 xl:grid-cols-3" data-pm-mobile-lanes>
        @foreach ($boardColumns as $statusKey => $columnLabel)
            @php
                $columnOrders = $workOrders->where('status', $statusKey);
                $columnVariant = match ($statusKey) {
                    'blocked' => 'danger',
                    'in_progress' => 'info',
                    default => 'neutral',
                };
            @endphp
            <div class="pm-mobile-lane rounded-2xl border border-pm-border bg-pm-subtle/40 p-3" data-pm-maintenance-lane="{{ $statusKey }}">
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
                        <article class="rounded-xl border border-pm-border bg-pm-panel p-4 shadow-sm" data-pm-work-order="{{ $workOrder->id }}">
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
                                <div class="flex items-center justify-between gap-3"><dt class="text-pm-muted">{{ $it ? 'Assegnato' : 'Assignee' }}</dt><dd class="font-semibold text-pm-text-secondary">{{ $workOrder->assignee?->name ?? ($it ? 'Non assegnato' : 'Unassigned') }}</dd></div>
                                <div class="flex items-center justify-between gap-3"><dt class="text-pm-muted">{{ $it ? 'Scadenza' : 'Due' }}</dt><dd class="font-mono font-semibold {{ $dueLate ? 'text-pm-danger' : 'text-pm-text-secondary' }}">{{ $workOrder->due_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                            </dl>

                            @if ($workOrder->notes)
                                <p class="mt-3 line-clamp-3 text-xs leading-5 text-pm-text-secondary">{{ $workOrder->notes }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2 border-t border-pm-border pt-3">
                                <x-crud-modal id="edit-maintenance-work-order-{{ $workOrder->id }}" :title="$it ? 'Aggiorna lavoro' : 'Update work order'" :description="$workOrder->schedule->tracker->component->name.' · '.$workOrder->schedule->name" :trigger="$it ? 'Aggiorna' : 'Update'" trigger-class="pm-ghost-button">
                                    @can('team-write')<form method="POST" action="{{ route('maintenance.work-orders.update', $workOrder) }}" class="grid gap-4 md:grid-cols-2">
                                        @csrf @method('PATCH')
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Titolo' : 'Title' }}</span><input class="pm-input" name="title" required maxlength="180" value="{{ $workOrder->title }}"></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Stato' : 'Status' }}</span><select class="pm-input" name="status" required><option value="todo" @selected($workOrder->status === 'todo')>{{ $it ? 'Da fare' : 'To do' }}</option><option value="in_progress" @selected($workOrder->status === 'in_progress')>{{ $it ? 'In lavorazione' : 'In progress' }}</option><option value="blocked" @selected($workOrder->status === 'blocked')>{{ $it ? 'Bloccato' : 'Blocked' }}</option><option value="cancelled">{{ $it ? 'Annullato' : 'Cancelled' }}</option></select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Priorità' : 'Priority' }}</span><select class="pm-input" name="priority" required>@foreach (['low', 'normal', 'high', 'critical'] as $priority)<option value="{{ $priority }}" @selected($workOrder->priority === $priority)>{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>@endforeach</select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Assegnato a' : 'Assignee' }}</span><select class="pm-input" name="assigned_to"><option value="">{{ $it ? 'Non assegnato' : 'Unassigned' }}</option>@foreach ($members as $member)<option value="{{ $member->id }}" @selected((string) $workOrder->assigned_to === (string) $member->id)>{{ $member->name }}</option>@endforeach</select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Scadenza' : 'Due date' }}</span><input class="pm-input" name="due_at" type="datetime-local" value="{{ $workOrder->due_at?->format('Y-m-d\TH:i') }}"></label>
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note operative' : 'Work notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $workOrder->notes }}</textarea></label>
                                        <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva' : 'Save' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>

                                <x-crud-modal id="complete-maintenance-work-order-{{ $workOrder->id }}" :title="$it ? 'Completa lavoro e registra service' : 'Complete work and record service'" :description="$workOrder->schedule->tracker->component->name.' · '.$workOrder->title" :trigger="$it ? 'Completa' : 'Complete'">
                                    @can('team-write')<form method="POST" action="{{ route('maintenance.work-orders.complete', $workOrder) }}" class="grid gap-4 sm:grid-cols-2">
                                        @csrf
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" name="performed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span><input class="pm-input" name="cost" type="number" inputmode="decimal" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · verrà registrato nei Costi' : 'Optional · will be recorded in Expenses' }}</span></label>
                                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Intervento eseguito' : 'Work performed' }}</span><input class="pm-input" name="description" required maxlength="180" value="{{ $workOrder->title }}"></label>
                                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note finali' : 'Completion notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                                        <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Completa e registra' : 'Complete and record' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>
                                <x-pitmetric.record-delete :action="route('maintenance.work-orders.destroy', $workOrder)" :label="__('crud.cancel_work')" :message="$it ? 'Annullare questo lavoro di manutenzione?' : 'Cancel this maintenance work order?'" />
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-pm-border px-4 py-8 text-center"><p class="text-sm font-semibold text-pm-text-secondary">{{ $it ? 'Nessun lavoro in questa colonna' : 'No jobs in this column' }}</p></div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</section>
