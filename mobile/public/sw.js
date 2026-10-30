/* PitMetric's public PWA shell. Never cache authenticated API traffic. */
const CACHE_PREFIX = 'pitmetric-pwa-shell-';
const BUILD_VERSION = '__PWA_VERSION__';
const PRECACHE_ASSETS = '__PWA_ASSETS__';
const CACHE_NAME = `${CACHE_PREFIX}${BUILD_VERSION}`;
const APP_URL = self.registration.scope;

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const response = await fetch(APP_URL, { cache: 'no-store' });
    if (!response.ok) throw new Error('PWA shell unavailable');

    const html = await response.clone().text();
    const assets = (Array.isArray(PRECACHE_ASSETS) ? PRECACHE_ASSETS : [...html.matchAll(/(?:src|href)="([^"]+)"/g)].map(match => match[1]))
      .map((asset) => new URL(asset, APP_URL))
      .filter((url) => url.origin === self.location.origin && url.pathname.startsWith(`${new URL(APP_URL).pathname}assets/`));

    const cache = await caches.open(CACHE_NAME);
    await cache.put(APP_URL, response);
    await cache.addAll(assets.map(asset => asset.href));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const names = await caches.keys();
    await Promise.all(names.filter((name) => name.startsWith(CACHE_PREFIX) && name !== CACHE_NAME).map((name) => caches.delete(name)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith(new URL(APP_URL).pathname)) return;

  event.respondWith((async () => {
    const cache = await caches.open(CACHE_NAME);
    try {
      const fresh = await fetch(request);
      if (fresh.ok && (request.mode === 'navigate' || url.pathname.startsWith(`${new URL(APP_URL).pathname}assets/`))) {
        await cache.put(request, fresh.clone());
      }
      return fresh;
    } catch (error) {
      const cached = await cache.match(request, { ignoreVary: true });
      if (cached) return cached;
      if (request.mode === 'navigate') {
        const shell = await cache.match(APP_URL, { ignoreVary: true });
        if (shell) return shell;
      }
      throw error;
    }
  })());
});
