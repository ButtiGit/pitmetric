# PitMetric Product Strategy

## Document authority

- `AGENTS.md` contains binding development rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines MVP boundaries.
- `FUNCTIONAL_SPECIFICATION.md` defines expected functional behaviour.
- `CORE_USER_FLOW.md` defines the critical end-to-end workflow.

## Product outcome

PitMetric is a credible Laravel portfolio project and a useful web application for amateur kart owners. Technical polish is necessary, but the product is successful only when real owner-drivers repeatedly use it to manage their own karts.

The first MVP serves one owner-driver with one or two karts. It gives that user exactly one personal workspace, created after email verification. All product data belongs to that workspace.

## Customer and problem

The initial customer is an amateur owner-driver who personally tracks kart use, maintenance, and costs, often from a phone at the circuit.

Today, important facts are commonly split among memory, notes, spreadsheets, and messages:

- component usage is hard to reconstruct;
- a maintenance schedule can be missed or completed without a reliable record;
- track sessions are not connected to component use;
- costs are not connected to time on track; and
- the real cost per hour is unclear.

PitMetric solves the connection problem rather than merely storing disconnected records.

## Value proposition

> PitMetric helps amateur kart owners know what to service, when to service it, and how much every hour on track really costs.

The core product flow is:

`track session → component usage → maintenance status → maintenance record → cost analysis`.

After a track session, the owner-driver should be able to record the essential facts quickly and receive a clear, trustworthy maintenance and cost picture.

## MVP product principles

- **Personal by design:** One user, one personal workspace, and no collaboration model.
- **Useful history:** Track session, component installation, and maintenance record history remains auditable.
- **Derived facts, not mutable counters:** Component usage comes from initial usage plus historical usage attribution; maintenance completion advances a schedule baseline without erasing total usage.
- **Fast at the track:** Session recording is a primary mobile action and should be practical in under a minute once setup exists.
- **Trustworthy costs:** Money uses integer cents, time and component usage use integer minutes, and lap times use integer milliseconds.
- **Professional clarity:** The product is dark, readable motorsport operations software with primary accent `#FF5A36`, strong active-navigation states, large selectable areas, and restrained checkered detail. Nintendo DS-era racing-menu influence is subtle only; no Nintendo or Mario Kart assets, branding, characters, fonts, sounds, or exact layouts are used.
- **Mobile first:** Critical workflows work at a 320px viewport, have visible labels, avoid horizontal page scrolling, and do not use colour as the sole status signal.

## Product loop

1. The owner-driver registers and verifies their email.
2. PitMetric provisions the personal workspace and completes onboarding.
3. The owner-driver creates a kart, component, component installation, and maintenance schedule.
4. The owner-driver registers a track session and exact component usage attribution.
5. PitMetric recalculates maintenance status.
6. The owner-driver completes maintenance, creating an auditable maintenance record.
7. The owner-driver may create an expense.
8. PitMetric recalculates cost per hour and updates the dashboard.

Each MVP capability should directly support this loop. A feature that does not strengthen it is deferred.

## MVP capabilities

- account registration, login, logout, password reset, profile settings, security settings, and email verification;
- one personal workspace per verified user;
- onboarding;
- garage and kart creation, viewing, editing, and archiving;
- component creation, editing, archiving, and history;
- component installation and removal history;
- usage-based maintenance schedules and status;
- track session creation, editing, and deletion;
- exact component usage attribution;
- maintenance completion and maintenance records;
- optional expenses;
- safe cost-per-hour calculation; and
- an operational dashboard with **Register Session** as the primary mobile action.

## Explicit non-goals

The following are outside the MVP and may be considered only after an approved future specification changes the product boundary:

- team collaboration, invitations, multiple roles, and multiple workspaces per user;
- telemetry, live telemetry, MyChron integration, and Alfano integration;
- artificial intelligence features, race strategy, and setup recommendations;
- subscriptions, payments, and public APIs;
- native mobile applications;
- social features and marketplaces;
- inventory management;
- championships and sponsor management;
- Formula Student-specific features; and
- sim-racing-specific features.

## Delivery principles

- Preserve the installed Laravel Livewire starter-kit architecture, Livewire single-file components, `pages::` aliases, Flux UI, `wire:navigate`, persisted toast behaviour, and dark-mode support.
- Use Laravel 13, PHP 8.3-compatible code, Livewire 4, Flux 2, Tailwind CSS 4 CSS-first configuration, and Pest.
- Keep critical business logic in focused actions or services rather than large Livewire components.
- Use policies and workspace-scoped queries for every domain record. Never trust a browser-supplied model ID without authorization and workspace checks.
- Use transactions for track-session creation, editing, and deletion; component installation changes; and maintenance completion.
- Prefer archiving historical entities over physical deletion.
- Preserve registration, login, password reset, profile settings, security settings, and email verification. Do not enable two-factor authentication or passkeys.

## Product validation

The MVP is not validated merely because it works in development or receives generic positive feedback. Before expanding it, seek evidence that it is useful:

- at least 10 conversations with amateur kart owners or drivers;
- at least 5 people who already track relevant maintenance, session, or cost data;
- at least 3 people who try the prototype;
- at least 3 users who register more than one track session;
- at least 1 user who continues using PitMetric for four weeks; and
- at least 1 user who says that losing PitMetric would remove meaningful value.

Research should also validate the practical assumptions behind the forms: what components users track, which maintenance intervals matter, which cost categories they value, and how much data entry they will tolerate after a session.

## MVP definition of done

The MVP is ready for real-user validation when a verified owner-driver can complete the core loop in their personal workspace without data leakage or double counting:

1. register, verify email, and finish onboarding;
2. create, view, edit, and archive a kart;
3. create a component and preserve component installation history;
4. create a maintenance schedule and understand its current status;
5. create, edit, and delete a track session with correct exact component usage attribution;
6. complete maintenance without losing total component usage;
7. optionally create an expense and obtain a safe cost-per-hour result; and
8. see the resulting status, history, and next action on the dashboard.

This requires feature coverage for every implemented workflow, unit coverage for important calculations, loading/validation/success/empty/error states, destructive confirmations, and a usable 320px mobile experience.
