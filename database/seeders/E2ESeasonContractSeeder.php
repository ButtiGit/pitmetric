<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\SeasonContractService;
use App\Services\WorkspaceContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class E2ESeasonContractSeeder extends Seeder
{
    public const OPERATOR_EMAIL = 'e2e-contract-operator@pitmetric.test';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Browser fixtures require local or testing environment.');
        }
        $user = User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);
        try {
            $workspace = app(WorkspaceContext::class)->personal($user);
            $service = app(SeasonContractService::class);
            $main = $this->pair($user, 'E2E Contract');
            $contract = $service->create($workspace, $user, ['driver_id' => $main['driver']->id, 'vehicle_id' => $main['vehicle']->id, 'season' => 'E2E Main 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'initial_races' => 5, 'notes' => str_repeat('Original season agreement note. ', 25).'END-CONTRACT-NOTES']);
            $service->extend($contract, $user, ['revision' => 1, 'added_races' => 2, 'reason' => 'Two additional races agreed']);
            for ($i = 1; $i <= 8; $i++) {
                $entry = $this->entry($user, $main, 'E2E Contract weekend '.$i, sprintf('2026-11-%02d', $i));
                $row = $service->attach($entry, $user, ['season_contract_id' => $contract->id, 'revision' => 0,
                    'reason' => $i === 8 ? str_repeat('Complete booking reason kept in history. ', 15).'END-CONTRACT-HISTORY' : 'Race booking '.$i]);
                if ($i <= 4) {
                    $service->change($row, $user, ['revision' => 1, 'status' => match ($i) {
                        1,2 => 'confirmed', 3 => 'absent', default => 'cancelled'
                    }, 'reason' => 'Manual race outcome '.$i]);
                }
            }
            $ui = $this->pair($user, 'E2E Contract UI');
            $this->entry($user, $ui, 'E2E Contract UI weekend', '2026-11-01');
            $quota = $this->pair($user, 'E2E Contract Quota');
            $last = $service->create($workspace, $user, ['driver_id' => $quota['driver']->id, 'vehicle_id' => $quota['vehicle']->id, 'season' => 'E2E Last Place', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'initial_races' => 1]);
            foreach (['A', 'B'] as $key) {
                $entry = $this->entry($user, $quota, 'E2E Quota weekend '.$key, '2026-11-01');
                $service->attach($entry, $user, ['season_contract_id' => $last->id, 'revision' => 0, 'reason' => 'Last allowance concurrency fixture']);
            }
            $operator = User::factory()->withDatabaseAccess()->create(['email' => self::OPERATOR_EMAIL, 'password' => Hash::make(E2EBrowserSeeder::PASSWORD)]);
            $operator->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'mechanic_engineer', 'status' => 'active']]);
        } finally {
            Auth::logout();
        }
    }

    /** @return array{driver: Driver, vehicle: Vehicle, version_id: int} */
    private function pair(User $user, string $name): array
    {
        $driver = Driver::create(['display_name' => $name.' Driver', 'status' => 'active']);
        $vehicle = Vehicle::create(['name' => $name.' Kart', 'category' => 'kart', 'status' => 'active']);
        $type = ComponentType::firstOrCreate(['name' => 'Contract test engine']);
        $component = Component::create(['name' => $name.' Engine', 'component_type_id' => $type->id, 'status' => 'active']);
        ComponentInstallation::create(['vehicle_id' => $vehicle->id, 'component_id' => $component->id, 'installed_at' => '2026-01-01', 'created_by' => $user->id]);
        $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => $name.' Configuration', 'status' => 'active']);
        $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);

        return ['driver' => $driver, 'vehicle' => $vehicle, 'version_id' => (int) $version->id];
    }

    /** @param array{driver: Driver, vehicle: Vehicle, version_id: int} $pair */
    private function entry(User $user, array $pair, string $name, string $date): EventEntry
    {
        $event = RaceEvent::create(['name' => $name, 'start_date' => $date, 'end_date' => $date, 'status' => 'completed', 'created_by' => $user->id]);

        return EventEntry::create(['event_id' => $event->id, 'driver_id' => $pair['driver']->id, 'vehicle_id' => $pair['vehicle']->id, 'configuration_version_id' => $pair['version_id']]);
    }
}
