<x-layouts::app :title="app()->getLocale() === 'it' ? 'Tempi' : 'Timing'">
    @php
        $it = app()->getLocale() === 'it';
        $formatMs = static function (?int $milliseconds): string {
            if ($milliseconds === null) return '—';
            $minutes = intdiv($milliseconds, 60000);
            $seconds = ($milliseconds % 60000) / 1000;
            return $minutes > 0 ? sprintf('%d:%06.3f', $minutes, $seconds) : sprintf('%.3f', $seconds);
        };
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1500px] space-y-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">{{ $it ? 'TRACKSIDE / TEMPI' : 'TRACKSIDE / TIMING' }}</p>
                    <x-pitmetric.page-header
                        :title="$it ? 'Tempi' : 'Timing'"
                        :description="$it ? 'Registra subito il dato in pista. Le anagrafiche mancanti si completano dopo.' : 'Capture the data immediately at the track. Missing master data can be completed later.'"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('team-write')
                        <x-crud-modal id="quick-lap" :title="$it ? 'Registra giro rapido' : 'Quick lap capture'" :trigger="$it ? '+ Registra giro' : '+ Capture lap'">
                            <form method="POST" action="{{ route('timing.quick.store') }}" class="grid gap-4" data-pm-quick-lap-form>
                                @csrf
                                <div class="rounded-xl border border-pm-accent/30 bg-pm-accent/5 p-3 text-sm text-pm-text-secondary">
                                    {{ $it ? 'Bastano tempo e nome circuito. Se il circuito non esiste ancora, il giro viene salvato comunque e PitMetric te lo ricorderà.' : 'Lap time and circuit name are enough. If the circuit does not exist yet, the lap is still saved and PitMetric will remind you.' }}
                                </div>
                                <label class="grid gap-2">
                                    <span class="pm-label">{{ $it ? 'Tempo giro' : 'Lap time' }}</span>
                                    <input class="pm-input font-mono text-lg font-black" name="lap_time" inputmode="decimal" autocomplete="off" required maxlength="20" placeholder="1:02.345" value="{{ old('lap_time') }}">
                                </label>
                                <label class="grid gap-2">
                                    <span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span>
                                    <input class="pm-input" name="circuit_name" list="quick-circuit-options" autocomplete="off" required maxlength="120" placeholder="{{ $it ? 'Es. Kart Planet' : 'e.g. Kart Planet' }}" value="{{ old('circuit_name') }}">
                                </label>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2">
                                        <span class="pm-label">{{ $it ? 'Pilota (facoltativo)' : 'Driver (optional)' }}</span>
                                        <input class="pm-input" name="driver_name" list="quick-driver-options" autocomplete="off" maxlength="120" value="{{ old('driver_name') }}">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="pm-label">{{ $it ? 'Veicolo (facoltativo)' : 'Vehicle (optional)' }}</span>
                                        <input class="pm-input" name="vehicle_name" list="quick-vehicle-options" autocomplete="off" maxlength="120" value="{{ old('vehicle_name') }}">
                                    </label>
                                </div>
                                <details class="rounded-xl border border-pm-border bg-pm-subtle/40 p-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-pm-text-secondary">{{ $it ? 'Aggiungi nota' : 'Add note' }}</summary>
                                    <textarea class="pm-input mt-3 min-h-20" name="notes" maxlength="1000" placeholder="{{ $it ? 'Pressioni, meteo, traffico...' : 'Pressures, weather, traffic...' }}">{{ old('notes') }}</textarea>
                                </details>
                                <button class="pm-race-button w-full py-3 text-base" type="submit">{{ $it ? 'Salva adesso' : 'Save now' }}</button>
                            </form>
                        </x-crud-modal>
                    @endcan
                    <a href="{{ route('telemetry.index') }}" class="rounded-lg border border-pm-border px-3 py-2.5 text-sm font-semibold text-pm-text-secondary hover:text-pm-text">{{ $it ? 'Telemetria' : 'Telemetry' }}</a>
                </div>
            </div>

            <datalist id="quick-circuit-options">@foreach ($circuits as $circuit)<option value="{{ $circuit->name }}"></option>@endforeach</datalist>
            <datalist id="quick-driver-options">@foreach ($drivers as $driver)<option value="{{ $driver->display_name }}"></option>@endforeach</datalist>
            <datalist id="quick-vehicle-options">@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->name }}"></option>@endforeach</datalist>

            @if (session('status'))<p role="status" class="pm-feedback">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="pm-feedback text-pm-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            @if ($pendingCaptureCount > 0)
                <section class="rounded-xl border border-amber-400/25 bg-amber-400/[0.06] p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 rounded-lg bg-amber-400/10 p-2 text-amber-300"><flux:icon.exclamation-triangle class="size-5" /></div>
                            <div>
                                <p class="font-black text-pm-text">{{ $pendingCaptureCount }} {{ $it ? 'registrazioni da completare' : 'captures need completion' }}</p>
                                <p class="mt-1 text-sm text-pm-muted">{{ $it ? 'I tempi sono già salvati. Mancano solo alcuni riferimenti, per esempio il circuito.' : 'The lap times are already safe. Only some references, such as the circuit, are missing.' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('timing.index', ['pending' => 1]) }}" class="pm-row-action">{{ $it ? 'Mostra' : 'Show' }}</a>
                            <a href="{{ route('circuits.index') }}" class="pm-row-action">{{ $it ? 'Circuiti' : 'Circuits' }}</a>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pm-panel overflow-hidden" aria-labelledby="quick-captures-title">
                <div class="flex items-center justify-between gap-3 border-b border-pm-border px-4 py-3 sm:px-5">
                    <div>
                        <h2 id="quick-captures-title" class="font-black text-pm-text">{{ $it ? 'Registrazioni rapide' : 'Quick captures' }}</h2>
                        <p class="mt-1 text-xs text-pm-muted">{{ $it ? 'Dati presi al volo in pista, anche prima di creare tutte le anagrafiche.' : 'Trackside data captured before every master record exists.' }}</p>
                    </div>
                    @if (! empty($filters['pending']))<a href="{{ route('timing.index') }}" class="pm-row-action">{{ $it ? 'Tutte' : 'All' }}</a>@endif
                </div>
                <div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($quickCaptures as $capture)
                        <article id="quick-capture-{{ $capture->id }}" class="rounded-xl border {{ $capture->status === 'pending' ? 'border-amber-400/25 bg-amber-400/[0.04]' : 'border-pm-border bg-pm-subtle/30' }} p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-mono text-2xl font-black text-pm-text">{{ $formatMs($capture->lap_time_ms) }}</p>
                                    <p class="mt-1 text-sm font-semibold text-pm-text-secondary">{{ $capture->circuitLayout?->circuit?->name ?? $capture->circuit_name ?? '—' }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-wide {{ $capture->status === 'pending' ? 'bg-amber-400/10 text-amber-300' : 'bg-emerald-400/10 text-emerald-300' }}">
                                    {{ $capture->status === 'pending' ? ($it ? 'Da completare' : 'Incomplete') : ($it ? 'Collegato' : 'Linked') }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-pm-muted">
                                @if ($capture->driver_name)<span>{{ $capture->driver?->display_name ?? $capture->driver_name }}</span>@endif
                                @if ($capture->vehicle_name)<span>{{ $capture->vehicle?->name ?? $capture->vehicle_name }}</span>@endif
                                <span>{{ $capture->captured_at?->format('d/m H:i') }}</span>
                            </div>
                            @if ($capture->status === 'pending')
                                <p class="mt-3 text-xs font-semibold text-amber-300">{{ $it ? 'Circuito non ancora collegato: il tempo resta comunque salvato.' : 'Circuit not linked yet: the lap time is still safely stored.' }}</p>
                            @endif
                            @if ($capture->notes)<p class="mt-3 text-xs leading-5 text-pm-muted">{{ $capture->notes }}</p>@endif
                        </article>
                    @empty
                        <div class="col-span-full px-3 py-8 text-center text-sm text-pm-muted">{{ $it ? 'Nessuna registrazione rapida. In pista usa “Registra giro”: bastano pochi secondi.' : 'No quick captures yet. At the track use “Capture lap”: it only takes a few seconds.' }}</div>
                    @endforelse
                </div>
            </section>

            <form method="GET" action="{{ route('timing.index') }}" class="pm-panel grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-5">
                <label class="grid gap-2">
                    <span class="pm-label">{{ $it ? 'Sessione' : 'Session' }}</span>
                    <select class="pm-input" name="session_id">
                        <option value="">{{ $it ? 'Tutte' : 'All' }}</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $session->id)>#{{ $session->id }} · {{ $session->started_at?->format('d/m/Y H:i') ?? '—' }} · {{ $session->vehicle?->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span><select class="pm-input" name="circuit_id"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($circuits as $circuit)<option value="{{ $circuit->id }}" @selected((string) ($filters['circuit_id'] ?? '') === (string) $circuit->id)>{{ $circuit->name }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) ($filters['driver_id'] ?? '') === (string) $driver->id)>{{ $driver->display_name }}</option>@endforeach</select></label>
                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo veicolo' : 'Vehicle type' }}</span><select class="pm-input" name="vehicle_category"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($vehicleCategories as $category)<option value="{{ $category }}" @selected(($filters['vehicle_category'] ?? '') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>@endforeach</select></label>
                <div class="flex items-end gap-2"><button class="pm-race-button flex-1" type="submit">{{ $it ? 'Filtra' : 'Filter' }}</button><a href="{{ route('timing.index') }}" class="rounded-lg border border-pm-border px-3 py-2.5 text-sm font-semibold text-pm-muted hover:text-pm-text">Reset</a></div>
            </form>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Miglior giro' : 'Best lap' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-accent">{{ $formatMs($summary['best_ms']) }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Media' : 'Average' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $formatMs($summary['average_ms']) }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Giri confrontati' : 'Compared laps' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $summary['count'] }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Piloti' : 'Drivers' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $summary['drivers'] }}</p></div>
            </div>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-pm-border px-4 py-3 sm:px-5"><h2 class="font-black text-pm-text">{{ $it ? 'Classifica giri consolidati' : 'Consolidated lap ranking' }}</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1050px] w-full text-left text-sm">
                        <thead class="bg-pm-subtle text-[11px] uppercase tracking-[0.08em] text-pm-muted"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ $it ? 'Tempo' : 'Lap' }}</th><th class="px-4 py-3">Δ best</th><th class="px-4 py-3">{{ $it ? 'Pilota' : 'Driver' }}</th><th class="px-4 py-3">{{ $it ? 'Veicolo' : 'Vehicle' }}</th><th class="px-4 py-3">{{ $it ? 'Circuito' : 'Circuit' }}</th><th class="px-4 py-3">{{ $it ? 'Sessione' : 'Session' }}</th><th class="px-4 py-3">{{ $it ? 'Settori' : 'Sectors' }}</th><th class="px-4 py-3">Source</th></tr></thead>
                        <tbody class="divide-y divide-pm-border">
                            @forelse ($laps as $lap)
                                @php($delta = $summary['best_ms'] === null ? null : $lap->lap_time_ms - $summary['best_ms'])
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 font-mono font-bold text-pm-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-mono text-base font-black text-pm-text">{{ $formatMs($lap->lap_time_ms) }}</td>
                                    <td class="px-4 py-3 font-mono {{ $delta === 0 ? 'font-bold text-pm-success' : 'text-pm-muted' }}">{{ $delta === null ? '—' : ($delta === 0 ? 'BEST' : '+'.number_format($delta / 1000, 3)) }}</td>
                                    <td class="px-4 py-3 font-semibold text-pm-text">{{ $lap->driver?->display_name ?? '—' }}</td>
                                    <td class="px-4 py-3"><div class="font-semibold text-pm-text">{{ $lap->vehicle?->name ?? '—' }}</div><div class="text-xs text-pm-muted">{{ ucfirst(str_replace('_', ' ', $lap->vehicle?->category ?? '')) }}</div></td>
                                    <td class="px-4 py-3 text-pm-text-secondary">{{ $lap->circuitLayout?->circuit?->name ?? '—' }} @if($lap->circuitLayout)· {{ $lap->circuitLayout->name }}@endif</td>
                                    <td class="px-4 py-3 text-pm-text-secondary">#{{ $lap->session_id }} · {{ $lap->session?->started_at?->format('d/m/Y') ?? '—' }}<div class="text-xs text-pm-muted">Lap {{ $lap->lap_number }}</div></td>
                                    <td class="px-4 py-3"><div class="flex flex-wrap gap-1">@forelse (($lap->sector_times_ms ?? []) as $sector => $milliseconds)<span class="rounded-md border border-pm-border bg-pm-subtle px-2 py-1 font-mono text-xs text-pm-text-secondary">S{{ $sector }} {{ $formatMs((int) $milliseconds) }}</span>@empty<span class="text-pm-muted">—</span>@endforelse</div></td>
                                    <td class="px-4 py-3 text-xs text-pm-muted">{{ $lap->telemetryImport?->source_vendor ?? $lap->source }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-5 py-12 text-center text-pm-muted">{{ $it ? 'Nessun giro consolidato disponibile con questi filtri.' : 'No consolidated laps match these filters.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
