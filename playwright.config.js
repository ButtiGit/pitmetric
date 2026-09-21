import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: process.env.CI
        ? [['line'], ['html', { outputFolder: 'playwright-report', open: 'never' }]]
        : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        channel: 'chrome',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chrome-desktop',
            use: {
                viewport: { width: 1440, height: 1000 },
            },
        },
        {
            name: 'chrome-mobile',
            use: {
                viewport: { width: 390, height: 844 },
                isMobile: true,
                hasTouch: true,
            },
        },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/login',
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
    },
});
