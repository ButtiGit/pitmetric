<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function fakeMobileWeather(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'api.met.no/*' => Http::response([
            'properties' => [
                'timeseries' => [[
                    'time' => '2026-10-28T13:00:00Z',
                    'data' => [
                        'instant' => [
                            'details' => [
                                'air_temperature' => 17.8,
                                'relative_humidity' => 60,
                                'wind_speed' => 2.5,
                                'wind_from_direction' => 180,
                                'air_pressure_at_sea_level' => 1012.6,
                                'cloud_area_fraction' => 35,
                            ],
                        ],
                        'next_1_hours' => [
                            'summary' => ['symbol_code' => 'partlycloudy_day'],
                            'details' => ['precipitation_amount' => 0.1],
                        ],
                    ],
                ]],
            ],
        ], 200, ['Expires' => now()->addMinutes(30)->toRfc7231String()]),
    ]);
}

it('returns the existing MET Norway forecast to an anonymous PWA or Android client', function () {
    Cache::flush();
    fakeMobileWeather();

    $coordinates = ['latitude' => 44.123456, 'longitude' => 7.987654];

    $this->postJson(route('mobile.weather'), $coordinates)
        ->assertSuccessful()
        ->assertJsonPath('data.provider', 'met_norway')
        ->assertJsonPath('data.latitude', 44.1235)
        ->assertJsonPath('data.longitude', 7.9877)
        ->assertJsonPath('data.air_temperature_c', 17.8)
        ->assertJsonPath('data.wind_speed_kmh', 9)
        ->assertJsonPath('data.symbol_code', 'partlycloudy_day')
        ->assertJsonCount(1, 'data.hourly');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.met.no/weatherapi/locationforecast/2.0/compact')
        && str_contains($request->url(), 'lat=44.1235')
        && $request->hasHeader('User-Agent', 'PitMetric/1.0 (+https://pitmetric.it)'));

    $this->postJson(route('mobile.weather'), $coordinates)->assertSuccessful();
    Http::assertSentCount(1);
});

it('rejects invalid mobile coordinates without contacting the forecast provider', function (array $coordinates) {
    Cache::flush();
    fakeMobileWeather();

    $this->postJson(route('mobile.weather'), $coordinates)->assertUnprocessable();
    Http::assertNothingSent();
})->with([
    [['latitude' => 91, 'longitude' => 7.5]],
    [['latitude' => 45, 'longitude' => -181]],
    [['latitude' => 'wrong', 'longitude' => 7.5]],
    [['longitude' => 7.5]],
]);

it('returns a safe error when the provider is unavailable', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['api.met.no/*' => Http::response(['message' => 'unavailable'], 503)]);

    $this->postJson(route('mobile.weather'), [
        'latitude' => 43.2123,
        'longitude' => 8.1234,
    ])->assertStatus(503)
        ->assertJsonStructure(['message'])
        ->assertDontSee('MET Norway weather request failed');
});
