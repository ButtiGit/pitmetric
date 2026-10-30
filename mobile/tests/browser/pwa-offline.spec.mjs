import { expect, test as base, devices, webkit } from '@playwright/test';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const test = base.extend({
  context: async ({ context, browserName }, use) => {
    if (browserName !== 'webkit') return use(context);
    // WebKit's ephemeral/private contexts do not preserve CacheStorage across reload.
    // An installed PWA uses a persistent profile: microsoft/playwright#41618.
    const profile = await mkdtemp(join(tmpdir(), 'pitmetric-webkit-'));
    const persistent = await webkit.launchPersistentContext(profile, { ...devices['iPhone 13'], headless: true });
    try { await use(persistent); }
    finally { await persistent.close(); await rm(profile, { recursive: true, force: true }); }
  },
});

test('PWA restores records offline and sends the same UUID only on explicit sync or login', async ({ page, context, request, browserName }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('console', message => {
    if (message.type() === 'error' && message.text().includes('TypeError')) errors.push(message.text());
  });
  await request.post('/__test/reset');
  const sent = async () => (await request.get('/__test/operations')).json();

  await page.goto('/mobile/');
  await page.evaluate(async () => {
    await navigator.serviceWorker.ready;
    if (!navigator.serviceWorker.controller) await new Promise(resolve => navigator.serviceWorker.addEventListener('controllerchange', resolve, { once: true }));
  });
  await page.getByRole('tab', { name: 'Altro' }).click();
  await page.getByRole('button', { name: 'Account', exact: false }).click();
  await page.getByLabel('Email', { exact: true }).fill('pn@example.test');
  await page.getByLabel('Password', { exact: true }).fill('password');
  await page.getByRole('button', { name: 'Accedi', exact: true }).last().click();
  await expect(page.getByText('Dati del team scaricati:', { exact: false })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Sincronizza ora', exact: false })).toBeEnabled();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await page.locator('ion-select').filter({ hasText: 'Sessione a cui inviare' }).click();
  await page.getByRole('radio', { name: /Kart PN/ }).click();
  await page.getByRole('button', { name: 'OK', exact: true }).click();
  // WebKit 2359 rejects SW navigation before cache lookup when setOffline is used.
  // Exercise real socket failures instead: https://github.com/microsoft/playwright/issues/42775
  if (browserName === 'webkit') await request.post('/__test/offline');
  else await context.setOffline(true);
  await page.evaluate(() => {
    window.pitmetricOriginalAdd = IDBObjectStore.prototype.add;
    IDBObjectStore.prototype.add = function () { throw new DOMException('Test quota failure', 'QuotaExceededError'); };
  });
  await page.getByLabel('Tempo manuale', { exact: true }).fill('51.234');
  await page.locator('.manual-card').getByRole('button', { name: 'Salva', exact: true }).click();
  await expect(page.getByLabel('Tempo manuale', { exact: true })).toHaveValue('51.234');
  await expect(page.getByText('Salvataggio offline non riuscito:', { exact: false })).toBeVisible();
  await page.evaluate(() => { IDBObjectStore.prototype.add = window.pitmetricOriginalAdd; delete window.pitmetricOriginalAdd; });
  await page.locator('.manual-card').getByRole('button', { name: 'Salva', exact: true }).click();
  await expect(page.locator('.lap-list-row').getByText('Da inviare', { exact: false })).toBeVisible();
  expect(await sent()).toHaveLength(0);

  const reopened = await page.reload();
  expect(reopened.fromServiceWorker()).toBe(true);
  await page.getByRole('tab', { name: 'Timer' }).click();
  await expect(page.getByText('0:51.234', { exact: true }).first()).toBeVisible();
  expect(await sent()).toHaveLength(0);
  if (browserName === 'webkit') await request.post('/__test/online');
  else await context.setOffline(false);
  await page.getByRole('tab', { name: 'Altro' }).click();
  await page.getByRole('button', { name: 'Account', exact: false }).click();
  await expect(page.getByText('1 da inviare', { exact: false }).last()).toBeVisible();
  expect(await sent()).toHaveLength(0);
  await page.getByRole('button', { name: 'Sincronizza ora', exact: false }).click();
  await expect(page.getByText('0 da inviare', { exact: false })).toBeVisible();
  expect(await sent()).toHaveLength(1);
  expect((await sent())[0].payload).toMatchObject({ session_id: 7, lap_time_ms: 51234 });
  await page.getByRole('button', { name: 'Scollega account', exact: false }).click();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await expect(page.getByText('0:51.234', { exact: true })).toHaveCount(0);
  expect(errors).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});

test.afterEach(async ({ page }, testInfo) => {
  if (testInfo.status !== testInfo.expectedStatus) {
    console.log('PWA failure UI:', await page.locator('body').innerText());
  }
});
