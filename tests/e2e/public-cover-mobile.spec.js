import { expect, test } from '@playwright/test';

test('mobile public cover keeps notices sequential and within the viewport', async ({ page, context }, testInfo) => {
    test.skip(!testInfo.project.name.includes('mobile'), 'Mobile cover regression only');

    await context.addCookies([
        {
            name: 'pitmetric_locale',
            value: 'it',
            url: 'http://127.0.0.1:8000',
        },
    ]);

    await page.goto('/');

    const cookieBanner = page.locator('[data-cookie-banner]');
    const partnerNotice = page.locator('[data-partner-notice]');

    await expect(cookieBanner).toBeVisible();
    await expect(partnerNotice).not.toBeVisible();
    await expect(partnerNotice).toHaveAttribute('data-mobile-notice-suppressed', 'true');

    await cookieBanner.locator('[data-cookie-choice="necessary"]').click();

    await expect(cookieBanner).toBeHidden();
    await expect(partnerNotice).toBeVisible();
    await expect(partnerNotice).not.toHaveAttribute('data-mobile-notice-suppressed', 'true');

    const hasHorizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(hasHorizontalOverflow).toBe(false);
});
