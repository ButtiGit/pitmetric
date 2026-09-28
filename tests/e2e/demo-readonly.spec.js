import { expect, test } from '@playwright/test';

test('public demo opens a seeded read-only workspace without horizontal overflow', async ({ page, context }) => {
    await context.addCookies([
        {
            name: 'pitmetric_locale',
            value: 'en',
            url: 'http://127.0.0.1:8000',
        },
    ]);

    await page.goto('/');

    const authActions = page.locator('[aria-label="Authentication actions"]');
    const compactDemo = authActions.getByRole('button', { name: 'Demo' });
    const desktopDemo = page.locator('header form[action$="/demo"] button').filter({ hasText: 'Demo' }).first();

    if (await compactDemo.isVisible()) {
        await compactDemo.click();
    } else {
        await desktopDemo.click();
    }

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.locator('meta[name="pitmetric-demo-read-only"]')).toHaveAttribute('content', 'true');

    await page.goto('/garage');

    await expect(page.locator('#pitmetric-demo')).toHaveAttribute('data-read-only', 'true');
    await expect(page.getByText('Kart #27')).toBeVisible();
    await expect(page.locator('form[data-form="vehicle"]')).toHaveCount(0);
    await expect(page.locator('[data-delete]')).toHaveCount(0);

    const hasHorizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(hasHorizontalOverflow).toBe(false);
});
