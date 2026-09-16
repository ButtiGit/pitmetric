<x-layouts::app :title="__('Sessions')">
    @php
        $it = app()->getLocale() === 'it';
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            return match ($metric->key) {
                'distance' => number_format($value / 1000, 1, ',', '.').' km',
                'runtime' => number_format($value / 3600, 1, ',', '.').' h',
                default => number_format($value, 0, ',', '.').' '.$metric->display_unit,
            };
        };
        $selectedVersion = old('configuration_version_id', $defaults['configuration_version_id']);
        $selectedSetup = old('technical_setup_id', $defaults['technical_setup_id']);
        $selectedLayout = old('circuit_layout_id', $defaults['circuit_layout_id']);
        $selectedType = old('session_type', $defaults['session_type']);
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">SESSIONS</p><x-pitmetric.page-header :title="$it ? 'Sessioni in pista' : 'Track sessions'" :description="$it ? 'Registra una sessione una sola volta: PitMetric aggiorna utilizzo, manutenzione e costi e conserva uno snapshot immutabile del setup tecnico usato.' : 'Record a session once: PitMetric updates usage, maintenance and costs and preserves an immutable snapshot of the technical setup used.'" /></div>
                <x-crud-modal id="record-session" :title="$it ? 'Registra sessione' : 'Record session'" :description="$it ? 'Scegli build componenti e setup tecnico. Se non selezioni un setup, PitMetric usa l’ultimo profilo attivo del mezzo e lo fotografa nello storico.' : 'Choose component build and technical setup. If no setup is selected, PitMetric uses the vehicle’s latest active profile and snapshots it into history.'" :trigger="$it ? '+ Registra sessione' : '+ Record session'" size="max-w-5xl">
                    <form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @csrf
                        <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione componenti' : 'Component configuration' }}</span><select class="pm-input" name="configuration_version_id" required><option value="">{{ $it ? 'Seleziona configurazione' : 'Select configuration' }}</option>@foreach ($versions as $version)<option value="{{ $version->id }}" @selected((string) $selectedVersion === (string) $version->id)>{{ $version->configuration->vehicle->name }} · {{ $version->configuration->name }} v{{ $version->version_number }} · {{ $version->components->count() }} {{ $it ? 'componenti' : 'components' }}</option>@endforeach</select></label>
                        <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Setup tecnico' : 'Technical setup' }}</span><select class="pm-input" name="technical_setup_id"><option value="">{{ $it ? 'Automatico · ultimo setup attivo del mezzo' : 'Automatic · latest active vehicle setup' }}</option>@foreach ($setups->groupBy('vehicle_id') as $vehicleSetups)<optgroup label="{{ $vehicleSetups->first()->vehicle->name }}">@foreach ($vehicleSetups as $setup)<option value="{{ $setup->id }}" @selected((string) $selectedSetup === (string) $setup->id)>{{ $setup->name }}</option>@endforeach</optgroup>@endforeach</select><span class="text-xs text-pm-muted"><a class="font-semibold text-pm-accent hover:underline" href="{{ route('setups.index') }}">{{ $it ? 'Gestisci setup tecnici' : 'Manage technical setups' }}</a></span></label>
                        <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Circuito / layout' : 'Circuit / layout' }}</span><select class="pm-input" name="circuit_layout_id"><option value="">{{ $it ? 'Nessun circuito' : 'No circuit' }}</option>@foreach ($layouts as $layout)<option value="{{ $layout->id }}" @selected((string) $selectedLayout === (string) $layout->id)>{{ $layout->circuit->name }} · {{ $layout->name }} · {{ number_format($layout->length_meters, 0, ',', '.') }} m</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo sessione' : 'Session type' }}</span><select class="pm-input" name="session_type" required>@foreach (['practice' => 'Practice', 'qualifying' => 'Qualifying', 'heat' => 'Heat', 'prefinal' => 'Prefinal', 'final' => 'Final', 'race' => 'Race', 'test' => 'Test'] as $value => $label)<option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" name="started_at" type="datetime-local" required value="{{ old('started_at', $defaults['started_at']) }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri completati' : 'Completed laps' }}</span><input class="pm-input" name="completed_laps" type="number" min="0" max="10000" step="1" value="{{ old('completed_laps') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" name="duration_minutes" type="number" min="0" max="1440" step="0.1" value="{{ old('duration_minutes') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Distanza manuale (km)' : 'Manual distance (km)' }}</span><input class="pm-input" name="distance_override_km" type="number" min="0" max="100000" step="0.001" value="{{ old('distance_override_km') }}"><span class="text-xs text-pm-muted">{{ $it ? 'Lascia vuoto per usare lunghezza layout × giri.' : 'Leave empty to use layout length × laps.' }}</span></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo sessione (€)' : 'Session cost (€)' }}</span><input class="pm-input" name="session_cost" type="number" min="0" max="1000000" step="0.01" value="{{ old('session_cost') }}"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato automaticamente nei Costi' : 'Optional · automatically recorded in Expenses' }}</span></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180" value="{{ old('cost_description') }}" placeholder="{{ $it ? 'Noleggio pista / iscrizione' : 'Track rental / entry fee' }}"></label>
                        <label class="grid gap-2 md:col-span-2 xl:col-span-4"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000">{{ old('notes') }}</textarea></label>
                        <div class="md:col-span-2 xl:col-span-4 flex justify-end"><button class="pm-race-button" type="submit" @disabled($versions->isEmpty())>{{ $it ? 'Registra sessione' : 'Record session' }}</button></div>
                    </form>
                </x-crud-modal>
            </div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ((int) session('maintenance_attention', 0) > 0)<div class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning">{{ $it ? session('maintenance_attention').' interventi richiedono attenzione dopo l’aggiornamento utilizzo.' : session('maintenance_attention').' maintenance items need attention after usage was updated.' }} <a href="{{ route('maintenance.index') }}" class="underline">{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</a></div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="grid gap-3 sm:grid-cols-3">
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Overdue</p><p class="mt-3 text-3xl font-black text-pm-danger">{{ $maintenanceSummary['overdue'] }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">Due soon</p><p class="mt-3 text-3xl font-black text-pm-warning">{{ $maintenanceSummary['due_soon'] }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Piani OK' : 'Schedules OK' }}</p><p class="mt-3 text-3xl font-black text-pm-success">{{ $maintenanceSummary['ok'] }}</p></article>
            </section>

            @if ($attentionSchedules->isNotEmpty())
                <section class="pm-panel border-pm-warning/25 p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-warning">{{ $it ? 'Prima di girare' : 'Before running' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Controlla gli interventi in attenzione' : 'Check maintenance items needing attention' }}</h2></div><a href="{{ route('maintenance.index') }}" class="pm-ghost-button">{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</a></div>
                    <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">@foreach ($attentionSchedules->take(6) as $schedule)@php($state = $maintenanceStates[$schedule->getKey()]['status'] ?? 'untracked')<div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><div class="flex items-start justify-between gap-3"><div><p class="font-bold text-pm-text">{{ $schedule->tracker->component->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $schedule->name }}</p></div><x-pitmetric.status-badge :label="$state" :variant="$state === 'overdue' ? 'danger' : 'warning'" /></div></div>@endforeach</div>
                </section>
            @endif

            <section class="space-y-3">
                @forelse ($sessions as $session)
                    @php
                        $snapshot = $session->setupSnapshot;
                        $snapshotValues = collect($snapshot?->values ?? [])->take(6);
                    @endphp
                    <article class="pm-panel p-5 sm:p-6 {{ (string) request('recorded') === (string) $session->id ? 'border-pm-accent/40' : '' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-black text-pm-text">{{ $session->vehicle->name }}</h2><x-pitmetric.status-badge :label="$session->status" variant="success" />@if ((string) request('recorded') === (string) $session->id)<span class="text-[10px] font-black uppercase tracking-[0.1em] text-pm-accent">{{ $it ? 'Appena registrata' : 'Just recorded' }}</span>@endif</div><p class="mt-1 text-sm text-pm-text-secondary">{{ $session->configurationVersion->configuration->name }} v{{ $session->configurationVersion->version_number }} · {{ ucfirst($session->session_type) }}</p><p class="mt-1 text-xs text-pm-muted">{{ $session->started_at?->format('d/m/Y H:i') }}@if ($session->circuitLayout) · {{ $session->circuitLayout->circuit->name }} / {{ $session->circuitLayout->name }}@endif</p></div>
                            <div class="flex flex-wrap gap-2">@foreach ($session->usageValues as $usageValue)<span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 font-mono text-xs font-bold text-pm-text">{{ $usageValue->metric->name }}: {{ $formatUsage($usageValue->value, $usageValue->metric) }}</span>@endforeach</div>
                        </div>

                        <div class="mt-4 rounded-xl border border-pm-border bg-pm-subtle p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-accent">SETUP SNAPSHOT</p><p class="mt-1 text-sm font-bold text-pm-text">{{ $snapshot?->name ?? ($it ? 'Setup non disponibile' : 'Setup unavailable') }}</p></div>@if ($snapshot)<span class="text-xs text-pm-muted">{{ $it ? 'Catturato' : 'Captured' }} {{ $snapshot->captured_at?->format('d/m/Y H:i') }}</span>@endif</div>
                            @if ($snapshotValues->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">@foreach ($snapshotValues as $key => $value)@php($definition = \App\Models\TechnicalSetup::FIELD_DEFINITIONS[$key] ?? ['label' => $key, 'unit' => ''])<span class="rounded-lg border border-pm-border bg-pm-panel px-2.5 py-1.5 text-xs text-pm-text-secondary"><strong class="text-pm-text">{{ $definition['label'] }}</strong> · {{ $value }}{{ $definition['unit'] !== '' ? ' '.$definition['unit'] : '' }}</span>@endforeach @if (count($snapshot?->values ?? []) > 6)<span class="px-2.5 py-1.5 text-xs font-bold text-pm-muted">+{{ count($snapshot->values) - 6 }}</span>@endif</div>
                            @else
                                <p class="mt-2 text-xs text-pm-muted">{{ $it ? 'Snapshot storico senza parametri tecnici specificati.' : 'Historical snapshot with no technical parameters specified.' }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessuna sessione' : 'No sessions'" :description="$it ? 'Crea una configurazione, prepara un setup tecnico e registra la prima sessione. PitMetric collegherà automaticamente componenti, setup, utilizzo, manutenzione e costi.' : 'Create a configuration, prepare a technical setup and record the first session. PitMetric will automatically connect components, setup, usage, maintenance and costs.'" />
                @endforelse
            </section>
        </div>
    </div>
</x-layouts::app>
