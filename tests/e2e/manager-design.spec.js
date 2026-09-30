import { expect, test } from '@playwright/test';

for (const width of [320, 390, 768, 1440]) {
    test(`demo navigation and layout at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 900 });
        for (const path of ['/demo', '/demo/manager/garage', '/demo/manager/components', '/demo/manager/sessions', '/demo/manager/maintenance']) {
            await page.goto(path);
            await expect(page.locator('[data-pm-workspace]')).toBeVisible();
            await expect(page.locator('[data-pm-workflow-nav]')).toHaveCount(0);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
        }
        await page.goto('/demo');
        await page.screenshot({ path: test.info().outputPath(`demo-${width}.png`), fullPage: true });
        const mobileNav = page.locator('[data-pm-demo-navigation]');
        if (width < 1024) {
            await expect(mobileNav).toBeVisible();
            await mobileNav.getByRole('link', { name: 'Garage', exact: true }).click();
            await expect(page).toHaveURL(/\/demo\/manager\/garage$/);
            await expect(mobileNav.getByRole('link', { name: 'Garage', exact: true })).toHaveAttribute('aria-current', 'page');
        } else {
            await expect(mobileNav).toBeHidden();
            await expect(page.locator('[data-pm-workspace-bar]')).toBeVisible();
        }
    });
}

test('authenticated manager removes the workflow banner and keeps core navigation', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard/);
    for (const path of ['/dashboard', '/garage', '/components', '/sessions']) {
        await page.goto(path);
        await expect(page.locator('[data-pm-workflow-nav]')).toHaveCount(0);
        await expect(page.locator('[data-pm-workspace]')).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
    }
});
