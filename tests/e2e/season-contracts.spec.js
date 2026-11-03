import { expect, test } from '@playwright/test';

async function login(page, email = 'e2e@pitmetric.test') {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
}

test('season contracts keep manual race balances and print their complete history', async ({ page }, testInfo) => {
    test.setTimeout(60_000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page);
    if (testInfo.project.name.includes('mobile')) await page.setViewportSize({ width: 320, height: 844 });
    await page.goto('/contracts');
    const main = page.locator('[data-contract-id]').filter({ hasText: 'E2E Main 2026' });
    const mainLink = main.getByRole('link', { name: 'Open contract', exact: true });
    await mainLink.evaluate(node => node.scrollIntoView({ block: 'center' }));
    await mainLink.click();
    await expect(page).toHaveURL(/\/contracts\/\d+$/);
    await expect(page.locator('[data-contract-participation]')).toHaveCount(8);
    await expect(page.locator('[data-contract-count="total"]')).toHaveText('7');
    await expect(page.locator('[data-contract-count="consumed"]')).toHaveText('2');
    await expect(page.locator('[data-contract-count="remaining"]')).toHaveText('5');
    await expect(page.getByText('END-CONTRACT-HISTORY', { exact: false })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
    const printUrl = await page.locator('a[href*="/print/contracts/"]').getAttribute('href');
    await page.goto(printUrl);
    const document = page.locator('[data-print-document="contract"]');
    await expect(document.locator('[data-contract-participation]')).toHaveCount(8);
    await expect(document).toContainText('END-CONTRACT-NOTES');
    await expect(document).toContainText('END-CONTRACT-HISTORY');
    expect(await document.locator('h1,h2,h3,p,dd,td,th').evaluateAll(nodes => nodes.filter(n => n.scrollWidth > n.clientWidth + 1).map(n => n.textContent))).toEqual([]);
    await page.screenshot({ path: testInfo.outputPath('print-contract.png'), fullPage: true });
    await page.emulateMedia({ media: 'print' });
    await page.pdf({ path: testInfo.outputPath('print-contract.pdf'), preferCSSPageSize: true });
    await page.emulateMedia({ media: null });
    const back = await page.locator('.print-toolbar a').first().getAttribute('href');
    await page.goto(back);
    const pending = page.locator('[data-contract-participation]').nth(4);
    const participationId = await pending.getAttribute('data-contract-participation');
    for (const [status, consumed] of [['confirmed', '3'], ['planned', '2']]) {
        const trigger = pending.getByRole('button', { name: 'Confirm / correct race', exact: true });
        await trigger.evaluate(node => node.scrollIntoView({ block: 'center' }));
        await trigger.click();
        const change = page.locator(`#participation-${participationId} form`);
        await change.locator('select[name="status"]').selectOption(status);
        await change.locator('textarea[name="reason"]').fill(`Browser confirmation and correction: ${status}`);
        page.once('dialog', dialog => dialog.accept());
        await change.locator('button[type="submit"]').click();
        await expect(page.locator('[data-contract-count="consumed"]')).toHaveText(consumed);
    }
    await page.getByRole('button', { name: 'Attach document', exact: true }).click();
    await page.locator('#contract-document input[name="document"]').setInputFiles(testInfo.outputPath('print-contract.pdf'));
    await page.locator('#contract-document button[type="submit"]').click();
    await expect(page.getByRole('link', { name: 'print-contract', exact: true }).last()).toBeVisible();

    await page.goto('/contracts');
    await page.getByRole('button', { name: 'New contract', exact: true }).click();
    const form = page.locator('#create-contract form');
    await form.locator('select[name="driver_id"]').selectOption({ label: 'E2E Contract UI Driver' });
    await form.locator('select[name="vehicle_id"]').selectOption({ label: 'E2E Contract UI Kart' });
    await form.locator('input[name="season"]').fill(`UI ${testInfo.project.name}`);
    await form.locator('input[name="initial_races"]').fill('2');
    // Non-overlapping annual periods let both browser projects exercise creation.
    const year = testInfo.project.name.includes('mobile') ? '2027' : '2026';
    await form.locator('input[name="starts_on"]').fill(`${year}-01-01`);
    await form.locator('input[name="ends_on"]').fill(`${year}-12-31`);
    await form.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/contracts\/\d+$/);
    await expect(page.locator('[data-contract-count="remaining"]')).toHaveText('2');
    await page.getByRole('button', { name: 'Extend contract', exact: true }).click();
    await page.locator('#extend-contract input[name="added_races"]').fill('1');
    await page.locator('#extend-contract textarea[name="reason"]').fill('Additional race agreed in browser test');
    await page.locator('#extend-contract button[type="submit"]').click();
    await expect(page.locator('[data-contract-count="total"]')).toHaveText('3');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
    expect(errors).toEqual([]);
});

test('two team operators cannot consume the last available race twice', async ({ page, browser }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Shared fixture is consumed once by the desktop concurrency check.');
    await login(page);
    await page.goto('/contracts');
    await page.locator('[data-contract-id]').filter({ hasText: 'E2E Last Place' }).getByRole('link', { name: 'Open contract', exact: true }).click();
    await expect(page).toHaveURL(/\/contracts\/\d+$/);
    const contractUrl = page.url();
    const forms = page.locator('form[action*="/contract-participations/"]');
    await expect(forms).toHaveCount(2);
    const actions = await forms.evaluateAll(nodes => nodes.map(n => ({ url: n.action, revision: n.querySelector('input[name="revision"]').value })));
    expect(actions).toHaveLength(2);
    const token1 = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const eventUrl = await page.locator('[data-contract-participation] a[href*="/events/"]').first().getAttribute('href');
    await page.goto('/team');
    const teamName = await page.locator('#edit-team input[name="name"]').inputValue();
    const context2 = await browser.newContext();
    const second = await context2.newPage();
    try {
        await login(second, 'e2e-contract-operator@pitmetric.test');
        await second.goto('/team');
        const switchForm = second.locator('form[action*="/team/switch/"]').filter({ hasText: teamName });
        const switchAction = await switchForm.getAttribute('action');
        const token2 = await second.locator('meta[name="csrf-token"]').getAttribute('content');
        await second.request.post(switchAction, { headers: { 'X-CSRF-TOKEN': token2 }, maxRedirects: 0 });
        await second.goto(contractUrl);
        const freshToken = await second.locator('meta[name="csrf-token"]').getAttribute('content');
        const responses = await Promise.all([
            page.request.patch(actions[0].url, { data: { revision: actions[0].revision, status: 'confirmed', reason: 'First operator confirmation' }, headers: { 'X-CSRF-TOKEN': token1, Accept: 'application/json', Referer: contractUrl }, maxRedirects: 0 }),
            second.request.patch(actions[1].url, { data: { revision: actions[1].revision, status: 'confirmed', reason: 'Second operator confirmation' }, headers: { 'X-CSRF-TOKEN': freshToken, Accept: 'application/json', Referer: contractUrl }, maxRedirects: 0 }),
        ]);
        expect(responses.map(r => r.status()).sort()).toEqual([302, 422]);
        await page.goto(contractUrl);
        await expect(page.locator('[data-contract-count="consumed"]')).toHaveText('1');
        await expect(page.locator('[data-contract-count="remaining"]')).toHaveText('0');
        await page.goto(eventUrl);
        await expect(page.locator('[data-entry-contract]')).toContainText('E2E Last Place');
    } finally {
        await context2.close();
    }
});
