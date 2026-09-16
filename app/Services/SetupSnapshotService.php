<?php

namespace App\Services;

use App\Models\Session;
use App\Models\SetupSnapshot;
use App\Models\TechnicalSetup;
use App\Models\User;

class SetupSnapshotService
{
    public function capture(Session $session, ?TechnicalSetup $technicalSetup, User $user): SetupSnapshot
    {
        $existing = SetupSnapshot::query()->where('session_id', $session->getKey())->first();

        if ($existing instanceof SetupSnapshot) {
            return $existing;
        }

        return SetupSnapshot::create([
            'session_id' => $session->getKey(),
            'technical_setup_id' => $technicalSetup?->getKey(),
            'vehicle_id' => $session->vehicle_id,
            'configuration_version_id' => $session->configuration_version_id,
            'name' => $technicalSetup->name ?? __('Unspecified setup'),
            'values' => $technicalSetup->values ?? [],
            'captured_at' => $session->started_at ?? now(),
            'created_by' => $user->getKey(),
        ]);
    }
}
