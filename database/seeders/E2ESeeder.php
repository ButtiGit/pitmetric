<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->withDatabaseAccess()
            ->create([
                'name' => 'PitMetric E2E Owner',
                'email' => 'e2e@pitmetric.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);
    }
}
