import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const databasePath = path.resolve(process.cwd(), 'database/e2e.sqlite');
const baseURL = 'http://127.0.0.1:8000';
const serverEnv = {
    ...process.env,
    APP_ENV: 'testing',
    APP_URL: baseURL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
};

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 45_000,
    expect: {
        timeout: 8_000,
    },
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI
        ? [['line'], ['html', { open: 'never' }]]
        : [['list'], ['html', { open: 'never' }]],
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL,
        browserName: 'chromium',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: `${baseURL}/login`,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
        env: serverEnv,
    },
    projects: [
        {
            name: 'chrome-desktop',
            use: {
                ...devices['Desktop Chrome'],
            },
        },
        {
            name: 'chrome-mobile-viewport',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 390, height: 844 },
                hasTouch: true,
            },
        },
    ],
});
