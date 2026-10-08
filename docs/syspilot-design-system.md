# SysPilot — Interface system v0.3

SysPilot is an operational workspace, not a futuristic marketing dashboard.
The UI should feel deliberate, professional, calm and efficient. Avoid
glassmorphism, gradients, gratuitous graphs, ambiguous icons and fabricated
statistics. Use the same component language as functionality expands.

## A — Art direction and tokens

- Colors: graphite canvas #0d1118, sidebar #10151e, panels #151b25,
  borders #2a3442, primary text #f0f3f7, secondary text #8593a5.
- Accent #91adfa, reserved for primary actions and active navigation.
- Completed #79cbb1, skipped #e5be80, blocked #f09b9d.
- UI font: DM Sans 400/500/600/700. Technical metadata: IBM Plex Mono.
- Rhythm: 4/8/12/16/24/32/48px. Radius: 8px controls, 12px panels.
- Information hierarchy: labels, content and primary actions before decoration.
- One prominent action per section; consistent labels and error feedback.

The CSS variables and components in public/syspilot/app.css are the source of
truth. Reuse the panel, button, tag, table, form and status patterns.

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
