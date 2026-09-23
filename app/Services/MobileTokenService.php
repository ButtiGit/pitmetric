<?php

namespace App\Services;

use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Support\Str;

class MobileTokenService
{
    public function issue(User $user, ?string $deviceName = null): string
    {
        $plainTextToken = 'pm_mob_'.Str::random(72);

        MobileAccessToken::create([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $plainTextToken),
            'device_name' => $deviceName,
            'expires_at' => now()->addDays(120),
        ]);

        return $plainTextToken;
    }

    public function revoke(string $plainTextToken): void
    {
        MobileAccessToken::query()
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->delete();
    }
}
