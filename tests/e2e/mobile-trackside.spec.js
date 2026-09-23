import { expect, test } from '@playwright/test';

const EMAIL = 'e2e@pitmetric.test';
const PASSWORD = 'PitMetric-E2E-2026!';

async function login(page) {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(EMAIL);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
}

async function expectMobileSheet(page, selector) {
    const dialog = page.locator(selector);
    await expect(dialog).toBeVisible();

    const box = await dialog.boundingBox();
    const viewport = page.viewportSize();

    expect(box).not.toBeNull();
    expect(viewport).not.toBeNull();
    expect(box.width).toBeGreaterThanOrEqual(viewport.width - 2);
    expect(Math.abs((box.y + box.height) - viewport.height)).toBeLessThanOrEqual(2);
}

async function expectNoHorizontalPageOverflow(page) {
    const hasOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(hasOverflow).toBeFalsy();
}

test('mobile trackside surfaces stay one-handed and action-first', async ({ page }, testInfo) => {
    test.skip(!testInfo.project.name.includes('mobile'), 'Mobile UX regression only.');

    await login(page);

    await test.step('Pit Mode keeps every quick action reachable and its chrome collision-free', async () => {
        await page.goto('/pit');

        const root = page.locator('[data-pm-pit-page]');
        const header = page.locator('[data-pm-pit-header]');
        const context = page.locator('[data-pm-pit-context]');
        const actions = page.locator('[data-pm-pit-action]');
        const bottomNav = page.locator('[data-pm-mobile-bottom-nav]');
        const followUpBell = page.locator('[data-pm-follow-up-bell]');

        await expect(root).toBeVisible();
        await expect(header).toBeVisible();
        await expect(actions).toHaveCount(5);
        await expect(context).not.toHaveAttribute('open', '');
        await expect(bottomNav).toBeVisible();
        await expect(followUpBell).toBeVisible();
        await expect(header.locator('a')).toBeHidden();
        await expectNoHorizontalPageOverflow(page);

        const headerBox = await header.boundingBox();
        const contextBox = await context.boundingBox();
        expect(headerBox).not.toBeNull();
        expect(contextBox).not.toBeNull();
        expect(headerBox.height).toBeLessThan(110);
        expect(headerBox.y + headerBox.height).toBeLessThan(contextBox.y);

        const titleDoesNotWrap = await header.locator('h1').evaluate((element) => element.getBoundingClientRect().height <= parseFloat(getComputedStyle(element).lineHeight) * 1.25);
        expect(titleDoesNotWrap).toBeTruthy();

        const bellRightInset = await followUpBell.evaluate((element) => window.innerWidth - element.getBoundingClientRect().right);
        expect(bellRightInset).toBeGreaterThan(64);

        const firstBox = await actions.nth(0).boundingBox();
        const secondBox = await actions.nth(1).boundingBox();
        expect(firstBox).not.toBeNull();
        expect(secondBox).not.toBeNull();
        expect(Math.abs(firstBox.y - secondBox.y)).toBeLessThanOrEqual(2);
        expect(firstBox.height).toBeGreaterThanOrEqual(80);
        expect(secondBox.height).toBeGreaterThanOrEqual(80);

        const pageBottomPadding = await root.evaluate((element) => parseFloat(getComputedStyle(element).paddingBottom));
        expect(pageBottomPadding).toBeGreaterThan(90);

        const note = page.locator('#pit-note');
        await note.scrollIntoViewIfNeeded();
        const noteBox = await note.boundingBox();
        const navBox = await bottomNav.boundingBox();
        expect(noteBox).not.toBeNull();
        expect(navBox).not.toBeNull();
        expect(noteBox.y).toBeLessThan(navBox.y);

        await note.locator('summary').click();
        await expect(note).toHaveAttribute('open', '');
        await expect(page.locator('#pit-lap')).not.toHaveAttribute('open', '');
    });

    await test.step('sessions expose a fixed primary action and bottom sheet', async () => {
        await page.goto('/sessions');

        const dock = page.locator('[data-pm-mobile-action-dock]');
        await expect(dock).toBeVisible();
        await expect(page.locator('[data-pm-mobile-primary-action]')).toContainText(/Record session|Registra sessione/i);
        expect(await dock.evaluate((element) => getComputedStyle(element).position)).toBe('fixed');
        await expectNoHorizontalPageOverflow(page);

        await page.locator('[data-pm-mobile-primary-action]').click();
        await expectMobileSheet(page, '#record-session');
        await page.locator('#record-session').getByRole('button', { name: /Close|Chiudi/i }).click();
    });

    await test.step('maintenance uses swipeable lanes and persistent create actions', async () => {
        await page.goto('/maintenance');

        const dock = page.locator('[data-pm-mobile-action-dock]');
        const lanes = page.locator('[data-pm-mobile-lanes]');
        await expect(dock).toBeVisible();
        await expect(lanes).toBeVisible();
        await expect(page.locator('[data-pm-maintenance-lane]')).toHaveCount(3);

        const isHorizontallyScrollable = await lanes.evaluate((element) => element.scrollWidth > element.clientWidth);
        expect(isHorizontallyScrollable).toBeTruthy();
        await expectNoHorizontalPageOverflow(page);

        await dock.getByRole('button', { name: /Schedule|Piano/i }).click();
        await expectMobileSheet(page, '#create-maintenance-schedule');
        await page.locator('#create-maintenance-schedule').getByRole('button', { name: /Close|Chiudi/i }).click();
    });

    await test.step('trackside prioritizes recording the next ready session', async () => {
        await page.goto('/events');
        await page.getByRole('link').filter({ hasText: 'E2E Trackside Weekend' }).first().click();

        const dock = page.locator('[data-pm-trackside-dock]');
        await expect(dock).toBeVisible();
        await expect(dock).toContainText('E2E Trackside Practice');

        const primary = dock.locator('[data-pm-mobile-primary-action]');
        await expect(primary).toContainText(/Record result|Registra risultato/i);
        const primaryBox = await primary.boundingBox();
        expect(primaryBox.height).toBeGreaterThanOrEqual(44);
        await expectNoHorizontalPageOverflow(page);

        await primary.click();
        await expectMobileSheet(page, '#record-next-schedule');
    });
});
