<?php

namespace Database\Factories;

use App\Models\Configuration;
use App\Models\ConfigurationVersion;
use App\Models\ContractParticipation;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\SeasonContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractParticipation> */
class ContractParticipationFactory extends Factory
{
    public function definition(): array
    {
        return ['season_contract_id' => SeasonContract::factory(),
            'workspace_id' => fn (array $a) => SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail()->workspace_id,
            'event_entry_id' => function (array $a): int {
                $c = SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail();
                $event = RaceEvent::create(['workspace_id' => $c->workspace_id, 'name' => fake()->words(3, true), 'start_date' => $c->starts_on, 'end_date' => $c->starts_on, 'created_by' => $c->created_by]);
                $configuration = Configuration::create(['workspace_id' => $c->workspace_id, 'vehicle_id' => $c->vehicle_id, 'name' => 'Contract fixture', 'status' => 'active']);
                $version = ConfigurationVersion::create(['configuration_id' => $configuration->id, 'version_number' => 1, 'created_by' => $c->created_by]);

                return EventEntry::create(['workspace_id' => $c->workspace_id, 'event_id' => $event->id, 'driver_id' => $c->driver_id, 'vehicle_id' => $c->vehicle_id, 'configuration_version_id' => $version->id])->id;
            },
            'event_id' => fn (array $a) => EventEntry::query()->whereKey($a['event_entry_id'])->firstOrFail()->event_id,
            'driver_id' => fn (array $a) => SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail()->driver_id,
            'vehicle_id' => fn (array $a) => EventEntry::query()->whereKey($a['event_entry_id'])->firstOrFail()->vehicle_id,
            'status' => 'planned', 'revision' => 1, 'created_by' => fn (array $a) => SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail()->created_by];
    }
}
