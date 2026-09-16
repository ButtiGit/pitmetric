@php
    $technicalSetup = $setup ?? null;
    $setupValues = $technicalSetup?->values ?? [];
    $fieldValue = static fn (string $key): mixed => old($key, $setupValues[$key] ?? '');
@endphp

<div class="grid gap-5">
    <div class="grid gap-4 md:grid-cols-2">
        @if (! $technicalSetup)
            <label class="grid gap-2">
                <span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span>
                <select class="pm-input" name="vehicle_id" required>
                    <option value="">{{ $it ? 'Seleziona mezzo' : 'Select vehicle' }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->name }}</option>
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
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Gomme' : 'Tyres' }}</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['tyre_pressure_fl' => 'FL', 'tyre_pressure_fr' => 'FR', 'tyre_pressure_rl' => 'RL', 'tyre_pressure_rr' => 'RR'] as $key => $label)
                <label class="grid gap-2"><span class="pm-label">{{ $label }} · bar</span><input class="pm-input" name="{{ $key }}" type="number" min="0" max="10" step="0.01" value="{{ $fieldValue($key) }}"></label>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Telaio e geometria' : 'Chassis & geometry' }}</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Altezza ant. (mm)' : 'Front ride height (mm)' }}</span><input class="pm-input" name="ride_height_front_mm" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('ride_height_front_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Altezza post. (mm)' : 'Rear ride height (mm)' }}</span><input class="pm-input" name="ride_height_rear_mm" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('ride_height_rear_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Camber ant. (°)' : 'Front camber (°)' }}</span><input class="pm-input" name="camber_front_deg" type="number" min="-15" max="15" step="0.01" value="{{ $fieldValue('camber_front_deg') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Camber post. (°)' : 'Rear camber (°)' }}</span><input class="pm-input" name="camber_rear_deg" type="number" min="-15" max="15" step="0.01" value="{{ $fieldValue('camber_rear_deg') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Toe ant. (mm)' : 'Front toe (mm)' }}</span><input class="pm-input" name="toe_front_mm" type="number" min="-20" max="20" step="0.01" value="{{ $fieldValue('toe_front_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Toe post. (mm)' : 'Rear toe (mm)' }}</span><input class="pm-input" name="toe_rear_mm" type="number" min="-20" max="20" step="0.01" value="{{ $fieldValue('toe_rear_mm') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Barra ant.' : 'Front anti-roll' }}</span><input class="pm-input" name="anti_roll_front" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('anti_roll_front') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Barra post.' : 'Rear anti-roll' }}</span><input class="pm-input" name="anti_roll_rear" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('anti_roll_rear') }}"></label>
        </div>
    </section>

    <section class="rounded-xl border border-pm-border bg-pm-subtle p-4">
        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'Controlli, aero e trasmissione' : 'Controls, aero & drivetrain' }}</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="grid gap-2"><span class="pm-label">Brake bias (%)</span><input class="pm-input" name="brake_bias_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('brake_bias_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Differenziale ingresso (%)' : 'Differential entry (%)' }}</span><input class="pm-input" name="differential_entry_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('differential_entry_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Differenziale uscita (%)' : 'Differential exit (%)' }}</span><input class="pm-input" name="differential_exit_pct" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('differential_exit_pct') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Aero anteriore' : 'Front aero' }}</span><input class="pm-input" name="aero_front" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('aero_front') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Aero posteriore' : 'Rear aero' }}</span><input class="pm-input" name="aero_rear" type="number" min="0" max="100" step="0.1" value="{{ $fieldValue('aero_rear') }}"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Rapporto finale' : 'Final drive' }}</span><input class="pm-input" name="final_drive" maxlength="30" value="{{ $fieldValue('final_drive') }}" placeholder="11/43"></label>
            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Carburante target (L)' : 'Fuel target (L)' }}</span><input class="pm-input" name="fuel_target_l" type="number" min="0" max="500" step="0.1" value="{{ $fieldValue('fuel_target_l') }}"></label>
        </div>
    </section>

    <label class="grid gap-2">
        <span class="pm-label">{{ $it ? 'Costo lavoro (€)' : 'Work cost (€)' }}</span>
        <input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01">
        <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · viene registrato automaticamente nei Costi.' : 'Optional · automatically recorded in Expenses.' }}</span>
    </label>
</div>
