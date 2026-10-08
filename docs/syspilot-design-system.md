# PitMetric · SysPilot — Visual identity v0.6

SysPilot is a PitMetric module, not a separate brand. Keep the same
graphite/red visual language as the authenticated PitMetric management
interface while preserving SysPilot's distinct intervention workflow.

## Shared PitMetric visual contract

- Follow `resources/css/app.css` dark tokens on `.pitmetric-app`:
  page #0b0d10, navigation #111317, surfaces #111419 / #15191f,
  border #272d36, foreground #f5f7fa, muted #a9b1bd.
- Primary accent #E10600, hover #F01812; completed #48b98a, warning
  #e6b85c, danger #ef7b7b. Do not use blue as the product accent.
- Use Instrument Sans for UI with a system monospace for technical IDs.
  Containers, fields and actions should follow PitMetric's compact
  spacing, corner radii, focus states and button hierarchy.
- Load the already-shipped `/brand/pitmetric-primary-dark.svg` in
  SysPilot. Show SysPilot as the module name beneath the master brand;
  breadcrumbs should link back to the PitMetric dashboard.
- The authenticated PitMetric sidebar links to SysPilot for users with
  enabled database access, including studio editors, but all API
  permissions remain governed by existing Laravel middleware.
- CSS `--pm-*` values in `public/syspilot/app.css` are a static
  mirror of PitMetric's dark tokens. If PitMetric changes them, sync
  these explicitly without importing Tailwind into the standalone app.
- Preserve noindex, same-origin API calls, original statuses, print
  reports, responsive layout, accessibility and optional step checks.
- The product stays at `/syspilot/` and `/api/syspilot/` for link
  compatibility. Renaming visuals must not rename database tables.

## Design rationale

Visually integrate the technical workflow with PitMetric while avoiding
unnecessary changes to Laravel session/login, SQL tables or AI prompts.
This layer is presentation-only apart from the visible parent-sidebar link.

---

## B — Master screen structures

### Overview (#home)
One headline, primary Create intervention CTA, three factual metrics, a
Resume work card showing the latest open task, short onboarding guidance,
and recent intervention rows. Empty dataset means empty state, not invented
sample statistics.

### New intervention (#new)
Two columns. Main panel: large free-form description, optional client
and asset, single CTA with loading feedback. Side rail: AI process explanation,
example requests that fill the form locally, selected model from backend.
Never expose credentials or submit anything until the user confirms.

### Intervention workspace (#work/ID)
Header with ID/status/context, accurate completion meter, phase-grouped
checklist forms for step status and notes. Sticky sidebar for context,
actions and chronological events. A completed status must come from the
technician, never inferred from the AI proposal. Closing remains subject
to the original backend checks. Print CSS generates a readable report.

### Archive (#archive)
Search and status filtering of the records returned by the API; compact
rows showing title, record ID, client/asset, timestamp and status.
The current bootstrap response includes only the newest 40 interventions,
so the screen explicitly calls this out. Server-side pagination is future work.

## Shared technical boundaries

Static HTML/CSS/JavaScript are served from public/syspilot; authenticated
operations remain in protected Laravel endpoints /api/syspilot. Reuse
PitMetric database.access. Never render secrets in frontend code. Escape
all user strings and respect server-side status and ownership checks.
HTTP 500 is a backend error, not proof that the user lacks permission.
Provide specific retry guidance without dumping private stack traces.

Responsive behavior: desktop at full sidebar width, compact at 1100px,
top navigation below 800px and single column below 550px. Preserve
keyboard focus, semantic forms, labels, readable contrast, reduced-motion
preferences and printer-friendly report output.

## Interaction patterns in v0.4

- AI revision: two-step proposal and explicit approval, no executable chat.
- Quick notes: human-controlled editable suggestions, not automatic outcomes.
- Autosave: communicate pending, saving, saved and failure states per step.
- Optimistic concurrency: reject stale revisions with HTTP 409.
- Print: display notes as readable report text without editing controls.

## v0.5 sub-checklist and completion evidence

- Root steps are grouped by phase; children are rendered immediately below
  their parent using the existing .check-item and .tag styles.
- Nested rows use the shared accent as a subtle indentation line, never
  an independent visual language.
- The "Approfondisci con IA" action fills the current AI editor, where
  the proposal and its parent relationship remain visible for approval.
- Previously declared done steps show a provenance cue in the detail.
  This cue reflects the user's words, not a technician-verified outcome.
