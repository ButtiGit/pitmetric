# PitMetric Project Scope

## Document authority

- `AGENTS.md` contains binding development and quality rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines the current Pilot Release boundary.
- `FUNCTIONAL_SPECIFICATION.md` and `CORE_USER_FLOW.md` contain detailed historical specifications. Where they still describe the original single-user MVP, this document and the implemented multi-workspace product model take precedence until those documents are refreshed.

## Current milestone

**PitMetric Pilot 0.1 — Security, Reliability & First Team**

The objective of this milestone is to make PitMetric safe, stable and useful enough for one real motorsport team to use with real operational data.

The first team will be contacted and onboarded manually. PitMetric does **not** need automated sales, automated billing or a fully self-service commercial funnel before this pilot starts.

## Product

PitMetric is technical operations software for small motorsport teams and serious owner-drivers. Karting is the first pilot market because it provides a realistic environment in which component usage, maintenance, setup, event preparation and costs matter while a small team can still adopt the product without enterprise procurement.

PitMetric's job is to connect operational facts that are otherwise split between memory, paper, spreadsheets, messaging apps and disconnected files.

## Main problem

A team needs reliable answers to connected questions such as:

- What vehicle and configuration are we running?
- Which components are installed and how much have they been used?
- What maintenance is due, approaching or already completed?
- What happened in each session and event?
- Which technical setup was used and what changed?
- What did the activity cost?
- Which tasks, documents or issues need attention before the next track activity?

PitMetric should provide those answers from one auditable operational history rather than behave like a collection of unrelated CRUD screens.

## Value proposition

> PitMetric gives a small motorsport team one trustworthy technical history for its vehicle, components, configurations, sessions, maintenance, events, setups and costs so the team knows what happened, what is due and what needs attention next.

The pilot's core operational loop is:

`team/workspace → vehicle → components/installations → configuration → event/session/setup → usage → maintenance → costs/documents → operational insight`

## Pilot users and access model

The Pilot Release supports a real team workflow rather than the former single-user-only MVP assumption.

In scope are:

- workspaces/teams as data-isolation boundaries;
- multiple users in a workspace;
- invitations;
- workspace switching where supported;
- role-based access and read-only roles already implemented; and
- an owner/manager operating model for sensitive team actions.

Every user must only see and act on data permitted by their current workspace membership and role.

## In-scope Pilot capabilities

The current Pilot Release may refine and stabilize the capabilities already present in the repository:

### Identity, team and security

- registration, login, logout, password reset and email verification;
- security/profile settings and supported two-factor authentication;
- workspace/team creation and selection;
- team membership, invitations and roles;
- application-level authorization and tenant isolation.

### Technical asset history

- garage/vehicles;
- drivers;
- component types and components;
- component installation/removal history;
- usage metrics and usage tracking;
- vehicle configurations and immutable/versioned configuration history.

### Track activity and engineering

- circuits/layouts where supported;
- track sessions and finalization;
- session/component usage attribution;
- technical setups and setup snapshots;
- deterministic performance/operational intelligence already implemented.

### Maintenance and reliability

- maintenance schedules and status;
- maintenance records and reset events;
- maintenance work orders/workboard;
- operational alerts and readiness information.

### Event operations

- race weekends/events;
- entries;
- event tasks;
- event notes;
- event schedule items;
- trackside/mobile event workflows already present.

### Costs, records and collaboration

- expenses and cost history;
- documents and authorized private downloads;
- audit trail;
- dashboard and Control Center;
- Data Hub export/import and workspace backup/restore workflows already implemented.

## Pilot feature freeze

The product surface above is sufficient to begin preparing for a first real team. Until pilot feedback proves otherwise, **do not add new macro product areas**.

Work is allowed and encouraged when it improves the existing Pilot Release through:

- security hardening;
- tenant-isolation and authorization coverage;
- data integrity and transactional safety;
- backup and recovery;
- monitoring and operational reliability;
- bug fixes;
- performance;
- mobile/trackside usability;
- accessibility;
- clearer onboarding and help;
- maintainability/refactoring of existing functionality;
- tests; and
- workflow improvements directly observed during the pilot.

## Explicitly deferred

These are not requirements for the first-team pilot unless the pilot itself demonstrates that one is necessary to continue:

- automated checkout, subscriptions and billing;
- automated customer acquisition or self-service sales;
- public third-party APIs;
- native iOS/Android applications;
- social feeds or marketplaces;
- a broad championship-management suite;
- a sponsor/CRM suite;
- generative AI features or speculative AI setup/race-strategy recommendations;
- full live telemetry platforms or hardware integrations such as MyChron/Alfano ingestion;
- unrelated sim-racing/game features inside the core product; and
- speculative enterprise features for customers PitMetric does not yet have.

## Data and integrity boundaries

- Workspace isolation is non-negotiable: a user must never obtain another workspace's private operational data by changing an identifier or URL.
- Relationships between workspace-owned records must remain inside the same workspace.
- Browser-supplied model IDs are never trusted without authorization and workspace checks.
- Historical technical and operational records remain auditable.
- Multi-record operations that would corrupt history if partially completed use database transactions.
- Monetary, duration, usage and lap-time values keep their documented canonical integer representations.
- Historical entities should be archived or transitioned through auditable states rather than physically deleted when deletion would destroy useful history.
- Customer documents and backups are private data and require explicit authorization to access.

## Technical direction

- Laravel 13;
- PHP version required by `composer.json` (currently PHP 8.4.x-compatible);
- Livewire 4 and Flux 2;
- Tailwind CSS 4;
- Pest 4;
- PostgreSQL-compatible production design, while preserving supported local development workflows;
- Laravel policies/gates, workspace scoping and explicit authorization for protected records; and
- no second frontend framework added without a demonstrated need.

## Pilot readiness definition

PitMetric is ready to invite the first real team when the existing product can be trusted with real pilot data. At minimum:

1. cross-workspace access attempts are covered and denied for protected resources;
2. role permissions are enforced consistently on sensitive actions;
3. production HTTPS/session/security settings are hardened;
4. private documents and uploads cannot be accessed outside their authorized workspace;
5. database and customer-file backups exist and a restore has been tested;
6. errors can be detected through production monitoring/logging;
7. destructive and multi-record workflows cannot easily leave corrupted partial state;
8. critical email/invitation/account-recovery flows work end to end;
9. the primary team workflows are usable on a phone at the track;
10. a complete internal simulated race-weekend workflow has been executed without needing manual database intervention; and
11. a staging environment or equivalent safe validation path exists before risky production changes.

## Pilot validation

The first pilot is intentionally high-touch. Success does not require immediate revenue.

The first team should be manually onboarded and observed. The most useful evidence is:

- whether the team can create and maintain its real technical structure;
- whether it records real sessions/events more than once;
- whether maintenance/readiness information changes a real action;
- which parts of the workflow still happen in spreadsheets, messages or paper;
- whether PitMetric is used again on the next track day/weekend; and
- whether the team would miss the product if access disappeared.

New macro features should be prioritized only after this evidence exists.
