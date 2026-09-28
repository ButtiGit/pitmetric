import { expect, test } from '@playwright/test';

test('mobile public cover uses telemetry navigation and keeps notices sequential', async ({ page, context }, testInfo) => {
    test.skip(!testInfo.project.name.includes('mobile'), 'Mobile cover regression only');

    await context.addCookies([
        {
            name: 'pitmetric_locale',
            value: 'it',
            url: 'http://127.0.0.1:8000',
        },
    ]);

    await page.goto('/');

    const mobileNav = page.getByRole('navigation', { name: 'Mobile navigation' });
    const mobileNavLinks = mobileNav.locator('a');

    await expect(mobileNav).toBeVisible();
    await expect(mobileNavLinks).toHaveCount(4);

    const navVisualState = await mobileNav.evaluate((nav) => {
        const firstLink = nav.querySelector('a');
        const activeLink = nav.querySelector('a.text-white');
        const navStyle = getComputedStyle(nav);
        const firstLinkStyle = getComputedStyle(firstLink);
        const firstIndexStyle = getComputedStyle(firstLink, '::before');
        const activeTraceStyle = activeLink ? getComputedStyle(activeLink, '::after') : null;

        return {
            display: navStyle.display,
            columns: navStyle.gridTemplateColumns.split(' ').length,
            borderRadius: firstLinkStyle.borderRadius,
            backgroundImage: firstLinkStyle.backgroundImage,
            firstIndex: firstIndexStyle.content,
            activeTraceOpacity: activeTraceStyle?.opacity,
        };
    });

    expect(navVisualState.display).toBe('grid');
    expect(navVisualState.columns).toBe(4);
    expect(navVisualState.borderRadius).toBe('0px');
    expect(navVisualState.backgroundImage).toBe('none');
    expect(navVisualState.firstIndex).toContain('01');
    expect(navVisualState.activeTraceOpacity).toBe('1');

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
