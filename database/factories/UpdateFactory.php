<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Update;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Update>
 */
class UpdateFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'excerpt' => fake()->sentence(18),
            'content' => fake()->paragraphs(5, true),
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
