<?php

namespace Database\Factories;

use App\Models\ContractExtension;
use App\Models\SeasonContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractExtension> */
class ContractExtensionFactory extends Factory
{
    public function definition(): array
    {
        return ['season_contract_id' => SeasonContract::factory(), 'workspace_id' => fn (array $a) => SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail()->workspace_id,
            'added_races' => 1, 'actor_name' => 'Contract operator', 'reason' => 'Additional race agreed', 'created_by' => fn (array $a) => SeasonContract::query()->whereKey($a['season_contract_id'])->firstOrFail()->created_by];
    }
}
