<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SessionHistoricalWeatherService
{
    private const HISTORY_URL = 'https://power.larc.nasa.gov/api/temporal/hourly/point';

    /** @return array<string, mixed> */
    public function capture(float $latitude, float $longitude, Carbon $sessionStartedAt): array
    {
        $latitude = round($latitude, 4);
        $longitude = round($longitude, 4);
        $hour = $sessionStartedAt->copy()->utc()->startOfHour();
        $cacheKey = sprintf('pitmetric:weather:nasa-power:%.4f:%.4f:%s', $latitude, $longitude, $hour->format('YmdH'));

        /** @var array<string, float|int|null> $values */
        $values = Cache::remember($cacheKey, now()->addDays(7), function () use ($latitude, $longitude, $hour): array {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'PitMetric/1.0 (+https://pitmetric.it)',
            ])
                ->connectTimeout(3)
                ->timeout(12)
                ->get(self::HISTORY_URL, [
                    'parameters' => 'T2M,RH2M,WS10M,PS,PRECTOT',
                    'community' => 'SB',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'start' => $hour->format('Ymd'),
                    'end' => $hour->format('Ymd'),
                    'time-standard' => 'UTC',
                    'format' => 'JSON',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('NASA POWER historical weather request failed with HTTP '.$response->status());
            }

            $parameters = $response->json('properties.parameter');

            if (! is_array($parameters)) {
                throw new RuntimeException('NASA POWER returned no historical weather parameters.');
            }

            $hourCode = $hour->format('YmdH');
            $temperature = $this->reading($parameters, 'T2M', $hourCode);

            if ($temperature === null) {
                throw new RuntimeException('NASA POWER has no historical weather for the selected session hour.');
            }

            $wind = $this->reading($parameters, 'WS10M', $hourCode);
            $pressure = $this->reading($parameters, 'PS', $hourCode, 2);

            return [
                'air_temperature_c' => $temperature,
                'humidity_pct' => $this->reading($parameters, 'RH2M', $hourCode),
                'wind_speed_kmh' => $wind === null ? null : round($wind * 3.6, 1),
                'pressure_hpa' => $pressure === null ? null : round($pressure * 10, 1),
                'precipitation_mm' => $this->reading($parameters, 'PRECTOT', $hourCode),
            ];
        });

        return [
            ...$values,
            'provider' => 'nasa_power',
            'data_type' => 'historical_reanalysis',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'valid_at' => $hour->toIso8601String(),
            'session_started_at' => $sessionStartedAt->toIso8601String(),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /** @param array<string, mixed> $parameters */
    private function reading(array $parameters, string $name, string $hourCode, int $precision = 1): ?float
    {
        $value = data_get($parameters, $name.'.'.$hourCode);

        if (! is_numeric($value) || (float) $value <= -900) {
            return null;
        }

        return round((float) $value, $precision);
    }
}
