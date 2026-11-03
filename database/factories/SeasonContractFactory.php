<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\SeasonContract;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SeasonContract> */
class SeasonContractFactory extends Factory
{
    public function definition(): array
    {
        return ['workspace_id' => auth()->check() ? app(WorkspaceContext::class)->currentId(auth()->user()) : Workspace::factory(),
            'driver_id' => fn (array $a) => Driver::create(['workspace_id' => $a['workspace_id'], 'display_name' => fake()->name(), 'status' => 'active'])->id,
            'vehicle_id' => fn (array $a) => Vehicle::factory()->create(['workspace_id' => $a['workspace_id'], 'category' => 'kart'])->id,
            'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'initial_races' => 5,
            'status' => 'active', 'revision' => 1, 'created_by' => fn () => auth()->id() ?? User::factory()->create()->id];
    }
}
