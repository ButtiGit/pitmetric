import { expect, test } from '@playwright/test';

const EMAIL = 'e2e@pitmetric.test';
const PASSWORD = 'PitMetric-E2E-2026!';
const BASELINE_VEHICLE = 'E2E Baseline Kart';

function surfaceName(testInfo) {
    return testInfo.project.name.includes('mobile') ? 'Mobile' : 'Desktop';
}

async function login(page) {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(EMAIL);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
}

async function openModal(page, accessibleName) {
    await page.getByRole('button', { name: accessibleName }).click();
}

async function selectOptionContaining(select, text) {
    const value = await select.locator('option').filter({ hasText: text }).first().getAttribute('value');
    expect(value, `Expected an option containing "${text}"`).toBeTruthy();
    await select.selectOption(value);
}

test('five vital workflows', async ({ page }, testInfo) => {
    const suffix = surfaceName(testInfo);
    const vehicleName = `E2E ${suffix} Vehicle`;
    const buildName = `E2E ${suffix} Build`;
    const sessionNote = `E2E ${suffix} finalized session`;
    const scheduleName = `E2E ${suffix} Chain Service`;
    const workTitle = `E2E ${suffix} Chain Inspection`;

    await test.step('login', async () => {
        await login(page);
        await expect(page.locator('body')).toContainText(/PitMetric/i);
    });

    await test.step('vehicle creation', async () => {
        await page.goto('/garage');
        await openModal(page, /Add vehicle/i);

        const form = page.locator('form[action$="/garage"]').first();
        await form.locator('input[name="name"]').fill(vehicleName);
        await form.locator('select[name="category"]').selectOption('kart');
        await form.locator('select[name="status"]').selectOption('active');
        await form.locator('input[name="manufacturer"]').fill('PitMetric E2E');
        await form.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/garage$/);
        await expect(page.getByText(vehicleName, { exact: true })).toBeVisible();
    });

    await test.step('configuration creation', async () => {
        await page.goto('/configurations');
        await openModal(page, /New configuration/i);

        const form = page.locator('form[action$="/configurations"]').first();
        await selectOptionContaining(form.locator('select[name="vehicle_id"]'), BASELINE_VEHICLE);
        await form.locator('input[name="name"]').fill(buildName);
        await form.locator('textarea[name="description"]').fill('Browser E2E physical-state snapshot');
        await form.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/configurations$/);
        await expect(page.getByText(buildName, { exact: true })).toBeVisible();
        await expect(page.locator('body')).toContainText(BASELINE_VEHICLE);
    });

    await test.step('session recording and finalization', async () => {
        await page.goto('/sessions');
        await openModal(page, /Record session/i);

        const form = page.locator('form[action$="/sessions"]').first();
        await selectOptionContaining(form.locator('select[name="configuration_version_id"]'), buildName);
        await form.locator('select[name="session_type"]').selectOption('practice');
        await form.locator('input[name="started_at"]').fill('2026-09-21T12:00');
        await form.locator('input[name="completed_laps"]').fill('12');
        await form.locator('input[name="duration_minutes"]').fill('18');
        await form.locator('input[name="distance_override_km"]').fill('12');
        await form.locator('textarea[name="notes"]').fill(sessionNote);
        await form.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/sessions\?recorded=\d+/);
        const recorded = page.locator('article[id^="session-"]').filter({ hasText: sessionNote }).first();
        await expect(recorded).toBeVisible();
        await expect(recorded).toContainText(/finalized/i);
        await expect(recorded).toContainText(BASELINE_VEHICLE);
    });

    await test.step('maintenance schedule, work order and completion', async () => {
        await page.goto('/maintenance');
        await openModal(page, /\+ Schedule/i);

        const scheduleForm = page.locator('form[action$="/maintenance"]').first();
        await selectOptionContaining(scheduleForm.locator('select[name="component_tracker_id"]'), 'E2E Chain #01');
        await scheduleForm.locator('input[name="name"]').fill(scheduleName);
        await scheduleForm.locator('input[name="interval_display"]').fill('20');
        await scheduleForm.locator('input[name="warning_display"]').fill('5');
        await scheduleForm.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/maintenance$/);
        await expect(page.getByText(scheduleName, { exact: true }).first()).toBeVisible();

        await openModal(page, /\+ Work order/i);
        const workForm = page.locator('form[action$="/maintenance/work-orders"]').first();
        await selectOptionContaining(workForm.locator('select[name="maintenance_schedule_id"]'), scheduleName);
        await workForm.locator('input[name="title"]').fill(workTitle);
        await workForm.locator('select[name="priority"]').selectOption('high');
        await workForm.locator('button[type="submit"]').click();

        const workCard = page.locator('article').filter({ hasText: workTitle }).first();
        await expect(workCard).toBeVisible();
        await workCard.getByRole('button', { name: /^Complete$/i }).click();

        const completionForm = page.locator('form[action*="/maintenance/work-orders/"][action$="/complete"]').filter({ has: page.locator(`input[value="${workTitle}"]`) }).first();
        await completionForm.locator('input[name="cost"]').fill('25');
        await completionForm.locator('textarea[name="notes"]').fill('Completed by browser E2E');
        await completionForm.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/maintenance$/);
        await expect(page.getByText('Maintenance completed, work order closed and cost linked to expenses.')).toBeVisible();
        await expect(workCard).toBeHidden();
        await expect(page.getByText(workTitle, { exact: false }).last()).toBeVisible();
    });
});
