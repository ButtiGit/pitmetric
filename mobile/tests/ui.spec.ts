import { expect, test } from '@playwright/test';

// Browser UI tests replace only the native SQLite bridge. Vue, Ionic, local
// records, validation and interaction handlers run as shipped.
test.beforeEach(async ({ page }) => {
  page.on('pageerror', error => { throw error; });
  await page.addInitScript(() => localStorage.setItem('pitmetric.mobile.onboarding.v1', 'done'));
  await page.route('**/node_modules/.vite/deps/@capacitor-community_sqlite.js*', route => route.fulfill({
    contentType: 'application/javascript',
    body: `export const CapacitorSQLite = {};
      export class SQLiteConnection {
        async createConnection() {
          return { open: async () => {}, execute: async () => ({}), query: async () => ({values: []}), run: async () => ({}) };
        }
      }`,
  }));
});

test('local lap persists and deletion requires confirmation', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'In pista', exact: true }).click();
  await page.getByLabel('Tempo sul giro', { exact: true }).fill('1:23.456');
  await page.getByRole('button', { name: 'Salva', exact: true }).click();
  await expect(page.locator('.lap-list-row')).toHaveCount(1);
  await page.locator('.lap-list-row').getByRole('button', { name: /^Elimina/ }).click();
  await page.getByRole('button', { name: 'Annulla', exact: true }).click();
  await expect(page.locator('.lap-list-row')).toHaveCount(1);
  await page.reload();
  await page.getByRole('button', { name: 'In pista', exact: true }).click();
  await expect(page.locator('.lap-list-row')).toHaveCount(1);
  await page.locator('.lap-list-row').getByRole('button', { name: /^Elimina/ }).click();
  await page.getByRole('button', { name: 'Elimina', exact: true }).click();
  await expect(page.locator('.lap-list-row')).toHaveCount(0);
});

test('session uses local time and rejects invalid values without saving', async ({ page }) => {
  await page.clock.setFixedTime(new Date('2026-09-30T08:30:00Z'));
  await page.goto('/');
  await page.getByRole('navigation').getByRole('button', { name: 'Sessioni', exact: true }).click();
  await expect(page.getByLabel('Inizio', { exact: true })).toHaveValue('2026-09-30T10:30');
  await page.getByLabel('Inizio', { exact: true }).fill('');
  await page.getByRole('button', { name: 'Salva sessione', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('data e un orario validi');
  await expect(page.locator('.activity-row')).toHaveCount(0);
  await page.getByLabel('Inizio', { exact: true }).fill('2026-09-30T10:30');
  await page.getByLabel('Best lap', { exact: true }).fill('bad-time');
  await page.getByRole('button', { name: 'Salva sessione', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('tempo valido');
  await expect(page.locator('.activity-row')).toHaveCount(0);
  await page.getByLabel('Best lap', { exact: true }).fill('1:23.456');
  await page.getByRole('button', { name: 'Salva sessione', exact: true }).click();
  await expect(page.locator('.activity-row')).toHaveCount(1);
  const records = await page.evaluate(() => JSON.parse(localStorage.getItem('pitmetric.quick-records') || '[]'));
  expect(records.find((record: { kind: string }) => record.kind === 'session').occurred_at).toBe('2026-09-30T08:30:00.000Z');
});

for (const width of [320, 390, 768]) {
  test(`app screens fit ${width}px with visible navigation`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto('/');
    for (const tab of ['Home', 'Timer', 'Sessioni', 'Garage', 'Altro']) {
      await page.getByRole('navigation').getByRole('button', { name: tab, exact: true }).click();
      await expect(page.getByRole('navigation').getByRole('button', { name: tab, exact: true })).toHaveAttribute('aria-current', 'page');
      await expect(page.locator('main.screen')).toBeVisible();
      await expect(page.locator('.bottom-nav')).toBeVisible();
      expect(await page.locator('main.screen').evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
    }
    await page.getByRole('navigation').getByRole('button', { name: 'Home', exact: true }).click();
    await page.screenshot({ path: test.info().outputPath(`app-${width}.png`), fullPage: true });
  });
}
