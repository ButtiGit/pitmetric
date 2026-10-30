import { expect, test } from '@playwright/test';

const session = { authenticated: true, cloudEnabled: true, canWrite: true, user: { id: 12, name: 'PN Rent', email: 'pn@example.test' }, workspace: { id: 50, name: 'PN Rent' } };
const snapshot = { workspace: session.workspace, vehicles: [{ id: 4, name: 'Kart PN', category: 'kart', status: 'active' }],
  events: [], configurations: [], setups: [], maintenance_schedules: [], work_orders: [], setup_fields: {}, drivers: [], timing_laps: [],
  sessions: [{ id: 7, vehicle_id: 4, vehicle: 'Kart PN', session_type: 'practice', started_at: '2026-10-30T09:00:00Z', status: 'draft' }],
};

test('PWA restores records offline and sends the same UUID only on explicit sync or login', async ({ page, context }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const sent = [];
  let nextUser = session;
  await context.route('https://pitmetric.it/api/mobile/**', async route => {
    const request = route.request();
    const headers = { 'access-control-allow-origin': '*', 'access-control-allow-headers': 'authorization,content-type,x-pitmetric-workspace', 'access-control-allow-methods': 'GET,POST,OPTIONS', 'content-type': 'application/json' };
    if (request.method() === 'OPTIONS') return route.fulfill({ status: 204, headers });
    let body = {};
    if (request.url().endsWith('/login')) body = { token: 'browser-test', session: nextUser };
    else if (request.url().endsWith('/me')) body = { session: nextUser };
    else if (request.url().endsWith('/bootstrap')) body = { data: snapshot, synced_at: '2026-10-30T09:30:00Z' };
    else if (request.url().endsWith('/sync')) {
      sent.push(request.postDataJSON());
      body = { duplicate: false, result: { id: 92, entity: 'timing_lap' } };
    }
    await route.fulfill({ status: 200, headers, body: JSON.stringify(body) });
  });

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
  await expect(page.getByText('0 da inviare', { exact: false })).toBeVisible();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await page.locator('ion-select').filter({ hasText: 'Sessione a cui inviare' }).click();
  await page.getByRole('radio', { name: /Kart PN/ }).click();
  await page.getByRole('button', { name: 'OK', exact: true }).click();
  await context.setOffline(true);
  await page.getByLabel('Tempo manuale', { exact: true }).fill('51.234');
  await page.locator('.manual-card').getByRole('button', { name: 'Salva', exact: true }).click();
  await expect(page.getByText('Da inviare', { exact: false }).first()).toBeVisible();
  expect(sent).toHaveLength(0);

  await page.reload();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await expect(page.getByText('0:51.234', { exact: true }).first()).toBeVisible();
  expect(sent).toHaveLength(0);
  await context.setOffline(false);
  await page.getByRole('tab', { name: 'Altro' }).click();
  await page.getByRole('button', { name: 'Account', exact: false }).click();
  await expect(page.getByText('1 da inviare', { exact: false }).last()).toBeVisible();
  expect(sent).toHaveLength(0);
  await page.getByRole('button', { name: 'Sincronizza ora', exact: false }).click();
  await expect(page.getByText('0 da inviare', { exact: false })).toBeVisible();
  expect(sent).toHaveLength(1);
  expect(sent[0].payload).toMatchObject({ session_id: 7, lap_time_ms: 51234 });
  await page.getByRole('button', { name: 'Scollega account', exact: false }).click();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await expect(page.getByText('0:51.234', { exact: true })).toHaveCount(0);
  expect(errors).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});
