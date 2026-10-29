<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['vehicle_id', 'name', 'description', 'values', 'status', 'created_by'])]
class TechnicalSetup extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    public const FIELD_DEFINITIONS = [
        'tyre_pressure_fl' => ['label' => 'Tyre pressure FL', 'unit' => 'bar', 'group' => 'tyres'],
        'tyre_pressure_fr' => ['label' => 'Tyre pressure FR', 'unit' => 'bar', 'group' => 'tyres'],
        'tyre_pressure_rl' => ['label' => 'Tyre pressure RL', 'unit' => 'bar', 'group' => 'tyres'],
        'tyre_pressure_rr' => ['label' => 'Tyre pressure RR', 'unit' => 'bar', 'group' => 'tyres'],
        'ride_height_front_mm' => ['label' => 'Front ride height', 'unit' => 'mm', 'group' => 'chassis'],
        'ride_height_rear_mm' => ['label' => 'Rear ride height', 'unit' => 'mm', 'group' => 'chassis'],
        'camber_front_deg' => ['label' => 'Front camber', 'unit' => 'deg', 'group' => 'chassis'],
        'caster_front_deg' => ['label' => 'Front caster', 'unit' => 'deg', 'group' => 'chassis'],
        'camber_mode' => ['label' => 'Camber measurement', 'unit' => '', 'group' => 'chassis'],
        'caster_mode' => ['label' => 'Caster measurement', 'unit' => '', 'group' => 'chassis'],
        'geometry_side_mode' => ['label' => 'Front geometry sides', 'unit' => '', 'group' => 'chassis'],
        'camber_front_position' => ['label' => 'Front camber reference', 'unit' => '', 'group' => 'chassis'],
        'caster_front_position' => ['label' => 'Front caster reference', 'unit' => '', 'group' => 'chassis'],
        'camber_front_left_deg' => ['label' => 'Camber left', 'unit' => 'deg', 'group' => 'chassis'],
        'camber_front_right_deg' => ['label' => 'Camber right', 'unit' => 'deg', 'group' => 'chassis'],
        'caster_front_left_deg' => ['label' => 'Caster left', 'unit' => 'deg', 'group' => 'chassis'],
        'caster_front_right_deg' => ['label' => 'Caster right', 'unit' => 'deg', 'group' => 'chassis'],
        'camber_front_left_position' => ['label' => 'Camber left reference', 'unit' => '', 'group' => 'chassis'],
        'camber_front_right_position' => ['label' => 'Camber right reference', 'unit' => '', 'group' => 'chassis'],
        'caster_front_left_position' => ['label' => 'Caster left reference', 'unit' => '', 'group' => 'chassis'],
        'caster_front_right_position' => ['label' => 'Caster right reference', 'unit' => '', 'group' => 'chassis'],
        'track_width_front_mm' => ['label' => 'Front track width', 'unit' => 'mm', 'group' => 'chassis'],
        'track_width_rear_mm' => ['label' => 'Rear track width', 'unit' => 'mm', 'group' => 'chassis'],
        'rear_width_mode' => ['label' => 'Rear axle measurement', 'unit' => '', 'group' => 'chassis'],
        'rear_axle_length_mm' => ['label' => 'Rear axle length', 'unit' => 'mm', 'group' => 'chassis'],
        'camber_rear_deg' => ['label' => 'Rear camber', 'unit' => 'deg', 'group' => 'chassis'],
        'toe_front_mm' => ['label' => 'Front toe', 'unit' => 'mm', 'group' => 'chassis'],
        'toe_rear_mm' => ['label' => 'Rear toe', 'unit' => 'mm', 'group' => 'chassis'],
        'anti_roll_front' => ['label' => 'Front anti-roll', 'unit' => '', 'group' => 'chassis'],
        'anti_roll_rear' => ['label' => 'Rear anti-roll', 'unit' => '', 'group' => 'chassis'],
        'brake_bias_pct' => ['label' => 'Brake bias', 'unit' => '%', 'group' => 'controls'],
        'differential_entry_pct' => ['label' => 'Differential entry', 'unit' => '%', 'group' => 'controls'],
        'differential_exit_pct' => ['label' => 'Differential exit', 'unit' => '%', 'group' => 'controls'],
        'aero_front' => ['label' => 'Front aero', 'unit' => '', 'group' => 'aero'],
        'aero_rear' => ['label' => 'Rear aero', 'unit' => '', 'group' => 'aero'],
        'pinion_teeth' => ['label' => 'Pinion teeth', 'unit' => '', 'group' => 'drivetrain'],
        'crown_teeth' => ['label' => 'Crown teeth', 'unit' => '', 'group' => 'drivetrain'],
        'final_drive' => ['label' => 'Final drive', 'unit' => '', 'group' => 'drivetrain'],
        'gear_ratio' => ['label' => 'Gear reduction', 'unit' => ':1', 'group' => 'drivetrain'],
        'fuel_target_l' => ['label' => 'Fuel target', 'unit' => 'L', 'group' => 'drivetrain'],
    ];

    public const KART_MEASUREMENT_MODES = [
        'degrees' => '°',
        'notches' => 'tacche',
        'holes' => 'fori',
        'position' => 'posizione',
    ];

    public const KART_SIDE_MODES = ['combined', 'separate'];

    public const KART_REAR_WIDTH_MODES = ['track', 'axle', 'both'];

    /** @return array<string, array<int, string>> */
    public static function kartGeometryRules(): array
    {
        $rules = [
            'camber_mode' => ['nullable', 'in:degrees,notches,holes,position'],
            'caster_mode' => ['nullable', 'in:degrees,notches,holes,position'],
            'geometry_side_mode' => ['nullable', 'in:combined,separate'],
            'rear_width_mode' => ['nullable', 'in:track,axle,both'],
            'camber_front_deg' => ['nullable', 'numeric', 'between:-15,15'],
            'caster_front_deg' => ['nullable', 'numeric', 'between:-15,30'],
            'track_width_front_mm' => ['nullable', 'integer', 'between:500,3000'],
            'track_width_rear_mm' => ['nullable', 'integer', 'between:500,3000'],
            'rear_axle_length_mm' => ['nullable', 'integer', 'between:300,2000'],
        ];

        foreach (['camber' => '-15,15', 'caster' => '-15,30'] as $kind => $limits) {
            $rules[$kind.'_front_position'] = ['nullable', 'string', 'max:40'];

            foreach (['left', 'right'] as $side) {
                $rules[$kind.'_front_'.$side.'_deg'] = ['nullable', 'numeric', 'between:'.$limits];
                $rules[$kind.'_front_'.$side.'_position'] = ['nullable', 'string', 'max:40'];
            }
        }

        return $rules;
    }

    /**
     * Keep only readings compatible with the selected units, sides and axle definition.
     * Omitted selectors retain the original degree / combined / track-width conventions.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, int|float|string>
     */
    public static function selectedKartGeometry(array $validated): array
    {
        $keys = ['track_width_front_mm'];

        foreach (['camber', 'caster'] as $kind) {
            $mode = $validated[$kind.'_mode'] ?? 'degrees';
            $suffix = $mode === 'degrees' ? 'deg' : 'position';

            if (($validated['geometry_side_mode'] ?? 'combined') === 'separate') {
                $keys[] = $kind.'_front_left_'.$suffix;
                $keys[] = $kind.'_front_right_'.$suffix;
            } else {
                $keys[] = $kind.'_front_'.$suffix;
            }
        }

        $rearMode = $validated['rear_width_mode'] ?? 'track';
        if ($rearMode !== 'axle') {
            $keys[] = 'track_width_rear_mm';
        }
        if ($rearMode !== 'track') {
            $keys[] = 'rear_axle_length_mm';
        }

        $keys = [...$keys, 'camber_mode', 'caster_mode', 'geometry_side_mode', 'rear_width_mode'];
        $values = [];

        foreach ($keys as $key) {
            if (isset($validated[$key]) && $validated[$key] !== '') {
                $values[$key] = $validated[$key];
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{camber: string, caster: string, rear_width: string}
     */
    public static function kartGeometrySummary(array $values): array
    {
        $summary = [];

        foreach (['camber', 'caster'] as $kind) {
            $mode = $values[$kind.'_mode'] ?? 'degrees';
            $suffix = $mode === 'degrees' ? 'deg' : 'position';

            if (($values['geometry_side_mode'] ?? 'combined') === 'separate') {
                $parts = [];
                foreach (['left' => 'SX', 'right' => 'DX'] as $side => $label) {
                    $reading = $values[$kind.'_front_'.$side.'_'.$suffix] ?? null;
                    if ($reading !== null && $reading !== '') {
                        $parts[] = $label.' '.self::formatKartGeometryReading($reading, $mode);
                    }
                }
                $summary[$kind] = $parts === [] ? '—' : implode(' · ', $parts);
            } else {
                $reading = $values[$kind.'_front_'.$suffix] ?? null;
                $summary[$kind] = $reading === null || $reading === '' ? '—' : self::formatKartGeometryReading($reading, $mode);
            }
        }

        $rearMode = $values['rear_width_mode'] ?? 'track';
        $rearValues = [];
        if ($rearMode !== 'axle' && isset($values['track_width_rear_mm'])) {
            $rearValues[] = ($rearMode === 'both' ? 'Carreggiata ' : '').$values['track_width_rear_mm'].' mm';
        }
        if ($rearMode !== 'track' && isset($values['rear_axle_length_mm'])) {
            $rearValues[] = 'Assale '.$values['rear_axle_length_mm'].' mm';
        }
        $summary['rear_width'] = $rearValues === [] ? '—' : implode(' · ', $rearValues);

        return $summary;
    }

    private static function formatKartGeometryReading(int|float|string $reading, string $mode): string
    {
        $value = trim((string) $reading);

        if ($mode === 'position') {
            return $value;
        }

        $unit = self::KART_MEASUREMENT_MODES[$mode] ?? '°';
        if (($mode === 'notches' && preg_match('/\b(tacca|tacche|notch|notches)\b/iu', $value))
            || ($mode === 'holes' && preg_match('/\b(foro|fori|hole|holes)\b/iu', $value))) {
            return $value;
        }

        return $value.' '.$unit;
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<SetupSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SetupSnapshot::class);
    }

    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }
}
