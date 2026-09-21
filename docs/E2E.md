# Real browser E2E

PitMetric uses Playwright for the five vital product workflows that must keep working as the UI evolves.

## Coverage

The suite runs the same flows in two Chrome/Chromium projects:

- `chrome-desktop` using the Playwright Desktop Chrome profile;
- `chrome-mobile-viewport` using a 390 × 844 viewport with touch enabled.

The browser performs real UI interactions for:

1. login;
2. vehicle creation;
3. component creation + installation + configuration snapshot;
4. session recording/finalization and component usage propagation;
5. maintenance schedule + work order + completed maintenance record.

The configuration workflow intentionally creates and installs its component through the UI because a valid PitMetric configuration must reflect real physical state.

## Isolation

`tests/e2e/global-setup.js` recreates `database/e2e.sqlite`, runs all migrations, and seeds only one verified owner account with database access. Product records are then created by the browser itself.

Credentials are test-only:

- email: `e2e@pitmetric.test`
- password: `password`

The E2E database is ignored by Git.

## Running locally

Install dependencies and the Playwright browser once:

```bash
npm install
npm run test:e2e:install
```

Then run:

```bash
npm run test:e2e
```

`test:e2e` builds Vite assets before Playwright starts Laravel. The test runner owns its isolated SQLite database and starts `php artisan serve` automatically.

## CI

The `e2e` job in `.github/workflows/tests.yml` runs independently from the PHP/static-analysis job. On failure, the Playwright HTML report, traces, screenshots and retained videos are uploaded as a workflow artifact.

## Selector rule

Prefer semantic locators and stable form `name` attributes. Shared CRUD modal triggers/dialogs expose `data-test="<modal-id>-trigger|dialog"` specifically so layout or wording changes do not make the critical flows brittle.
