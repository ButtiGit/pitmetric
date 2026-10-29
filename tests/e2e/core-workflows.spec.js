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

test('five vital workflows', async ({ page, context }, testInfo) => {
    test.setTimeout(180_000);
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

    await test.step('circuit GPS proposes coordinates and keeps manual values when permission is denied', async () => {
        await context.grantPermissions(['geolocation']);
        await context.setGeolocation({ latitude: 45.08291027, longitude: 7.68734942, accuracy: 12 });

        await page.goto('/circuits');
        await page.getByRole('button', { name: /add circuit|aggiungi circuito/i }).first().click();
        const form = page.locator('#create-circuit form');
        await expect(form).toBeVisible();

        const circuitName = `E2E ${suffix} GPS circuit`;
        await form.locator('input[name="name"]').fill(circuitName);
        await form.locator('input[name="layout_name"]').fill('Full');
        await form.locator('input[name="length_meters"]').fill('1250');
        await form.locator('[data-pm-circuit-gps]').click();

        await expect(form.locator('input[name="latitude"]')).toHaveValue('45.08291027');
        await expect(form.locator('input[name="longitude"]')).toHaveValue('7.68734942');
        await expect(form.locator('[data-pm-circuit-gps-status]')).toContainText(/coordinate/i);
        await expect(form).toBeVisible();

        await form.locator('button[type="submit"]').click();
        await expect(page).toHaveURL(/\/circuits$/);
        await expect(page.getByText(circuitName, { exact: true })).toBeVisible();
        await expect(page.locator('section.pm-panel').filter({ hasText: circuitName })).toContainText('45.08291027, 7.68734942');

        await page.getByRole('button', { name: /add circuit|aggiungi circuito/i }).first().click();
        const manualForm = page.locator('#create-circuit form');
        await expect(manualForm).toBeVisible();
        await manualForm.locator('input[name="latitude"]').fill('44.00000000');
        await manualForm.locator('input[name="longitude"]').fill('8.00000000');
        await page.evaluate(() => {
            Object.defineProperty(navigator, 'geolocation', {
                configurable: true,
                value: { getCurrentPosition(_success, error) { error({ code: 1 }); } },
            });
        });
        await manualForm.locator('[data-pm-circuit-gps]').click();
        await expect(manualForm.locator('[data-pm-circuit-gps-status]')).toContainText(/permesso|permission/i);
        await expect(manualForm.locator('[data-pm-circuit-gps]')).toBeEnabled();
        await expect(manualForm.locator('input[name="latitude"]')).toHaveValue('44.00000000');
        await expect(manualForm.locator('input[name="longitude"]')).toHaveValue('8.00000000');
    });

    await test.step('workflow stages stay fully readable at every width', async () => {
        await expectReadableWorkflows(page, testInfo);
    });

    if (!testInfo.project.name.includes('mobile')) {
        await test.step('sidebar information stays grouped above the active account', async () => {
            await expectSidebarFooter(page);
        });
    }

    await test.step('championship modals import and scoring work end to end', async () => {
        await expectChampionshipFlow(page, testInfo);
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

    await test.step('kart gearing updates its calculated ratio while entering teeth', async () => {
        await page.goto('/setups');
        await openModal(page, /\+ New setup/i);

        const form = page.locator('form[action$="/setups"]').first();
        await selectOptionContaining(form.locator('select[name="vehicle_id"]'), BASELINE_VEHICLE);

        const pinion = form.locator('input[name="pinion_teeth"]');
        const crown = form.locator('input[name="crown_teeth"]');
        const ratio = form.locator('[aria-live="polite"]');

        await expect(pinion).toBeVisible();
        await expect(crown).toBeVisible();
        await pinion.fill('11');
        await crown.fill('43');
        await expect(ratio).toContainText('3.909:1');

        await crown.fill('44');
        await expect(ratio).toContainText('4.000:1');
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

async function expectReadableWorkflows(page, testInfo) {
    const originalViewport = page.viewportSize();

    for (const locale of ['it', 'en']) {
        const token = await page.locator('input[name="_token"]').first().inputValue();
        const response = await page.request.post('/locale', {
            form: { _token: token, locale, redirect: '/dashboard' },
        });
        expect(response.ok()).toBeTruthy();

        for (const [path, selector] of [
            ['/dashboard', '[data-pm-dashboard-workflow]'],
            ['/components', '[data-pm-workflow-nav]'],
        ]) {
            await page.goto(path);
            const workflow = page.locator(selector);
            await expect(workflow).toBeAttached();
            await expect(workflow.locator('[data-pm-workflow-title]')).toHaveCount(7);
            await page.evaluate(() => document.fonts.ready);

            for (const width of [320, 390, 768, 1024, 1280, 1440, 1536, 1920]) {
                await page.setViewportSize({ width, height: 1000 });
                if (!await workflow.isVisible()) {
                    for (const label of await workflow.locator('[data-pm-workflow-title], [data-pm-workflow-status]').all()) {
                        await expect(label).toBeHidden();
                    }
                    continue;
                }
                await expect(async () => {
                    const problems = await workflow.evaluate((root) => {
                        const bounds = root.getBoundingClientRect();
                        const problems = [];
                        const elements = [root, ...root.querySelectorAll('nav, ol, li, a, p, [data-pm-workflow-title], [data-pm-workflow-status]')];

                        for (const element of elements) {
                            const rect = element.getBoundingClientRect();
                            if (element.scrollWidth > element.clientWidth + 1
                                || rect.left < Math.max(0, bounds.left) - 1
                                || rect.right > Math.min(innerWidth, bounds.right) + 1) {
                                problems.push(`Overflow: ${element.textContent.trim()}`);
                            }
                            if (element.matches('[data-pm-workflow-title], [data-pm-workflow-status]')) {
                                const lineHeight = parseFloat(getComputedStyle(element).lineHeight);
                                const textRange = document.createRange();
                                textRange.selectNodeContents(element);
                                if (textRange.getBoundingClientRect().height > lineHeight + 1) {
                                    problems.push(`Split label: ${element.textContent.trim()}`);
                                }
                            }
                        }
                        return problems;
                    });
                    expect(problems, `${locale} ${path} at ${width}px`).toEqual([]);
                }).toPass();
            }

            await page.screenshot({ path: testInfo.outputPath(`workflow-${locale}-${path.slice(1)}.png`), fullPage: true });
        }
    }
    await page.setViewportSize(originalViewport);
}

async function expectSidebarFooter(page) {
    const originalViewport = page.viewportSize();
    await page.goto('/dashboard');
    const footer = page.locator('[data-pm-sidebar-footer]');
    const menu = footer.locator('[data-pm-sidebar-info]');
    const summary = menu.locator('summary');
    const account = page.locator('[data-test="sidebar-menu-button"]');

    for (const height of [600, 1000]) {
        await page.setViewportSize({ width: originalViewport.width, height });
        await expect(footer).toBeVisible();
        await expect(menu).not.toHaveAttribute('open', '');
        await expect(menu.locator('a')).toHaveCount(4);
        await expect(menu.locator('a').first()).toBeHidden();
        await summary.click();
        await expect(menu).toHaveAttribute('open', '');
        for (const link of await menu.locator('a').all()) {
            await expect(link).toBeVisible();
        }
        await expect(menu.getByRole('group', { name: 'Language', exact: true })).toBeVisible();
        await expect(menu.getByRole('button', { name: 'English', exact: true })).toHaveAttribute('aria-pressed', 'true');

        const footerBox = await footer.boundingBox();
        const accountBox = await account.boundingBox();
        expect(footerBox.y + footerBox.height).toBeLessThanOrEqual(accountBox.y + 1);
        expect(accountBox.y + accountBox.height).toBeLessThanOrEqual(height + 1);
        await summary.click();
    }

    await summary.click();
    await menu.getByRole('button', { name: 'Italiano', exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'it');
    await summary.click();
    await expect(menu.getByRole('link', { name: 'Chi sono', exact: true })).toHaveAttribute('href', /\/it\/about$/);
    await expect(menu.getByRole('link', { name: 'Home', exact: true })).toHaveAttribute('href', /\/it$/);
    await expect(menu.getByRole('link', { name: 'Aggiornamenti', exact: true })).toHaveAttribute('href', /\/it\/updates$/);
    await menu.getByRole('button', { name: 'English', exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');

    await page.goto('/newsletter');
    await expect(menu).toHaveAttribute('open', '');
    await expect(menu.getByRole('link', { name: 'Newsletter', exact: true })).toBeVisible();
    await summary.click();
    await summary.focus();
    await summary.press('Enter');
    await expect(menu).toHaveAttribute('open', '');
    await page.setViewportSize(originalViewport);
}

async function expectChampionshipFlow(page, testInfo) {
    const surface = surfaceName(testInfo);
    const name = `E2E ${surface} Championship`;
    const alice = `E2E ${surface} Alice`;
    const bob = `E2E ${surface} Bob`;
    const licence = `E2E-${surface}-A`;
    const errors = [];
    const captureError = (error) => errors.push(error.message);
    page.on('pageerror', captureError);
    await page.goto('/championships');
    await page.getByRole('button', { name: 'New championship', exact: true }).click();
    const create = page.locator('#new-championship');
    await expect(create).toBeVisible();
    await create.locator('[name="name"]').fill(name);
    await create.locator('[name="season"]').fill('2027');
    await create.locator('[name="category"]').fill('KZ');
    await create.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page).toHaveURL(/\/championships\/\d+$/);
    await expect(page.getByRole('heading', { name, exact: true })).toBeVisible();

    await page.getByRole('button', { name: /^Enrolled drivers/ }).click();
    await page.getByRole('button', { name: 'Enroll driver', exact: true }).click();
    const enroll = page.locator('#new-championship-participant');
    await enroll.locator('[name="name"]').fill(alice);
    await enroll.locator('[name="number"]').fill('7');
    await enroll.locator('[name="licence"]').fill(licence);
    await enroll.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('[data-championship-participant]').filter({ hasText: alice })).toBeVisible();

    await page.getByRole('button', { name: 'Import Excel / CSV', exact: true }).click();
    const wizard = page.locator('#championship-import');
    await wizard.locator('[name="file"]').setInputFiles({
        name: 'drivers.csv', mimeType: 'text/csv',
        buffer: Buffer.from(`Name,Number,Licence,Team\n${alice},7,${licence},E2E\n${bob},8,E2E-${surface}-B,E2E\n`),
    });
    await wizard.getByRole('button', { name: 'Read file', exact: true }).click();
    await expect(wizard.locator('[data-import-step="1"]')).toBeVisible();
    await expect(wizard.locator('[data-field="name"]')).toHaveValue('0');
    const previewResponse = page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname.endsWith('/imports/preview'));
    await wizard.getByRole('button', { name: 'Check preview', exact: true }).click();
    expect((await previewResponse).ok()).toBeTruthy();
    await expect(wizard.locator('[data-import-step="2"]')).toBeVisible();
    await expect(wizard.locator('[data-import-row-state="new"]')).toHaveCount(1);
    await expect(wizard.locator('[data-import-row-state="skip"]')).toHaveCount(1);
    await expect(wizard.locator('[data-import-confirm]')).toBeEnabled();
    const confirmed = page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname.endsWith('/imports/confirm'));
    const importedPage = page.waitForNavigation({ waitUntil: 'domcontentloaded' });
    await wizard.getByRole('button', { name: 'Confirm import', exact: true }).click();
    expect((await confirmed).ok()).toBeTruthy();
    await importedPage;
    await expect(page.getByRole('status').filter({ hasText: 'Import completed' })).toContainText('1 rows imported');

    await page.getByRole('button', { name: /^Rounds/ }).click();
    await page.getByRole('button', { name: 'Add round', exact: true }).click();
    const round = page.locator('#new-championship-round');
    await round.locator('[name="name"]').fill('E2E Busca');
    await round.locator('[name="starts_on"]').fill('2026-11-01');
    await round.locator('[name="ends_on"]').fill('2026-11-02');
    await round.getByRole('button', { name: 'Save', exact: true }).click();
    const roundCard = page.locator('[data-championship-round]').filter({ hasText: 'E2E Busca' });
    await expect(roundCard).toBeVisible();
    await roundCard.getByRole('button', { name: /^Results/ }).click();
    const resultList = page.locator('dialog[id^="championship-round-results-"][open]');
    await resultList.getByRole('button', { name: 'Add result', exact: true }).click();
    const result = page.locator('dialog[id^="new-championship-result-"][open]');
    await selectOptionContaining(result.locator('[name="participant_id"]'), alice);
    await result.locator('[name="position"]').fill('1');
    await result.locator('[name="adjustment"]').fill('-0.50');
    await result.locator('[name="pole"]').check();
    await result.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('[data-championship-round]').getByRole('button', { name: 'Results (1)', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Standings', exact: true }).click();
    await expect(page.locator('[data-standings-row]').filter({ hasText: alice })).toContainText('24.5');
    await page.getByRole('button', { name: 'Scoring', exact: true }).click();
    const scoring = page.locator('#championship-scoring');
    await scoring.getByRole('button', { name: 'Add position', exact: true }).click();
    await scoring.locator('[name="points[21]"]').fill('0.25');
    await scoring.locator('[name="points[1]"]').fill('30.50');
    await scoring.locator('[name="pole_bonus"]').fill('1.25');
    await scoring.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('[data-standings-row]').filter({ hasText: alice })).toContainText('31.25');
    if (testInfo.project.name.includes('mobile')) {
        await page.setViewportSize({ width: 320, height: 844 });
        const lastDriver = page.locator('[data-standings-row]').last();
        await lastDriver.scrollIntoViewIfNeeded();
        const lastDriverBox = await lastDriver.boundingBox();
        const navigationBox = await page.locator('[data-pm-mobile-bottom-nav]').boundingBox();
        expect(lastDriverBox.y + lastDriverBox.height).toBeLessThanOrEqual(navigationBox.y + 1);
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('championship-standings.png'), fullPage: true });
    expect(errors).toEqual([]);
    page.off('pageerror', captureError);
}
