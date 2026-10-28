import { expect, test } from '@playwright/test';

const EMAIL = 'e2e@pitmetric.test';
const PASSWORD = 'PitMetric-E2E-2026!';

async function selectNamedOption(select, name) {
    const value = await select.locator('option').filter({ hasText: name }).first().getAttribute('value');
    expect(value).toBeTruthy();
    await select.selectOption(value);
}

test('compares two technical setups for one kart without modifying them', async ({ page }, testInfo) => {
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));
    const suffix = testInfo.project.name.includes('mobile') ? 'Mobile' : 'Desktop';
    const startingName = `E2E ${suffix} Comparison A`;
    const updatedName = `E2E ${suffix} Comparison B`;

    await page.goto('/login');
    await page.locator('input[name="email"]').fill(EMAIL);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);

    for (const [name, caster] of [[startingName, '9'], [updatedName, '10.5']]) {
        await page.goto('/setups');
        await page.getByRole('button', { name: /\+ New setup/i }).click();

        const form = page.locator('#create-technical-setup form');
        await selectNamedOption(form.locator('select[name="vehicle_id"]'), 'E2E Baseline Kart');
        await form.locator('input[name="name"]').fill(name);
        await form.locator('input[name="tyre_pressure_fl"]').fill('0.80');
        await form.locator('input[name="caster_front_deg"]').fill(caster);
        await form.locator('button[type="submit"]').click();
        await expect(page).toHaveURL(/\/setups$/);
    }

    await page.getByRole('button', { name: /Compare setups/i }).click();
    const dialog = page.locator('#compare-technical-setups');
    await expect(dialog).toBeVisible();

    await selectNamedOption(dialog.locator('[data-pm-compare-vehicle]'), 'E2E Baseline Kart');
    await selectNamedOption(dialog.locator('[data-pm-compare-before]'), startingName);
    await selectNamedOption(dialog.locator('[data-pm-compare-after]'), updatedName);

    const casterRow = dialog.locator('[data-pm-compare-field="caster_front_deg"]');
    await expect(casterRow).toHaveAttribute('data-pm-compare-changed', 'true');
    await expect(casterRow).toContainText('10.5 deg');
    await expect(dialog.locator('[data-pm-compare-field="tyre_pressure_fl"]')).toHaveCount(0);

    await dialog.getByRole('checkbox', { name: /Show differences only/i }).uncheck();
    const pressureRow = dialog.locator('[data-pm-compare-field="tyre_pressure_fl"]');
    await expect(pressureRow).toHaveAttribute('data-pm-compare-changed', 'false');
    await expect(pressureRow).toContainText('0.80 bar');

    await dialog.locator('[data-pm-compare-after]').selectOption(await dialog.locator('[data-pm-compare-before]').inputValue());
    await expect(dialog).toContainText('Select two different setups');
    expect(pageErrors).toEqual([]);
});
