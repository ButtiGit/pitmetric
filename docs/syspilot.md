# SysPilot in PitMetric

SysPilot is an isolated, authenticated private pilot at https://pitmetric.it/syspilot/.

## Components

- Static UI: public/syspilot/index.html, app.css, app.js
- Protected Laravel JSON API: /api/syspilot/*
- Controller: app/Http/Controllers/SysPilotController.php
- OpenAI Responses adapter: app/Services/SysPilot/ChecklistGenerator.php
- Access policy: app/Http/Middleware/EnsureDatabaseAccess.php
- Database: syspilot_interventions, syspilot_steps, syspilot_events

## Deploy with Coolify

Set the following variables through Coolify on the PitMetric service:

    SYSPILOT_OPENAI_API_KEY=your-api-key
    SYSPILOT_OPENAI_MODEL=gpt-4o-mini

The API reuses PitMetric's existing `database.access` middleware: authenticated,
verified users with database access enabled can use SysPilot. The existing
editor override and suspended team membership restrictions continue to apply.
No separate allowlist is required. Keep keys on the server, never in GitHub
or browser JavaScript. Use fake/demo customer data while testing.

The existing Composer deploy script executes php artisan migrate --force.
Ensure it runs after deployment to add the three isolated SysPilot tables.
After changing environment variables, redeploy or refresh Laravel config cache.

## Interface foundation

The shared design language and four screen contracts are documented in
[syspilot-design-system.md](syspilot-design-system.md). The frontend uses a
consistent navigation shell, status vocabulary, typography and components.
The Nixpacks Nginx configuration also serves /syspilot/ via index.html.

## MVP workflow

1. Log into PitMetric with an account authorized to access the PitMetric database.
2. Visit /syspilot/ and describe an IT task in Italian.
3. The Responses API returns a proposed checklist (max 5 phases, 14 steps).
4. Confirm each step's state and optional technician notes.
5. Close only after no steps remain pending/blocked.
6. Print / Save as PDF from the browser as a report.

The model creates a plan, not evidence of completed work. No remote commands
are executed. The application stores user-entered notes and its AI-generated
plan in the existing Laravel database, scoped to the authenticated user.

## Operations and known limitations

- The static shell is publicly reachable, but all data and actions are gated.
- This pilot requires an already existing PitMetric user account.
- Interventions are private per user; collaboration and multi-tenant
  organization workspaces are not supported yet.
- Browser Print / Save as PDF is the report export; server-side PDF is pending.
- Data in the main PitMetric DB stays there between redeploys only if your
  Coolify database persistence is configured correctly.
- For production/real customer information, add a dedicated SysPilot tenancy
  and privacy/legal review before expanding access.

## Workspace v0.4 — first operational increment

- Technicians can ask the AI to add, edit, move or remove checklist steps.
- The AI produces a reviewable preview. Nothing is applied until the
  technician explicitly confirms.
- Requests require existing PitMetric DB access, and AI is rate-limited.
  Step context includes title, phase, detail and state, never technician notes.
- Revision tokens are encrypted, bound to the owner and intervention, expire
  after 10 minutes, and include a fingerprint of the current checklist.
  Stale or tampered tokens are rejected.
- Completed/skipped steps cannot be changed or removed through AI.
  Draft steps with notes cannot be deleted; states and existing notes are
  never modified by an AI revision. Changes are audited after approval.
- Notes and states save automatically without rerendering after short
  typing pauses or immediately upon state change; a manual Save now
  button is retained. Quick notes insert editable snippets.
- Per-step revision checks prevent overwrites from another browser tab.
  Unsaved drafts remain in memory on failed requests with retry feedback;
  navigation flushes pending saves and beforeunload warns if needed.
- No localStorage persistence for technician notes: they may be sensitive.
- Future increments can add manual drag-and-drop, nested checklists and
  speech transcription once this flow is verified with real technicians.

## API changes

- POST /api/syspilot/interventions/{id}/ai/propose:
  accepts JSON with instruction, returns summary, preview and approval token.
- POST /api/syspilot/interventions/{id}/ai/apply:
  accepts the token, checks identity/snapshot and applies transactionally.
- GET /api/syspilot/interventions/{id}: steps include revision checksums.
- PATCH /api/syspilot/interventions/{id}/steps/{step}:
  supports optional revision and returns the updated revision.

No migrations were added for this increment: it uses existing SysPilot tables.
If bootstrap responds HTTP 500, inspect Laravel logs and check the original
SysPilot migration status; the UI upgrade does not fix missing tables.
