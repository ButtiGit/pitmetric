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
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">SESSIONS</p><x-pitmetric.page-header :title="$it ? 'Sessioni in pista' : 'Track sessions'" :description="$it ? 'Registra una sessione: PitMetric calcola l’utilizzo e lo propaga solo ai componenti compatibili della configurazione selezionata.' : 'Record a session: PitMetric calculates usage and propagates it only to compatible components in the selected configuration.'" /></div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="pm-panel p-5 sm:p-6">
                <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Registra sessione' : 'Record session' }}</h2>
                <form method="POST" action="{{ route('sessions.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione' : 'Configuration' }}</span><select class="pm-input" name="configuration_version_id" required><option value="">{{ $it ? 'Seleziona configurazione' : 'Select configuration' }}</option>@foreach ($versions as $version)<option value="{{ $version->id }}">{{ $version->configuration->vehicle->name }} · {{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                    <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Circuito / layout' : 'Circuit / layout' }}</span><select class="pm-input" name="circuit_layout_id"><option value="">{{ $it ? 'Nessun circuito' : 'No circuit' }}</option>@foreach ($layouts as $layout)<option value="{{ $layout->id }}">{{ $layout->circuit->name }} · {{ $layout->name }} · {{ number_format($layout->length_meters, 0, ',', '.') }} m</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo sessione' : 'Session type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="race">Race</option><option value="test">Test</option></select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" name="started_at" type="datetime-local" required></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri completati' : 'Completed laps' }}</span><input class="pm-input" name="completed_laps" type="number" min="0" max="10000" step="1"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" name="duration_minutes" type="number" min="0" max="1440" step="0.1"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Distanza manuale (km)' : 'Manual distance (km)' }}</span><input class="pm-input" name="distance_override_km" type="number" min="0" max="100000" step="0.001"><span class="text-xs text-pm-muted">{{ $it ? 'Lascia vuoto per usare lunghezza layout × giri.' : 'Leave empty to use layout length × laps.' }}</span></label>
                    <label class="grid gap-2 md:col-span-2 xl:col-span-3"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><input class="pm-input" name="notes" maxlength="4000"></label>
                    <div class="md:col-span-2 xl:col-span-4"><button class="pm-race-button" type="submit" @disabled($versions->isEmpty())>{{ $it ? 'Registra e finalizza sessione' : 'Record and finalize session' }}</button></div>
                </form>
            </section>

            <section class="space-y-3">
                @forelse ($sessions as $session)
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-black text-pm-text">{{ $session->vehicle->name }}</h2><x-pitmetric.status-badge :label="$session->status" variant="success" /></div><p class="mt-1 text-sm text-pm-text-secondary">{{ $session->configurationVersion->configuration->name }} v{{ $session->configurationVersion->version_number }} · {{ ucfirst($session->session_type) }}</p><p class="mt-1 text-xs text-pm-muted">{{ $session->started_at?->format('d/m/Y H:i') }}@if ($session->circuitLayout) · {{ $session->circuitLayout->circuit->name }} / {{ $session->circuitLayout->name }}@endif</p></div>
                            <div class="flex flex-wrap gap-2">@foreach ($session->usageValues as $usageValue)<span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 font-mono text-xs font-bold text-pm-text">{{ $usageValue->metric->name }}: {{ $formatUsage($usageValue->value, $usageValue->metric) }}</span>@endforeach</div>
                        </div>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessuna sessione' : 'No sessions'" :description="$it ? 'Crea una configurazione e registra la prima sessione. L’utilizzo dei componenti verrà aggiornato in modo storico e idempotente.' : 'Create a configuration and record the first session. Component usage will be updated historically and idempotently.'" />
                @endforelse
            </section>
        </div>
    </div>
</x-layouts::app>
