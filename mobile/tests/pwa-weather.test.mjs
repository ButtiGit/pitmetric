import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { withLocationPermissions } from '../configure-android-geolocation.mjs';
import { loadDeviceWeather, weatherDescription, weatherHour, weatherSky, windDirection } from '../src/mobile-weather.ts';

const mobileRoot = fileURLToPath(new URL('../', import.meta.url));
const source = path => readFileSync(resolve(mobileRoot, path), 'utf8');

test('weather descriptions and compass directions render MET Norway symbols in Italian', () => {
  assert.equal(weatherSky('clearsky_day'), 'clear');
  assert.equal(weatherSky('rainshowersandthunder_day'), 'storm');
  assert.equal(weatherSky('sleet'), 'rain');
  assert.equal(weatherSky('snowshowers_night'), 'snow');
  assert.equal(weatherDescription('rain'), 'Pioggia');
  assert.equal(windDirection(0), 'N');
  assert.equal(windDirection(225), 'SO');
  assert.equal(windDirection(359), 'N');
  assert.equal(windDirection(null), '—');
  assert.notEqual(weatherHour('2026-10-28T13:00:00Z'), '—');
});

test('a GPS forecast request sends rounded coordinates with no account or session data', async () => {
  const previousFetch = globalThis.fetch;
  let calls = 0;
  globalThis.fetch = async (url, options) => {
    calls++;
    assert.equal(url, 'https://pitmetric.it/api/mobile/weather');
    assert.equal(options.method, 'POST');
    assert.equal(options.headers.Accept, 'application/json');
    assert.deepEqual(JSON.parse(options.body), { latitude: 44.1235, longitude: 7.9877 });
    return new Response(JSON.stringify({ data: {
      provider: 'met_norway', captured_at: '2026-10-28T13:00:00+00:00',
      air_temperature_c: 18.5, symbol_code: 'partlycloudy_day', hourly: [],
    } }), { status: 200, headers: { 'Content-Type': 'application/json' } });
  };
  try {
    const result = await loadDeviceWeather(44.123456, 7.987654);
    assert.equal(result.air_temperature_c, 18.5);
    assert.equal(calls, 1);
  } finally {
    globalThis.fetch = previousFetch;
  }
});

test('invalid GPS coordinates never reach the network', async () => {
  await assert.rejects(() => loadDeviceWeather(91, 8), /non è valida/);
  await assert.rejects(() => loadDeviceWeather(NaN, 8), /non è valida/);
});

test('unavailable weather service keeps a useful error instead of raw API details', async () => {
  const previousFetch = globalThis.fetch;
  globalThis.fetch = async () => new Response(JSON.stringify({ message: 'Internal error' }), { status: 503 });
  try {
    await assert.rejects(() => loadDeviceWeather(44, 8), /temporaneamente non disponibile/);
  } finally {
    globalThis.fetch = previousFetch;
  }
});

test('generated Android manifest receives precisely one of each native location permission', () => {
  const original = '<?xml version="1.0"?><manifest xmlns:android="http://schemas.android.com/apk/res/android"><application android:label="PitMetric" /></manifest>';
  const withPermissions = withLocationPermissions(original);
  assert.match(withPermissions, /android\.permission\.ACCESS_COARSE_LOCATION/);
  assert.match(withPermissions, /android\.permission\.ACCESS_FINE_LOCATION/);
  assert.equal(withLocationPermissions(withPermissions), withPermissions);
  assert.equal((withPermissions.match(/uses-permission/g) || []).length, 2);
});

test('APK build and local native sync both add permissions to the regenerated Android manifest', () => {
  assert.match(source('../.github/workflows/android-apk.yml'), /npx cap sync android\s+node configure-android-geolocation\.mjs/);
  assert.match(source('package.json'), /cap sync && node configure-android-geolocation\.mjs/);
  const app = source('src/App.vue');
  assert.match(app, /active === 'weather'/);
  assert.match(app, /@click="updateWeatherFromGps"/);
  assert.match(app, /weatherError/);
});
