import { expect, test } from '@playwright/test';

test('outreach is ready to send with just addresses and Enter', async ({ page }, testInfo) => {
    const errors = [];
    const dialogs = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.message());
        await dialog.dismiss();
    });

    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    await page.goto('/studio/outreach');
    await expect(page.getByRole('heading', { name: 'Presenta PitMetric in un invio' })).toBeVisible();
    const form = page.locator('#outreach-form');
    const options = page.locator('#outreach-options');
    const preview = page.locator('#outreach-preview');
    const logo = preview.getByAltText('PitMetric', { exact: true });
    await expect(logo).toBeVisible();
    await expect(logo).toHaveAttribute('src', /\/brand\/pitmetric-primary-dark-email\.png$/);
    await expect.poll(() => logo.evaluate((image) => image.complete && image.naturalWidth > 0)).toBeTruthy();
    await expect(options).not.toHaveAttribute('open', '');
    await expect(form.locator('[required]')).toHaveCount(1);
    await expect(preview).toContainText('aggiornamenti di sicurezza');
    await expect(preview).toContainText('Android');
    expect(await preview.locator('[data-outreach-message] p').count()).toBeGreaterThan(3);
    await expect(preview).not.toContainText(/Studio|SysPilot|ButtiDesk/);

    await options.locator('summary').click();
    await page.locator('#locale').selectOption('en');
    await expect(preview).toContainText('security updates');
    await expect(preview.locator('[data-outreach-label="app"]')).toHaveAttribute('href', /\/en\/app$/);
    await page.locator('#locale').selectOption('it');
    await form.locator('input[name="tone"][value="custom"]').check();
    await page.locator('#message').fill('<img src=x onerror=alert(1)>');
    await expect(preview).toContainText('<img src=x onerror=alert(1)>');
    await expect(preview.locator('img')).toHaveCount(1);
    await expect(preview.locator('[data-outreach-message] img')).toHaveCount(0);
    await page.locator('#note').fill('Un saluto dal paddock.');
    await expect(preview.locator('[data-outreach-note]')).toBeVisible();
    await page.locator('#note').fill('');
    await expect(preview.locator('[data-outreach-note]')).toBeHidden();
    await form.locator('input[name="tone"][value="professional"]').check();
    await options.locator('summary').click();

    if (testInfo.project.name.includes('mobile')) {
        await page.setViewportSize({ width: 320, height: 844 });
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('outreach-ready.png'), fullPage: true });

    let sendRequests = 0;
    page.on('request', (request) => {
        if (request.method() === 'POST' && new URL(request.url()).pathname === '/studio/outreach') sendRequests++;
    });
    const recipients = page.getByLabel('Destinatari', { exact: true });
    await recipients.fill('first@example.test');
    await recipients.press('Shift+Enter');
    await recipients.pressSequentially('second@example.test');
    await expect(recipients).toHaveValue('first@example.test\nsecond@example.test');
    const sent = page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/studio/outreach');
    await recipients.press('Enter');
    expect((await sent).status()).toBe(302);
    await expect(page.getByRole('status').filter({ hasText: 'Invio completato' })).toBeVisible();
    await expect(page.getByRole('status').filter({ hasText: 'Invio completato' })).toContainText('2 inviate, 0 escluse, 0 non riuscite');
    await expect(page.getByLabel('Destinatari', { exact: true })).toHaveValue('');
    expect(sendRequests).toBe(1);
    expect(dialogs).toEqual([]);
    expect(errors).toEqual([]);
});
