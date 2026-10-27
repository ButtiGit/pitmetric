<?php

namespace Database\Factories;

use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Championship> */
class ChampionshipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'season' => '2026',
            'status' => 'planned',
            'points' => [1 => 2500, 2 => 1800, 3 => 1500, 4 => 1200, 5 => 1000, 6 => 800, 7 => 600, 8 => 400, 9 => 200, 10 => 100],
        ];
    }
}
