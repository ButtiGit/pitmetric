<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventScheduleItem;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class E2ETracksideSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('E2ETracksideSeeder may only run in local or testing environments.');
        }

        $user = User::query()->where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);

        try {
            $vehicle = Vehicle::query()->where('name', 'E2E Baseline Kart')->firstOrFail();
            $component = Component::query()->where('name', 'E2E Chain #01')->firstOrFail();

            $configuration = Configuration::query()->firstOrCreate([
                'vehicle_id' => $vehicle->id,
                'name' => 'E2E Baseline Build',
            ], [
                'description' => 'Stable physical-state fixture for browser E2E.',
                'status' => 'active',
            ]);

            $version = $configuration->versions()->orderByDesc('version_number')->first();
            if ($version === null) {
                $version = app(CreateConfigurationVersionService::class)->create(
                    $configuration,
                    $user,
                    [$component->id],
                    'E2E baseline physical snapshot',
                );
            }

            $driver = Driver::query()->firstOrCreate([
                'display_name' => 'E2E Driver',
            ], [
                'racing_number' => '27',
                'status' => 'active',
            ]);

            $event = RaceEvent::query()->updateOrCreate([
                'name' => 'E2E Trackside Weekend',
            ], [
                'start_date' => now()->startOfDay(),
                'end_date' => now()->addDay()->startOfDay(),
                'status' => 'active',
                'championship' => 'PitMetric Browser E2E',
                'round_label' => 'Mobile UX',
                'created_by' => $user->id,
            ]);

            $entry = EventEntry::query()->updateOrCreate([
                'event_id' => $event->id,
                'vehicle_id' => $vehicle->id,
            ], [
                'driver_id' => $driver->id,
                'configuration_version_id' => $version->id,
                'entry_number' => '27',
            ]);

            EventScheduleItem::query()->updateOrCreate([
                'event_id' => $event->id,
                'label' => 'E2E Trackside Practice',
            ], [
                'event_entry_id' => $entry->id,
                'session_type' => 'practice',
                'starts_at' => now()->addMinutes(30),
                'duration_minutes' => 20,
                'status' => 'ready',
                'notes' => 'Ready for mobile trackside regression coverage.',
                'created_by' => $user->id,
            ]);
        } finally {
            Auth::logout();
        }
    }
}
