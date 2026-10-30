import { expect, test } from '@playwright/test';

test('PWA restores records offline and sends the same UUID only on explicit sync or login', async ({ page, context, request }) => {
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
  await context.setOffline(true);
  await page.getByLabel('Tempo manuale', { exact: true }).fill('51.234');
  await page.locator('.manual-card').getByRole('button', { name: 'Salva', exact: true }).click();
  await expect(page.locator('.lap-list-row').getByText('Da inviare', { exact: false })).toBeVisible();
  expect(await sent()).toHaveLength(0);

  await page.reload();
  await page.getByRole('tab', { name: 'Timer' }).click();
  await expect(page.getByText('0:51.234', { exact: true }).first()).toBeVisible();
  expect(await sent()).toHaveLength(0);
  await context.setOffline(false);
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
