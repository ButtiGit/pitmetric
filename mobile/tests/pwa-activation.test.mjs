import assert from 'node:assert/strict';
import { after, beforeEach, test } from 'node:test';
import { trackFirstOpen } from '../src/app-activation.ts';
import { memoryStorage } from './helpers/web-storage.mjs';

const originalFetch = globalThis.fetch;
const originalStorage = globalThis.localStorage;
const originalNavigator = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
const response = (body, status = 200) => new Response(JSON.stringify(body), { status });
const accepted = { registered: true, release_key: '2026-11-09-first-open-v1' };

beforeEach(() => {
  globalThis.localStorage = memoryStorage();
  Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { onLine: true } });
});

after(() => {
  globalThis.fetch = originalFetch;
  globalThis.localStorage = originalStorage;
  if (originalNavigator) Object.defineProperty(globalThis, 'navigator', originalNavigator);
  else delete globalThis.navigator;
});

test('first PWA opening reports once and later launches do not call the server', async () => {
  const requests = [];
  globalThis.fetch = async (url, options) => {
    requests.push({ url, options });
    return response(accepted);
  };

  assert.equal(await trackFirstOpen('pwa'), true);
  assert.equal(await trackFirstOpen('pwa'), true);
  assert.equal(requests.length, 1);
  assert.ok(requests[0].url.endsWith('/api/mobile/activation'));
  assert.equal(requests[0].options.credentials, 'omit');
  assert.equal(requests[0].options.method, 'POST');
  const payload = JSON.parse(requests[0].options.body);
  assert.equal(payload.platform, 'pwa');
  assert.match(payload.installation_id, /^[a-f0-9-]{36}$/);
});

test('offline opening preserves the identity and retries when connected', async () => {
  globalThis.navigator.onLine = false;
  globalThis.fetch = async () => { throw Error('Should not fetch while offline'); };
  assert.equal(await trackFirstOpen('pwa'), false);
  const storedId = localStorage.getItem('pitmetric.mobile.installation.v1');
  assert.ok(storedId);

  globalThis.navigator.onLine = true;
  globalThis.fetch = async (_url, options) => {
    assert.equal(JSON.parse(options.body).installation_id, storedId);
    return response(accepted);
  };
  assert.equal(await trackFirstOpen('pwa'), true);
});

test('network failures and incomplete server replies never mark a launch as confirmed', async () => {
  globalThis.fetch = async () => { throw new Error('network'); };
  assert.equal(await trackFirstOpen('android'), false);
  const storedId = localStorage.getItem('pitmetric.mobile.installation.v1');
  globalThis.fetch = async () => response({ registered: true, release_key: 'other-release' });
  assert.equal(await trackFirstOpen('android'), false);
  globalThis.fetch = async (_url, options) => {
    assert.equal(JSON.parse(options.body).installation_id, storedId);
    assert.equal(JSON.parse(options.body).platform, 'android');
    return response(accepted);
  };
  assert.equal(await trackFirstOpen('android'), true);
});

test('simultaneous first opening attempts share one request', async () => {
  let requests = 0;
  globalThis.fetch = async () => {
    requests++;
    return response(accepted);
  };
  assert.deepEqual(await Promise.all([trackFirstOpen('ios'), trackFirstOpen('ios')]), [true, true]);
  assert.equal(requests, 1);
});
