<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspaces()->exists();
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $this->belongsToWorkspace($user, $vehicle);
    }

    public function create(User $user): bool
    {
        return $user->workspaces()->exists();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->belongsToWorkspace($user, $vehicle);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->belongsToWorkspace($user, $vehicle);
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $this->belongsToWorkspace($user, $vehicle);
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return false;
    }

    private function belongsToWorkspace(User $user, Vehicle $vehicle): bool
    {
        return $user->workspaces()->whereKey($vehicle->workspace_id)->exists();
    }
}
