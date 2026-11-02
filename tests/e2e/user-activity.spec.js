import { expect, test } from '@playwright/test';

test('user studio shows creation totals and the successful login on desktop and mobile', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    if (testInfo.project.name.includes('mobile')) await page.setViewportSize({ width: 320, height: 844 });
    await page.goto('/studio/users?q=e2e%40pitmetric.test');
    const activity = page.locator('[data-user-activity]:visible');
    await expect(activity).toHaveCount(1);
    await expect(activity).toContainText('Records created');
    await expect(activity).toContainText('Last sign-in');
    await expect(activity.locator('[data-records-created]')).toHaveText(/^\d+$/);
    await expect(activity.locator('[data-last-login] time')).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T/);
    await expect(activity.locator('[data-last-login]')).toHaveText(/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    expect(errors).toEqual([]);
});
