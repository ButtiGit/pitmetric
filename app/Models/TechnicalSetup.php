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
        'final_drive' => ['label' => 'Final drive', 'unit' => '', 'group' => 'drivetrain'],
        'fuel_target_l' => ['label' => 'Fuel target', 'unit' => 'L', 'group' => 'drivetrain'],
    ];

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
