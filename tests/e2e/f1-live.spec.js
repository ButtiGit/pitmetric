import { expect, test } from '@playwright/test';

test('editors can use F1 live and replay without leaking polling across navigation', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    let feedCalls = 0;
    let historyCalls = 0;
    let offline = false;
    await page.route('**/studio/f1-live/feed*', (route) => {
        feedCalls++;
        return route.fulfill({ status: offline ? 503 : 200, json: offline
            ? { live: false, message: 'Servizio F1 temporaneamente non disponibile.' }
            : { live: true, age_seconds: 1, session: { Name: 'Race' }, lap_count: { CurrentLap: 12, TotalLaps: 58 },
                drivers: { 44: { Tla: 'HAM', timing: { Position: '1', LastLapTime: { Value: '1:31.200' } }, car: { speed: 299 }, position: { x: 120, y: 10 } } },
                history: { 44: [{ utc: '2026-10-25T15:00:00Z', speed: 280 }, { utc: '2026-10-25T15:00:01Z', speed: 299 }] },
                race_control: [{ Category: 'Flag', Message: '<img src=x onerror=alert(1)>' }] } });
    });
    await page.route('**/studio/f1-live/events*', (route) => route.fulfill({ json: {
        events: [{ round: 1, name: 'Australian Grand Prix', sessions: [] }],
    } }));
    await page.route('**/studio/f1-live/history*', (route) => {
        historyCalls++;
        return route.fulfill({ json: historyCalls === 1 ? { pending: true } : {
            event: 'Australian Grand Prix', drivers: { HAM: { driver: 'HAM', lap: 12, lap_seconds: 91.2, compound: 'MEDIUM',
                telemetry: [{ t: 0, speed: 280 }, { t: 1, speed: 299 }], position: [{ x: 0, y: 0 }, { x: 120, y: 10 }] } },
        } });
    });
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e-f1@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    await page.goto('/studio/f1-live');
    await expect(page.locator('#f1-state')).toContainText('LIVE');
    await expect(page.locator('#f1-timing')).toContainText('HAM');
    await expect(page.locator('#f1-timing')).toContainText('299');
    await expect(page.locator('#f1-messages')).toContainText('<img src=x onerror=alert(1)>');
    await expect(page.locator('#f1-messages img')).toHaveCount(0);
    await expect(page.locator('#f1-chart-caption')).toContainText('2 campioni');
    await page.locator('#f1-history-tab').click();
    await page.locator('#f1-hdrivers').fill('HAM');
    await page.locator('#f1-compare').click();
    await expect(page.locator('#f1-history-status')).toContainText('dati caricati', { timeout: 10_000 });
    await expect(page.locator('#f1-history-summary')).toContainText('91.200 s');
    expect(historyCalls).toBe(2);
    if (testInfo.project.name.includes('mobile')) await page.setViewportSize({ width: 320, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('f1-replay.png'), fullPage: true });
    await page.locator('#f1-live-tab').click();
    offline = true;
    await page.locator('#f1-refresh').click();
    await expect(page.locator('#f1-warning')).toContainText('temporaneamente non disponibile');
    await page.clock.install();
    await page.evaluate(() => window.Livewire.navigate('/dashboard'));
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    const callsAfterLeaving = feedCalls;
    await page.clock.runFor(6_000);
    expect(feedCalls).toBe(callsAfterLeaving);
    await page.evaluate(() => window.Livewire.navigate('/studio/f1-live'));
    await expect(page.getByRole('heading', { name: 'F1 Live Lab' })).toBeVisible();
    await expect.poll(() => feedCalls).toBeGreaterThan(callsAfterLeaving);
    expect(errors).toEqual([]);
});
