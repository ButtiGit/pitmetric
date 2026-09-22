# PitMetric

PitMetric is a motorsport operations platform for small teams and drivers. It connects the physical state of a vehicle with component usage, configurations, track sessions, maintenance, race-weekend operations, costs, timing and telemetry.

The core product principle is simple: **prepare → configure → run → record → maintain → learn**. PitMetric treats the physical component installation as the source of truth and preserves an auditable history of what was actually used on track.

## Stack

- PHP 8.4 / Laravel 13
- Livewire 4 + Flux
- Tailwind CSS 4 + Vite
- SQLite for the default local/test environment
- Pest + PHPStan + Laravel Pint
- Playwright with Chrome desktop and mobile projects

## Local setup

```bash
composer setup
composer dev
```

`composer setup` installs PHP and Node dependencies, creates `.env` when needed, generates the application key, runs migrations and builds the frontend.

For a normal production-style build:

```bash
npm run build
```

## Quality gates

The main CI gate is available locally through:

```bash
composer ci:check
```

It runs formatting checks, static analysis and the Pest suite. Browser coverage is separate:

```bash
npm run e2e
npm run e2e:desktop
npm run e2e:mobile
```

The Playwright suite covers the vital workflow in real Chrome: login, vehicle creation, configuration capture, session recording/finalization and maintenance. A dedicated mobile scenario also protects the trackside action dock, bottom-sheet forms and swipeable maintenance lanes.

## Core architecture

The critical operational chain is:

1. Create an active vehicle.
2. Install physical components on that vehicle.
3. Capture a configuration version from the current physical installation.
4. Record/finalize a track session against an aligned configuration.
5. Propagate usage to installed component trackers.
6. Surface maintenance readiness and complete required work.
7. Preserve setup, cost, event and audit history for the next run.

Workspace isolation is enforced across workspace-owned records and backed by dedicated integrity/isolation tests.

## Documentation

Start with [`docs/README.md`](docs/README.md) for the documentation map. The most important references are:

- [`docs/PRODUCT_STRATEGY.md`](docs/PRODUCT_STRATEGY.md) — product direction and prioritization.
- [`docs/PROJECT_SCOPE.md`](docs/PROJECT_SCOPE.md) — scope and boundaries.
- [`docs/CORE_USER_FLOW.md`](docs/CORE_USER_FLOW.md) — primary user journey.
- [`docs/CORE_DOMAIN_ARCHITECTURE.md`](docs/CORE_DOMAIN_ARCHITECTURE.md) — domain architecture.
- [`docs/DATABASE_DESIGN.md`](docs/DATABASE_DESIGN.md) — data model.
- [`docs/PILOT_CORE_WORKFLOW.md`](docs/PILOT_CORE_WORKFLOW.md) — pilot workflow and acceptance focus.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — deployment notes.

## Repository boundaries

- `app/` contains application/domain code.
- `resources/` contains the Laravel frontend and Blade UI.
- `tests/Feature/` contains application regression coverage.
- `tests/e2e/` contains real browser workflows.
- `public/game_sim.php` and `public/game_assets/` contain an isolated experimental Race Engineer game. It is intentionally separate from the PitMetric core workflow and has its own lightweight CI validation.

Generated build output, browser reports, local environment files and packaged brand archives must not be committed.
