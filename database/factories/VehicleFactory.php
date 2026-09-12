<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Kart #27', 'Track Car', 'Prototype #01']),
            'category' => fake()->randomElement(Vehicle::CATEGORIES),
            'manufacturer' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('Model ??-##'),
            'year' => fake()->optional()->numberBetween(1990, (int) date('Y')),
            'identifier' => fake()->optional()->bothify('ID-####'),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
