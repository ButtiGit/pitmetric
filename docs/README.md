# PitMetric documentation map

The documentation is split between **authoritative product/domain references**, **operational references**, and **historical implementation notes**. When documents disagree, prefer the authoritative group and the current application/tests.

## Authoritative references

- `PRODUCT_STRATEGY.md` — product direction, priorities and what not to expand before a real pilot.
- `PROJECT_SCOPE.md` — product boundaries and supported workflows.
- `FUNCTIONAL_SPECIFICATION.md` — detailed functional behavior.
- `CORE_USER_FLOW.md` — end-to-end operational journey.
- `CORE_DOMAIN_ARCHITECTURE.md` — domain boundaries and major concepts.
- `DATABASE_DESIGN.md` — persistence model and relationships.
- `UI_APPLICATION_SPECIFICATION.md` — broad application UX specification.

## Operational references

- `PILOT_CORE_WORKFLOW.md` — pilot path and acceptance focus.
- `DEPLOYMENT.md` — deployment/runtime notes.
- `TECHNICAL_SETUP.md` — setup-specific implementation notes.
- `UPDATE_STUDIO_SETUP.md` — update-studio setup.

## Historical implementation notes

These files explain completed refactors. They are useful for rationale, but they are not the current source of truth for file layout:

- `REFACTOR_STEP3.md` — progressive split of large controllers/services/views.
- `FRONTEND_CONSOLIDATION.md` — public/app bundle separation and native-first form consolidation.

Current behavior is ultimately protected by the codebase, Pest regression suite and Playwright browser workflows.
