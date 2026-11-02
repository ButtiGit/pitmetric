<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\RecordSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class E2EPrintSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('E2EPrintSeeder may only run in local or testing environments.');
        }
        $user = User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);
        try {
            $vehicle = Vehicle::create(['name' => 'E2E Print Kart', 'category' => 'kart', 'status' => 'active', 'manufacturer' => 'Print fixture', 'identifier' => 'PRINT-27', 'notes' => 'Printable identification notes.']);
            $type = ComponentType::firstOrCreate(['name' => 'E2E Print engine']);
            $component = Component::create(['name' => 'E2E Print Engine', 'component_type_id' => $type->id, 'serial_number' => 'ENGINE-PRINT-27', 'status' => 'active']);
            ComponentInstallation::create(['vehicle_id' => $vehicle->id, 'component_id' => $component->id, 'installed_at' => now(), 'created_by' => $user->id]);
            $configuration = Configuration::create(['name' => 'E2E Print Configuration', 'vehicle_id' => $vehicle->id, 'status' => 'active']);
            $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);
            $setup = TechnicalSetup::create(['name' => 'E2E Print Setup', 'vehicle_id' => $vehicle->id, 'status' => 'active', 'created_by' => $user->id, 'values' => [
                'camber_mode' => 'notches', 'caster_mode' => 'degrees', 'geometry_side_mode' => 'separate', 'camber_front_left_position' => '2', 'camber_front_right_position' => '3',
                'caster_front_left_deg' => 4, 'caster_front_right_deg' => 5, 'rear_width_mode' => 'both', 'track_width_rear_mm' => 1390, 'rear_axle_length_mm' => 1040,
                'tyre_pressure_fl' => 0.85, 'pinion_teeth' => 11, 'crown_teeth' => 44, 'gear_ratio' => 4,
            ]]);
            $driver = Driver::create(['display_name' => 'E2E Print Driver', 'status' => 'active']);
            $event = RaceEvent::create(['name' => 'E2E Print Weekend', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'status' => 'active', 'created_by' => $user->id]);
            $entry = EventEntry::create(['event_id' => $event->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'configuration_version_id' => $version->id]);
            $session = app(RecordSessionService::class)->record($user->workspaces()->firstOrFail(), $user, [
                'configuration_version_id' => $version->id, 'technical_setup_id' => $setup->id, 'event_id' => $event->id, 'event_entry_id' => $entry->id,
                'session_type' => 'practice', 'started_at' => '2026-11-01 10:00:00', 'duration_minutes' => 20, 'notes' => str_repeat('Complete session note, preserved in the printed report. ', 16).'END-PRINT-NOTES',
            ])['session'];
            for ($lap = 1; $lap <= 75; $lap++) {
                $session->timingLaps()->create(['vehicle_id' => $vehicle->id, 'lap_number' => $lap, 'lap_time_ms' => 60000 + $lap, 'source' => 'manual', 'is_valid' => $lap !== 1]);
            }
            foreach (['cold', 'hot'] as $phase) {
                $session->tyreReadings()->create(['phase' => $phase, 'pressures' => ['fl' => $phase === 'cold' ? 0.8 : 1.05, 'fr' => 0.9, 'rl' => 0.85], 'measured_at' => '2026-11-01 10:20:00', 'created_by' => $user->id, 'notes' => 'Full pressure reading note.']);
            }
            $session->weatherObservations()->create(['values' => ['air_temperature_c' => 18, 'track_temperature_c' => 25], 'measured_at' => '2026-11-01 10:00:00', 'created_by' => $user->id]);
            $session->forceFill(['weather_snapshot' => ['provider' => 'met_norway', 'forecast_at' => '2026-11-01 10:00:00', 'captured_at' => '2026-11-01 09:50:00', 'air_temperature_c' => 17], 'historical_weather_snapshot' => ['provider' => 'nasa_power', 'session_started_at' => '2026-11-01 10:00:00', 'valid_at' => '2026-11-01 10:00:00', 'air_temperature_c' => 16]])->save();
        } finally {
            Auth::logout();
        }
    }
}
