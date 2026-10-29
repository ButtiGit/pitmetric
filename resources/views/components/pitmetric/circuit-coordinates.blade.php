@props(['latitude' => null, 'longitude' => null, 'it' => false])

<fieldset class="grid gap-3 sm:col-span-2" data-pm-circuit-coordinates>
    <legend class="pm-label mb-2">{{ $it ? 'Posizione del circuito per il meteo' : 'Circuit location for weather' }}</legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-2">
            <span class="pm-label">{{ $it ? 'Latitudine' : 'Latitude' }}</span>
            <input class="pm-input" name="latitude" type="number" inputmode="decimal" min="-90" max="90" step="any" placeholder="45.00000000" value="{{ $latitude }}">
        </label>
        <label class="grid gap-2">
            <span class="pm-label">{{ $it ? 'Longitudine' : 'Longitude' }}</span>
            <input class="pm-input" name="longitude" type="number" inputmode="decimal" min="-180" max="180" step="any" placeholder="7.00000000" value="{{ $longitude }}">
        </label>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            class="pm-ghost-button inline-flex min-h-11 items-center gap-2 disabled:cursor-wait disabled:opacity-60"
            data-pm-circuit-gps
            data-gps-locating="{{ $it ? 'Rilevamento della posizione in corso…' : 'Detecting your location…' }}"
            data-gps-ready="{{ $it ? 'Coordinate rilevate. Controllale, poi salva il circuito.' : 'Coordinates detected. Review them, then save the circuit.' }}"
            data-gps-unsupported="{{ $it ? 'GPS non disponibile: apri PitMetric su HTTPS e verifica le impostazioni del dispositivo.' : 'Location unavailable: use HTTPS and check your device settings.' }}"
            data-gps-denied="{{ $it ? 'Permesso posizione negato. Abilitalo dalle impostazioni del browser e riprova.' : 'Location permission denied. Allow it in your browser settings and try again.' }}"
            data-gps-timeout="{{ $it ? 'Il GPS non ha risposto in tempo. Riprova oppure inserisci le coordinate a mano.' : 'GPS timed out. Try again or enter the coordinates manually.' }}"
            data-gps-unavailable="{{ $it ? 'Posizione non rilevabile. Controlla il segnale GPS oppure inserisci i valori manualmente.' : 'Location could not be detected. Check GPS signal or enter the coordinates manually.' }}"
        >
            <flux:icon.map-pin class="size-4" />
            {{ $it ? 'Usa posizione attuale' : 'Use current location' }}
        </button>
        <span class="min-w-0 flex-1 text-xs text-pm-muted" role="status" aria-live="polite" data-pm-circuit-gps-status></span>
    </div>
    <p class="text-xs text-pm-muted">{{ $it ? 'Facoltativo. Le coordinate GPS vengono proposte nei campi, ma sono salvate solo quando confermi il modulo. Puoi modificarle o cancellarle.' : 'Optional. GPS coordinates fill the fields but are saved only when you submit the form. You may edit or clear them.' }}</p>
</fieldset>
