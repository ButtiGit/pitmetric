# PitMetric Project Scope

## Document authority

- `AGENTS.md` contains binding development rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines MVP boundaries.
- `FUNCTIONAL_SPECIFICATION.md` defines expected functional behaviour.
- `CORE_USER_FLOW.md` defines the critical end-to-end workflow.

## Product

PitMetric is a web application for amateur owner-drivers who personally manage one or two karts.

The MVP gives each user one personal workspace. Every PitMetric domain record belongs to that workspace; team collaboration, invitations, and multiple roles are not part of the product.

## Main problem

An owner-driver needs a reliable answer to four connected questions:

- How much has each component been used?
- Which maintenance schedule is approaching or overdue?
- What maintenance record has already been completed?
- What does every hour of track use really cost?

Those facts are commonly split between memory, notes, spreadsheets, and messages. PitMetric turns track-session data into timely maintenance information and cost analysis.

## Value proposition

> PitMetric helps amateur kart owners know what to service, when to service it, and how much every hour on track really costs.

The core product flow is:

`track session → component usage → maintenance status → maintenance record → cost analysis`.

## MVP modules

- authentication;
- email verification;
- one personal workspace per user;
- onboarding;
- garage and kart management;
- component management;
- component installation history;
- maintenance schedules;
- track sessions;
- component usage attribution;
- maintenance records;
- expenses; and
- dashboard.

## Explicit exclusions

The following are outside the MVP. They are future possibilities only if an approved specification explicitly changes this scope:

- team collaboration, invitations, multiple roles, and multiple workspaces per user;
- telemetry and MyChron or Alfano integrations;
- artificial intelligence features, race strategy, and setup recommendations;
- subscriptions, payments, and public APIs;
- native mobile applications;
- social features and marketplaces;
- inventory management;
- championships and sponsor management;
- Formula Student-specific workflows; and
- sim-racing-specific workflows.

## Technical direction

- Laravel 13 with PHP 8.3-compatible code;
- Livewire 4 single-file components, preserving the existing `⚡` naming convention and `pages::` aliases;
- Flux 2 and the installed Flux overrides;
- Tailwind CSS 4 with CSS-first configuration, without introducing a Tailwind configuration file unless technically necessary;
- Pest using the existing `test()` style;
- SQLite for local development; and
- a production schema compatible with MySQL.

The application remains Laravel, Livewire, Blade, and Flux. It does not introduce another frontend framework.

## Data and integrity boundaries

- Monetary amounts are integer cents.
- Durations and component usage are integer minutes.
- Lap times are integer milliseconds.
- Historical karts, component installations, track sessions, maintenance records, and expenses stay auditable.
- Historical entities are archived in preference to physical deletion where appropriate.
- A browser-supplied model ID is never trusted without authorization and workspace ownership checks.
- Session creation, session editing, session deletion, component installation changes, and maintenance completion use database transactions.

## Completion criteria

### Functionally complete

The MVP is functionally complete when a verified owner-driver can, within their personal workspace:

1. register, verify their email, sign in, recover their password, and complete onboarding;
2. create, view, edit, and archive a kart;
3. create components and retain component installation history;
4. create maintenance schedules and see approaching or overdue maintenance;
5. create, edit, and delete track sessions with correct component usage attribution;
6. complete maintenance without losing historical component usage;
7. create optional expenses and see a safe, accurate cost-per-hour result; and
8. view current maintenance, session, cost, and recent-activity information on the dashboard.

Every workflow must provide loading, validation, success, error, and empty states. Destructive actions require confirmation. The product must remain usable at a 320px viewport, and the primary mobile action is **Register Session**.

Each implemented workflow requires feature coverage. Important calculations require unit coverage.

### Validated by real users

Technical completion alone does not validate the product. Before expanding the MVP, PitMetric should demonstrate:

- at least 10 conversations with amateur kart owners or drivers;
- at least 5 people who already track relevant maintenance, session, or cost data;
- at least 3 people who try the prototype;
- at least 3 users who register more than one track session;
- at least 1 user who continues using PitMetric for four weeks; and
- at least 1 user who says that losing PitMetric would remove meaningful value.
