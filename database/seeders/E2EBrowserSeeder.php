<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class E2EBrowserSeeder extends Seeder
{
    public const EMAIL = 'e2e@pitmetric.test';

    public const PASSWORD = 'PitMetric-E2E-2026!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('E2EBrowserSeeder may only run in local or testing environments.');
        }

        $user = User::query()->firstOrNew(['email' => self::EMAIL]);
        $user->forceFill([
            'name' => 'PitMetric E2E',
            'email_verified_at' => now(),
            'database_access_enabled' => true,
            'password' => Hash::make(self::PASSWORD),
        ])->save();

        if (! $user->workspaces()->exists()) {
            $workspace = \App\Models\Workspace::factory()->create(['name' => 'PitMetric E2E Team']);
            $user->workspaces()->attach($workspace, [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        Auth::login($user);

        try {
            $vehicle = Vehicle::query()->firstOrCreate(
                ['name' => 'E2E Baseline Kart'],
                ['category' => 'kart', 'status' => 'active', 'manufacturer' => 'PitMetric'],
            );

            $type = ComponentType::query()->firstOrCreate(['name' => 'E2E Chain']);
            $component = Component::query()->firstOrCreate(
                ['name' => 'E2E Chain #01'],
                ['component_type_id' => $type->id, 'status' => 'active'],
            );

            $metric = UsageMetricType::query()->where('key', 'distance')->firstOrFail();
            ComponentTracker::query()->firstOrCreate([
                'component_id' => $component->id,
                'usage_metric_type_id' => $metric->id,
            ], [
                'is_active' => true,
            ]);

            ComponentInstallation::query()->firstOrCreate([
                'vehicle_id' => $vehicle->id,
                'component_id' => $component->id,
                'removed_at' => null,
            ], [
                'created_by' => $user->id,
                'installed_at' => now(),
            ]);
        } finally {
            Auth::logout();
        }
    }
}
