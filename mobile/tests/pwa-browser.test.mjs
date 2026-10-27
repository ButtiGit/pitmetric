import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const mobileRoot = fileURLToPath(new URL('../', import.meta.url));
const source = (relativePath) => readFileSync(resolve(mobileRoot, relativePath), 'utf8');

test('PWA deployment uses the public /mobile/ path without changing the native build', () => {
  const config = source('vite.config.ts');
  assert.match(config, /base: mode === 'pwa' \? '\/mobile\/' : '\/'/);
  assert.match(config, /outDir: mode === 'pwa' \? '\.\.\/public\/mobile' : 'dist'/);
});

test('browser boot uses web-compatible local quick records before native plugins', () => {
  const app = source('src/App.vue');
  assert.match(app, /if \(webPreview\) \{\s*quick\.value = await quickRecords\(\);\s*ready\.value = true;\s*return;/);
  assert.match(app, /webPreview && \['garage', 'gallery', 'account', 'weekend'\]/);
  assert.match(app, /if \(!webPreview\) pending\.value = await pendingCount\(\)/);
});

test('browser mode registers a scoped offline worker and excludes native builds', () => {
  const entry = source('src/main.ts');
  const serviceWorker = source('public/sw.js');
  assert.match(entry, /import\.meta\.env\.MODE === 'pwa'/);
  assert.match(entry, /!Capacitor\.isNativePlatform\(\)/);
  assert.match(entry, /serviceWorker\.register/);
  assert.match(serviceWorker, /self\.registration\.scope/);
  assert.match(serviceWorker, /url\.pathname\.startsWith\(new URL\(APP_URL\)\.pathname\)/);
  assert.match(serviceWorker, /request\.method !== 'GET'/);
});
