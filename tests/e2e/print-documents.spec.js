import { expect, test } from '@playwright/test';

test('A4 sheets open from their records and preserve complete multipage session reports', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('e2e@pitmetric.test');
    await page.locator('input[name="password"]').fill('PitMetric-E2E-2026!');
    await page.locator('form').filter({ has: page.locator('input[name="email"]') }).locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard(?:\?|$)/);
    if (testInfo.project.name.includes('mobile')) await page.setViewportSize({ width: 320, height: 844 });
    const urls = {};
    for (const [path, name, kind] of [['/garage', 'E2E Print Kart', 'vehicle'], ['/setups', 'E2E Print Setup', 'setup'], ['/sessions', 'E2E Print Kart', 'session']]) {
        await page.goto(path);
        const record = page.locator('article').filter({ hasText: name });
        const printLink = record.locator('a[href*="/print/"]').filter({ hasText: 'Print / Save PDF' });
        await expect(printLink).toBeVisible();
        urls[kind] = await printLink.getAttribute('href');
        if (kind === 'session') {
            urls.tyres = await record.locator('a[href$="/tyres"]').getAttribute('href');
            urls.checklist = await record.locator('a[href$="/checklist"]').getAttribute('href');
        }
    }
    await page.goto('/tasks');
    urls.worklist = await page.locator('a[href*="/print/open-work"]').getAttribute('href');
    for (const kind of ['vehicle', 'setup', 'tyres', 'session', 'checklist', 'worklist']) {
        await page.goto(urls[kind]);
        const document = page.locator('[data-print-document]');
        await expect(document).toHaveAttribute('data-print-document', kind);
        await expect(document).toContainText('E2E Print Kart');
        await expect(document).toContainText('Handwritten notes');
        await expect(page.locator('[data-pm-sidebar]')).toHaveCount(0);
        expect(await page.evaluate(() => window.document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
        const clipped = await document.locator('h1,h2,p,dd,td,th').evaluateAll(elements => elements.filter(element => element.scrollWidth > element.clientWidth + 1).map(element => element.textContent));
        expect(clipped).toEqual([]);
        if (kind === 'worklist') {
            expect(await document.locator('[data-work-row]').count()).toBeGreaterThanOrEqual(33);
            await expect(document).toContainText('E2E Work item 32');
            await expect(document).toContainText('E2E Worklist blocked service');
        }
        if (kind === 'checklist') {
            await expect(document).toContainText('E2E Print Weekend');
            await expect(document).toContainText('E2E Print Driver');
            await expect(document.locator('.print-check-box')).toHaveCount(6);
        }
        if (kind === 'session') {
            await expect(document.locator('[data-print-lap]')).toHaveCount(75);
            await expect(document).toContainText('1:00.002');
            await expect(document).toContainText('1:00.075');
            await expect(document).toContainText('END-PRINT-NOTES');
            await expect(document).toContainText('NASA POWER');
            await expect(document).toContainText('MET Norway');
        }
        await page.screenshot({ path: testInfo.outputPath(`print-${kind}.png`), fullPage: true });
        await page.evaluate(() => { window.print = () => { window.__didPrint = true; }; });
        await page.getByRole('button', { name: 'Print / Save PDF', exact: true }).click();
        expect(await page.evaluate(() => window.__didPrint)).toBeTruthy();
        await page.emulateMedia({ media: 'print' });
        await expect(page.locator('.print-toolbar')).toBeHidden();
        const pdf = await page.pdf({ path: testInfo.outputPath(`print-${kind}.pdf`), preferCSSPageSize: true });
        const boxes = [...pdf.toString('latin1').matchAll(/\/MediaBox\s*\[\s*0\s+0\s+([\d.]+)\s+([\d.]+)\s*\]/g)];
        expect(boxes.length).toBeGreaterThan(0);
        if (kind === 'session' || kind === 'worklist') expect([...pdf.toString('latin1').matchAll(/\/Type\s*\/Page\b/g)].length).toBeGreaterThan(1);
        for (const box of boxes) {
            expect(Math.abs(Number(box[1]) - 595.276)).toBeLessThan(1);
            expect(Math.abs(Number(box[2]) - 841.89)).toBeLessThan(1);
        }
        await page.emulateMedia({ media: null });
    }
    expect(errors).toEqual([]);
});
