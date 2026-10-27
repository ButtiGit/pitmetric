import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { resolve } from 'node:path';

const mobileRoot = fileURLToPath(new URL('../', import.meta.url));
const manifest = JSON.parse(readFileSync(resolve(mobileRoot, 'public/manifest.webmanifest'), 'utf8'));

function assertPngSize(relativePath, size) {
  const png = readFileSync(resolve(mobileRoot, 'public', relativePath));
  assert.equal(png.subarray(0, 8).toString('hex'), '89504e470d0a1a0a');
  assert.equal(png.readUInt32BE(16), size);
  assert.equal(png.readUInt32BE(20), size);
}

test('PWA identity and navigation stay confined to /mobile/', () => {
  assert.equal(manifest.id, '/mobile/');
  assert.equal(manifest.start_url, '/mobile/');
  assert.equal(manifest.scope, '/mobile/');
  assert.equal(manifest.display, 'standalone');
  assert.equal(manifest.lang, 'it');
  assert.equal(manifest.icons.length, 2);
});

test('manifest icons and iOS home-screen icon have the declared sizes', () => {
  for (const icon of manifest.icons) {
    const size = Number(icon.sizes.split('x')[0]);
    assertPngSize(icon.src, size);
  }

  assertPngSize('icons/pitmetric-180.png', 180);
});

test('the shell exposes manifest and iOS install metadata with the Vite base path', () => {
  const html = readFileSync(resolve(mobileRoot, 'index.html'), 'utf8');
  assert.match(html, /href="%BASE_URL%manifest\.webmanifest"/);
  assert.match(html, /href="%BASE_URL%icons\/pitmetric-180\.png"/);
  assert.match(html, /name="apple-mobile-web-app-capable" content="yes"/);
});
