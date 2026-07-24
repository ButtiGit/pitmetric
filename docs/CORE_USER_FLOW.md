# PitMetric Core User Flow

## Document authority

- `AGENTS.md` contains binding development rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines MVP boundaries.
- `FUNCTIONAL_SPECIFICATION.md` defines expected functional behaviour.
- `CORE_USER_FLOW.md` defines the critical end-to-end workflow.

## Purpose

This is the critical end-to-end workflow for the reduced PitMetric MVP:

`registration → email verification → personal workspace creation → onboarding preferences → first kart creation → first component creation → maintenance schedule creation → track session registration → component usage attribution → maintenance status recalculation → maintenance completion → optional expense creation → cost-per-hour recalculation → dashboard update`.

The flow assumes an amateur owner-driver, one personal workspace per user, and no team collaboration, invitations, or multiple roles.

## Non-negotiable data and workflow rules

- Every PitMetric domain record is scoped to the authenticated user's personal workspace.
- Monetary values are stored as integer cents; durations and component usage as integer minutes; lap times as integer milliseconds.
- A session-component relation stores the exact `usage_minutes` for that component in that track session.
- Session creation and component usage attribution commit in one database transaction.
- Session editing replaces the complete pivot attribution set in one transaction; it never increments a mutable usage counter or double-counts.
- Session deletion removes active attribution from calculations transactionally while retaining an auditable historical session record.
- Total component usage is derived from initial usage plus active historical attribution, not maintained as an independently mutable total.
- Completing maintenance updates the maintenance schedule baseline at the current usage snapshot; it never changes total component usage.
- Historical track sessions, component installations, and maintenance records remain auditable.
- Every linked kart, component, component installation, maintenance schedule, track session, maintenance record, and expense must belong to the same workspace.
- Cost per hour is safe when duration is zero: no division occurs and the result is shown as unavailable.

## 1. Registration

- **Purpose:** Create a Fortify account that can begin the email-verification flow.
- **Entry conditions:** The visitor is a guest and does not already have a PitMetric account for the email address.
- **Screen shown:** The existing registration screen.
- **User actions:** Enter name, email, password, and password confirmation; submit registration.
- **Required fields:** Name, email, password, and password confirmation.
- **Optional fields:** None.
- **Validation rules:** Name is non-blank; email is valid and unique; password follows the existing Fortify rules; confirmation matches.
- **Authorization rules:** Guests only. An authenticated user is redirected away from registration.
- **Database effects:** Create one user with `email_verified_at = null`; queue or send the standard verification notification. No workspace or domain record is created at this step.
- **Transaction requirements:** Use the established Fortify registration flow. Account creation must not leave a partially created user after a failed registration.
- **Success result:** The user is authenticated where the starter-kit flow permits and is directed to the email verification notice.
- **Failure behavior:** Show field-level validation or safe registration errors; preserve non-sensitive submitted input and create no user on failure.
- **Empty states:** Explain how a returning user can sign in and how a new user can register.
- **Loading behavior:** Disable duplicate submission, indicate progress, and do not imply that a verification email was sent until registration succeeds.
- **Mobile behavior:** Use a single-column form with visible labels, password requirements in text, large controls, and no horizontal scrolling at 320px.
- **Related automated tests:** Registration succeeds with valid data; duplicate or invalid email fails; password confirmation fails when mismatched; new user is unverified and sent toward verification.

## 2. Email verification

- **Purpose:** Confirm control of the registered email address before access to verified PitMetric routes.
- **Entry conditions:** The user is authenticated, unverified, and either on the verification notice or opening a valid verification link.
- **Screen shown:** The email verification notice or the signed verification-link endpoint's resulting confirmation screen.
- **User actions:** Open the verification email link or request a resend, then continue after successful confirmation.
- **Required fields:** No form field is required to verify through the signed link.
- **Optional fields:** None.
- **Validation rules:** The link must be signed, unexpired, and identify the current authenticated user; resend requests follow existing throttling.
- **Authorization rules:** Only the intended authenticated user may complete verification. Verified application routes reject authenticated users whose email remains unverified.
- **Database effects:** Set `email_verified_at` once verification succeeds; do not create duplicate verification state on repeat visits.
- **Transaction requirements:** Verification state is persisted atomically before workspace provisioning begins.
- **Success result:** The user becomes verified and proceeds to personal workspace creation.
- **Failure behavior:** Invalid, expired, or mismatched links show a safe recovery path to resend verification. Protected application data remains unavailable.
- **Empty states:** The notice explains that a message may be delayed and offers resend guidance.
- **Loading behavior:** Resend and link-confirmation actions show progress and prevent rapid duplicate requests.
- **Mobile behavior:** The notice is readable without scrolling sideways and makes the resend action easy to reach.
- **Related automated tests:** An unverified authenticated user is redirected to the verification notice from a `verified` route; a verified user can access the dashboard; a valid verification link marks the user verified; changing email removes verification when appropriate.

## 3. Personal workspace creation

- **Purpose:** Provision the single private data boundary used by the MVP.
- **Entry conditions:** The user is authenticated and verified, has no existing personal workspace, and has entered the post-verification flow.
- **Screen shown:** A short provisioning state followed by onboarding; no workspace chooser is shown.
- **User actions:** Continue from verification. Workspace provisioning itself requires no naming or membership decision.
- **Required fields:** None.
- **Optional fields:** None.
- **Validation rules:** The user may have only one personal workspace; the ownership relationship must be unique and idempotent.
- **Authorization rules:** Only the verified owner can enter their workspace. There is no access path for another user.
- **Database effects:** Create the user's personal workspace and ownership association exactly once.
- **Transaction requirements:** Create the workspace and ownership association in one transaction, protected by a uniqueness constraint or equivalent idempotent guard.
- **Success result:** A verified user has one usable workspace and enters onboarding.
- **Failure behavior:** Roll back incomplete provisioning, show a retryable error, and never create a second workspace on retry.
- **Empty states:** A verified account without a workspace sees the provisioning/onboarding route, not an empty shared workspace selector.
- **Loading behavior:** Show a short non-blocking progress state while provisioning is checked or created.
- **Mobile behavior:** Keep the screen simple, with a plain-language explanation and no compact desktop-only workspace controls.
- **Related automated tests:** A verified user gets one workspace; retrying provisioning does not duplicate it; another user cannot access its records; an unverified user cannot enter it.

## 4. Onboarding preferences

- **Purpose:** Orient the verified owner-driver and move them efficiently to their first kart.
- **Entry conditions:** The user is verified, owns the personal workspace, and has not completed onboarding.
- **Screen shown:** A short onboarding screen with a concise product introduction and any supported appearance preference controls.
- **User actions:** Review the flow, optionally set an appearance preference, and choose **Create First Kart**.
- **Required fields:** No preference field is required.
- **Optional fields:** Supported appearance preference only; it never changes canonical units or domain data.
- **Validation rules:** Optional preference values must be recognized by the existing settings architecture.
- **Authorization rules:** Only the workspace owner can complete their own onboarding.
- **Database effects:** Persist supported preference and onboarding-complete state if the implementation stores them; no kart or component is created implicitly.
- **Transaction requirements:** Preference and completion-state persistence is atomic when both are stored together.
- **Success result:** Onboarding is marked complete and the user reaches the first-kart creation screen.
- **Failure behavior:** Leave onboarding incomplete, retain the previous preference, and show an actionable error.
- **Empty states:** Explain that the workspace has no karts, components, schedules, sessions, maintenance records, or expenses yet.
- **Loading behavior:** The continue action prevents duplicate submission and exposes a loading label.
- **Mobile behavior:** Use one task per screen or a short stacked flow; ensure the primary action remains easily reachable at 320px.
- **Related automated tests:** Verified new user is routed to onboarding; optional preference persists when valid; invalid preference is rejected; onboarding does not bypass verification or create additional workspace records.

## 5. First kart creation

- **Purpose:** Create the first operational kart in the personal garage.
- **Entry conditions:** The user is verified, owns the workspace, and has completed or is continuing onboarding.
- **Screen shown:** The create-kart screen, reached from onboarding or the garage.
- **User actions:** Enter the kart identity and optional details; submit **Create Kart**.
- **Required fields:** Kart name.
- **Optional fields:** Manufacturer, model, year, chassis number, race number, purchase date, purchase price in cents, and notes.
- **Validation rules:** Name is non-blank; year is a plausible integer; purchase date is valid; purchase price is a non-negative integer number of cents; text fields meet safe length limits.
- **Authorization rules:** The new kart is assigned to the current workspace only. A supplied foreign workspace or model identifier is ignored or denied.
- **Database effects:** Insert an active kart belonging to the personal workspace.
- **Transaction requirements:** Kart creation is atomic; it must not create partial component installations, schedules, or expenses.
- **Success result:** The kart appears in the garage and its detail screen directs the user to create a component.
- **Failure behavior:** Show field errors and retain safe input; write no kart on failure.
- **Empty states:** The first-kart screen explains why a kart is needed before installation or track-session recording.
- **Loading behavior:** Disable duplicate create requests and show that the kart is being saved.
- **Mobile behavior:** Present labeled fields in a single column; use a large primary save action and no horizontal form layout.
- **Related automated tests:** Verified owner can create a kart; required and numeric fields validate; another user cannot create a kart in or view the first user's workspace; archive state is initially active.

## 6. First component creation

- **Purpose:** Record the first component whose use and maintenance will be tracked.
- **Entry conditions:** The user is verified, owns the workspace, and has at least one active kart available for a later installation.
- **Screen shown:** The create-component screen, reached from the kart detail or component list.
- **User actions:** Enter the component identity, starting usage, and optional purchase details; submit **Create Component**.
- **Required fields:** Component name and category.
- **Optional fields:** Brand, model, serial number, purchase date, purchase price in cents, initial usage in minutes, and notes.
- **Validation rules:** Name and category are non-blank; initial usage defaults to zero when omitted and otherwise is a non-negative integer minutes value; monetary values are non-negative integer cents; optional dates and text are valid.
- **Authorization rules:** The component belongs to the current workspace. A user cannot attach it to another workspace through a browser-supplied ID.
- **Database effects:** Insert a component with its stored initial usage and no mutable derived total.
- **Transaction requirements:** Component creation is atomic; no installation or maintenance schedule is assumed or auto-created.
- **Success result:** The component is visible in the component list and the user can create a component installation.
- **Failure behavior:** Invalid input shows field-level errors and writes no component.
- **Empty states:** Explain that no components exist and distinguish an uninstalled component from one with no history.
- **Loading behavior:** The create action prevents duplicate records while it saves.
- **Mobile behavior:** Display category and unit labels clearly; initial usage is entered in whole minutes with an accessible numeric field.
- **Related automated tests:** Owner can create a component; invalid negative minutes/cents fail; total usage begins as initial usage; another workspace cannot read or alter the component.

## 7. Maintenance schedule creation

- **Purpose:** Establish the usage interval and baseline from which maintenance status is derived.
- **Entry conditions:** The user is verified, owns the workspace, and has a component in that workspace.
- **Screen shown:** The create-maintenance-schedule screen from the component detail.
- **User actions:** Choose the component, enter schedule name or maintenance type and interval, optionally add notes, then save.
- **Required fields:** Component, schedule name or maintenance type, and interval in minutes.
- **Optional fields:** Notes.
- **Validation rules:** Component belongs to the workspace; name is non-blank; interval is a positive integer number of minutes; all linked entities are in the same workspace.
- **Authorization rules:** Only the workspace owner can create a schedule for their component.
- **Database effects:** Insert a maintenance schedule whose baseline is the component's current derived total usage.
- **Transaction requirements:** Schedule creation and baseline snapshot occur atomically. It must never change total component usage.
- **Success result:** The schedule appears on the component and dashboard with a calculated initial status.
- **Failure behavior:** Validation or persistence failure writes no schedule and preserves the component's usage history.
- **Empty states:** A component without a schedule shows **Create Maintenance Schedule**; no schedule is never misrepresented as an overdue status.
- **Loading behavior:** Save feedback prevents duplicate schedules and explains that status is being calculated after success.
- **Mobile behavior:** Explain minutes in text, show readable status labels, and keep the create action reachable.
- **Related automated tests:** Owner can create a schedule; zero or negative interval fails; schedule baseline equals current total usage; a foreign component cannot be selected.

## 8. Track session registration

- **Purpose:** Capture a real period of kart use and begin the usage-attribution transaction.
- **Entry conditions:** The user is verified, owns the workspace, and has an active kart. Component installations may exist for the chosen session date.
- **Screen shown:** The **Register Session** screen, reachable as the primary mobile dashboard action and from the kart.
- **User actions:** Choose the kart, enter date and duration, optionally enter session details, select eligible components, and submit once.
- **Required fields:** Session date, kart, and duration in minutes.
- **Optional fields:** Track name, laps, best lap time in milliseconds, track conditions, weather conditions, notes, and component selections.
- **Validation rules:** Kart is active and in the workspace; duration is a positive integer minutes value; laps are a non-negative integer; a supplied best lap is a positive integer milliseconds value; selected components must be eligible and workspace-scoped.
- **Authorization rules:** Only the owner can create a session for their workspace and active kart.
- **Database effects:** The session is not considered created until its selected component attribution is saved with it in the transaction described in the next step.
- **Transaction requirements:** Start one database transaction covering session creation and all component attribution; no partial record may be committed.
- **Success result:** The user sees a saved session, the attributed component usage, and updated maintenance information.
- **Failure behavior:** Any invalid session or attribution input rolls back the entire operation and leaves no partial session.
- **Empty states:** With no active kart, direct to **Create Kart**. With no eligible component installation, allow a session without attribution and explain how to add one.
- **Loading behavior:** A single submit control enters a loading state and prevents duplicate session saves.
- **Mobile behavior:** **Register Session** is prominent; fields are stacked, units are visible, and component rows remain usable without sideways scrolling.
- **Related automated tests:** Owner can register a valid session; duration and lap fields validate; a foreign or archived kart is rejected; failed attribution rolls back the session; no eligible component state is clear.

## 9. Component usage attribution

- **Purpose:** Store the exact component use associated with the just-registered track session.
- **Entry conditions:** The user is creating or editing a session for an authorized workspace kart and has selected zero or more eligible components.
- **Screen shown:** The component-attribution section inside the track-session form, with compatible installations suggested for the session date.
- **User actions:** Select an eligible component, enter its exact `usage_minutes`, remove unwanted rows, and submit the session.
- **Required fields:** For every selected component, component identity and exact `usage_minutes`.
- **Optional fields:** No attribution row is required when no component was used or no eligible component exists.
- **Validation rules:** `usage_minutes` is a positive integer and no greater than the session duration; duplicate rows are forbidden; component and relevant installation must be in the same workspace and valid on the session date.
- **Authorization rules:** The session, kart, component, and component installation all require current-workspace ownership checks.
- **Database effects:** Insert one session-component relation per selected component, storing exact `usage_minutes`.
- **Transaction requirements:** Commit relations together with session creation. On edit, replace the full pivot set in the same transaction; do not increment a component counter.
- **Success result:** The attribution is visible in the session history and contributes to derived component usage.
- **Failure behavior:** A bad row, foreign ID, or persistence error rejects the complete session operation or edit replacement with no partial pivot rows.
- **Empty states:** Explain why an eligible list is empty and link to component creation or installation; do not invent attribution automatically.
- **Loading behavior:** Prevent adding duplicate rows or re-submitting while attribution is validating and saving.
- **Mobile behavior:** Each row shows a readable component name, a labeled whole-minute input, and an accessible remove control.
- **Related automated tests:** Exact `usage_minutes` is stored; invalid, duplicate, foreign, or date-incompatible attribution fails; create is atomic; session edit replaces old attribution with no double count; delete removes attribution transactionally.

## 10. Maintenance status recalculation

- **Purpose:** Translate updated component usage into actionable maintenance status.
- **Entry conditions:** A relevant session transaction has committed, or a schedule, attribution, or maintenance completion has changed.
- **Screen shown:** Updated component, maintenance-schedule, kart, and dashboard status views.
- **User actions:** Review the textual status and open the relevant schedule or maintenance completion action.
- **Required fields:** None; status is calculated.
- **Optional fields:** None.
- **Validation rules:** Use only active historical attribution in the workspace, the component's initial usage, the schedule baseline, and a positive interval. A missing required value produces an explicit unavailable state.
- **Authorization rules:** Status is visible only to the workspace owner for the associated component and schedule.
- **Database effects:** No mutable total-usage counter is updated. Cached presentation data, if ever used, must be safely invalidated and reproducible from history.
- **Transaction requirements:** Recalculation runs only after the relevant write transaction commits; it must not observe a partial session or partial attribution set.
- **Success result:** The UI identifies not-due, approaching, due, or overdue maintenance according to the documented threshold policy.
- **Failure behavior:** Show an unavailable/error state rather than misleading status; preserve historical data for recovery.
- **Empty states:** A component with no maintenance schedule states that no schedule has been configured.
- **Loading behavior:** Show a concise recalculation/loading state after a relevant save; do not block access to committed history.
- **Mobile behavior:** State is communicated with words and supporting iconography in addition to colour, with a large relevant action.
- **Related automated tests:** Derived total equals initial usage plus historical attribution; status changes after attribution; status recalculates after session edit/delete; a missing schedule is distinct from not due; calculations use workspace-scoped records only.

## 11. Maintenance completion

- **Purpose:** Capture completed work and set the next maintenance cycle without erasing usage history.
- **Entry conditions:** The user is verified, owns the workspace, and is viewing an active maintenance schedule for their component.
- **Screen shown:** The maintenance-completion form from the schedule, component, or dashboard.
- **User actions:** Confirm completion date and work description, optionally name a service provider or adjust the next interval, submit completion, and optionally continue to expense creation.
- **Required fields:** Maintenance schedule, completion date, and maintenance description or type.
- **Optional fields:** Service provider, notes, and adjusted next interval in minutes.
- **Validation rules:** Schedule and component belong to the workspace; completion date is valid; description is non-blank; adjusted interval is a positive integer minutes value; an archived or foreign schedule cannot be completed.
- **Authorization rules:** Only the workspace owner can complete maintenance for their own schedule.
- **Database effects:** Create a maintenance record with the current usage snapshot, then update the schedule baseline to that snapshot. Total component usage remains derived and unchanged.
- **Transaction requirements:** Maintenance-record creation, baseline update, and optional interval update commit in one transaction.
- **Success result:** A new auditable maintenance record appears, the schedule status refreshes, and the user can optionally create a linked expense.
- **Failure behavior:** Roll back record, baseline, and interval changes together; show the error and preserve existing schedule state.
- **Empty states:** A component without a schedule directs to schedule creation; a schedule with no records explains that maintenance has not yet been completed.
- **Loading behavior:** Disable repeat completion while saving and do not report success until the transaction commits.
- **Mobile behavior:** Use visible labels, a confirmation step, clear success feedback, and a large optional **Add Expense** follow-up.
- **Related automated tests:** Completion creates a record and updates baseline atomically; total usage does not change; invalid interval fails; foreign schedule is denied; rollback preserves baseline; historical record remains visible.

## 12. Optional expense creation

- **Purpose:** Record a cost without making cost entry a blocking part of maintenance or session tracking.
- **Entry conditions:** The user is verified, owns the workspace, and may have just completed maintenance or be viewing an expense list.
- **Screen shown:** The add-expense screen, optionally pre-contextualized from a kart, component, track session, or maintenance record.
- **User actions:** Enter amount, date, category, and description; optionally link the expense; submit or skip the optional step.
- **Required fields:** Amount in cents, date, category, and description.
- **Optional fields:** Kart, component, track session, maintenance record, and notes.
- **Validation rules:** Amount is a positive integer cents value; date is valid; category and description are non-blank; each optional linked entity belongs to the same workspace.
- **Authorization rules:** Only the workspace owner may create an expense and attach it to their own records.
- **Database effects:** Insert one expense and any permitted links; do not change component usage or maintenance status.
- **Transaction requirements:** Expense and its links save atomically. A failure must not leave a partial expense or partial foreign-key association.
- **Success result:** The expense appears in contextual history and becomes eligible for cost-per-hour calculation.
- **Failure behavior:** Show field or authorization errors; write no partial expense. Skipping returns safely to the prior completed workflow.
- **Empty states:** If no expenses exist, explain that expense entry is optional and offer **Add Expense** without implying missing maintenance.
- **Loading behavior:** Prevent duplicate money entries while the form is saving and show a clear success result only after commit.
- **Mobile behavior:** Use a currency-friendly whole-cents input, clear optional-link selectors, and a readable single-column form.
- **Related automated tests:** Valid expense saves; negative or non-integer cents fail; foreign links fail; optional links work; expense does not modify maintenance or usage; unauthorised user cannot access it.

## 13. Cost-per-hour recalculation

- **Purpose:** Give the owner-driver a defensible operating-cost rate from recorded expenses and track time.
- **Entry conditions:** An expense, track session, session edit/delete, or relevant archive-state change has committed; the user opens a calculation surface.
- **Screen shown:** Cost summary on the dashboard and, where useful, an authorized kart detail.
- **User actions:** Review the calculated rate and optionally apply authorized kart or date context filters.
- **Required fields:** None; the calculation consumes stored history.
- **Optional fields:** Kart and date-range filters, when available.
- **Validation rules:** Include only workspace-scoped, current records in context; date ranges are valid and ordered; calculate only when included active session duration is greater than zero.
- **Authorization rules:** The user can calculate only from their own workspace's expense and session data.
- **Database effects:** No persisted mutable rate is required. Any cached result must be reproducible from source records and invalidated after relevant writes.
- **Transaction requirements:** Calculation reads only committed data, never an in-flight session, attribution, or expense transaction.
- **Success result:** Display `included expense cents ÷ (included session minutes / 60)` with clear context and currency formatting.
- **Failure behavior:** A query or calculation error produces a recoverable unavailable state; it must never divide by zero or display an invented rate.
- **Empty states:** With no expenses, no sessions, or zero included duration, explain what data is needed and show cost per hour as unavailable.
- **Loading behavior:** Show an updating state after a relevant save and avoid showing stale values as final.
- **Mobile behavior:** Present a labeled, readable card rather than an unexplained decimal; filters remain usable without horizontal scrolling.
- **Related automated tests:** Correct rate for known cents/minutes; zero duration is safe; excluded or archived records do not contribute; relevant session edits/deletes update the rate; cross-workspace records never contribute.

## 14. Dashboard update

- **Purpose:** Close the core loop by making the latest session, maintenance, expense, and cost information immediately useful.
- **Entry conditions:** The preceding write transaction has committed or the user has navigated to the dashboard as a verified workspace owner.
- **Screen shown:** The authenticated dashboard.
- **User actions:** Review active karts, recent track sessions, maintenance status, recent maintenance records, expenses, cost per hour, and choose the next action such as **Register Session**.
- **Required fields:** None.
- **Optional fields:** Useful dashboard context filters only, scoped to the workspace.
- **Validation rules:** Every summary query is workspace-scoped; derived values use committed records and safe zero-duration handling.
- **Authorization rules:** The dashboard requires authenticated, verified access and shows only the current owner's workspace data.
- **Database effects:** No domain record is mutated merely by loading the dashboard.
- **Transaction requirements:** The dashboard reads after committed writes; it must not trigger hidden corrective writes to usage, maintenance, or cost values.
- **Success result:** The user sees the committed track session, correct component usage and maintenance status, new maintenance record or expense when applicable, and the updated cost context.
- **Failure behavior:** Show an actionable dashboard error or partial-card error without exposing another workspace's data or claiming a stale result is current.
- **Empty states:** Give a new user a guided sequence: create a kart, create a component, create a maintenance schedule, then **Register Session**. Distinguish no data from failed loading.
- **Loading behavior:** Show predictable loading placeholders or labels; persist success feedback coherently through `wire:navigate` and existing toast behaviour.
- **Mobile behavior:** Use a dark, readable, vertically stacked layout with large selectable areas, strong active navigation, restrained checkered detail, and **Register Session** as the primary mobile action.
- **Related automated tests:** Verified user reaches dashboard; unverified user is redirected to verification; summaries reflect committed session, maintenance, and expense data; another workspace's records do not appear; zero-duration cost state is safe.

## Definition of Done

The critical flow is done when a newly registered owner-driver can verify their email, receive exactly one personal workspace, complete onboarding, create a kart and component, create a maintenance schedule, register and correctly edit or delete a track session with exact component usage attribution, complete maintenance, optionally add an expense, and see accurate derived dashboard information.

Completion also requires:

- secure `verified` route enforcement and workspace isolation;
- historical track-session, component-installation, and maintenance-record auditability;
- transactions for all required write workflows;
- safe cents, minutes, and milliseconds handling;
- no double counting after session changes;
- safe zero-duration cost-per-hour behaviour;
- visible labels, destructive confirmations, accessible non-colour status signals, and required loading, validation, success, error, and empty states; and
- feature and unit test coverage described below.

## Dependency order

1. Preserve Fortify registration, login, password reset, and email verification.
2. Enforce verified access and provision one personal workspace after verification.
3. Complete onboarding and personal settings without adding collaborative concepts.
4. Create the kart and component foundations.
5. Implement component installation history.
6. Implement maintenance schedules and derived status calculation.
7. Implement atomic track-session creation with exact component usage attribution.
8. Implement atomic track-session editing and deletion with correct attribution replacement or removal.
9. Implement maintenance completion and auditable maintenance records.
10. Implement optional expenses and cost-per-hour calculation.
11. Connect all committed data to dashboard summaries, filters, states, and mobile presentation.

## Unresolved assumptions needing user validation

- Which appearance preference, if any, should onboarding persist beyond the existing settings capability?
- Should the personal workspace be provisioned immediately after verification as specified here, or should a failed/on-hold onboarding be represented separately from a created workspace in the data model?
- What exact schedule policy defines “approaching due” versus “due” and “overdue” for an interval?
- Must a newly created component be installed before it can receive a maintenance schedule, or may schedules exist for stored spare components?
- Which component categories and installation compatibility rules should the first MVP present by default?
- Should a track session with no eligible component installation remain allowed, as this document specifies, for historical or partial-data use cases?
- Which expense categories are sufficient for the first real-user trial, and should every maintenance completion offer a prefilled expense form?
- Should cost per hour be shown only per workspace and kart, or also per selected date range in the first dashboard?

## Required test matrix

| Area | Required automated coverage |
| --- | --- |
| Registration and verification | Registration validation, verification notification flow, unverified redirect from verified routes, valid verification success, verified dashboard access, and email-change re-verification |
| Workspace isolation | One workspace per user, idempotent provisioning, policies, foreign-ID denial, and workspace-scoped queries |
| Karts and components | Create validation, archive behaviour, cents/minutes units, and cross-workspace denial |
| Component installations | Same-workspace links, date rules, no overlapping active installation, transactional changes, and audit-history retention |
| Maintenance schedules and status | Positive interval validation, baseline snapshot, usage-since-maintenance calculation, approaching/due/overdue policy, and no-schedule state |
| Track-session creation | Required fields, integer duration and lap-time validation, atomic session plus attribution commit, exact `usage_minutes`, and rollback on invalid attribution |
| Track-session edit and delete | Full pivot replacement without double counting, transactional removal from active calculations, audit retention, and updated maintenance/cost results |
| Maintenance completion | Atomic maintenance record and baseline update, unchanged total usage, optional interval validation, and rollback on failure |
| Expenses and cost per hour | Integer-cent validation, same-workspace links, optional expense behaviour, known cents/minutes calculation, excluded/archived record handling, and zero-duration safety |
| Dashboard and interface states | Workspace-scoped summaries, empty/error/loading/success states, persisted toast behaviour with `wire:navigate`, mobile 320px checks, visible labels, confirmation dialogs, and non-colour status communication |
