@props(['session', 'ready', 'page', 'it', 'circuit'])

@php
    $history = $ready ? $session->historical_weather_snapshot : null;
    $start = $session->started_at;
    $eligibleDate = $start && $start->lessThanOrEqualTo(now()->subDays(3)) && $start->greaterThanOrEqualTo(\Illuminate\Support\Carbon::parse('2001-01-01', 'UTC'));
    $forecast = $session->weather_snapshot ?? [];
    $hasLocation = $circuit !== null || (is_numeric($forecast['latitude'] ?? null) && is_numeric($forecast['longitude'] ?? null));
    $historyMatchesSession = $history && $start && isset($history['session_started_at'])
        && \Illuminate\Support\Carbon::parse($history['session_started_at'])->equalTo($start);
    $fields = [
        ['name' => 'air_temperature_c', 'label' => $it ? 'Temperatura aria' : 'Air temperature', 'unit' => '°C'],
        ['name' => 'humidity_pct', 'label' => $it ? 'Umidità' : 'Humidity', 'unit' => '%'],
        ['name' => 'wind_speed_kmh', 'label' => $it ? 'Vento' : 'Wind', 'unit' => 'km/h'],
        ['name' => 'pressure_hpa', 'label' => $it ? 'Pressione al suolo' : 'Surface pressure', 'unit' => 'hPa'],
        ['name' => 'precipitation_mm', 'label' => $it ? 'Precipitazioni nell’ora' : 'Hourly precipitation', 'unit' => 'mm'],
    ];
@endphp

@if ($ready)
    <section class="pm-weather-glass relative mt-6 min-w-0 rounded-2xl p-4 sm:p-5" aria-labelledby="session-weather-history-title" data-pm-session-weather-history>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/70">{{ $it ? 'STORICO ORARIO · NASA POWER' : 'HOURLY HISTORY · NASA POWER' }}</p>
                <h3 id="session-weather-history-title" class="mt-1 text-lg font-bold text-white">{{ $it ? 'Meteo all’orario della sessione' : 'Weather at the session time' }}</h3>
                <p class="mt-1 text-xs leading-5 text-white/75">
                    {{ $it ? 'Inizio sessione:' : 'Session started:' }}
                    <strong class="text-white">{{ $start?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</strong>
                    · {{ $it ? 'ora italiana' : 'Italian time' }}
                </p>
                <p class="mt-2 text-xs leading-5 text-white/80">{{ $it ? 'Ricostruzione meteorologica storica da modello, non misurazione sulla pista. Non sostituisce le rilevazioni degli operatori né il bollettino MET Norway.' : 'Historical model reconstruction, not a trackside measurement. It does not replace operator readings or the MET Norway forecast.' }}</p>
            </div>
            @can('update', $session)
                @if ($eligibleDate && $hasLocation)
                    <form method="POST" action="{{ route('sessions.weather-history.store', $session) }}">
                        @csrf
                        <input type="hidden" name="page" value="{{ $page }}">
                        <button type="submit" class="pm-weather-glass-button min-h-11 rounded-xl px-4 py-2.5 text-sm font-bold">{{ $history ? ($it ? 'Aggiorna storico' : 'Refresh history') : ($it ? 'Recupera storico' : 'Fetch history') }}</button>
                    </form>
                @endif
            @endcan
        </div>

        @if ($history)
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-white/90">
                <span class="rounded-full border border-white/25 bg-white/15 px-3 py-1 font-bold">{{ $it ? 'DATI STORICI DA MODELLO' : 'HISTORICAL MODEL DATA' }}</span>
                <span>{{ $it ? 'Ora di riferimento:' : 'Reference hour:' }} {{ \Illuminate\Support\Carbon::parse($history['valid_at'])->timezone('Europe/Rome')->format('d/m/Y H:i') }}</span>
            </div>
            @unless ($historyMatchesSession)
                <p class="mt-3 rounded-xl border border-amber-200/35 bg-amber-500/15 p-3 text-xs font-semibold text-amber-50">{{ $it ? 'L’orario della sessione è cambiato rispetto allo storico salvato. Recupera nuovamente i dati.' : 'The session start time changed since this history was saved. Refresh the historical data.' }}</p>
            @endunless
            <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($fields as $field)
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-3.5">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/75">{{ $field['label'] }}</p>
                        <p class="mt-2 text-lg font-bold text-white">{{ isset($history[$field['name']]) ? number_format((float) $history[$field['name']], 1, ',', '.') . ' ' . $field['unit'] : '—' }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs leading-5 text-white/70">{{ $it ? 'Fonte: NASA POWER (medie orarie da modelli meteorologici). Valori riferiti a una cella geografica, non a sensori sul kart. Posizione:' : 'Source: NASA POWER (hourly averages from meteorological models). Grid values, not kart sensors. Location:' }} {{ number_format((float) $history['latitude'], 4, '.', '') }}°, {{ number_format((float) $history['longitude'], 4, '.', '') }}° · {{ $it ? 'Recuperato il' : 'Retrieved' }} {{ \Illuminate\Support\Carbon::parse($history['captured_at'])->timezone('Europe/Rome')->format('d/m/Y H:i') }}. <a href="https://power.larc.nasa.gov/" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-2">NASA POWER</a></p>
            <p class="mt-1 text-xs leading-5 text-white/80">{{ $it ? 'Temperatura asfalto: disponibile solo nelle misurazioni inserite dagli operatori.' : 'Track temperature is available only from operator-entered measurements.' }}</p>
        @else
            <p class="mt-4 rounded-xl border border-white/15 bg-white/10 p-3 text-sm text-white/90">
                @if (! $eligibleDate)
                    {{ $it ? 'Lo storico orario è recuperabile da tre giorni dopo la sessione (per date dal 2001). Nel frattempo rimangono consultabili previsioni salvate e misure manuali.' : 'Hourly history becomes available three days after the session (from 2001 onward). Saved forecasts and manual readings remain available in the meantime.' }}
                @elseif (! $hasLocation)
                    {{ $it ? 'Per recuperare lo storico, salva le coordinate del circuito oppure una posizione associata alla sessione.' : 'To fetch history, save circuit coordinates or a location associated with the session.' }}
                @else
                    {{ $it ? 'Nessun dato storico ancora salvato. Premi «Recupera storico» per consultare l’ora della sessione.' : 'No historical model data saved yet. Select “Fetch history” to view the session hour.' }}
                @endif
            </p>
        @endif
    </section>
@endif
