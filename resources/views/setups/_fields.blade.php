@php
    $technicalSetup = $setup ?? null;
    $setupValues = $technicalSetup?->values ?? [];
    $fieldValue = static fn (string $key): mixed => old($key, $setupValues[$key] ?? '');
    $initialVehicle = $technicalSetup?->vehicle ?? $vehicles->firstWhere('id', old('vehicle_id'));
    $initialIsKart = $initialVehicle?->category === 'kart';
    $legacyGearParts = [];
    preg_match('/^\s*(\d+)\s*\/\s*(\d+)\s*$/', (string) ($setupValues['final_drive'] ?? ''), $legacyGearParts);
    $pinionValue = old('pinion_teeth', $setupValues['pinion_teeth'] ?? ($legacyGearParts[1] ?? ''));
    $crownValue = old('crown_teeth', $setupValues['crown_teeth'] ?? ($legacyGearParts[2] ?? ''));
@endphp

<div class="grid gap-5" x-data="{ isKart: @js($initialIsKart), advanced: false }">
    <div class="grid gap-4 md:grid-cols-2">
        @if (! $technicalSetup)
            <label class="grid gap-2">
                <span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span>
                <select class="pm-input" name="vehicle_id" required x-on:change="isKart = $event.target.selectedOptions[0]?.dataset.category === 'kart'">
                    <option value="">{{ $it ? 'Seleziona mezzo' : 'Select vehicle' }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" data-category="{{ $vehicle->category }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->name }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <div class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                <p class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</p>
                <p class="mt-2 font-bold text-pm-text">{{ $technicalSetup->vehicle->name }}</p>
            </div>
        @endif

        <label class="grid gap-2">
            <span class="pm-label">{{ $it ? 'Nome setup' : 'Setup name' }}</span>
            <input class="pm-input" name="name" maxlength="120" required value="{{ old('name', $technicalSetup?->name) }}" placeholder="Dry baseline">
        </label>
    </div>

    <label class="grid gap-2">
        <span class="pm-label">{{ $it ? 'Descrizione / condizioni' : 'Description / conditions' }}</span>
        <textarea class="pm-input min-h-20" name="description" maxlength="2000" placeholder="{{ $it ? 'Asciutto, pista gommata, temperatura media...' : 'Dry, rubbered track, medium temperature...' }}">{{ old('description', $technicalSetup?->description) }}</textarea>
    </label>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent"><span x-show="isKart">{{ $it ? 'Pressioni a freddo · prima della sessione' : 'Cold tyre pressures · before the session' }}</span><span x-show="!isKart">{{ $it ? 'Gomme' : 'Tyres' }}</span></p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['tyre_pressure_fl' => 'FL', 'tyre_pressure_fr' => 'FR', 'tyre_pressure_rl' => 'RL', 'tyre_pressure_rr' => 'RR'] as $key => $label)
                <label class="grid gap-2"><span class="pm-label">{{ $label }} · bar</span><input class="pm-input" name="{{ $key }}" type="number" inputmode="decimal" min="0" max="10" step="0.01" value="{{ $fieldValue($key) }}"></label>
            @endforeach
        </div>
        <p x-show="isKart" class="mt-3 text-xs text-pm-muted">{{ $it ? 'Questi valori vengono fissati nello snapshot quando registri la sessione. Le pressioni a caldo si aggiungono dopo, nello storico sessioni.' : 'These values are captured when the session is recorded. Hot pressures are added afterwards in session history.' }}</p>
    </section>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Telaio e geometria' : 'Chassis & geometry' }}</p>
        <p x-show="isKart" class="mt-2 text-xs text-pm-muted">{{ $it ? 'Registra camber e caster anteriori in gradi. I valori rimangono nello snapshot della sessione, anche se modifichi il setup in seguito.' : 'Record front camber and caster in degrees. The values stay in the session snapshot even if the setup changes later.' }}</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Camber ant. (°)' : 'Front camber (°)' }}</span><input class="pm-input" name="camber_front_deg" type="number" inputmode="decimal" min="-15" max="15" step="0.01" value="{{ $fieldValue('camber_front_deg') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Caster ant. (°)' : 'Front caster (°)' }}</span><input class="pm-input" name="caster_front_deg" type="number" inputmode="decimal" min="-15" max="30" step="0.01" value="{{ $fieldValue('caster_front_deg') }}"></label>
            <label class="grid gap-2" x-show="isKart"><span class="pm-label">{{ $it ? 'Carreggiata ant. (mm)' : 'Front track width (mm)' }}</span><input class="pm-input" name="track_width_front_mm" type="number" inputmode="numeric" min="500" max="3000" step="1" value="{{ $fieldValue('track_width_front_mm') }}"></label>
            <label class="grid gap-2" x-show="isKart"><span class="pm-label">{{ $it ? 'Carreggiata post. (mm)' : 'Rear track width (mm)' }}</span><input class="pm-input" name="track_width_rear_mm" type="number" inputmode="numeric" min="500" max="3000" step="1" value="{{ $fieldValue('track_width_rear_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Altezza ant. (mm)' : 'Front ride height (mm)' }}</span><input class="pm-input" name="ride_height_front_mm" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('ride_height_front_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Altezza post. (mm)' : 'Rear ride height (mm)' }}</span><input class="pm-input" name="ride_height_rear_mm" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('ride_height_rear_mm') }}"></label>
            <label class="grid gap-2" x-show="!isKart || advanced"><span class="pm-label">{{ $it ? 'Camber post. (°)' : 'Rear camber (°)' }}</span><input class="pm-input" name="camber_rear_deg" type="number" min="-15" max="15" step="0.01" value="{{ $fieldValue('camber_rear_deg') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Toe ant. (mm)' : 'Front toe (mm)' }}</span><input class="pm-input" name="toe_front_mm" type="number" min="-20" max="20" step="0.01" value="{{ $fieldValue('toe_front_mm') }}"></label>
            <label class="grid gap-2" x-show="!isKart || advanced"><span class="pm-label">{{ $it ? 'Toe post. (mm)' : 'Rear toe (mm)' }}</span><input class="pm-input" name="toe_rear_mm" type="number" min="-20" max="20" step="0.01" value="{{ $fieldValue('toe_rear_mm') }}"></label>
            <label class="grid gap-2" x-show="!isKart || advanced"><span class="pm-label">{{ $it ? 'Barra ant.' : 'Front anti-roll' }}</span><input class="pm-input" name="anti_roll_front" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('anti_roll_front') }}"></label>
            <label class="grid gap-2" x-show="!isKart || advanced"><span class="pm-label">{{ $it ? 'Barra post.' : 'Rear anti-roll' }}</span><input class="pm-input" name="anti_roll_rear" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('anti_roll_rear') }}"></label>
        </div>
        <p x-show="isKart" class="mt-3 text-xs text-pm-muted">{{ $it ? 'Misura sempre la carreggiata con lo stesso riferimento: potrai confrontare i setup fra una sessione e l’altra.' : 'Always measure track width using the same reference points to compare setups across sessions.' }}</p>
    </section>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Trasmissione' : 'Drivetrain' }}</p>
        <div x-show="isKart" x-data="{ pinion: @js((string) $pinionValue), crown: @js((string) $crownValue) }" class="mt-3 grid gap-3 sm:grid-cols-2">
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Denti pignone' : 'Pinion teeth' }}</span><input class="pm-input" name="pinion_teeth" type="number" inputmode="numeric" min="1" max="200" step="1" x-model="pinion" value="{{ $pinionValue }}" placeholder="11"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Denti corona' : 'Crown teeth' }}</span><input class="pm-input" name="crown_teeth" type="number" inputmode="numeric" min="1" max="200" step="1" x-model="crown" value="{{ $crownValue }}" placeholder="43"></label>
            <div class="rounded-lg border border-pm-border bg-pm-panel p-3 sm:col-span-2" aria-live="polite">
                <span class="pm-label">{{ $it ? 'Rapporto calcolato · corona ÷ pignone' : 'Calculated ratio · crown ÷ pinion' }}</span>
                <strong class="mt-1 block font-mono text-lg text-pm-text" x-text="Number(pinion) > 0 && Number(crown) > 0 ? (Number(crown) / Number(pinion)).toFixed(3) + ':1' : '—'"></strong>
            </div>
        </div>
        <label class="mt-3 grid gap-2" x-show="!isKart || advanced"><span class="pm-label">{{ $it ? 'Rapporto finale manuale / precedente' : 'Manual / legacy final drive' }}</span><input class="pm-input" name="final_drive" maxlength="30" value="{{ $fieldValue('final_drive') }}" placeholder="11/43"><span x-show="isKart" class="text-xs text-pm-muted">{{ $it ? 'Usato soltanto quando non specifichi pignone e corona.' : 'Used only when pinion and crown are not specified.' }}</span></label>
        <p x-show="isKart" class="mt-3 text-xs text-pm-muted">{{ $it ? 'Inserisci entrambi i numeri: PitMetric salva anche il formato pignone/corona. I vecchi rapporti restano disponibili nei parametri avanzati.' : 'Enter both values: PitMetric also saves pinion/crown notation. Older ratios remain available under advanced parameters.' }}</p>
    </section>

    <button class="pm-ghost-button justify-center" type="button" x-show="isKart" x-on:click="advanced = !advanced" x-text="advanced ? @js($it ? 'Nascondi parametri avanzati' : 'Hide advanced parameters') : @js($it ? 'Mostra parametri avanzati' : 'Show advanced parameters')"></button>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4" x-show="!isKart || advanced">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Controlli, aero e altri parametri' : 'Controls, aero & other parameters' }}</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="grid gap-2"><span class="pm-label">Brake bias (%)</span><input class="pm-input" name="brake_bias_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('brake_bias_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Differenziale ingresso (%)' : 'Differential entry (%)' }}</span><input class="pm-input" name="differential_entry_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('differential_entry_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Differenziale uscita (%)' : 'Differential exit (%)' }}</span><input class="pm-input" name="differential_exit_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('differential_exit_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Aero anteriore' : 'Front aero' }}</span><input class="pm-input" name="aero_front" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('aero_front') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Aero posteriore' : 'Rear aero' }}</span><input class="pm-input" name="aero_rear" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('aero_rear') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Carburante target (L)' : 'Fuel target (L)' }}</span><input class="pm-input" name="fuel_target_l" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('fuel_target_l') }}"></label>
        </div>
    </section>

    <label class="grid gap-2">
        <span class="pm-label">{{ $it ? 'Costo lavoro (€)' : 'Work cost (€)' }}</span>
        <input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01">
        <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · viene registrato automaticamente nei Costi.' : 'Optional · automatically recorded in Expenses.' }}</span>
    </label>
</div>
