<?php

namespace Database\Seeders;

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CompleteMaintenanceService;
use App\Services\CreateConfigurationVersionService;
use App\Services\RecordSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class E2EWeekendReportSeeder extends Seeder
{
    public const EVENT = 'E2E Complete Weekend';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Browser fixtures require local or testing environment.');
        }
        $user = User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);
        try {
            $vehicle = Vehicle::create(['name' => 'E2E Weekend Kart', 'category' => 'kart', 'status' => 'active']);
            $type = ComponentType::firstOrCreate(['name' => 'E2E Weekend engine']);
            $component = Component::create(['name' => 'E2E Weekend Engine', 'component_type_id' => $type->id, 'serial_number' => 'WEEKEND-27', 'status' => 'active']);
            ComponentInstallation::create(['vehicle_id' => $vehicle->id, 'component_id' => $component->id, 'installed_at' => '2026-01-01', 'created_by' => $user->id]);
            $configuration = Configuration::create(['name' => 'E2E Weekend Configuration', 'vehicle_id' => $vehicle->id, 'status' => 'active']);
            $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);
            $setup = TechnicalSetup::create(['name' => 'E2E Weekend Setup', 'vehicle_id' => $vehicle->id, 'status' => 'active', 'created_by' => $user->id, 'values' => ['camber_front_deg' => -1, 'caster_front_deg' => 3, 'track_width_rear_mm' => 1390, 'gear_ratio' => 4]]);
            $circuit = Circuit::create(['name' => 'E2E Weekend Circuit']);
            $layout = $circuit->layouts()->create(['name' => 'Main layout', 'length_meters' => 1000, 'is_active' => true]);
            $event = RaceEvent::create(['name' => self::EVENT, 'circuit_layout_id' => $layout->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'status' => 'completed', 'created_by' => $user->id, 'notes' => str_repeat('Weekend overview note preserved in full. ', 25).'END-WEEKEND-NOTES']);
            $driver = Driver::create(['display_name' => 'E2E Weekend Driver', 'status' => 'active']);
            $entry = EventEntry::create(['event_id' => $event->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'configuration_version_id' => $version->id]);
            for ($i = 1; $i <= 3; $i++) {
                $setup->update(['values' => ['camber_front_deg' => -1, 'caster_front_deg' => 3, 'track_width_rear_mm' => 1390, 'gear_ratio' => 3 + $i]]);
                $date = Carbon::parse('2026-11-01 10:00:00', 'UTC')->addHours($i - 1);
                $session = app(RecordSessionService::class)->record($user->workspaces()->firstOrFail(), $user, ['configuration_version_id' => $version->id, 'technical_setup_id' => $setup->id, 'event_id' => $event->id, 'event_entry_id' => $entry->id, 'session_type' => 'practice', 'started_at' => $date->format('Y-m-d H:i:s'), 'duration_minutes' => 20, 'completed_laps' => 10, 'session_cost' => 10, 'notes' => 'E2E Weekend session '.$i])['session'];
                for ($lap = 1; $lap <= 10; $lap++) {
                    $session->timingLaps()->create(['vehicle_id' => $vehicle->id, 'lap_number' => $lap, 'lap_time_ms' => 60000 + $lap, 'source' => 'manual', 'is_valid' => $lap > 1]);
                }
                foreach (['cold', 'hot'] as $phase) {
                    $session->tyreReadings()->create(['phase' => $phase, 'pressures' => ['fl' => $phase === 'cold' ? 0.8 : 1.05, 'fr' => 0.85, 'rl' => 0.9, 'rr' => 0.95], 'measured_at' => $date, 'created_by' => $user->id, 'notes' => 'Recorded weekend pressure note']);
                }
                $session->weatherObservations()->create(['values' => ['air_temperature_c' => 18, 'track_temperature_c' => 25], 'measured_at' => $date, 'created_by' => $user->id]);
                $session->forceFill(['weather_snapshot' => ['provider' => 'met_norway', 'latitude' => 45.0, 'longitude' => 7.0, 'forecast_at' => $date->toIso8601String(), 'captured_at' => $date->toIso8601String(), 'air_temperature_c' => 17], 'historical_weather_snapshot' => ['provider' => 'nasa_power', 'data_type' => 'historical_reanalysis', 'latitude' => 45.0, 'longitude' => 7.0, 'captured_at' => $date->toIso8601String(), 'session_started_at' => $date->toIso8601String(), 'valid_at' => $date->toIso8601String(), 'air_temperature_c' => 16]])->save();
            }
            Session::create(['event_id' => $event->id, 'event_entry_id' => $entry->id, 'vehicle_id' => $vehicle->id, 'configuration_version_id' => $version->id, 'session_type' => 'practice', 'started_at' => '2026-11-01 16:00:00', 'status' => 'draft', 'created_by' => $user->id, 'notes' => 'E2E Weekend provisional session']);
            for ($i = 1; $i <= 6; $i++) {
                EventTask::create(['event_id' => $event->id, 'event_entry_id' => $entry->id, 'title' => 'E2E Weekend task '.$i, 'description' => str_repeat('Full operational task description. ', 15).'END-WEEKEND-TASK-'.$i, 'status' => $i % 2 ? 'todo' : 'done', 'assigned_to' => $user->id, 'completed_by' => $i % 2 ? null : $user->id, 'completed_at' => $i % 2 ? null : now(), 'created_by' => $user->id]);
                Expense::create(['event_id' => $event->id, 'currency' => 'EUR', 'category' => 'travel', 'description' => 'E2E Weekend general expense '.$i, 'amount_cents' => 100, 'occurred_at' => '2026-11-01 12:00:00', 'created_by' => $user->id]);
            }
            $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
            $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'E2E Weekend service', 'interval_value' => 10000, 'is_active' => true]);
            for ($i = 1; $i <= 3; $i++) {
                app(CompleteMaintenanceService::class)->complete($schedule, $user, Carbon::parse('2026-11-01 15:00:00')->addMinutes($i), 'E2E Weekend maintenance '.$i, 1250, 'Complete maintenance notes.', eventId: $event->id);
            }
            EventNote::create(['event_id' => $event->id, 'event_entry_id' => $entry->id, 'kind' => 'technical', 'body' => str_repeat('Recorded technical issue. ', 35).'END-WEEKEND-ISSUE', 'occurred_at' => '2026-11-01 17:00:00', 'created_by' => $user->id]);
        } finally {
            Auth::logout();
        }
    }
}
