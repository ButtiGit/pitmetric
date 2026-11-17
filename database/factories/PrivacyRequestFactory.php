<?php

namespace Database\Factories;

use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrivacyRequest> */
class PrivacyRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'access',
            'status' => 'received',
            'details' => 'Vorrei una copia dei miei dati personali.',
            'history' => [],
            'due_at' => now()->addMonthNoOverflow(),
        ];
    }
}
