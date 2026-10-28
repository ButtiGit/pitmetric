<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SessionWeatherService
{
    private const FORECAST_URL = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';

    /** @return array<string, mixed> */
    public function capture(float $latitude, float $longitude): array
    {
        $latitude = round($latitude, 4);
        $longitude = round($longitude, 4);
        $forecast = $this->forecast($latitude, $longitude);

        return [
            ...$forecast,
            'provider' => 'met_norway',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function forecast(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('pitmetric:weather:met-norway:%.4f:%.4f', $latitude, $longitude);
        $cached = Cache::get($cacheKey);
        /** @var array<string, mixed>|null $cachedForecast */
        $cachedForecast = is_array($cached) && is_array($cached['forecast'] ?? null) ? $cached['forecast'] : null;

        if ($cachedForecast !== null && is_array($cached) && ($cached['expires_at'] ?? 0) > now()->timestamp) {
            return $cachedForecast;
        }

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'PitMetric/1.0 (+https://pitmetric.it)',
        ];

        if (is_array($cached) && is_string($cached['last_modified'] ?? null)) {
            $headers['If-Modified-Since'] = $cached['last_modified'];
        }

        $response = Http::withHeaders($headers)
            ->connectTimeout(3)
            ->timeout(8)
            ->get(self::FORECAST_URL, ['lat' => $latitude, 'lon' => $longitude]);

        if ($response->status() === 304 && $cachedForecast !== null) {
            $forecast = $cachedForecast;
        } elseif ($response->successful()) {
            $forecast = $this->normalize($response->json());
        } else {
            throw new RuntimeException('MET Norway weather request failed with HTTP '.$response->status());
        }

        $expiresAt = strtotime((string) $response->header('Expires')) ?: now()->addMinutes(20)->timestamp;

        Cache::put($cacheKey, [
            'forecast' => $forecast,
            'expires_at' => max($expiresAt, now()->addMinute()->timestamp),
            'last_modified' => $response->header('Last-Modified') ?: (is_array($cached) ? ($cached['last_modified'] ?? null) : null),
        ], now()->addDay());

        return $forecast;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(mixed $payload): array
    {
        $timeseries = data_get($payload, 'properties.timeseries');
        $series = data_get($timeseries, '0');
        $details = data_get($series, 'data.instant.details');
        $forecastTime = data_get($series, 'time');

        if (! is_array($details) || ! is_numeric($details['air_temperature'] ?? null) || ! is_string($forecastTime)) {
            throw new RuntimeException('MET Norway returned an incomplete weather forecast.');
        }

        $wind = $this->numericOrNull($details['wind_speed'] ?? null);
        $symbol = data_get($series, 'data.next_1_hours.summary.symbol_code')
            ?? data_get($series, 'data.next_6_hours.summary.symbol_code');

        $hourly = [];

        foreach (array_slice(is_array($timeseries) ? $timeseries : [], 0, 6) as $hour) {
            $temperature = data_get($hour, 'data.instant.details.air_temperature');
            $time = data_get($hour, 'time');

            if (! is_numeric($temperature) || ! is_string($time)) {
                continue;
            }

            $hourSymbol = data_get($hour, 'data.next_1_hours.summary.symbol_code');
            $hourly[] = [
                'at' => $time,
                'temperature_c' => round((float) $temperature, 1),
                'symbol_code' => is_string($hourSymbol) ? $hourSymbol : 'cloudy',
            ];
        }

        return [
            'forecast_at' => $forecastTime,
            'air_temperature_c' => round((float) $details['air_temperature'], 1),
            'humidity_pct' => is_numeric($details['relative_humidity'] ?? null) ? (int) round((float) $details['relative_humidity']) : null,
            'wind_speed_kmh' => $wind === null ? null : round($wind * 3.6, 1),
            'wind_direction_deg' => $this->numericOrNull($details['wind_from_direction'] ?? null),
            'pressure_hpa' => $this->numericOrNull($details['air_pressure_at_sea_level'] ?? null),
            'cloud_cover_pct' => $this->numericOrNull($details['cloud_area_fraction'] ?? null),
            'precipitation_next_hour_mm' => $this->numericOrNull(data_get($series, 'data.next_1_hours.details.precipitation_amount')),
            'symbol_code' => is_string($symbol) ? $symbol : 'cloudy',
            'hourly' => $hourly,
        ];
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 1) : null;
    }
}
