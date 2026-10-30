import assert from 'node:assert/strict';
import { after, beforeEach, test } from 'node:test';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { readWebWeather, saveWebWeather } from '../src/web-weather-store.ts';

const oldIndexedDB = globalThis.indexedDB;
const exampleWeather = {
  provider: 'met_norway', latitude: 44.2211, longitude: 7.9944,
  captured_at: '2026-10-29T08:00:00Z', forecast_at: '2026-10-29T09:00:00Z',
  air_temperature_c: 12.6, humidity_pct: 70, wind_speed_kmh: 9.4,
  wind_direction_deg: 90, pressure_hpa: 1015, cloud_cover_pct: 20,
  precipitation_next_hour_mm: 0, symbol_code: 'fair_day',
  hourly: [{ at: '2026-10-29T09:00:00Z', temperature_c: 13, symbol_code: 'fair_day' }],
};

function memoryIndexedDB() {
  const databases = new Map();
  return {
    open(name) {
      const request = { error: null };
      queueMicrotask(() => {
        const upgrade = !databases.has(name);
        if (upgrade) {
          const stores = new Map();
          const database = {
            objectStoreNames: { contains: key => stores.has(key) },
            createObjectStore(key, options) {
              stores.set(key, { keyPath: options.keyPath, data: new Map() });
            },
            transaction(name, mode) {
              const store = stores.get(name);
              assert.ok(store);
              let pending = 0;
              const tx = { oncomplete: null, onerror: null, onabort: null, error: null };
              const enqueue = callback => {
                const item = { onsuccess: null, result: undefined };
                pending++;
                queueMicrotask(() => {
                  item.result = callback();
                  item.onsuccess?.();
                  pending--;
                  if (!pending) setTimeout(() => tx.oncomplete?.(), 0);
                });
                return item;
              };
              tx.objectStore = () => ({
                get: key => enqueue(() => structuredClone(store.data.get(key))),
                put: entry => enqueue(() => {
                  assert.equal(mode, 'readwrite');
                  store.data.set(entry[store.keyPath], structuredClone(entry));
                }),
              });
              tx.abort = () => { tx.onabort?.(); };
              return tx;
            },
            close() {},
          };
          databases.set(name, database);
        }
        request.result = databases.get(name);
        if (upgrade) request.onupgradeneeded?.();
        request.onsuccess?.();
      });
      return request;
    },
  };
}

beforeEach(() => { globalThis.indexedDB = memoryIndexedDB(); });
after(() => {
  if (oldIndexedDB === undefined) delete globalThis.indexedDB;
  else globalThis.indexedDB = oldIndexedDB;
});

test('last MET Norway snapshot survives reopening and can be replaced by a newer GPS result', async () => {
  assert.equal(await readWebWeather(), null);
  await saveWebWeather(exampleWeather);
  assert.deepEqual(await readWebWeather(), exampleWeather);
  const newer = { ...exampleWeather, captured_at: '2026-10-29T09:10:00Z', air_temperature_c: 14.3 };
  await saveWebWeather(newer);
  assert.deepEqual(await readWebWeather(), newer);
});

test('invalid weather cannot replace a previously saved forecast', async () => {
  await saveWebWeather(exampleWeather);
  await assert.rejects(saveWebWeather({ ...exampleWeather, captured_at: 'not a date' }), /non valida/);
  assert.deepEqual(await readWebWeather(), exampleWeather);
});

test('missing IndexedDB reports failed persistence and never invents cached weather', async () => {
  delete globalThis.indexedDB;
  await assert.rejects(readWebWeather(), /non disponibile/);
  await assert.rejects(saveWebWeather(exampleWeather), /non disponibile/);
});

test('PWA and Android share weather status, but keep forecasts in their own persistent stores', () => {
  const app = readFileSync(fileURLToPath(new URL('../src/App.vue', import.meta.url)), 'utf8');
  const native = readFileSync(fileURLToPath(new URL('../src/lib.ts', import.meta.url)), 'utf8');
  assert.match(app, /restoreSavedWeather\(\)/);
  assert.match(app, /readWebWeather\(\)/);
  assert.match(app, /readNativeWeather\(\)/);
  assert.match(app, /saveWebWeather\(latest\)/);
  assert.match(app, /saveNativeWeather\(latest\)/);
  assert.match(app, /Connessione assente/);
  assert.match(app, /Aggiornamento non disponibile offline/);
  assert.match(app, /addEventListener\('offline'/);
  assert.match(app, /networkStatusChange/);
  assert.match(native, /CREATE TABLE IF NOT EXISTS mobile_weather/);
});
