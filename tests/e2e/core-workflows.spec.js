import { expect, test } from '@playwright/test';

const credentials = {
    email: 'e2e@pitmetric.test',
    password: 'password',
};

function scenario(testInfo) {
    const mobile = testInfo.project.name.includes('mobile');
    const label = mobile ? 'Mobile' : 'Desktop';

    return {
        label,
        vehicle: `${label} E2E Kart`,
        component: `${label} E2E Engine`,
        configuration: `${label} Race Build`,
        sessionNote: `${label} E2E finalized session`,
        schedule: `${label} Engine Service`,
        workOrder: `${label} Service Job`,
        maintenanceDescription: `${label} Completed service`,
    };
}

async function dismissOnboarding(page) {
    const close = page.locator('[data-pm-tour-close]');

    try {
        await close.waitFor({ state: 'visible', timeout: 1_200 });
        await close.click();
    } catch {
        // The tour only appears on first access; later tests should not depend on it.
    }
}

async function login(page) {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(credentials.email);
    await page.locator('input[name="password"]').fill(credentials.password);
    await page.locator('[data-test="login-button"]').click();
    await page.waitForURL(/\/dashboard(?:\?|$)/);
    await dismissOnboarding(page);
}

async function selectOptionContaining(select, text) {
    const option = select.locator('option').filter({ hasText: text }).first();
    const value = await option.getAttribute('value');

    expect(value, `Expected an option containing "${text}"`).toBeTruthy();
    await select.selectOption(value);
}

async function submitDialog(dialog) {
    await dialog.locator('button[type="submit"]').click();
}

test.describe.serial('PitMetric vital workflows', () => {
    test('login reaches the authenticated dashboard', async ({ page }) => {
        await login(page);

        await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
        await expect(page.locator('.pitmetric-app')).toBeVisible();
    });

    test('creates a vehicle through the Garage UI', async ({ page }, testInfo) => {
        const data = scenario(testInfo);
        await login(page);
        await page.goto('/garage');

        await page.locator('[data-test="create-vehicle-trigger"]').click();
        const dialog = page.locator('[data-test="create-vehicle-dialog"]');
        await expect(dialog).toBeVisible();

        await dialog.locator('input[name="name"]').fill(data.vehicle);
        await dialog.locator('select[name="category"]').selectOption('kart');
        await dialog.locator('select[name="status"]').selectOption('active');
        await dialog.locator('input[name="manufacturer"]').fill('PitMetric E2E');
        await dialog.locator('input[name="model"]').fill('PM-01');
        await submitDialog(dialog);

        await expect(page).toHaveURL(/\/garage(?:\?|$)/);
        await expect(page.getByRole('heading', { name: data.vehicle })).toBeVisible();
    });

    test('creates, installs and snapshots a real component configuration', async ({ page }, testInfo) => {
        const data = scenario(testInfo);
        await login(page);
        await page.goto('/components');

        await page.locator('[data-test="create-component-trigger"]').click();
        let dialog = page.locator('[data-test="create-component-dialog"]');
        await dialog.locator('input[name="name"]').fill(data.component);
        await dialog.locator('input[name="type_name"]').fill('Engine');
        await dialog.locator('select[name="metric_key"]').selectOption('runtime');
        await dialog.locator('input[name="serial_number"]').fill(`${data.label.toUpperCase()}-ENG-01`);
        await submitDialog(dialog);

        await expect(page.getByText(data.component, { exact: true }).first()).toBeVisible();

        await page.locator('[data-test="install-component-trigger"]').click();
        dialog = page.locator('[data-test="install-component-dialog"]');
        await selectOptionContaining(dialog.locator('select[name="component_id"]'), data.component);
        await selectOptionContaining(dialog.locator('select[name="vehicle_id"]'), data.vehicle);
        await dialog.locator('input[name="position_or_role"]').fill('Primary engine');
        await submitDialog(dialog);

        const componentRow = page.locator('tr').filter({ hasText: data.component });
        await expect(componentRow).toContainText(data.vehicle);

        await page.goto('/configurations');
        await page.locator('[data-test="create-configuration-trigger"]').click();
        dialog = page.locator('[data-test="create-configuration-dialog"]');
        await selectOptionContaining(dialog.locator('select[name="vehicle_id"]'), data.vehicle);
        await dialog.locator('input[name="name"]').fill(data.configuration);
        await dialog.locator('textarea[name="description"]').fill('E2E physical build snapshot');
        await submitDialog(dialog);

        const configurationCard = page.locator('article').filter({ hasText: data.configuration });
        await expect(configurationCard).toBeVisible();
        await expect(configurationCard).toContainText(data.component);
        await expect(configurationCard).toContainText('Aligned');
    });

    test('records and finalizes a session with component usage', async ({ page }, testInfo) => {
        const data = scenario(testInfo);
        await login(page);
        await page.goto('/sessions');

        await page.locator('[data-test="record-session-trigger"]').click();
        const dialog = page.locator('[data-test="record-session-dialog"]');
        await selectOptionContaining(dialog.locator('select[name="configuration_version_id"]'), data.configuration);
        await dialog.locator('select[name="session_type"]').selectOption('test');
        await dialog.locator('input[name="completed_laps"]').fill('12');
        await dialog.locator('input[name="duration_minutes"]').fill('12.5');
        await dialog.locator('input[name="distance_override_km"]').fill('6.4');
        await dialog.locator('textarea[name="notes"]').fill(data.sessionNote);
        await submitDialog(dialog);

        await expect(page).toHaveURL(/\/sessions\?recorded=\d+/);
        const sessionCard = page.locator('article').filter({ hasText: data.sessionNote });
        await expect(sessionCard).toBeVisible();
        await expect(sessionCard).toContainText(data.vehicle);
        await expect(sessionCard).toContainText('final');
        await expect(sessionCard).toContainText('Runtime');
    });

    test('plans, boards and completes maintenance', async ({ page }, testInfo) => {
        const data = scenario(testInfo);
        await login(page);
        await page.goto('/maintenance');

        await page.locator('[data-test="create-maintenance-schedule-trigger"]').click();
        let dialog = page.locator('[data-test="create-maintenance-schedule-dialog"]');
        await selectOptionContaining(dialog.locator('select[name="component_tracker_id"]'), data.component);
        await dialog.locator('input[name="name"]').fill(data.schedule);
        await dialog.locator('input[name="interval_display"]').fill('0.10');
        await dialog.locator('input[name="warning_display"]').fill('0.02');
        await submitDialog(dialog);

        await expect(page.getByText(data.schedule, { exact: true }).first()).toBeVisible();

        await page.locator('[data-test="create-maintenance-work-order-trigger"]').click();
        dialog = page.locator('[data-test="create-maintenance-work-order-dialog"]');
        await selectOptionContaining(dialog.locator('select[name="maintenance_schedule_id"]'), data.schedule);
        await dialog.locator('input[name="title"]').fill(data.workOrder);
        await dialog.locator('select[name="priority"]').selectOption('high');
        await submitDialog(dialog);

        const workOrderCard = page.locator('article').filter({ hasText: data.workOrder });
        await expect(workOrderCard).toBeVisible();
        await workOrderCard.getByRole('button', { name: 'Complete', exact: true }).click();

        dialog = page.locator('dialog[open]').filter({ hasText: data.workOrder });
        await expect(dialog).toBeVisible();
        await dialog.locator('input[name="description"]').fill(data.maintenanceDescription);
        await dialog.locator('input[name="cost"]').fill('75');
        await dialog.locator('textarea[name="notes"]').fill('Completed from the E2E workboard flow');
        await submitDialog(dialog);

        await expect(page.getByText(`${data.component} · ${data.maintenanceDescription}`, { exact: true })).toBeVisible();
        await expect(page.locator('article').filter({ hasText: data.schedule })).toContainText('ok');
    });
});
