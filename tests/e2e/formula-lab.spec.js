import { test, expect } from '@playwright/test';

const openCareer = async page => {
    await page.goto('/formula-lab');
    await page.getByRole('button', { name: /Aurora Corse/ }).click();
    await expect(page.getByRole('heading', { name: /Una buona macchina/ })).toBeVisible();
};

test('a car can be developed, tested and saved without using operational team data', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await openCareer(page);
    await page.getByRole('button', { name: 'Progetta Fondo', exact: true }).click();
    await page.locator('.design').filter({ hasText: 'Finestra ampia' }).getByRole('button', { name: /Sviluppa EVO 1/ }).click();
    await page.getByRole('button', { name: 'Chiudi finestra' }).click();
    await page.getByRole('button', { name: 'Progetta Sospensioni', exact: true }).click();
    await page.locator('.design').filter({ hasText: 'Assorbimento' }).getByRole('button', { name: /Sviluppa EVO 1/ }).click();
    await page.getByRole('button', { name: 'Chiudi finestra' }).click();
    await expect(page.getByText(/Finestra elastica/)).toBeVisible();
    await page.getByRole('button', { name: /02Assetto e prove/ }).click();
    await page.getByRole('button', { name: 'Esegui una prova →' }).click();
    await expect(page.getByText('Il quaderno delle prove')).toBeVisible();
    await page.reload();
    await page.getByRole('button', { name: /02Assetto e prove/ }).click();
    await expect(page.getByText('Il quaderno delle prove')).toBeVisible();
    await expect(page.getByText('3 prove disponibili')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    expect(errors).toEqual([]);
});

test('a race pauses for decisions and can be resumed through a complete GP', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await openCareer(page);
    await page.getByRole('button', { name: /04Weekend/ }).click();
    await page.getByRole('button', { name: 'Avvia qualifica e GP →' }).click();
    await expect(page.getByText('Giro 0', { exact: false })).toBeVisible();
    for (let i = 0; i < 16; i++) await page.getByRole('button', { name: 'Un giro', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Safety car in pista' })).toBeVisible();
    await page.reload();
    await page.getByRole('button', { name: /04Weekend/ }).click();
    await expect(page.getByRole('heading', { name: 'Safety car in pista' })).toBeVisible();
    await page.getByRole('button', { name: 'Segui il consiglio' }).click();
    for (let i = 0; i < 13; i++) await page.getByRole('button', { name: 'Un giro', exact: true }).click();
    await expect(page.getByText('GP completato', { exact: true })).toBeVisible();
    await expect(page.getByText(/Il prossimo passo è già finanziato/)).toBeVisible();
    await page.getByRole('button', { name: 'Al prossimo GP →' }).click();
    await expect(page.getByText(/STAGIONE 1 · GP 2 DI 6/i)).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    expect(errors).toEqual([]);
});
