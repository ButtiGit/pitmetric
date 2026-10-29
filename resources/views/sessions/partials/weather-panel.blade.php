@php
    $weather = $weatherSession?->weather_snapshot;
    $symbol = (string) ($weather['symbol_code'] ?? 'cloudy');
    $sky = match (true) {
        str_contains($symbol, 'thunder') => 'storm',
        str_contains($symbol, 'rain'), str_contains($symbol, 'sleet') => 'rain',
        str_contains($symbol, 'snow') => 'snow',
        str_contains($symbol, 'clearsky'), str_contains($symbol, 'fair') => 'clear',
        default => 'clouds',
    };
    $condition = match ($sky) {
        'storm' => $it ? 'Temporale' : 'Thunderstorm',
        'rain' => $it ? 'Pioggia' : 'Rain',
        'snow' => $it ? 'Neve' : 'Snow',
        'clear' => $it ? 'Cielo sereno' : 'Clear sky',
        default => $it ? 'Nuvoloso / variabile' : 'Cloudy / variable',
    };
    $capturedAt = isset($weather['captured_at']) ? \Illuminate\Support\Carbon::parse($weather['captured_at'])->timezone('Europe/Rome')->format('d/m/Y H:i') : null;
    $forecastAt = isset($weather['forecast_at']) ? \Illuminate\Support\Carbon::parse($weather['forecast_at']) : null;
    $forecastMatchesSession = $forecastAt && $weatherSession?->started_at && abs($forecastAt->diffInMinutes($weatherSession->started_at)) <= 90;
    $weatherCircuit = $weatherSession?->weatherCircuit();
    $weatherLocationLabel = match ($weather['location_source'] ?? null) {
        'circuit' => ($it ? 'Circuito' : 'Circuit').' · '.($weather['circuit_name'] ?? ($it ? 'Posizione salvata' : 'Saved location')),
        'gps' => $it ? 'GPS del dispositivo' : 'Device GPS',
        'manual' => $it ? 'Coordinate manuali' : 'Manual coordinates',
        default => $it ? 'Posizione registrata' : 'Saved location',
    };
@endphp

<section id="session-weather" class="pm-weather-dashboard relative isolate scroll-mt-20 overflow-hidden p-5 text-white sm:p-7" data-sky="{{ $sky }}" aria-labelledby="session-weather-title">
    <div class="pm-weather-glow pointer-events-none absolute -right-16 -top-28 size-72 rounded-full" aria-hidden="true"></div>
    <div class="relative grid min-w-0 gap-7 lg:grid-cols-2 lg:gap-10">
        <div class="flex min-w-0 flex-col gap-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/70">PITMETRIC / {{ $it ? 'METEO PISTA' : 'TRACK WEATHER' }}</p>
                    <h2 id="session-weather-title" class="mt-1 text-xl font-semibold tracking-tight text-white sm:text-2xl">{{ $it ? 'Condizioni in pista' : 'Track conditions' }}</h2>
                    <p class="mt-1 text-xs text-white/70">{{ $it ? 'Previsioni riferite al circuito o alla posizione scelta' : 'Weather for the circuit or chosen location' }}</p>
                </div>
                <div class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.13em] text-white/90">MET NORWAY</div>
            </div>

            @if ($sessions->isNotEmpty())
                <form method="GET" action="{{ route('sessions.index') }}" class="flex min-w-0 flex-wrap items-end gap-2">
                    @if ($sessions->currentPage() > 1)<input type="hidden" name="page" value="{{ $sessions->currentPage() }}">@endif
                    <label class="min-w-0 flex-1 basis-48">
                        <span class="mb-1.5 block text-xs font-semibold text-white/75">{{ $it ? 'Sessione da visualizzare' : 'Session to view' }}</span>
                        <select name="weather_session" class="pm-weather-select min-h-11 w-full min-w-0 rounded-xl px-3 py-2.5 text-sm font-medium text-white" aria-label="{{ $it ? 'Seleziona sessione meteo' : 'Select weather session' }}">
                            @foreach ($sessions as $choice)
                                <option value="{{ $choice->id }}" @selected($weatherSession?->id === $choice->id)>
                                    #{{ $choice->id }} · {{ $choice->vehicle?->name }} · {{ $choice->started_at?->format('d/m H:i') ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <button class="pm-weather-glass-button min-h-11 rounded-xl px-4 py-2.5 text-sm font-semibold" type="submit">{{ $it ? 'Mostra' : 'Show' }}</button>
                </form>
            @endif

            <div class="flex min-w-0 flex-wrap items-center gap-x-6 gap-y-2">
                <div class="pm-weather-temperature font-light leading-none tracking-[-0.08em]">{{ isset($weather['air_temperature_c']) ? round((float) $weather['air_temperature_c']) : '—' }}<span class="align-top text-[0.5em] tracking-normal">°</span></div>
                <div class="min-w-0">
                    <p class="text-lg font-semibold text-white">{{ $weather ? $condition : ($it ? 'Nessuna rilevazione' : 'No forecast saved') }}</p>
                    <p class="mt-1 text-xs leading-5 text-white/75">{{ $weather ? ($it ? 'Ultima acquisizione · ' : 'Captured · ').$capturedAt : ($it ? 'Scegli una sessione e rileva il meteo.' : 'Choose a session and capture its weather.') }}</p>
                    @if ($weather)
                        <p class="mt-1 text-xs font-semibold leading-5 text-white/85">{{ $it ? 'Origine posizione:' : 'Location source:' }} {{ $weatherLocationLabel }}</p>
                        <p class="mt-1 text-xs leading-5 text-white/80">{{ $it ? 'Previsione valida per:' : 'Forecast valid for:' }} {{ $forecastAt?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? ($it ? 'orario non indicato' : 'time unavailable') }}</p>
                        @if ($weatherSession && ! $forecastMatchesSession)
                            <p class="mt-2 rounded-xl border border-amber-200/40 bg-amber-500/15 p-3 text-xs font-semibold text-amber-50">{{ $it ? 'Questo bollettino non è riferito all’ora della sessione. Per conoscere il meteo di quel momento, consulta lo storico orario qui sotto.' : 'This forecast does not match the session time. See the hourly history below for conditions at that time.' }}</p>
                        @endif
                    @endif
                </div>
            </div>

            @if ($weatherSession)
                @can('update', $weatherSession)
                    <div class="flex flex-col gap-3">
                        @if ($weatherCircuit)
                            <div class="pm-weather-glass grid gap-3 rounded-2xl p-3.5 sm:p-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/70">{{ $it ? 'Posizione consigliata' : 'Recommended location' }}</p>
                                    <p class="mt-1 break-words text-sm font-bold text-white">{{ $weatherCircuit->name }}</p>
                                    <p class="mt-1 text-xs text-white/75">{{ number_format((float) $weatherCircuit->latitude, 4, '.', '') }}°, {{ number_format((float) $weatherCircuit->longitude, 4, '.', '') }}°</p>
                                </div>
                                <form method="POST" action="{{ route('sessions.weather.store', $weatherSession) }}">
                                    @csrf
                                    <input type="hidden" name="source" value="circuit">
                                    <input type="hidden" name="page" value="{{ $sessions->currentPage() }}">
                                    <button type="submit" class="pm-weather-action min-h-12 w-full rounded-2xl px-5 py-3 text-sm font-bold sm:w-auto">{{ $weather ? ($it ? 'Aggiorna dal circuito' : 'Update from circuit') : ($it ? 'Rileva meteo del circuito' : 'Get circuit weather') }}</button>
                                </form>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('sessions.weather.store', $weatherSession) }}" data-pm-weather-location class="flex flex-wrap items-start gap-3">
                            @csrf
                            <input type="hidden" name="source" value="gps">
                            <input type="hidden" name="latitude">
                            <input type="hidden" name="longitude">
                            <input type="hidden" name="page" value="{{ $sessions->currentPage() }}">
                            <button type="button" data-pm-weather-gps
                                data-weather-locating="{{ $it ? 'Sto cercando la tua posizione…' : 'Finding your location…' }}"
                                data-weather-saving="{{ $it ? 'Posizione trovata. Importazione in corso…' : 'Location found. Importing weather…' }}"
                                data-weather-unsupported="{{ $it ? 'GPS non disponibile. Apri PitMetric in HTTPS oppure usa le coordinate manuali.' : 'GPS unavailable. Open PitMetric over HTTPS or enter coordinates manually.' }}"
                                data-weather-denied="{{ $it ? 'Permesso GPS negato. Abilita la posizione per PitMetric nelle impostazioni del dispositivo o inserisci le coordinate.' : 'GPS permission denied. Enable location for PitMetric or enter coordinates.' }}"
                                data-weather-timeout="{{ $it ? 'Ricerca GPS scaduta. Riprova all’aperto o inserisci le coordinate.' : 'GPS lookup timed out. Retry outdoors or enter coordinates.' }}"
                                data-weather-unavailable="{{ $it ? 'Posizione non disponibile. Riprova oppure usa le coordinate manuali.' : 'Location unavailable. Retry or enter coordinates manually.' }}"
                                class="{{ $weatherCircuit ? 'pm-weather-glass-button' : 'pm-weather-action' }} inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                                {{ $weatherCircuit ? ($it ? 'Usa invece il GPS del dispositivo' : 'Use device GPS instead') : ($weather ? ($it ? 'Aggiorna da GPS' : 'Update from GPS') : ($it ? 'Rileva meteo con GPS' : 'Get weather from GPS')) }}
                            </button>
                            <p data-pm-weather-status class="min-w-0 flex-1 basis-full text-xs leading-5 text-white/85" role="status" aria-live="polite"></p>
                        </form>
                        <details class="text-xs text-white/80">
                            <summary class="w-fit cursor-pointer py-2 font-semibold underline underline-offset-4">{{ $it ? 'GPS non disponibile? Inserisci le coordinate' : 'No GPS? Enter coordinates manually' }}</summary>
                            <form method="POST" action="{{ route('sessions.weather.store', $weatherSession) }}" class="mt-2 grid max-w-sm gap-3 sm:grid-cols-2">
                                @csrf
                                <input type="hidden" name="source" value="manual">
                                <input type="hidden" name="page" value="{{ $sessions->currentPage() }}">
                                <label class="grid gap-1"><span>{{ $it ? 'Latitudine' : 'Latitude' }}</span><input type="number" name="latitude" inputmode="decimal" step="any" min="-90" max="90" class="pm-weather-select min-h-11 w-full rounded-xl px-3" required placeholder="44.3910"></label>
                                <label class="grid gap-1"><span>{{ $it ? 'Longitudine' : 'Longitude' }}</span><input type="number" name="longitude" inputmode="decimal" step="any" min="-180" max="180" class="pm-weather-select min-h-11 w-full rounded-xl px-3" required placeholder="7.5470"></label>
                                <button type="submit" class="pm-weather-glass-button min-h-11 rounded-xl px-4 font-semibold sm:col-span-2">{{ $it ? 'Importa meteo' : 'Import weather' }}</button>
                            </form>
                        </details>
                    </div>
                @endcan
            @else
                <p class="text-sm leading-6 text-white/75">{{ $it ? 'Prima registra una sessione: il meteo sarà associato al kart e alla giornata.' : 'Record a session first: the weather will be linked to that vehicle and track day.' }}</p>
            @endif
        </div>

        <div class="flex min-w-0 flex-col justify-between gap-5">
            <div class="flex justify-center lg:justify-end" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="pm-weather-illustration h-32 w-40 sm:h-36 sm:w-48" viewBox="0 0 180 150" fill="none">
                    <circle cx="105" cy="59" r="39" fill="#FFD97C" opacity=".20"/>
                    <circle cx="105" cy="59" r="29" fill="#FFD67A"/>
                    <path d="M44 119h90c17 0 25-10 25-23 0-14-11-23-25-23-5 0-10 1-14 4-4-18-20-30-39-30-21 0-37 13-40 33C25 80 15 89 15 102c0 10 9 17 29 17Z" fill="white" fill-opacity=".96"/>
                    <path d="M43 114h94" stroke="#CADCF3" stroke-width="3" stroke-linecap="round" opacity=".7"/>
                    @if ($sky === 'rain' || $sky === 'storm')
                        <path d="M53 126l-5 11m35-11-5 11m34-11-5 11" stroke="#9BDEFF" stroke-width="5" stroke-linecap="round"/>
                    @elseif ($sky === 'snow')
                        <path d="M55 130h8m-4-4v8m35-4h8m-4-4v8" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    @endif
                </svg>
            </div>

            <div>
                <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.13em] text-white/70">{{ $it ? 'PREVISIONE MET NORWAY' : 'MET NORWAY FORECAST' }}</p>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                @foreach ([
                    [$it ? 'Umidità' : 'Humidity', isset($weather['humidity_pct']) ? round((float) $weather['humidity_pct']).'%' : '—'],
                    [$it ? 'Vento' : 'Wind', isset($weather['wind_speed_kmh']) ? number_format((float) $weather['wind_speed_kmh'], 1, ',', '.').' km/h' : '—'],
                    [$it ? 'Pressione' : 'Pressure', isset($weather['pressure_hpa']) ? round((float) $weather['pressure_hpa']).' hPa' : '—'],
                    [$it ? 'Nuvolosità' : 'Cloud cover', isset($weather['cloud_cover_pct']) ? round((float) $weather['cloud_cover_pct']).'%' : '—'],
                    [$it ? 'Pioggia pross. ora' : 'Rain next hour', isset($weather['precipitation_next_hour_mm']) ? number_format((float) $weather['precipitation_next_hour_mm'], 1, ',', '.').' mm' : '—'],
                    [$it ? 'Posizione' : 'Position', $weather ? number_format((float) ($weather['latitude'] ?? 0), 3, '.', '').'°, '.number_format((float) ($weather['longitude'] ?? 0), 3, '.', '').'°' : '—'],
                ] as [$label, $value])
                    <div class="pm-weather-glass min-w-0 rounded-2xl p-3 sm:p-3.5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-white/70">{{ $label }}</p>
                        <p class="mt-2 break-words text-sm font-semibold leading-5 text-white sm:text-base">{{ $value }}</p>
                    </div>
                @endforeach
                </div>
            </div>

            @if (! empty($weather['hourly']))
                <div>
                    <p class="mb-2.5 text-[10px] font-bold uppercase tracking-[0.15em] text-white/75">{{ $it ? 'Prossime ore · previsione salvata' : 'Next hours · saved forecast' }}</p>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        @foreach ($weather['hourly'] as $hour)
                            @php
                                $hourSymbol = (string) ($hour['symbol_code'] ?? 'cloudy');
                                $hourSky = match (true) {
                                    str_contains($hourSymbol, 'thunder') => 'storm',
                                    str_contains($hourSymbol, 'rain'), str_contains($hourSymbol, 'sleet') => 'rain',
                                    str_contains($hourSymbol, 'snow') => 'snow',
                                    str_contains($hourSymbol, 'clearsky'), str_contains($hourSymbol, 'fair') => 'clear',
                                    default => 'clouds',
                                };
                            @endphp
                            <div class="pm-weather-glass flex min-w-0 flex-col items-center gap-1 rounded-2xl px-2 py-2.5 text-center">
                                <span class="text-[11px] font-semibold text-white/75">{{ \Illuminate\Support\Carbon::parse($hour['at'])->timezone('Europe/Rome')->format('H:i') }}</span>
                                <svg class="size-7" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    @if ($hourSky === 'clear')
                                        <circle cx="16" cy="16" r="6" fill="#FFDC81"/>
                                        <path d="M16 3v4m0 18v4M3 16h4m18 0h4M6.8 6.8l2.8 2.8m12.8 12.8 2.8 2.8M25.2 6.8l-2.8 2.8M9.6 22.4l-2.8 2.8" stroke="#FFDC81" stroke-width="2" stroke-linecap="round"/>
                                    @else
                                        <path d="M10 24h13a6 6 0 0 0 0-12c-.9 0-1.8.2-2.6.6a8.5 8.5 0 0 0-16.2 3.1A4.6 4.6 0 0 0 10 24Z" fill="white" fill-opacity=".94"/>
                                        @if ($hourSky === 'rain' || $hourSky === 'storm')
                                            <path d="m11 26-2 4m8-4-2 4m8-4-2 4" stroke="#9DDEFF" stroke-width="2" stroke-linecap="round"/>
                                            @if ($hourSky === 'storm')
                                                <path d="m19 15-4 5h4l-3 5" stroke="#FFC75F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            @endif
                                        @elseif ($hourSky === 'snow')
                                            <path d="M12 26v4m-2-2h4m8-2v4m-2-2h4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                        @endif
                                    @endif
                                </svg>
                                <strong class="text-sm font-semibold text-white">{{ round((float) $hour['temperature_c']) }}°</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-[11px] leading-5 text-white/65">{{ $it ? 'Dati previsionali, non misure di sensori sulla pista. Le coordinate del circuito, del GPS o inserite manualmente vengono arrotondate e inviate a MET Norway solo quando richiedi l’importazione.' : 'Forecast model data, not on-track sensor measurements. Circuit, GPS or manually entered coordinates are rounded and sent to MET Norway only when you request an import.' }} <a href="https://www.met.no/en" target="_blank" rel="noopener noreferrer" class="font-semibold text-white underline underline-offset-2">MET Norway</a> · <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">CC BY 4.0</a></p>
        </div>
    </div>
    @if ($weatherSession)
        <x-pitmetric.weather-session-history :session="$weatherSession" :ready="$historicalWeatherReady" :page="$sessions->currentPage()" :it="$it" :circuit="$weatherCircuit" />
        <x-pitmetric.weather-observations :session="$weatherSession" :history="$weatherObservationHistory" :ready="$weatherObservationsReady" :page="$sessions->currentPage()" :it="$it" />
    @endif
</section>
