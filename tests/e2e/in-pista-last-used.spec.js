import { expect, test } from '@playwright/test';

const EMAIL = 'e2e@pitmetric.test';
const PASSWORD = 'PitMetric-E2E-2026!';

test('a mechanic can recall last used kart settings without automatically saving them', async ({ page }, testInfo) => {
    const suffix = testInfo.project.name.includes('mobile') ? 'Mobile' : 'Desktop';
    const setupName = `E2E ${suffix} Last used setup`;
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message));

    await page.goto('/login');
    await page.locator('input[name="email"]').fill(EMAIL);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);

    await page.goto('/setups');
    await page.getByRole('button', { name: /\+ New setup/i }).click();
    const createForm = page.locator('#create-technical-setup form');
    const kartOption = await createForm.locator('select[name="vehicle_id"] option').filter({ hasText: 'E2E Baseline Kart' }).first().getAttribute('value');
    expect(kartOption).toBeTruthy();
    await createForm.locator('select[name="vehicle_id"]').selectOption(kartOption);
    await createForm.locator('input[name="name"]').fill(setupName);
    await createForm.locator('input[name="camber_front_deg"]').fill('-1.0');
    await createForm.locator('input[name="caster_front_deg"]').fill('9');
    await createForm.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/setups$/);

    await page.goto(`/pit?kart=${kartOption}`);
    await page.getByRole('button', { name: /Avvia sessione/i }).click();
    await expect(page.locator('[data-in-pista]')).toContainText('Sessione in corso');
    await expect(page.locator('.in-pista-overview-setup')).toContainText(setupName);
    await expect(page.locator('[data-in-pista-best-lap-detail]')).toHaveText('Nessun tempo valido registrato');

    await page.locator('[data-in-pista-dialog-open="in-pista-adjust"]').click();
    const dialog = page.locator('#in-pista-adjust');
    await expect(dialog).toBeVisible();
    await dialog.locator('input[name="camber_front_deg"]').fill('-1.7');
    await dialog.locator('input[name="caster_front_deg"]').fill('10.5');
    await dialog.getByRole('button', { name: 'Registra regolazioni' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.locator('[data-in-pista-adjustment-count]')).toHaveText('1 regolazione registrata');
    await expect(page.locator('.in-pista-overview-setup [data-in-pista-geometry-summary="camber"]')).toHaveText('-1.7 °');

    await page.locator('#in-pista-lap-input').fill('0:52.345');
    await page.getByRole('button', { name: '+ Salva tempo' }).click();
    const lapRow = page.locator('[data-in-pista-lap-row]').first();
    await expect(lapRow).toContainText('0:52.345');
    await expect(page.locator('[data-in-pista-best-lap-detail]')).toHaveText('Giro #1 · Manuale');
    await expect(lapRow).toHaveClass(/is-best/);
    await lapRow.getByRole('button', { name: 'Correggi' }).click();
    const correctionDialog = page.locator('#in-pista-lap-correction');
    await expect(correctionDialog).toBeVisible();
    await correctionDialog.locator('input[name="lap_time"]').fill('0:51.123');
    await correctionDialog.locator('textarea[name="reason"]').fill('Errore manuale');
    await correctionDialog.getByRole('button', { name: 'Salva correzione' }).click();
    await expect(correctionDialog).toBeHidden();
    await expect(lapRow).toContainText('0:51.123');
    await expect(lapRow).toContainText('Storico correzioni (1)');
    await expect(page.locator('[data-in-pista-best-lap]')).toHaveText('0:51.123');
    await expect(page.locator('[data-in-pista-best-lap-detail]')).toHaveText('Giro #1 · Manuale');
    await expect(page.locator('[data-in-pista-lap-count]')).toHaveText('1');

    page.once('dialog', confirmation => confirmation.accept());
    await page.locator('[data-in-pista-finish] button[type="submit"]').click();
    await expect(page.locator('[data-in-pista-latest]')).toBeVisible();
    await page.locator('[data-in-pista-latest] summary').click();
    await expect(page.locator('[data-in-pista-latest]')).toContainText('-1.7');

    await page.getByRole('button', { name: /Avvia sessione/i }).click();
    await page.locator('[data-in-pista-dialog-open="in-pista-adjust"]').click();
    await expect(dialog.getByRole('button', { name: 'Riprendi valori' })).toBeVisible();
    await expect(dialog.locator('input[name="camber_front_deg"]')).toHaveValue('');
    await dialog.getByRole('button', { name: 'Riprendi valori' }).click();
    await expect(dialog.locator('input[name="camber_front_deg"]')).toHaveValue('-1.7');
    await expect(dialog.locator('input[name="caster_front_deg"]')).toHaveValue('10.5');

    await dialog.getByRole('button', { name: 'Chiudi' }).click();
    page.once('dialog', confirmation => confirmation.accept());
    await page.locator('[data-in-pista-finish] button[type="submit"]').click();
    expect(pageErrors).toEqual([]);
});
