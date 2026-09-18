<x-layouts::app :title="app()->getLocale() === 'it' ? 'Telemetria' : 'Telemetry'">
    @php
        $it = app()->getLocale() === 'it';
        $selectedIds = $selectedImports->pluck('id')->map(fn ($id) => (string) $id)->all();
        $formatMs = static function (?int $milliseconds): string {
            if ($milliseconds === null) return '—';
            $minutes = intdiv($milliseconds, 60000);
            $seconds = ($milliseconds % 60000) / 1000;
            return $minutes > 0 ? sprintf('%d:%06.3f', $minutes, $seconds) : sprintf('%.3f', $seconds);
        };
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1600px] space-y-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>

                    <x-pitmetric.page-header
                        :title="$it ? 'Telemetria' : 'Telemetry'"
                        :description="$it ? 'Importa acquisizioni diverse e confronta canali, GPS e tempi nello stesso schermo.' : 'Import different acquisitions and compare channels, GPS and lap timing on one screen.'"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('timing.index') }}" class="rounded-lg border border-pm-border px-4 py-2.5 text-sm font-bold text-pm-text-secondary hover:bg-pm-subtle">{{ $it ? 'Apri Tempi' : 'Open Timing' }}</a>
                    @can('team-write')
                        <button type="button" class="pm-race-button" onclick="document.getElementById('telemetry-import-panel').classList.toggle('hidden')">+ {{ $it ? 'Importa file' : 'Import file' }}</button>
                    @endcan
                </div>
            </div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            @can('team-write')
                <section id="telemetry-import-panel" class="pm-panel {{ $errors->any() ? '' : 'hidden' }} p-5">
                    <div class="mb-4"><h2 class="font-black text-pm-text">{{ $it ? 'Importa acquisizione' : 'Import acquisition' }}</h2><p class="mt-1 text-sm text-pm-muted">CSV / VBO · max 50 MB. {{ $it ? 'Scegli la sessione: veicolo e circuito vengono ereditati automaticamente.' : 'Choose the session: vehicle and circuit are inherited automatically.' }}</p></div>
                    <form method="POST" action="{{ route('telemetry.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">@csrf
                        <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Sessione' : 'Session' }}</span><select class="pm-input" name="session_id" required><option value="">—</option>@foreach ($sessions as $session)<option value="{{ $session->id }}" @selected((string) old('session_id') === (string) $session->id)>#{{ $session->id }} · {{ $session->started_at?->format('d/m/Y H:i') ?? '—' }} · {{ $session->vehicle?->name }} @if($session->circuitLayout)· {{ $session->circuitLayout->circuit?->name }} / {{ $session->circuitLayout->name }}@endif</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id"><option value="">{{ $it ? 'Da weekend gara / non specificato' : 'From event / unspecified' }}</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) old('driver_id') === (string) $driver->id)>{{ $driver->display_name }}</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Origine' : 'Source' }}</span><select class="pm-input" name="source_vendor" required>@foreach(['generic' => 'Generic CSV', 'aim' => 'AiM / RaceStudio', 'racebox' => 'RaceBox', 'racechrono' => 'RaceChrono', 'vbox' => 'Racelogic VBOX'] as $value => $label)<option value="{{ $value }}" @selected(old('source_vendor', 'generic') === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="grid gap-2 md:col-span-2 xl:col-span-3"><span class="pm-label">File</span><input class="pm-input file:mr-3 file:rounded-md file:border-0 file:bg-pm-accent file:px-3 file:py-1.5 file:font-bold file:text-white" type="file" name="telemetry_file" accept=".csv,.txt,.vbo,text/csv,text/plain" required></label>
                        <div class="flex items-end"><button class="pm-race-button w-full" type="submit">{{ $it ? 'Importa e normalizza' : 'Import and normalize' }}</button></div>
                    </form>
                </section>
            @endcan

            <form method="GET" action="{{ route('telemetry.index') }}" class="pm-panel p-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Sessione' : 'Session' }}</span><select class="pm-input" name="session_id"><option value="">{{ $it ? 'Tutte' : 'All' }}</option>@foreach ($sessions as $session)<option value="{{ $session->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $session->id)>#{{ $session->id }} · {{ $session->vehicle?->name }} · {{ $session->started_at?->format('d/m') }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) ($filters['driver_id'] ?? '') === (string) $driver->id)>{{ $driver->display_name }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo veicolo' : 'Vehicle type' }}</span><select class="pm-input" name="vehicle_category"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($vehicleCategories as $category)<option value="{{ $category }}" @selected(($filters['vehicle_category'] ?? '') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">Layout</span><select class="pm-input" name="circuit_layout_id"><option value="">{{ $it ? 'Tutti' : 'All' }}</option>@foreach ($layouts as $layout)<option value="{{ $layout->id }}" @selected((string) ($filters['circuit_layout_id'] ?? '') === (string) $layout->id)>{{ $layout->circuit?->name }} · {{ $layout->name }}</option>@endforeach</select></label>
                    <div class="flex items-end gap-2"><button class="pm-race-button flex-1" type="submit">{{ $it ? 'Filtra' : 'Filter' }}</button><a href="{{ route('telemetry.index') }}" class="rounded-lg border border-pm-border px-3 py-2.5 text-sm font-semibold text-pm-muted">Reset</a></div>
                </div>

                <div class="mt-4 border-t border-pm-border pt-4">
                    <div class="mb-2 flex items-center justify-between gap-3"><div><p class="pm-label">{{ $it ? 'Confronta acquisizioni' : 'Compare acquisitions' }}</p><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'Seleziona fino a 4 file. I grafici vengono sovrapposti automaticamente.' : 'Select up to 4 files. Charts are overlaid automatically.' }}</p></div><button class="rounded-lg border border-pm-border px-3 py-2 text-xs font-bold text-pm-text-secondary hover:bg-pm-subtle" type="submit">{{ $it ? 'Aggiorna confronto' : 'Update comparison' }}</button></div>
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        @forelse ($imports as $import)
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-pm-border bg-pm-subtle p-3 hover:border-pm-accent/60">
                                <input class="mt-1 h-4 w-4 accent-[#FF5A36]" type="checkbox" name="compare[]" value="{{ $import->id }}" @checked(in_array((string) $import->id, $selectedIds, true))>
                                <span class="min-w-0"><span class="block truncate text-sm font-bold text-pm-text">{{ $import->driver?->display_name ?? ($it ? 'Pilota non specificato' : 'Unspecified driver') }} · {{ $import->vehicle?->name }}</span><span class="mt-0.5 block truncate text-xs text-pm-muted">{{ strtoupper($import->source_vendor) }} · {{ $import->original_filename }}</span><span class="mt-1 block font-mono text-[11px] text-pm-muted">{{ number_format($import->sample_count, 0, ',', '.') }} samples · {{ $import->lap_count }} laps</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-pm-muted">{{ $it ? 'Nessuna telemetria importata.' : 'No telemetry imported.' }}</p>
                        @endforelse
                    </div>
                </div>
            </form>

            @if ($selectedImports->isNotEmpty())
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($selectedImports as $import)
                        @php($best = $import->laps->where('is_valid', true)->min('lap_time_ms'))
                        <article class="pm-panel p-4">
                            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-black text-pm-text">{{ $import->driver?->display_name ?? '—' }}</p><p class="truncate text-xs text-pm-muted">{{ $import->vehicle?->name }} · {{ $import->circuitLayout?->circuit?->name ?? '—' }}</p></div><span class="rounded-md border border-pm-border bg-pm-subtle px-2 py-1 font-mono text-[10px] uppercase text-pm-muted">{{ $import->source_vendor }}</span></div>
                            <div class="mt-4 grid grid-cols-2 gap-2"><div><p class="pm-label">Best lap</p><p class="mt-1 font-mono text-lg font-black text-pm-accent">{{ $formatMs($best) }}</p></div><div><p class="pm-label">Samples</p><p class="mt-1 font-mono text-lg font-black text-pm-text">{{ number_format($import->sample_count, 0, ',', '.') }}</p></div></div>
<x-pitmetric.record-delete :action="route('telemetry.destroy', $import)" :label="$it ? 'Elimina acquisizione' : 'Delete acquisition'" :message="$it ? 'Eliminare file, campioni e giri di questa acquisizione? La sessione resta nello storico.' : 'Delete this acquisition, its file, samples and laps? The session stays in history.'" />
                        </article>
                    @endforeach
                </div>

                <section class="pm-panel p-4 sm:p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"><div><h2 class="font-black text-pm-text">{{ $it ? 'Canali sincronizzati' : 'Synchronized channels' }}</h2><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'Asse X: distanza quando disponibile, altrimenti tempo. Ogni grafico usa la propria scala.' : 'X axis: distance when available, otherwise time. Each chart uses its own scale.' }}</p></div><div id="telemetry-channel-controls" class="flex max-w-full flex-wrap gap-2"></div></div>
                    <div id="telemetry-charts" class="mt-5 grid gap-4"></div>
                </section>

                <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
                    <section class="pm-panel p-4 sm:p-5"><h2 class="font-black text-pm-text">GPS trace</h2><p class="mt-1 text-xs text-pm-muted">{{ $it ? 'Tracce sovrapposte quando latitudine/longitudine sono presenti.' : 'Overlaid traces when latitude/longitude are present.' }}</p><div class="mt-4 h-[360px] rounded-xl border border-pm-border bg-[#090b0e] p-2"><canvas id="telemetry-map" class="h-full w-full"></canvas></div></section>
                    <section class="pm-panel overflow-hidden"><div class="border-b border-pm-border px-4 py-3"><h2 class="font-black text-pm-text">{{ $it ? 'Giri importati' : 'Imported laps' }}</h2></div><div class="max-h-[420px] overflow-auto"><table class="w-full min-w-[480px] text-sm"><thead class="sticky top-0 bg-pm-subtle text-left text-[10px] uppercase tracking-[0.08em] text-pm-muted"><tr><th class="px-4 py-3">Dataset</th><th class="px-4 py-3">Lap</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Sectors</th></tr></thead><tbody class="divide-y divide-pm-border">@foreach($selectedImports as $import)@forelse($import->laps->sortBy('lap_number') as $lap)<tr><td class="max-w-[180px] truncate px-4 py-3 text-xs text-pm-muted">{{ $import->driver?->display_name ?? '—' }} / {{ $import->vehicle?->name }}</td><td class="px-4 py-3 font-mono font-bold text-pm-text">{{ $lap->lap_number }}</td><td class="px-4 py-3 font-mono font-black text-pm-accent">{{ $formatMs($lap->lap_time_ms) }}</td><td class="px-4 py-3 font-mono text-xs text-pm-muted">@forelse(($lap->sector_times_ms ?? []) as $sector => $ms)S{{ $sector }} {{ $formatMs((int)$ms) }}@if(!$loop->last) · @endif @empty—@endforelse</td></tr>@empty<tr><td colspan="4" class="px-4 py-4 text-xs text-pm-muted">{{ $import->original_filename }}: {{ $it ? 'nessun giro codificato nel file' : 'no encoded laps in file' }}</td></tr>@endforelse @endforeach</tbody></table></div></section>
                </div>
            @else
                <x-pitmetric.empty-state :title="$it ? 'Importa la prima acquisizione' : 'Import your first acquisition'" :description="$it ? 'PitMetric conserverà il file originale e normalizzerà i canali per confrontarli qui.' : 'PitMetric will keep the source file and normalize channels for comparison here.'" />
            @endif
        </div>
    </div>

    @if ($selectedImports->isNotEmpty())
        <script>
            (() => {
                const datasets = @json($series);
                const channelNames = @json($availableChannels);
                const palette = ['#FF5A36', '#5BC0EB', '#9BE564', '#C77DFF'];
                const preferred = ['speed_kmh', 'rpm', 'throttle', 'brake', 'lateral_g', 'longitudinal_g'];
                const selected = new Set(preferred.filter(name => channelNames.includes(name)).slice(0, 4));
                if (selected.size === 0 && channelNames.length) selected.add(channelNames[0]);

                const valueFor = (point, channel) => channel === 'speed_kmh' ? point.speed_kmh : point.channels?.[channel];
                const pretty = name => name.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());

                function setupCanvas(canvas) {
                    const rect = canvas.getBoundingClientRect();
                    const ratio = window.devicePixelRatio || 1;
                    canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                    canvas.height = Math.max(1, Math.floor(rect.height * ratio));
                    const ctx = canvas.getContext('2d');
                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                    return { ctx, width: rect.width, height: rect.height };
                }

                function drawChart(canvas, channel) {
                    const { ctx, width, height } = setupCanvas(canvas);
                    const padding = { left: 50, right: 14, top: 14, bottom: 28 };
                    const usableW = Math.max(1, width - padding.left - padding.right);
                    const usableH = Math.max(1, height - padding.top - padding.bottom);
                    const allValues = [];
                    const allX = [];
                    const useDistance = datasets.some(series => series.points.some(point => point.distance !== null));

                    datasets.forEach(series => series.points.forEach(point => {
                        const value = Number(valueFor(point, channel));
                        const x = useDistance && point.distance !== null ? Number(point.distance) : Number(point.time);
                        if (Number.isFinite(value) && Number.isFinite(x)) { allValues.push(value); allX.push(x); }
                    }));

                    ctx.clearRect(0, 0, width, height);
                    ctx.fillStyle = '#090b0e'; ctx.fillRect(0, 0, width, height);
                    if (!allValues.length) { ctx.fillStyle = '#7f8794'; ctx.font = '12px sans-serif'; ctx.fillText('No data', padding.left, padding.top + 18); return; }
                    let minY = Math.min(...allValues), maxY = Math.max(...allValues), minX = Math.min(...allX), maxX = Math.max(...allX);
                    if (minY === maxY) { minY -= 1; maxY += 1; }
                    if (minX === maxX) maxX = minX + 1;
                    const xPos = value => padding.left + ((value - minX) / (maxX - minX)) * usableW;
                    const yPos = value => padding.top + usableH - ((value - minY) / (maxY - minY)) * usableH;

                    ctx.strokeStyle = '#242932'; ctx.lineWidth = 1;
                    for (let i = 0; i <= 4; i++) { const y = padding.top + (usableH / 4) * i; ctx.beginPath(); ctx.moveTo(padding.left, y); ctx.lineTo(width - padding.right, y); ctx.stroke(); }
                    ctx.fillStyle = '#7f8794'; ctx.font = '10px monospace'; ctx.fillText(maxY.toFixed(1), 6, padding.top + 4); ctx.fillText(minY.toFixed(1), 6, padding.top + usableH);
                    ctx.fillText(useDistance ? `${(maxX / 1000).toFixed(2)} km` : `${(maxX / 1000).toFixed(1)} s`, width - 70, height - 8);

                    datasets.forEach((series, index) => {
                        ctx.strokeStyle = palette[index % palette.length]; ctx.lineWidth = 1.7; ctx.beginPath(); let started = false;
                        series.points.forEach(point => {
                            const value = Number(valueFor(point, channel));
                            const x = useDistance && point.distance !== null ? Number(point.distance) : Number(point.time);
                            if (!Number.isFinite(value) || !Number.isFinite(x)) return;
                            if (!started) { ctx.moveTo(xPos(x), yPos(value)); started = true; } else ctx.lineTo(xPos(x), yPos(value));
                        });
                        ctx.stroke();
                    });
                }

                function drawMap() {
                    const canvas = document.getElementById('telemetry-map'); if (!canvas) return;
                    const { ctx, width, height } = setupCanvas(canvas);
                    const points = datasets.flatMap(series => series.points.filter(p => p.lat !== null && p.lon !== null));
                    ctx.fillStyle = '#090b0e'; ctx.fillRect(0, 0, width, height);
                    if (!points.length) { ctx.fillStyle = '#7f8794'; ctx.font = '12px sans-serif'; ctx.fillText('GPS data not available', 18, 28); return; }
                    const minLat = Math.min(...points.map(p => Number(p.lat))), maxLat = Math.max(...points.map(p => Number(p.lat))), minLon = Math.min(...points.map(p => Number(p.lon))), maxLon = Math.max(...points.map(p => Number(p.lon)));
                    const latSpan = Math.max(0.000001, maxLat - minLat), lonSpan = Math.max(0.000001, maxLon - minLon), pad = 20;
                    datasets.forEach((series, index) => { const gps = series.points.filter(p => p.lat !== null && p.lon !== null); if (!gps.length) return; ctx.strokeStyle = palette[index % palette.length]; ctx.lineWidth = 2; ctx.beginPath(); gps.forEach((p, i) => { const x = pad + ((Number(p.lon) - minLon) / lonSpan) * (width - pad * 2); const y = height - pad - ((Number(p.lat) - minLat) / latSpan) * (height - pad * 2); i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y); }); ctx.stroke(); });
                }

                function renderControls() {
                    const root = document.getElementById('telemetry-channel-controls'); root.innerHTML = '';
                    channelNames.forEach(channel => { const label = document.createElement('label'); label.className = 'cursor-pointer rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-xs font-bold text-pm-text-secondary'; const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'mr-2 accent-[#FF5A36]'; input.checked = selected.has(channel); input.addEventListener('change', () => { input.checked ? selected.add(channel) : selected.delete(channel); renderCharts(); }); label.append(input, document.createTextNode(pretty(channel))); root.appendChild(label); });
                }

                function renderCharts() {
                    const root = document.getElementById('telemetry-charts'); root.innerHTML = '';
                    [...selected].forEach(channel => { const card = document.createElement('div'); card.className = 'rounded-xl border border-pm-border bg-[#090b0e] p-3'; const title = document.createElement('div'); title.className = 'mb-2 text-xs font-black uppercase tracking-[0.08em] text-pm-muted'; title.textContent = pretty(channel); const canvas = document.createElement('canvas'); canvas.className = 'h-[230px] w-full'; card.append(title, canvas); root.appendChild(card); drawChart(canvas, channel); });
                    if (!selected.size) root.innerHTML = '<div class="rounded-xl border border-pm-border p-6 text-sm text-pm-muted">Select at least one channel.</div>';
                }

                renderControls(); renderCharts(); drawMap();
                let timer; window.addEventListener('resize', () => { clearTimeout(timer); timer = setTimeout(() => { renderCharts(); drawMap(); }, 120); });
            })();
        </script>
    @endif
</x-layouts::app>
