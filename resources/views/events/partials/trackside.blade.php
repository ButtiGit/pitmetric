<div class="space-y-5">
    <section class="pm-panel p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Programma Trackside' : 'Trackside schedule' }}</h2>
                <p class="mt-1 text-sm text-pm-muted">{{ $it ? 'Quello che deve succedere dopo, non solo quello che è già successo.' : 'What needs to happen next, not only what already happened.' }}</p>
            </div>

            @if ($canWrite)
                <x-crud-modal id="add-schedule-item" :title="$it ? 'Pianifica sessione' : 'Schedule session'" :description="$it ? 'Aggiungi la prossima attività del weekend e assegnala a una entry quando serve.' : 'Add the next weekend activity and assign it to an entry when needed.'" :trigger="$it ? '+ Pianifica' : '+ Schedule'">
                    @can('team-write')<form method="POST" action="{{ route('events.schedule.store', $event) }}" class="grid gap-4 md:grid-cols-2">
                        @csrf
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo sessione' : 'Session type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="heat">Heat</option><option value="prefinal">Prefinal</option><option value="final">Final</option><option value="race">Race</option><option value="test">Test</option></select></label>
                        <label class="grid gap-2"><span class="pm-label">Entry</span><select class="pm-input" name="event_entry_id"><option value="">{{ $it ? 'Attività generale team' : 'Whole-team activity' }}</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }} · {{ $entry->vehicle->name }}</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora previste' : 'Planned date and time' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ $defaultSessionAt->format('Y-m-d\TH:i') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata prevista (min)' : 'Planned duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="1" max="1440"></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Etichetta' : 'Label' }}</span><input class="pm-input" name="label" maxlength="120" placeholder="{{ $it ? 'Es. Qualifica gruppo B' : 'e.g. Qualifying group B' }}"></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note operative' : 'Operational notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                        <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi al programma' : 'Add to schedule' }}</button></div>
                    </form>@endcan
                </x-crud-modal>
            @endif
        </div>

        @if ($nextScheduleItem)
            <div class="mt-5 rounded-2xl border {{ $nextScheduleItem->status === 'live' ? 'border-pm-danger/40 bg-pm-danger-subtle' : 'border-pm-accent/35 bg-pm-accent/5' }} p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2"><span class="text-xs font-black uppercase tracking-[0.14em] text-pm-accent">{{ $nextScheduleItem->status === 'live' ? ($it ? 'IN PISTA' : 'ON TRACK') : ($it ? 'PROSSIMA' : 'NEXT') }}</span><x-pitmetric.status-badge :label="$nextScheduleItem->status" :variant="$scheduleStatusVariant($nextScheduleItem->status)" /></div>
                        <p class="mt-2 text-2xl font-black text-pm-text">{{ $nextScheduleItem->starts_at->format('H:i') }} · {{ $nextScheduleItem->label ?: ucfirst($nextScheduleItem->session_type) }}</p>
                        <p class="mt-1 text-sm text-pm-text-secondary">{{ $nextScheduleItem->starts_at->format('d/m/Y') }}{{ $nextScheduleItem->duration_minutes ? ' · '.$nextScheduleItem->duration_minutes.' min' : '' }}{{ $nextScheduleItem->eventEntry ? ' · '.$nextScheduleItem->eventEntry->driver->display_name.' / '.$nextScheduleItem->eventEntry->vehicle->name : ' · Team' }}</p>
                    </div>
                    @if ($canWrite && $nextScheduleItem->eventEntry && ! in_array($nextScheduleItem->status, ['completed', 'cancelled'], true))
                        @php($nextEntryVersions = $versions->filter(fn ($version) => (int) $version->configuration->vehicle_id === (int) $nextScheduleItem->eventEntry->vehicle_id))
                        <x-crud-modal id="record-next-schedule" :title="$it ? 'Registra sessione completata' : 'Record completed session'" :description="($nextScheduleItem->eventEntry->driver->display_name).' · '.ucfirst($nextScheduleItem->session_type)" :trigger="$it ? 'Registra risultato' : 'Record result'">
                            @can('team-write')<form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2">
                                @csrf
                                <input type="hidden" name="event_id" value="{{ $event->id }}"><input type="hidden" name="event_entry_id" value="{{ $nextScheduleItem->event_entry_id }}"><input type="hidden" name="schedule_item_id" value="{{ $nextScheduleItem->id }}"><input type="hidden" name="session_type" value="{{ $nextScheduleItem->session_type }}">
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione usata' : 'Configuration used' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required>@foreach ($nextEntryVersions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}" @selected($version->id === $nextScheduleItem->eventEntry->configuration_version_id)>{{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Partenza effettiva' : 'Actual start' }}</span><input class="pm-input" type="datetime-local" name="started_at" required value="{{ $nextScheduleItem->starts_at->format('Y-m-d\TH:i') }}"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri' : 'Laps' }}</span><input class="pm-input" type="number" name="completed_laps" min="0" max="10000"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata effettiva (min)' : 'Actual duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="0" max="1440" step="0.1" value="{{ $nextScheduleItem->duration_minutes }}"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo sessione (€)' : 'Session cost (€)' }}</span><input class="pm-input" type="number" name="session_cost" min="0" max="1000000" step="0.01"></label>
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note sessione' : 'Session notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000"></textarea></label>
                                <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva e chiudi attività' : 'Save and complete activity' }}</button></div>
                            </form>@endcan
                        </x-crud-modal>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-5 space-y-2">
            @forelse ($sortedSchedule as $item)
                <article class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-sm font-black text-pm-text">{{ $item->starts_at->format('d/m H:i') }}</span><x-pitmetric.status-badge :label="$item->status" :variant="$scheduleStatusVariant($item->status)" /></div>
                            <p class="mt-1 font-bold text-pm-text">{{ $item->label ?: ucfirst($item->session_type) }}</p>
                            <p class="mt-1 text-xs text-pm-muted">{{ ucfirst($item->session_type) }}{{ $item->duration_minutes ? ' · '.$item->duration_minutes.' min' : '' }}{{ $item->eventEntry ? ' · '.$item->eventEntry->driver->display_name.' / '.$item->eventEntry->vehicle->name : ' · Team' }}{{ $item->session_id ? ' · '.($it ? 'sessione registrata' : 'session recorded') : '' }}</p>
                            @if ($item->notes)<p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $item->notes }}</p>@endif
                        </div>

                        @if ($canWrite)
                            <x-crud-modal id="edit-schedule-{{ $item->id }}" :title="$it ? 'Aggiorna attività' : 'Update activity'" :description="$item->label ?: ucfirst($item->session_type)" :trigger="$it ? 'Gestisci' : 'Manage'" trigger-class="pm-ghost-button">
                                @can('team-write')<form method="POST" action="{{ route('events.schedule.update', $item) }}" class="grid gap-4 md:grid-cols-2">
                                    @csrf @method('PATCH')
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><select class="pm-input" name="session_type" required>@foreach (['practice','qualifying','heat','prefinal','final','race','test'] as $type)<option value="{{ $type }}" @selected($item->session_type === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
                                    <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status" required>@foreach (['planned','ready','live','completed','cancelled'] as $status)<option value="{{ $status }}" @selected($item->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                                    <label class="grid gap-2"><span class="pm-label">Entry</span><select class="pm-input" name="event_entry_id"><option value="">Team</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}" @selected($item->event_entry_id === $entry->id)>{{ $entry->driver->display_name }} · {{ $entry->vehicle->name }}</option>@endforeach</select></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Ora prevista' : 'Planned time' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ $item->starts_at->format('Y-m-d\TH:i') }}"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="1" max="1440" value="{{ $item->duration_minutes }}"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Etichetta' : 'Label' }}</span><input class="pm-input" name="label" maxlength="120" value="{{ $item->label }}"></label>
                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $item->notes }}</textarea></label>
                                    <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva' : 'Save' }}</button></div>
                                </form>@endcan
                                @if (! $item->session_id)
                                    @can('team-write')<form method="POST" action="{{ route('events.schedule.destroy', $item) }}" class="mt-5 border-t border-pm-border pt-4" onsubmit="return confirm(@js($it ? 'Rimuovere questa attività dal programma?' : 'Remove this activity from the schedule?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline" type="submit">{{ $it ? 'Rimuovi dal programma' : 'Remove from schedule' }}</button></form>@endcan
                                @endif
                            </x-crud-modal>
                        @endif
                    </div>
                </article>
            @empty
                <x-pitmetric.empty-state :title="$it ? 'Programma vuoto' : 'Schedule is empty'" :description="$it ? 'Pianifica le sessioni prima del weekend per vedere sempre cosa viene dopo.' : 'Schedule sessions before the weekend so the team always knows what comes next.'" />
            @endforelse
        </div>
    </section>

    <section class="pm-panel p-5 sm:p-6">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">ENTRIES</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Piloti e mezzi' : 'Drivers and vehicles' }}</h2></div><span class="text-sm text-pm-muted">{{ $event->entries->count() }}</span></div>
        <div class="mt-5 grid gap-4 xl:grid-cols-2">
            @forelse ($event->entries as $entry)
                @php($entryVersions = $versions->filter(fn ($version) => (int) $version->configuration->vehicle_id === (int) $entry->vehicle_id))
                <article class="rounded-xl border border-pm-border bg-pm-subtle p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-4"><div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-pm-text">{{ $entry->driver->display_name }}</h3>@if ($entry->entry_number)<span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs font-bold text-pm-text">#{{ $entry->entry_number }}</span>@endif</div><p class="mt-1 text-sm font-semibold text-pm-text-secondary">{{ $entry->vehicle->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $entry->configurationVersion->configuration->name }} v{{ $entry->configurationVersion->version_number }} · {{ $entry->configurationVersion->components->count() }} {{ $it ? 'componenti' : 'components' }}</p></div><span class="text-xs font-bold text-pm-muted">{{ $entry->sessions->count() }} {{ $it ? 'sessioni' : 'sessions' }}</span></div>
                    @if ($entry->notes)<p class="mt-3 text-xs leading-5 text-pm-text-secondary">{{ $entry->notes }}</p>@endif
                    <div class="pm-record-actions"><x-pitmetric.record-editor id="edit-entry-{{ $entry->id }}" :title="$it ? 'Modifica iscrizione' : 'Edit entry'" :action="route('events.entries.update', $entry)">
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Numero gara' : 'Entry number' }}</span><input class="pm-input" name="entry_number" type="text" value="{{ $entry->entry_number }}" maxlength="20"></label>
                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">Note</span><textarea class="pm-input min-h-24" name="notes" maxlength="2000">{{ $entry->notes }}</textarea></label>
                    </x-pitmetric.record-editor><x-pitmetric.record-delete :action="route('events.entries.destroy', $entry)" /></div>

                    @if ($canWrite)
                        <div class="mt-4">
                            <x-crud-modal id="entry-session-{{ $entry->id }}" :title="$it ? 'Registra sessione non pianificata' : 'Record unscheduled session'" :description="$entry->driver->display_name.' · '.$entry->vehicle->name" :trigger="$it ? '+ Sessione' : '+ Session'" trigger-class="pm-ghost-button">
                                @can('team-write')<form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2">
                                    @csrf
                                    <input type="hidden" name="event_id" value="{{ $event->id }}"><input type="hidden" name="event_entry_id" value="{{ $entry->id }}">
                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione' : 'Configuration' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required>@foreach ($entryVersions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}" @selected($version->id === $entry->configuration_version_id)>{{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="heat">Heat</option><option value="prefinal">Prefinal</option><option value="final">Final</option><option value="race">Race</option><option value="test">Test</option></select></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="started_at" required value="{{ $defaultSessionAt->format('Y-m-d\TH:i') }}"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri' : 'Laps' }}</span><input class="pm-input" type="number" name="completed_laps" min="0" max="10000"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="0" max="1440" step="0.1"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span><input class="pm-input" type="number" name="session_cost" min="0" max="1000000" step="0.01"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180"></label>
                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000"></textarea></label>
                                    <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Registra sessione' : 'Record session' }}</button></div>
                                </form>@endcan
                            </x-crud-modal>
                        </div>
                    @endif
                </article>
            @empty
                <div class="xl:col-span-2"><x-pitmetric.empty-state :title="$it ? 'Nessuna entry' : 'No entries'" :description="$it ? 'Aggiungi pilota, mezzo e configurazione per iniziare il weekend.' : 'Add a driver, vehicle and configuration to start the weekend.'" /></div>
            @endforelse
        </div>
    </section>

    <section class="pm-panel p-5 sm:p-6">
        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">SESSION HISTORY</p>
        <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Cronologia reale' : 'Actual session history' }}</h2>
        <div class="mt-5 space-y-3">
            @forelse ($sortedSessions as $sessionItem)
                <article class="rounded-xl border {{ (string) request('recorded') === (string) $sessionItem->id ? 'border-pm-accent/50' : 'border-pm-border' }} bg-pm-subtle p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"><div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-pm-text">{{ ucfirst($sessionItem->session_type) }} · {{ $sessionItem->eventEntry?->driver?->display_name ?? $sessionItem->vehicle->name }}</h3><x-pitmetric.status-badge :label="$sessionItem->status" variant="success" /></div><p class="mt-1 text-sm text-pm-text-secondary">{{ $sessionItem->vehicle->name }} · {{ $sessionItem->configurationVersion->configuration->name }} v{{ $sessionItem->configurationVersion->version_number }}</p><p class="mt-1 text-xs text-pm-muted">{{ $sessionItem->started_at?->format('d/m/Y H:i') }}</p></div><div class="flex flex-wrap gap-2">@foreach ($sessionItem->usageValues as $usage)<span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs text-pm-text">{{ $usage->metric->name }}: {{ $formatUsage($usage->value, $usage->metric) }}</span>@endforeach</div></div>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-pm-border p-4 text-sm text-pm-muted">{{ $it ? 'Nessuna sessione registrata.' : 'No sessions recorded.' }}</p>
            @endforelse
        </div>
    </section>
</div>
