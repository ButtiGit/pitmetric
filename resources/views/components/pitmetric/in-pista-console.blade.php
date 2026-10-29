@props([
    'karts', 'kart', 'trackSession', 'activeSession', 'eventEntry',
    'configurationVersion', 'laps', 'lapCount', 'bestLap', 'lapCorrections', 'sessionCaptures', 'coldReading',
    'hotReading', 'setupValues', 'lastUsedSettings',
])

@php
    $isLive = $activeSession instanceof \App\Models\Session;
    $eventName = $eventEntry?->raceEvent?->name ?? $trackSession?->raceEvent?->name;
    $driverName = $eventEntry?->driver?->display_name ?? $trackSession?->eventEntry?->driver?->display_name;
    $weather = $trackSession?->weather_snapshot;
    $formatLap = static fn (int $ms): string => sprintf('%d:%02d.%03d', intdiv($ms, 60000), intdiv($ms % 60000, 1000), $ms % 1000);
    $setupFields = [
        'camber_front_deg' => ['Camber', '°'],
        'caster_front_deg' => ['Caster', '°'],
        'track_width_front_mm' => ['Carreggiata ant.', 'mm'],
        'track_width_rear_mm' => ['Carreggiata post.', 'mm'],
        'final_drive' => ['Rapporto', ''],
    ];
    $lastUsedFields = [
        'camber_front_deg' => ['Camber ant.', '°'],
        'caster_front_deg' => ['Caster ant.', '°'],
        'track_width_front_mm' => ['Carreggiata ant.', 'mm'],
        'track_width_rear_mm' => ['Carreggiata post.', 'mm'],
        'pinion_teeth' => ['Pignone', 'denti'],
        'crown_teeth' => ['Corona', 'denti'],
        'final_drive' => ['Rapporto', ''],
    ];
@endphp

<section class="in-pista" data-in-pista>
    <header class="in-pista-head">
        <div>
            <p class="in-pista-eyebrow"><span class="in-pista-live-dot"></span> TRACKSIDE / PN RENT PILOT</p>
            <h1>In pista<span class="in-pista-head-period">.</span></h1>
            <p>Un kart. Una sessione. Tutto quello che serve, senza cambiare pagina.</p>
        </div>
        <span class="in-pista-head-mark">PIT<span>01</span></span>
    </header>

    <div class="in-pista-context">
        <form action="{{ route('pit-mode.index') }}" method="GET" data-in-pista-kart-select>
            <label for="in-pista-kart">KART ATTIVO</label>
            <div class="in-pista-select-wrap">
                <select id="in-pista-kart" name="kart" aria-label="Seleziona il kart" required>
                    @forelse ($karts as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected($kart?->id === $vehicle->id)>{{ $vehicle->name }}{{ $vehicle->identifier ? ' · '.$vehicle->identifier : '' }}</option>
                    @empty
                        <option value="">Nessun kart attivo</option>
                    @endforelse
                </select>
                <span aria-hidden="true">v</span>
            </div>
        </form>
        <div class="in-pista-context-meta"><span>PILOTA</span><strong>{{ $driverName ?: 'Non assegnato' }}</strong></div>
        <div class="in-pista-context-meta"><span>EVENTO</span><strong>{{ $eventName ?: 'Sessione libera' }}</strong></div>
    </div>

    @if (!$kart)
        <div class="in-pista-empty"><strong>Nessun kart attivo</strong><p>Aggiungi un kart nel Garage per iniziare a registrare dati in pista.</p><a href="{{ route('garage.index') }}">Apri Garage</a></div>
    @else
        <div class="in-pista-session-bar" aria-label="Sessione selezionata">
            <div class="in-pista-session-icon">S<span aria-hidden="true">/</span>{{ $trackSession?->id ?? '—' }}</div>
            <div class="in-pista-session-description">
                <strong>{{ $isLive ? 'Sessione in corso' : ($trackSession ? 'Ultima sessione' : 'Nessuna sessione aperta') }}</strong>
                <span>{{ $trackSession ? ucfirst($trackSession->session_type).' · '.$trackSession->started_at?->format('d/m H:i') : 'Pronta per essere avviata' }}</span>
            </div>
            <span class="in-pista-status {{ $isLive ? 'is-live' : '' }}">{{ $isLive ? 'IN CORSO' : ($trackSession ? 'CONCLUSA' : 'PRONTA') }}</span>
        </div>

        @if (!$isLive)
            <section class="in-pista-start in-pista-panel">
                <div class="in-pista-section-label">01 / PREPARAZIONE</div>
                <h2>Pronti per la prossima uscita?</h2>
                <p>Apri una sessione per {{ $kart->name }}. Pilota, evento e configurazione vengono collegati automaticamente quando disponibili.</p>
                @if ($configurationVersion)
                    <div class="in-pista-start-setup"><span>CONFIGURAZIONE</span><strong>{{ $configurationVersion->configuration?->name ?? 'Configurazione' }} · v{{ $configurationVersion->version_number }}</strong></div>
                    <form action="{{ route('pit-mode.sessions.start') }}" method="POST" class="in-pista-start-form">
                        @csrf
                        <input type="hidden" name="kart_id" value="{{ $kart->id }}">
                        <label for="in-pista-session-type">TIPO SESSIONE</label>
                        <select id="in-pista-session-type" name="session_type">
                            <option value="practice">Prove libere</option><option value="qualifying">Qualifica</option>
                            <option value="heat">Manche</option><option value="prefinal">Prefinale</option>
                            <option value="final">Finale</option><option value="race">Gara</option>
                            <option value="test">Test</option>
                        </select>
                        <button class="in-pista-primary" type="submit">Avvia sessione <span aria-hidden="true">+</span></button>
                    </form>
                @else
                    <p class="in-pista-warning">Per questo kart serve prima una configurazione attiva. I dati delle sessioni precedenti rimangono consultabili.</p>
                    <a href="{{ route('configurations.index') }}" class="in-pista-primary in-pista-link-button">Prepara la configurazione</a>
                @endif
            </section>
        @endif

        @if ($lastUsedSettings)
            <details class="in-pista-recent in-pista-panel" data-in-pista-latest>
                <summary>
                    <div>
                        <span>RIFERIMENTO PER IL KART</span>
                        <strong>Ultime regolazioni utilizzate</strong>
                        <small>{{ $lastUsedSettings['session_date'] }} · {{ $lastUsedSettings['setup_name'] }}</small>
                    </div>
                    <span class="in-pista-recent-expand" aria-hidden="true">Dettagli</span>
                </summary>
                <div class="in-pista-recent-body">
                    <div class="in-pista-recent-grid">
                        @foreach ($lastUsedSettings['values'] as $field => $value)
                            <div>
                                <span>{{ $lastUsedFields[$field][0] }}</span>
                                <strong>{{ $value }}{{ $lastUsedFields[$field][1] ? ' '.$lastUsedFields[$field][1] : '' }}</strong>
                            </div>
                        @endforeach
                    </div>
                    <p>Valori ricavati dall'ultima sessione conclusa, incluse le regolazioni registrate durante la prova. Sono un riferimento, non un nuovo salvataggio.</p>
                </div>
            </details>
        @endif

        @if ($trackSession)
            <section class="in-pista-weather in-pista-panel" aria-label="Meteo della sessione">
                <div class="in-pista-weather-glow" aria-hidden="true"></div>
                <div class="in-pista-weather-heading"><div><span>CONDIZIONI IN PISTA</span><h2>Meteo GPS</h2></div><span class="in-pista-weather-symbol" aria-hidden="true"></span></div>
                <div class="in-pista-weather-main"><strong data-in-pista-weather-temp>{{ is_array($weather) ? round((float) ($weather['air_temperature_c'] ?? 0)).'°' : '—°' }}</strong><span data-in-pista-weather-condition>{{ is_array($weather) ? 'Ultimo aggiornamento '.\Illuminate\Support\Carbon::parse($weather['captured_at'] ?? now())->format('H:i') : 'Rileva le condizioni attuali' }}</span></div>
                <div class="in-pista-weather-details"><span>Vento <strong data-in-pista-weather-wind>{{ is_array($weather) ? ($weather['wind_speed_kmh'] ?? '—').' km/h' : '—' }}</strong></span><span>Umidità <strong data-in-pista-weather-humidity>{{ is_array($weather) ? ($weather['humidity_pct'] ?? '—').'%' : '—' }}</strong></span><span>Pioggia / 1h <strong data-in-pista-weather-rain>{{ is_array($weather) ? ($weather['precipitation_next_hour_mm'] ?? '—').' mm' : '—' }}</strong></span></div>
                @if ($isLive)
                    <form action="{{ route('sessions.weather.store', $trackSession) }}" method="POST" data-in-pista-form data-pm-weather-location>
                        @csrf
                        <input type="hidden" name="latitude"><input type="hidden" name="longitude">
                        <button type="button" class="in-pista-weather-button" data-pm-weather-gps
                            data-weather-unsupported="Per usare il GPS apri il sito in HTTPS."
                            data-weather-locating="Rilevo la posizione…"
                            data-weather-saving="Aggiorno il meteo…"
                            data-weather-denied="Consenti la posizione nelle impostazioni di Safari."
                            data-weather-timeout="GPS non disponibile in tempo utile. Riprova."
                            data-weather-unavailable="Impossibile rilevare la posizione.">Usa GPS e aggiorna</button>
                        <span class="in-pista-weather-message" data-pm-weather-status role="status"></span>
                    </form>
                @endif
                <p class="in-pista-attribution">Previsioni MET Norway (CC BY 4.0), non misure dei sensori · GPS solo su richiesta</p>
            </section>

            <section class="in-pista-phase" aria-labelledby="in-pista-before-title">
                <div class="in-pista-phase-title"><span class="in-pista-phase-num">01</span><div><p>PREPARAZIONE</p><h2 id="in-pista-before-title">Prima della sessione</h2></div></div>
                <div class="in-pista-actions">
                    <button type="button" class="in-pista-action" data-in-pista-dialog-open="in-pista-cold" @disabled(!$isLive)><span class="in-pista-action-icon">○</span><strong>Pressioni fredde</strong><small>{{ $coldReading ? 'Ultime registrate' : '4 gomme · bar' }}</small><span class="in-pista-action-arrow">+</span></button>
                    <button type="button" class="in-pista-action" data-in-pista-dialog-open="in-pista-adjust" @disabled(!$isLive)><span class="in-pista-action-icon">R</span><strong>Regolazioni</strong><small>Camber, caster, carreggiata, rapporto</small><span class="in-pista-action-arrow">+</span></button>
                </div>
            </section>

            <section class="in-pista-phase" aria-labelledby="in-pista-after-title">
                <div class="in-pista-phase-title"><span class="in-pista-phase-num is-after">02</span><div><p>DEBRIEF</p><h2 id="in-pista-after-title">Dopo la sessione</h2></div></div>
                <div class="in-pista-actions">
                    <button type="button" class="in-pista-action" data-in-pista-dialog-open="in-pista-hot" @disabled(!$isLive)><span class="in-pista-action-icon">◉</span><strong>Pressioni calde</strong><small>Rilevate al rientro</small><span class="in-pista-action-arrow">+</span></button>
                    <button type="button" class="in-pista-action" data-in-pista-dialog-open="in-pista-note" @disabled(!$isLive)><span class="in-pista-action-icon">!</span><strong>Nota / problema</strong><small>Osservazioni meccaniche</small><span class="in-pista-action-arrow">+</span></button>
                </div>
                @if ($isLive)
                    <form action="{{ route('pit-mode.sessions.laps.store', $trackSession) }}" method="POST" class="in-pista-lap-entry" data-in-pista-form>
                        @csrf
                        <div><label for="in-pista-lap-input">TEMPO GIRO</label><p>Registra senza uscire dalla schermata</p></div>
                        <input id="in-pista-lap-input" name="lap_time" type="text" required inputmode="decimal" maxlength="20" placeholder="0:52.340" autocomplete="off">
                        <button type="submit">+ Salva tempo</button>
                    </form>
                @endif
            </section>

            <section class="in-pista-summary in-pista-panel" aria-labelledby="in-pista-summary-title">
                <div class="in-pista-section-label">03 / RIEPILOGO</div>
                <div class="in-pista-summary-title"><h2 id="in-pista-summary-title">Quello che è cambiato</h2><span>{{ $trackSession->started_at?->format('d/m/Y') }}</span></div>
                <div class="in-pista-summary-stats">
                    <div><span>GIRI REGISTRATI</span><strong data-in-pista-lap-count>{{ $lapCount }}</strong></div>
                    <div><span>MIGLIOR GIRO</span><strong data-in-pista-best-lap>{{ $bestLap === null ? '—' : $formatLap((int) $bestLap) }}</strong></div>
                    <div><span>SETUP DI PARTENZA</span><strong>{{ $trackSession->setupSnapshot?->name ?? '—' }}</strong></div>
                </div>
                <section class="in-pista-laps" aria-label="Tempi della sessione">
                    <div class="in-pista-laps-heading"><h3>Tempi registrati</h3><span>Ultimi 20 giri · modifica solo tempi manuali</span></div>
                    <div class="in-pista-laps-list" data-in-pista-laps>
                        @forelse ($laps as $lap)
                            @php($corrections = $lapCorrections->get($lap->getKey(), collect()))
                            <article class="in-pista-lap-row" data-in-pista-lap-row="{{ $lap->getKey() }}">
                                <div class="in-pista-lap-row-main">
                                    <span class="in-pista-lap-number">#{{ $lap->lap_number }}</span>
                                    <div class="in-pista-lap-time-wrap"><strong data-in-pista-lap-time>{{ $formatLap((int) $lap->lap_time_ms) }}</strong><small>{{ $lap->source === 'manual' ? 'Manuale' : 'Import' }}</small></div>
                                    <span class="in-pista-lap-edited" data-in-pista-lap-edited @if ($corrections->isEmpty()) hidden @endif>Corretto</span>
                                    @if ($lap->source === 'manual' && $lap->telemetry_import_id === null)
                                        <button type="button" data-in-pista-edit-lap
                                            data-in-pista-lap-action="{{ route('pit-mode.sessions.laps.update', [$trackSession, $lap]) }}"
                                            data-in-pista-lap-number="{{ $lap->lap_number }}"
                                            data-in-pista-lap-ms="{{ $lap->lap_time_ms }}"
                                            data-in-pista-lap-label="{{ $formatLap((int) $lap->lap_time_ms) }}">Correggi</button>
                                    @endif
                                </div>
                                @if ($corrections->isNotEmpty())
                                    <details class="in-pista-lap-history" data-in-pista-lap-history>
                                        <summary>Storico correzioni ({{ $corrections->count() }})</summary>
                                        <ul>
                                            @foreach ($corrections as $correction)
                                                <li><strong>{{ $formatLap((int) ($correction->before_values['lap_time_ms'] ?? 0)) }} a {{ $formatLap((int) ($correction->after_values['lap_time_ms'] ?? 0)) }}</strong><span>{{ $correction->user?->name ?? 'Operatore' }} · {{ $correction->created_at?->format('d/m H:i') }}</span>@if (! empty($correction->after_values['reason']))<p>{{ $correction->after_values['reason'] }}</p>@endif</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </article>
                        @empty
                            <p class="in-pista-laps-empty" data-in-pista-empty-laps>Nessun tempo ancora registrato.</p>
                        @endforelse
                    </div>
                </section>
                <div class="in-pista-setup-summary">
                    @foreach ($setupFields as $field => [$label, $unit])
                        <div><span>{{ $label }}</span><strong data-in-pista-setup-field="{{ $field }}">{{ isset($setupValues[$field]) ? $setupValues[$field].' '.$unit : '—' }}</strong></div>
                    @endforeach
                </div>
                <div class="in-pista-reading-row"><span>Pressioni fredde</span><strong data-in-pista-reading-cold>{{ $coldReading ? implode(' / ', $coldReading->pressures).' bar' : 'Non rilevate' }}</strong></div>
                <div class="in-pista-reading-row"><span>Pressioni calde</span><strong data-in-pista-reading-hot>{{ $hotReading ? implode(' / ', $hotReading->pressures).' bar' : 'Non rilevate' }}</strong></div>
                <h3>Registro della sessione</h3>
                <div class="in-pista-activity" data-in-pista-activity>
                    @forelse ($sessionCaptures as $capture)
                        <article><span>{{ $capture->occurred_at?->format('H:i') }}</span><div><strong>{{ match ($capture->kind) { 'setup_adjustment' => 'Regolazione', 'issue' => 'Problema', default => 'Nota' } }}</strong><p>{{ $capture->kind === 'setup_adjustment' ? implode(' · ', array_keys((array) data_get($capture->payload, 'values', []))) : $capture->notes }}</p></div></article>
                    @empty
                        <p data-in-pista-empty-activity>Nessuna modifica ancora registrata.</p>
                    @endforelse
                </div>
            </section>

            @if ($isLive)
                <form method="POST" action="{{ route('pit-mode.sessions.finish', $trackSession) }}" class="in-pista-finish" data-in-pista-finish>
                    @csrf
                    <button type="submit">Concludi sessione e registra utilizzo <span aria-hidden="true">OK</span></button>
                    <p>Chiudere la sessione aggiorna i contatori di utilizzo e blocca lo storico. I dati non vengono eliminati.</p>
                </form>
            @endif
        @endif
    @endif

    <div class="in-pista-toast" data-in-pista-toast role="status" aria-live="polite" hidden></div>

    @if ($trackSession)
        <dialog class="in-pista-dialog" id="in-pista-lap-correction" aria-label="Correggi un tempo manuale">
            <div class="in-pista-dialog-head"><div><span>REGISTRO TEMPI</span><h2>Correggi giro <span data-in-pista-edit-lap-title></span></h2></div><button type="button" data-in-pista-dialog-close aria-label="Chiudi">×</button></div>
            <p class="in-pista-dialog-hint">Il tempo precedente non viene cancellato: ogni modifica mantiene valore originale, operatore, data e motivo.</p>
            <form method="POST" action="{{ route('pit-mode.sessions.laps.update', [$trackSession, $laps->firstWhere('source', 'manual')?->getKey() ?? 0]) }}" data-in-pista-form data-in-pista-correction>
                @csrf
                @method('PATCH')
                <input name="expected_lap_time_ms" type="hidden">
                <label class="in-pista-dialog-field">Tempo corretto <input name="lap_time" type="text" required maxlength="20" inputmode="decimal" autocomplete="off" placeholder="0:52.340"></label>
                <label class="in-pista-dialog-field">Motivo <textarea name="reason" rows="2" maxlength="500" placeholder="Es. errore di trascrizione (facoltativo)"></textarea></label>
                <button class="in-pista-primary" type="submit">Salva correzione</button>
            </form>
        </dialog>
    @endif

    @if ($isLive)
        @foreach (['cold' => 'Pressioni fredde', 'hot' => 'Pressioni calde'] as $phase => $label)
            <dialog class="in-pista-dialog" id="in-pista-{{ $phase }}" aria-label="{{ $label }}">
                <div class="in-pista-dialog-head"><div><span>{{ $phase === 'cold' ? 'PRIMA DELLA SESSIONE' : 'DOPO LA SESSIONE' }}</span><h2>{{ $label }}</h2></div><button type="button" data-in-pista-dialog-close aria-label="Chiudi">×</button></div>
                <form method="POST" action="{{ route('sessions.tyre-readings.store', $trackSession) }}" data-in-pista-form>
                    @csrf
                    <input type="hidden" name="phase" value="{{ $phase }}">
                    <div class="in-pista-tyre-grid">
                        @foreach (['fl' => 'Anteriore SX', 'fr' => 'Anteriore DX', 'rl' => 'Posteriore SX', 'rr' => 'Posteriore DX'] as $corner => $cornerLabel)
                            <label><span>{{ $cornerLabel }} · bar</span><input name="pressure_{{ $corner }}" type="number" inputmode="decimal" min="0.1" max="10" step="0.01" value="{{ ($phase === 'cold' ? $coldReading : $hotReading)?->pressures[$corner] ?? '' }}" placeholder="0.80"></label>
                        @endforeach
                    </div>
                    <label class="in-pista-dialog-field">Note <input name="notes" maxlength="1000" placeholder="Asfalto, variazioni, comportamento…"></label>
                    <button class="in-pista-primary" type="submit">Salva {{ strtolower($label) }}</button>
                </form>
            </dialog>
        @endforeach

        <dialog class="in-pista-dialog" id="in-pista-adjust" aria-label="Regolazioni del kart">
            <div class="in-pista-dialog-head"><div><span>ASSETTO CORRENTE</span><h2>Regolazioni</h2></div><button type="button" data-in-pista-dialog-close aria-label="Chiudi">×</button></div>
            <p class="in-pista-dialog-hint">Compila solo i valori modificati. Verranno registrati nello storico della sessione senza sovrascrivere lo snapshot iniziale.</p>
            <form method="POST" action="{{ route('pit-mode.sessions.adjustments.store', $trackSession) }}" data-in-pista-form>
                @csrf
                @if ($lastUsedSettings)
                    <div class="in-pista-last-fill">
                        <div><strong>Riparti dall'ultima uscita</strong><span>{{ $lastUsedSettings['session_date'] }} · {{ $lastUsedSettings['setup_name'] }}</span></div>
                        <button type="button" data-in-pista-apply-last>Riprendi valori</button>
                        <p>I valori saranno inseriti nei campi sottostanti. Controllali prima di registrarli.</p>
                        @foreach ($lastUsedSettings['values'] as $field => $value)
                            @if ($field !== 'final_drive' && (! in_array($field, ['pinion_teeth', 'crown_teeth'], true) || isset($lastUsedSettings['values']['pinion_teeth'], $lastUsedSettings['values']['crown_teeth'])))
                                <input type="hidden" data-in-pista-recommended-field="{{ $field }}" value="{{ $value }}">
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="in-pista-tyre-grid">
                    <label><span>Camber ant. · °</span><input name="camber_front_deg" type="number" inputmode="decimal" min="-15" max="15" step="0.1" placeholder="{{ $setupValues['camber_front_deg'] ?? '0' }}"></label>
                    <label><span>Caster ant. · °</span><input name="caster_front_deg" type="number" inputmode="decimal" min="-15" max="30" step="0.1" placeholder="{{ $setupValues['caster_front_deg'] ?? '0' }}"></label>
                    <label><span>Carreggiata ant. · mm</span><input name="track_width_front_mm" type="number" inputmode="numeric" min="500" max="3000" step="1" placeholder="{{ $setupValues['track_width_front_mm'] ?? '—' }}"></label>
                    <label><span>Carreggiata post. · mm</span><input name="track_width_rear_mm" type="number" inputmode="numeric" min="500" max="3000" step="1" placeholder="{{ $setupValues['track_width_rear_mm'] ?? '—' }}"></label>
                    <label><span>Pignone · denti</span><input name="pinion_teeth" type="number" inputmode="numeric" min="1" max="200" step="1" placeholder="{{ $setupValues['pinion_teeth'] ?? '—' }}"></label>
                    <label><span>Corona · denti</span><input name="crown_teeth" type="number" inputmode="numeric" min="1" max="200" step="1" placeholder="{{ $setupValues['crown_teeth'] ?? '—' }}"></label>
                </div>
                <label class="in-pista-dialog-field">Motivo della modifica <input name="notes" maxlength="1000" placeholder="Sottosterzo, poca trazione…"></label>
                <button class="in-pista-primary" type="submit">Registra regolazioni</button>
            </form>
        </dialog>

        <dialog class="in-pista-dialog" id="in-pista-note" aria-label="Nota o problema della sessione">
            <div class="in-pista-dialog-head"><div><span>DEBRIEF TECNICO</span><h2>Nota / problema</h2></div><button type="button" data-in-pista-dialog-close aria-label="Chiudi">×</button></div>
            <form method="POST" action="{{ route('pit-mode.sessions.notes.store', $trackSession) }}" data-in-pista-form>
                @csrf
                <label class="in-pista-dialog-field">Tipo <select name="kind"><option value="note">Nota tecnica</option><option value="issue">Problema meccanico</option></select></label>
                <label class="in-pista-dialog-field">Priorità <select name="severity"><option value="warning">Da controllare</option><option value="critical">Critico</option><option value="info">Osservazione</option></select></label>
                <label class="in-pista-dialog-field">Descrizione <textarea name="note" required maxlength="2000" rows="4" placeholder="Cosa è successo? Che cosa va verificato?"></textarea></label>
                <button class="in-pista-primary" type="submit">Registra nella sessione</button>
            </form>
        </dialog>
    @endif
</section>
