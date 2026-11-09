<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class StudioAppActivations
{
    public const RELEASE_KEY = '2026-11-09-first-open-v1';

    public function record(string $installationId, string $platform): void
    {
        DB::table('studio_app_activations')->insertOrIgnore([
            'release_key' => self::RELEASE_KEY,
            'platform' => $platform,
            'installation_hash' => hash_hmac('sha256', $installationId, (string) config('app.key')),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{total: int, pwa: int, android: int, ios: int}
     */
    public function totals(): array
    {
        $counts = DB::table('studio_app_activations')
            ->where('release_key', self::RELEASE_KEY)
            ->select('platform')
            ->selectRaw('COUNT(*) AS activations')
            ->groupBy('platform')
            ->pluck('activations', 'platform');

        return [
            'total' => (int) $counts->sum(),
            'pwa' => (int) $counts->get('pwa', 0),
            'android' => (int) $counts->get('android', 0),
            'ios' => (int) $counts->get('ios', 0),
        ];
    }
}
