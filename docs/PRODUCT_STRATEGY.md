# PitMetric Product Strategy

## Document authority

- `AGENTS.md` contains binding development and quality rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines the current Pilot Release boundary.
- Detailed legacy specifications remain useful for implementation history, but old single-user-only assumptions do not override the current multi-workspace Pilot scope.

## Product outcome

PitMetric's immediate objective is to become a product that one real motorsport team can trust with real operational data.

The next milestone is **not** automated monetization. The first team can be contacted directly, manually onboarded and even use the product without paying while PitMetric proves that it solves an important workflow well.

The product succeeds at this stage when the team returns to it because it is safer and more useful than reconstructing technical history from memory, paper, spreadsheets and chat messages.

## Initial customer

The first target is a small karting/motorsport team or a serious owner-driver operating like a small team.

This customer is a strong pilot fit when they already need to track several of the following:

- vehicle configuration;
- component life and usage;
- maintenance intervals and completed work;
- track sessions;
- technical setups;
- race-weekend/event tasks;
- costs;
- documents; and
- readiness before the next session or event.

PitMetric does not need to satisfy large professional teams, championships, sponsors, fans or broad consumer use cases during this pilot.

## Product promise

> PitMetric is the technical operating system for a small motorsport team: one trustworthy place for vehicle history, components, configurations, sessions, maintenance, events, setups, costs and the next operational actions.

The product should make the connection between records more valuable than the records themselves.

The critical loop is:

`prepare → configure → run → record → propagate usage → inspect maintenance/readiness → act → preserve history → prepare again`

## Why PitMetric can be valuable

A spreadsheet can store values. PitMetric should outperform it by preserving relationships and consequences:

- a session knows which vehicle/configuration was used;
- component usage flows from real activity rather than a manually edited total;
- maintenance status reacts to that usage;
- completed maintenance remains auditable;
- technical setups remain connected to track activity;
- event tasks and readiness expose what needs attention next;
- expenses and documents remain attached to operational context; and
- team members see the same current technical picture subject to their permissions.

That connected history is the core product advantage to validate.

## Pilot product principles

- **Trust before breadth:** Security, data integrity and recoverability are more important than another feature category.
- **Existing surface before expansion:** Improve what already exists before creating a new macro module.
- **Team-safe by design:** Workspace isolation and role permissions are product requirements, not implementation details.
- **Auditable history:** Historical sessions, configurations, installations, maintenance and other operational records should remain explainable after the fact.
- **Fast at the track:** Common tasks must be usable from a phone with poor attention and imperfect connectivity.
- **Clear next action:** Dashboards and Control Center should help answer what needs attention rather than merely show totals.
- **Deterministic before speculative:** Prefer explainable calculations/rules over opaque AI recommendations during the pilot.
- **High-touch onboarding is acceptable:** The founder can create/enable accounts, help enter initial data and teach the workflow personally.
- **Revenue is optional during validation:** Learning whether the product becomes operationally useful comes before automating payment.

## Current Pilot product surface

The current codebase already contains enough product breadth for a real pilot. The product may refine and harden:

- identity/security;
- workspaces, teams, invitations and roles;
- garage/vehicles and drivers;
- components, installations and usage tracking;
- configurations and versions;
- track sessions;
- maintenance and work orders;
- race weekends/events and operational tasks;
- technical setups/snapshots;
- expenses;
- documents;
- dashboard/Control Center;
- deterministic performance/operational intelligence;
- Data Hub import/export;
- audit history;
- operational notifications; and
- trackside/mobile workflows already implemented.

This breadth is now a constraint: the next engineering phase should make these workflows dependable instead of adding another category.

## Pilot delivery model

The first customer journey can be intentionally manual:

1. identify a suitable karting/motorsport team;
2. contact them directly and explain that PitMetric is in a private pilot;
3. create or enable access manually;
4. onboard the owner/manager together;
5. enter the first real vehicle/components/configuration with them;
6. use PitMetric through a real practice day or race weekend;
7. observe where the team hesitates, works around the product or returns to external tools;
8. fix security, reliability and workflow friction first; and
9. repeat use before deciding what to build next.

No checkout, pricing page or automated subscription engine is required for this process.

## Pilot success signals

The pilot should answer whether PitMetric earns a place in the team's routine. Useful signals include:

- the team completes onboarding with real data;
- at least one real vehicle has meaningful component/configuration history;
- multiple real sessions or events are recorded rather than one demo entry;
- maintenance/readiness information is consulted before action;
- more than one authorized team member can use the shared workspace without permission confusion;
- the team returns on a later track day/weekend;
- the team asks to keep its data/history in PitMetric; and
- the team can name a workflow it no longer wants to manage manually elsewhere.

Revenue can be explored after these signals exist; it is not a prerequisite for the first pilot.

## What to learn from the first team

Pay close attention to:

- which data they already track today;
- which fields they refuse to enter because they are too slow or unclear;
- which actions happen at the track versus at home/workshop;
- which roles actually need edit access;
- which maintenance intervals are operationally important;
- whether configuration/setup history is understandable later;
- what they still copy into WhatsApp, paper or spreadsheets;
- which screens they use repeatedly;
- which alerts they trust or ignore; and
- what failure would make them stop trusting the product.

These observations should drive the post-pilot roadmap.

## Explicit non-goals before pilot evidence

Do not prioritize these merely because they could make PitMetric look more complete:

- automated billing/subscriptions;
- self-service commercial onboarding;
- public APIs;
- native mobile applications;
- social/community features or marketplaces;
- broad championship-management or sponsor-management products;
- generative AI assistants, opaque setup recommendations or race-strategy prediction;
- full telemetry/hardware integration unless needed to unblock the pilot;
- speculative enterprise administration; and
- unrelated gaming/sim-racing functionality in the core product.

## Engineering priorities for the Pilot milestone

In order of importance:

1. tenant isolation and authorization correctness;
2. production security hardening;
3. backup, restore and customer-file durability;
4. transactional/data-integrity correctness;
5. monitoring and error visibility;
6. reliable authentication, invitations and email flows;
7. mobile/trackside usability and clear error states;
8. regression coverage for critical workflows;
9. maintainability/performance improvements that reduce pilot risk; and
10. only then, workflow changes requested by observed real use.

## Decision rule for new features

Before adding a new macro feature during the Pilot milestone, answer all three questions:

1. Did the pilot team hit a real problem that existing PitMetric capabilities cannot solve?
2. Is the problem repeated or important enough to affect continued use?
3. Can the problem be solved without weakening security, reliability or the core operational loop?

If the answer is not clearly yes, defer the feature.

## Exit from Pilot 0.1

PitMetric should leave this phase only after:

- a real team has used it with real operational data;
- the product has survived repeated use across more than one session/event cycle;
- known critical security/reliability risks for that pilot are addressed;
- backup/restore and support procedures are proven; and
- feedback is strong enough to define the next product boundary from evidence rather than speculation.
