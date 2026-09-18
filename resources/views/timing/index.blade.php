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
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">{{ $it ? 'ANALISI TEMPI' : 'TIMING ANALYSIS' }}</p>
                    <x-pitmetric.page-header
                        :title="$it ? 'Tempi' : 'Timing'"
                        :description="$it ? 'Confronta giri e settori provenienti dalle sessioni e dagli import telemetrici.' : 'Compare laps and sectors from sessions and telemetry imports.'"
                    />
                </div>
                <a href="{{ route('telemetry.index') }}" class="pm-race-button inline-flex items-center justify-center">{{ $it ? 'Apri Telemetria' : 'Open Telemetry' }}</a>
            </div>

            <form method="GET" action="{{ route('timing.index') }}" class="pm-panel grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-5">
                <label class="grid gap-2">
                    <span class="pm-label">{{ $it ? 'Sessione' : 'Session' }}</span>
                    <select class="pm-input" name="session_id">
                        <option value="">{{ $it ? 'Tutte' : 'All' }}</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $session->id)>
                                #{{ $session->id }} · {{ $session->started_at?->format('d/m/Y H:i') ?? '—' }} · {{ $session->vehicle?->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span>
                    <select class="pm-input" name="circuit_id">
                        <option value="">{{ $it ? 'Tutti' : 'All' }}</option>
                        @foreach ($circuits as $circuit)<option value="{{ $circuit->id }}" @selected((string) ($filters['circuit_id'] ?? '') === (string) $circuit->id)>{{ $circuit->name }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span>
                    <select class="pm-input" name="driver_id">
                        <option value="">{{ $it ? 'Tutti' : 'All' }}</option>
                        @foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) ($filters['driver_id'] ?? '') === (string) $driver->id)>{{ $driver->display_name }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="pm-label">{{ $it ? 'Tipo veicolo' : 'Vehicle type' }}</span>
                    <select class="pm-input" name="vehicle_category">
                        <option value="">{{ $it ? 'Tutti' : 'All' }}</option>
                        @foreach ($vehicleCategories as $category)<option value="{{ $category }}" @selected(($filters['vehicle_category'] ?? '') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>@endforeach
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button class="pm-race-button flex-1" type="submit">{{ $it ? 'Filtra' : 'Filter' }}</button>
                    <a href="{{ route('timing.index') }}" class="rounded-lg border border-pm-border px-3 py-2.5 text-sm font-semibold text-pm-muted hover:text-pm-text">Reset</a>
                </div>
            </form>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Miglior giro' : 'Best lap' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-accent">{{ $formatMs($summary['best_ms']) }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Media' : 'Average' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $formatMs($summary['average_ms']) }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Giri confrontati' : 'Compared laps' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $summary['count'] }}</p></div>
                <div class="pm-panel p-4"><p class="pm-label">{{ $it ? 'Piloti' : 'Drivers' }}</p><p class="mt-2 font-mono text-2xl font-black text-pm-text">{{ $summary['drivers'] }}</p></div>
            </div>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-pm-border px-4 py-3 sm:px-5"><h2 class="font-black text-pm-text">{{ $it ? 'Classifica giri' : 'Lap ranking' }}</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1050px] w-full text-left text-sm">
                        <thead class="bg-pm-subtle text-[11px] uppercase tracking-[0.08em] text-pm-muted">
                            <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">{{ $it ? 'Tempo' : 'Lap' }}</th><th class="px-4 py-3">{{ $it ? 'Δ best' : 'Δ best' }}</th><th class="px-4 py-3">{{ $it ? 'Pilota' : 'Driver' }}</th><th class="px-4 py-3">{{ $it ? 'Veicolo' : 'Vehicle' }}</th><th class="px-4 py-3">{{ $it ? 'Circuito' : 'Circuit' }}</th><th class="px-4 py-3">{{ $it ? 'Sessione' : 'Session' }}</th><th class="px-4 py-3">{{ $it ? 'Settori' : 'Sectors' }}</th><th class="px-4 py-3">Source</th></tr>
                        </thead>
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
                                <tr><td colspan="9" class="px-5 py-12 text-center text-pm-muted">{{ $it ? 'Nessun giro disponibile con questi filtri. Importa un file telemetrico che contenga numero giro e tempi.' : 'No laps match these filters. Import telemetry containing lap numbers and times.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
