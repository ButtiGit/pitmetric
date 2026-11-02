import { expect, test } from '@playwright/test';

test('vehicle preparation exposes incomplete tracking and keeps vehicle filtering usable on mobile', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    if (testInfo.project.name.includes('mobile')) await page.setViewportSize({ width: 320, height: 844 });
    await page.goto('/tasks');
    const overview = page.locator('[data-vehicle-readiness]');
    await overview.locator(':scope > summary').click();
    const kart = page.locator('[data-readiness-vehicle]').filter({ hasText: 'E2E Baseline Kart' });
    await expect(kart).toBeVisible();
    await expect(kart).not.toHaveAttribute('data-readiness-state', 'clear');
    await expect(kart).toContainText('Incomplete tracking');
    const vehicleId = await kart.getAttribute('data-readiness-vehicle');
    await kart.locator('a[href*="vehicle_id="]').click();
    await expect(page).toHaveURL(new RegExp(`vehicle_id=${vehicleId}`));
    await expect(overview).toHaveAttribute('open', '');
    await expect(page.locator('[data-readiness-vehicle]')).toHaveCount(1);
    await expect(kart).toBeVisible();
    await expect(kart.locator('a[href$="/maintenance"]')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('vehicle-preparation.png'), fullPage: true });
    expect(errors).toEqual([]);
});
