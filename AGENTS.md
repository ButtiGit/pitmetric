<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

# PitMetric Project Rules

## Product

- PitMetric is a Laravel 13, Livewire 4 and Flux 2 web application for small motorsport teams and serious owner-drivers, with karting as the first pilot market.
- The current milestone is **PitMetric Pilot 0.1 — Security, Reliability & First Team**.
- The immediate goal is not automated sales or self-service commercialization. The goal is to make the existing product safe, stable, understandable and useful for one manually onboarded real team.
- The core operational loop is: workspace/team → vehicle → components/installations → configuration → event/session/setup → usage → maintenance → costs/documents → operational insight.
- Team collaboration, invitations, workspace switching and role-based access are existing product capabilities and are inside the current pilot scope.
- `docs/PROJECT_SCOPE.md` is the authority for the current Pilot Release boundary. Older single-user assumptions that remain in detailed legacy specifications must not be used to remove or disable already implemented pilot capabilities.

## Existing project conventions

- Preserve the installed Laravel Livewire starter-kit architecture.
- Preserve Livewire single-file components and the existing `⚡` filename convention where already used.
- Preserve `pages::` Livewire aliases where already used.
- Preserve Flux UI components and existing Flux overrides.
- Preserve `wire:navigate`, persisted toast behaviour and dark-mode support.
- Preserve Tailwind CSS 4 CSS-first configuration.
- Do not introduce a Tailwind configuration file unless technically necessary.
- Do not use Tailwind CSS 3 directives or conventions.
- Keep code compatible with the PHP version required by `composer.json`.
- Use Pest with the existing `test()` style.
- Preserve UTF-8 and LF formatting.
- Be cautious when editing Unicode filenames on Windows.

## Architecture

- Use Laravel, Livewire, Blade and Flux.
- Do not introduce React, Vue or another frontend framework.
- Prefer existing application patterns before introducing new abstractions.
- Keep critical business logic out of large controllers and Livewire components; use focused action or service classes for transactional domain workflows.
- Use Laravel policies, authorization gates and workspace-scoped queries for protected domain records.
- Every workspace-owned PitMetric record must be isolated from other workspaces.
- Never trust a model ID received from the browser without checking authorization, membership and workspace ownership.
- Every relationship between workspace-owned entities must preserve the same-workspace invariant.
- Use database transactions for multi-record operations whose partial completion would corrupt history, usage, maintenance, configuration, event or financial data.
- Store monetary values as integer cents unless an existing schema explicitly documents another canonical representation.
- Store canonical durations and component usage using the established integer units in the domain model.
- Store lap times using the established integer millisecond representation where applicable.
- Prefer archiving or auditable state transitions over physical deletion for historical operational records.

## Pilot Release boundary

The current codebase already contains the product surface to validate with the first team. Until the first pilot produces evidence that another macro capability is necessary, development should focus on:

- security and tenant isolation;
- reliability, backups and recovery;
- correctness and data integrity;
- test coverage and regression prevention;
- performance and query efficiency;
- mobile/trackside usability;
- accessibility and clear error handling;
- onboarding and first-use clarity;
- maintainability of existing workflows; and
- bugs or workflow improvements discovered during pilot use.

Existing pilot capabilities that may be improved but should not be removed merely because older documentation omitted them include:

- workspaces, teams, invitations and roles;
- vehicles, drivers, components and installation history;
- configurations and configuration versions;
- track sessions and usage attribution;
- maintenance schedules, records and work orders;
- expenses and cost history;
- race weekends/events, entries, tasks, schedules and notes;
- technical setups and snapshots;
- documents;
- dashboard and Control Center;
- deterministic performance/operational intelligence;
- Data Hub import/export;
- audit trail and operational notifications; and
- trackside/mobile workflows already present in the application.

Do not add a new macro product area unless the user explicitly approves a scope change. Deferred examples include:

- automated billing, subscriptions and self-service sales;
- public APIs for third parties;
- native mobile applications;
- social feeds and marketplaces;
- broad championship or sponsor-management suites;
- generative AI or speculative setup/race-strategy recommendations;
- live telemetry platforms or hardware integrations that are not required to unblock the first pilot; and
- unrelated sim-racing/game functionality inside the core operational product.

## Design direction

- Professional motorsport operations software.
- Dark interface.
- Primary accent: #FF5A36.
- Subtle Nintendo DS-era racing-menu influence only.
- Large and clear selectable areas.
- Strong active-navigation states.
- Restrained checkered detailing.
- Do not copy Nintendo or Mario Kart assets, branding, characters, fonts, sounds or exact layouts.
- Prioritize readability and professional credibility over decorative effects.
- Design mobile-first and support a 320px viewport.
- Prioritize trackside tasks and the next operational action on small screens.
- Never use colour as the only way to communicate a state.
- Forms must have visible labels.
- Destructive actions require confirmation.
- Loading, validation, success, empty and error states are required.
- Avoid horizontal page scrolling.

## Quality

- Do not edit `.env`.
- Do not commit secrets.
- Do not commit `vendor`, `node_modules`, SQLite databases or generated build artefacts unless already intentionally tracked.
- Add or update feature tests for implemented workflows and regression fixes.
- Add unit/service-level tests for important calculations and domain rules.
- Add explicit authorization and cross-workspace tests whenever a protected resource or relationship changes.
- Avoid unrelated refactors.
- Inspect the current repository before adding files or choosing paths.
- Documentation-only changes do not require application tests or frontend builds unless they change executable project configuration.

Before completing coding tasks that modify executable application code, run the relevant affected tests and the repository's formatting/build checks required by the change.

For every completed task report:

- changed files;
- architectural decisions;
- test results;
- formatting result;
- frontend build result; and
- any unresolved risk.
