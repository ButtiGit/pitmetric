import { defineConfig, devices } from '@playwright/test';
import { fileURLToPath } from 'node:url';

export default defineConfig({
  testDir: './tests/browser',
  fullyParallel: false,
  workers: 1,
  use: { baseURL: 'http://127.0.0.1:4174', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [
    { name: 'chromium-pwa', use: { ...devices['Desktop Chrome'] } },
    { name: 'iphone-pwa', use: { ...devices['iPhone 13'], browserName: 'webkit' } },
  ],
  webServer: {
    cwd: fileURLToPath(new URL('..', import.meta.url)),
    command: 'node mobile/tests/browser/server.mjs',
    url: 'http://127.0.0.1:4174/mobile/',
    reuseExistingServer: !process.env.CI,
  },
});
