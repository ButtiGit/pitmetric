# PitMetric Functional Specification

## Document authority

- `AGENTS.md` contains binding development rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines MVP boundaries.
- `FUNCTIONAL_SPECIFICATION.md` defines expected functional behaviour.
- `CORE_USER_FLOW.md` defines the critical end-to-end workflow.

## 1. Document purpose and authority

- **Purpose:** Define the observable MVP behaviour for PitMetric without expanding its product scope.
- **Actions:** Product and engineering decisions are checked against this document and the authority notice above.
- **Fields:** None.
- **Validation:** A proposed behaviour must use the established terms and remain inside the MVP boundaries.
- **Permissions:** This is documentation, not a user-facing capability.
- **Success:** A requirement can be implemented and tested without contradicting the governing documents.
- **Failure:** An unclear or conflicting requirement is held for clarification instead of being silently invented.
- **Empty state:** Not applicable.
- **Mobile:** Not applicable.
- **Business rules:** `AGENTS.md` is binding; this document defines behaviour, not a second implementation architecture.

## 2. User type

- **Purpose:** Serve an amateur owner-driver who personally manages one or two karts.
- **Actions:** The owner-driver records their own karts, components, maintenance, track sessions, and expenses.
- **Fields:** Account name and email identify the user; all operational data is held in their personal workspace.
- **Validation:** The product must not assume a workshop, organization, championship, or multi-user operating model.
- **Permissions:** A user accesses only their own personal workspace.
- **Success:** The user can manage the core flow independently.
- **Failure:** Requests for collaboration, multiple roles, or extra workspaces are outside the MVP.
- **Empty state:** A new owner-driver sees onboarding rather than a fabricated dashboard.
- **Mobile:** The primary workflows are usable at a 320px viewport.
- **Business rules:** One verified user has one personal workspace in this MVP.

## 3. Authentication and email verification

- **Purpose:** Establish a secure, verified identity before access to PitMetric domain data.
- **Actions:** Register, sign in, sign out, request and complete a password reset, resend verification, open the signed verification link, and confirm a password when required.
- **Fields:** Registration requires name, email, password, and password confirmation. Login requires email and password. Password reset requires email, reset token, password, and confirmation.
- **Validation:** Email is valid and unique; passwords meet the existing Fortify rules; reset and verification links must be valid, signed, unexpired, and bound to the intended user.
- **Permissions:** Guests may use registration, login, and password-reset screens. Authenticated but unverified users may use only the verification flow and permitted account actions. Verified users may access application routes.
- **Success:** Registration creates an unverified account and sends a verification notification. Verification records `email_verified_at` and advances the user to workspace onboarding. A successful login uses the existing Fortify redirect behaviour.
- **Failure:** Invalid credentials, throttling, invalid links, expired links, or mismatched users show a safe error and do not reveal protected data.
- **Empty state:** A visitor without an account is offered registration and password recovery.
- **Mobile:** Auth forms have visible labels, keyboard-friendly input order, clear errors, and no horizontal scrolling.
- **Business rules:** The standard Laravel email-verification contract is required. The `verified` middleware must redirect an authenticated unverified user to the verification notice. Two-factor authentication and passkeys are not enabled.

## 4. Personal workspace

- **Purpose:** Scope all PitMetric data to one personal workspace per user.
- **Actions:** After email verification, the user enters onboarding and receives their personal workspace; no workspace picker or switching control is shown.
- **Fields:** The system stores the workspace and its single user ownership. A user-facing workspace name is not required for the MVP.
- **Validation:** Ownership is unique per user and provisioning is idempotent so a retry cannot create a second workspace.
- **Permissions:** Only the owning authenticated, verified user can read or change records in that workspace.
- **Success:** The workspace is available before any kart, component, maintenance schedule, track session, maintenance record, or expense is created.
- **Failure:** If provisioning cannot complete, no partial domain data is exposed; the user receives a recoverable error and can retry safely.
- **Empty state:** A verified user with no completed onboarding is directed into onboarding.
- **Mobile:** Workspace setup uses a single-column flow with a clear continuation action.
- **Business rules:** Every domain record is workspace-scoped. There are no teams, invitations, collaborators, multiple roles, or multiple workspaces per user.

## 5. Onboarding

- **Purpose:** Move a newly verified owner-driver from account creation to their first useful PitMetric record.
- **Actions:** Review a short introduction, optionally set available appearance preferences, and continue to create the first kart.
- **Fields:** No operational field is required to finish onboarding. Any appearance preference is optional and must not change canonical storage units.
- **Validation:** The user must be verified and own the current workspace; optional preferences must use supported values.
- **Permissions:** Only the workspace owner can complete their onboarding.
- **Success:** The workspace is marked ready for use and the user is taken to the first-kart action.
- **Failure:** A failed save leaves onboarding incomplete and displays an actionable error without duplicating a workspace.
- **Empty state:** The screen explains that no karts or components exist yet.
- **Mobile:** Show progress in plain text, one clear next action, and touch-friendly controls.
- **Business rules:** Onboarding never bypasses email verification and never creates another workspace.

## 6. Dashboard

- **Purpose:** Give the owner-driver a current operational summary and a fast route to the core flow.
- **Actions:** Review active karts, recent track sessions, approaching or overdue maintenance schedules, recent maintenance records, expenses, and cost per hour; choose **Register Session**.
- **Fields:** Dashboard filters, when present, are limited to the current workspace and useful date or kart context.
- **Validation:** Summary queries use only authorized workspace records and ignore archived or deleted-from-calculation records where relevant.
- **Permissions:** A verified workspace owner may view only their dashboard.
- **Success:** The dashboard reflects the latest committed data and links to the relevant detail or creation screen.
- **Failure:** A failed summary query shows a retryable error without showing stale data as current.
- **Empty state:** With no track sessions, show a clear **Register Session** action; with no karts, direct the user to create one.
- **Mobile:** **Register Session** is the primary mobile action. Cards stack without horizontal scrolling and never rely on colour alone for maintenance state.
- **Business rules:** Maintenance status and cost per hour are derived values, not editable dashboard counters. A zero-duration denominator produces a safe unavailable result.

## 7. Garage

- **Purpose:** List the workspace's karts and provide the entry point for kart management.
- **Actions:** View active karts, search or filter them when useful, open a kart, create a kart, and include archived karts only through an explicit archive view.
- **Fields:** Search text and archive-state filter are optional.
- **Validation:** Filters are bounded and apply only to the current workspace.
- **Permissions:** A verified owner sees only their own workspace's karts.
- **Success:** The selected kart opens with its component installation, maintenance, track-session, and expense history available through authorized views.
- **Failure:** An unknown, archived-as-active, or foreign kart is not exposed.
- **Empty state:** Explain that no karts exist and offer **Create Kart**.
- **Mobile:** Use large, distinct tappable kart cards and an always-visible create action without a dense desktop-only table.
- **Business rules:** Garage is a personal workspace capability, not a shared team garage.

## 8. Kart creation, view, edit, and archive

- **Purpose:** Maintain accurate, auditable identities for the karts being operated.
- **Actions:** Create a kart, view its details and history, edit current details, and archive it after confirmation.
- **Fields:** Create and edit require a kart name. Manufacturer, model, year, chassis number, race number, purchase date, purchase price in cents, and notes are optional.
- **Validation:** Name is non-blank; year is a plausible integer; purchase date is a valid date; purchase price is a non-negative integer number of cents; text fields have safe length limits.
- **Permissions:** The kart must belong to the current workspace for view, edit, or archive actions.
- **Success:** A create action adds the kart to the active garage. An edit preserves history. An archive hides it from active selection while preserving its historical relationships.
- **Failure:** Invalid fields show field-level errors. A foreign or missing kart is denied without leaking its details. An archive failure changes nothing.
- **Empty state:** A newly created kart shows clear empty sections for component installations, maintenance schedules, track sessions, maintenance records, and expenses.
- **Mobile:** Form fields are labeled and single-column; archive is separated from ordinary edits and requires a confirmation dialog.
- **Business rules:** Karts are archived rather than physically deleted when history exists. An archived kart cannot be selected for a new track session or component installation.

## 9. Components

- **Purpose:** Record reusable, maintainable kart parts such as engines, tyres, chains, brakes, and custom components.
- **Actions:** Create, view, edit, archive, and inspect the usage, installation, maintenance, and expense history of a component.
- **Fields:** Name and category are required. Brand, model, serial number, purchase date, purchase price in cents, initial usage in minutes, and notes are optional; omitted initial usage is zero.
- **Validation:** Name and category are non-blank; initial usage is a non-negative integer number of minutes; monetary values are non-negative integer cents; dates and optional text lengths are valid.
- **Permissions:** A component and every linked record must belong to the current workspace.
- **Success:** The component is available for installation and maintenance scheduling, and its total usage can be derived reliably.
- **Failure:** Invalid input retains entered values and shows field errors. A foreign or missing component cannot be viewed or altered.
- **Empty state:** Explain that no components exist and offer **Create Component**.
- **Mobile:** Component cards expose name, category, usage, and maintenance state in readable text with large actions.
- **Business rules:** Total component usage is derived from initial usage plus active historical usage attribution. Archiving preserves history and prevents new use unless the component is restored by an approved future workflow.

## 10. Component installation and removal

- **Purpose:** Preserve which component was fitted to which kart, and when, so track-session attribution is credible.
- **Actions:** Install a component on a kart, view the installation timeline, and remove a component from a kart.
- **Fields:** Kart, component, and installation date are required. Removal date and notes are optional.
- **Validation:** Kart and component are in the same workspace; dates are valid; removal is not earlier than installation; a component cannot have overlapping active installations; a kart/component relationship must be valid on the track-session date.
- **Permissions:** Only the workspace owner may change installations for their own kart and component.
- **Success:** The installation is recorded as historical data and eligible components can be suggested for usage attribution on matching sessions.
- **Failure:** Invalid dates, duplicate active installations, foreign entities, and conflicting changes leave the existing timeline unchanged.
- **Empty state:** A kart with no installation history explains how to install a component; a component with no installation history explains that it is not currently fitted.
- **Mobile:** Installation and removal forms use date controls with clear labels and confirmation for removal.
- **Business rules:** Installation changes use a database transaction. Removal ends an installation history entry; it does not erase past session attribution.

## 11. Maintenance schedules

- **Purpose:** Define when a component needs maintenance based on accumulated usage.
- **Actions:** Create, view, edit, archive when no longer applicable, and inspect the current status of a maintenance schedule.
- **Fields:** Component, schedule name or maintenance type, and interval in minutes are required. Notes and a future interval adjustment are optional.
- **Validation:** The component belongs to the workspace; interval is a positive integer number of minutes; names are non-blank; linked data remains in the same workspace.
- **Permissions:** Only the workspace owner may manage schedules for their components.
- **Success:** The schedule receives a baseline at the component's current total usage and appears with a derived status.
- **Failure:** Invalid intervals or foreign components are denied without changing the schedule.
- **Empty state:** A component with no schedule explains how to add one; the dashboard distinguishes this from a schedule that is not due.
- **Mobile:** Status includes text and an icon or label in addition to colour, with a direct **Complete Maintenance** action when applicable.
- **Business rules:** Status is calculated from total component usage and the schedule baseline. Creating or editing a schedule never rewrites historical total usage.

## 12. Track sessions

- **Purpose:** Record a period of kart use that drives component usage and subsequent maintenance status.
- **Actions:** Create a track session, select a kart, enter the duration, and choose eligible component usage attribution.
- **Fields:** Session date, kart, and duration in minutes are required. Track name, laps, best lap time in milliseconds, track conditions, weather conditions, and notes are optional.
- **Validation:** The kart belongs to the workspace and is active; duration is a positive integer number of minutes; laps are a non-negative integer; a supplied best lap is a positive integer number of milliseconds; dates and text fields are valid.
- **Permissions:** Only the workspace owner may create a track session in their workspace.
- **Success:** The track session and its usage attribution commit together, maintenance status recalculates, and the dashboard updates.
- **Failure:** Any invalid session field or attribution prevents the entire operation; no partial session or usage data is saved.
- **Empty state:** Without an active kart, explain that a kart must be created first. Without eligible installations, allow a session with no attribution and explain how to add components.
- **Mobile:** The form is single-column, saves once, prevents duplicate submissions while loading, and keeps **Register Session** prominent.
- **Business rules:** Session creation and component usage attribution are one database transaction. Duration is stored in integer minutes, never floating-point hours.

## 13. Track session editing and deletion

- **Purpose:** Correct or withdraw a track session without double-counting component usage or destroying auditability.
- **Actions:** Edit session fields and attribution, or delete a session after explicit confirmation.
- **Fields:** The editable fields are the track-session fields and exact component `usage_minutes` values. Deletion requires confirmation, not a free-text reason.
- **Validation:** The same create rules apply. Every replacement component belongs to the workspace and is valid for the session date.
- **Permissions:** Only the owner of the session's workspace may edit or delete it.
- **Success:** Editing replaces the old attribution set atomically and recalculates affected maintenance status. Deletion removes active attribution from calculations atomically and preserves an auditable historical record.
- **Failure:** A validation, authorization, or persistence failure rolls back every session and attribution change.
- **Empty state:** A kart with no sessions explains how to register the first one. A deleted or archived session is clearly labeled and not presented as active data.
- **Mobile:** Edit and delete controls are distinct; deletion uses a clear confirmation dialog and a loading state.
- **Business rules:** Session edits replace pivot attribution rather than incrementing counters. Session deletion removes attribution transactionally so it cannot continue affecting usage, maintenance, or cost-per-hour calculations.

## 14. Component usage attribution

- **Purpose:** Attribute exact track-session use to the components that were used.
- **Actions:** Select or remove eligible components during track-session creation or editing and enter each component's exact `usage_minutes`.
- **Fields:** Track session, component, and `usage_minutes` are required for each attribution row.
- **Validation:** `usage_minutes` is a positive integer no greater than the session duration; each component and its relevant installation are in the same workspace and valid for the session date; duplicate component rows are rejected.
- **Permissions:** Only the workspace owner may manage attribution for their own session and components.
- **Success:** Each relationship stores the exact `usage_minutes` and derived component totals and maintenance status update after commit.
- **Failure:** Invalid or foreign attribution causes the surrounding session transaction to fail with no partial pivot rows.
- **Empty state:** A session with no eligible installed components explains why none are listed and how to create or install one.
- **Mobile:** Each row has a visible component name, a labeled numeric-minute input, an accessible remove action, and no gesture-only control.
- **Business rules:** The session-component relation stores exact `usage_minutes`. Total component usage is derived from initial usage plus active historical attribution; it is not a mutable counter.

## 15. Maintenance status

- **Purpose:** Communicate whether a maintenance schedule is not due, approaching due, due, or overdue.
- **Actions:** Review current status on component, schedule, kart, and dashboard views; open the associated schedule or completion action.
- **Fields:** No user-entered status field exists. Status is derived from total component usage, schedule baseline, and interval in minutes.
- **Validation:** Calculations use valid workspace-scoped historical attribution and the current schedule baseline; corrupted or missing data is reported rather than guessed.
- **Permissions:** A user sees status only for schedules in their own workspace.
- **Success:** Status refreshes after a relevant session, session edit/delete, schedule change, or maintenance completion.
- **Failure:** If status cannot be calculated, display an explicit unavailable/error state and do not present a misleading due state.
- **Empty state:** A component without a schedule states that no maintenance schedule exists.
- **Mobile:** The status includes readable words and supporting iconography, not colour alone, and links to the appropriate next action.
- **Business rules:** Usage since maintenance equals current total component usage minus the schedule baseline. The warning threshold is a documented schedule policy; it must be consistent wherever status appears.

## 16. Maintenance completion

- **Purpose:** Record completed work and reset the relevant maintenance schedule baseline without losing component history.
- **Actions:** Open a maintenance schedule, confirm completion, create a maintenance record, optionally adjust the next interval, and optionally continue to expense creation.
- **Fields:** Maintenance schedule, completion date, and maintenance description or type are required. Service provider, notes, and an adjusted next interval are optional.
- **Validation:** The schedule and component belong to the workspace; dates are valid; any adjusted interval is a positive integer number of minutes; the user cannot complete an archived or foreign schedule.
- **Permissions:** Only the workspace owner may complete maintenance for their own component.
- **Success:** A maintenance record captures the usage snapshot and the schedule baseline moves to that snapshot in one transaction. The updated status is shown immediately.
- **Failure:** Validation or persistence failure creates no maintenance record and leaves the existing baseline unchanged.
- **Empty state:** A component without a schedule directs the user to add one; a schedule without records explains that no maintenance has been completed yet.
- **Mobile:** Completion is a clear, confirmable action with visible required fields, a loading state, and a success message.
- **Business rules:** Completing maintenance changes the schedule baseline, never total component usage. Maintenance records remain historical and auditable. A maintenance cost is represented by an optional linked expense to avoid duplicate cost accounting.

## 17. Expenses

- **Purpose:** Record money spent so the user can understand cost in context and cost per hour.
- **Actions:** Create, view, edit, archive where appropriate, and filter expenses; optionally link an expense to a kart, component, track session, or maintenance record.
- **Fields:** Amount in cents, date, category, and description are required. Kart, component, track session, maintenance record, and notes are optional.
- **Validation:** Amount is a positive integer number of cents; dates are valid; category and description are non-blank; every optional linked entity belongs to the same workspace.
- **Permissions:** Only the workspace owner may read or change their own expenses.
- **Success:** The expense appears in relevant histories and cost calculations after commit.
- **Failure:** Invalid currency input, foreign linked entities, or a failed save leave no partial record and show a useful error.
- **Empty state:** Explain that no expenses exist and offer **Add Expense** without making an expense mandatory after maintenance.
- **Mobile:** Use a currency-friendly input backed by integer cents, labeled link selectors, and a compact detail view.
- **Business rules:** Expenses are optional. They do not change component usage or maintenance status. Archived expenses are excluded from current totals but remain auditable.

## 18. Cost per hour

- **Purpose:** Show the real cost of operating a kart or workspace relative to recorded track use.
- **Actions:** Review cost per hour on the dashboard and relevant kart views; optionally filter by authorized kart or date range.
- **Fields:** No editable calculated field exists. Inputs are included expense amounts in cents and active track-session duration in minutes.
- **Validation:** Only workspace-scoped, non-archived records in the selected context contribute. The denominator must be greater than zero before a numeric result is shown.
- **Permissions:** A user sees calculations only from their own workspace data.
- **Success:** The application displays a clearly labeled monetary rate derived after relevant expense or session changes.
- **Failure:** Missing or invalid source data produces a safe unavailable state and never a divide-by-zero error.
- **Empty state:** With no expenses or no active track-session duration, explain what is needed to calculate a rate.
- **Mobile:** Present the result as a readable summary card with its context, not an unlabeled decimal.
- **Business rules:** Cost per hour is `included expense cents ÷ (included session minutes / 60)`. Zero duration is safe and produces no numeric rate.

## 19. Profile, security, and preferences

- **Purpose:** Let the user maintain account information while retaining the existing Fortify and Livewire architecture.
- **Actions:** Update name and email, update password through the existing security flow, use password confirmation where required, and change supported appearance preferences.
- **Fields:** Profile requires name and email. Password change requires the current password, new password, and confirmation. Appearance fields are limited to supported existing preferences.
- **Validation:** Name and email are valid; email uniqueness is preserved; current password is verified; new passwords follow Fortify rules; preferences use supported values.
- **Permissions:** An authenticated user may change only their own account and preferences.
- **Success:** Profile and password updates persist through the established settings pages. A changed email becomes unverified and requires fresh verification before verified routes are available.
- **Failure:** Incorrect current password, duplicate email, invalid fields, or failed persistence show safe field-level errors and preserve the previous secure state.
- **Empty state:** Not applicable to the core profile fields; unset optional preferences use the existing default.
- **Mobile:** Settings use visible labels, clear sections, keyboard-friendly inputs, and accessible validation feedback.
- **Business rules:** Preserve registration, login, password reset, profile settings, and security settings. Do not enable two-factor authentication or passkeys. The interface must support dark mode; a light theme is not an MVP requirement.

## 20. Useful search and filtering

- **Purpose:** Help an owner-driver find relevant records without turning the MVP into a broad reporting system.
- **Actions:** Search or filter karts, components, maintenance schedules, track sessions, maintenance records, and expenses by useful text, status, date, kart, or component context.
- **Fields:** Query text and context-appropriate filters such as archive state, maintenance status, date range, kart, or component are optional.
- **Validation:** Dates are valid and ordered; filter values are recognized; referenced kart and component filters belong to the workspace; result limits prevent unbounded requests.
- **Permissions:** Searches are always scoped to the current workspace.
- **Success:** Results are clearly filtered, retain enough context to understand the match, and can be cleared.
- **Failure:** Invalid filters return a safe validation response rather than a broad unscoped result.
- **Empty state:** State whether no records exist yet or no records match the current filters, and offer a clear-filter action where relevant.
- **Mobile:** Filters collapse into an accessible, labeled control; active filters remain visible without consuming the whole viewport.
- **Business rules:** This is targeted list filtering, not a public search, social feed, telemetry explorer, or advanced reporting engine.

## 21. Loading, validation, success, error, and empty states

- **Purpose:** Make every workflow understandable and safe during normal and failed interactions.
- **Actions:** Submit a form, wait for a result, correct field errors, retry a recoverable failure, dismiss a success message, and act from an empty state.
- **Fields:** Forms expose their own labeled fields; status messages identify the operation and affected record where useful.
- **Validation:** Server-side validation is authoritative; client-side affordances do not replace it.
- **Permissions:** Authorization failures do not disclose other workspace data and use an appropriate safe response.
- **Success:** Persisted success feedback remains coherent with `wire:navigate` behaviour and the existing toast behaviour.
- **Failure:** Errors explain the next safe action; destructive operations cannot report success unless the transaction committed.
- **Empty state:** Every primary list, detail history, and calculation distinguishes “nothing exists” from “something could not be loaded.”
- **Mobile:** Loading controls prevent duplicate taps, preserve accessible labels, and do not cause layout shift or horizontal scrolling.
- **Business rules:** Loading, validation, success, empty, and error states are required; colour is never the only status signal.

## 22. Authentication and workspace isolation

- **Purpose:** Prevent cross-user access and insecure direct object references.
- **Actions:** Access a route, load a record, submit a form, or follow a link only through authenticated and authorized workspace-scoped handling.
- **Fields:** Route and form identifiers are treated as untrusted selectors, not proof of authorization.
- **Validation:** Every linked kart, component, component installation, maintenance schedule, track session, maintenance record, and expense is checked to be in the same workspace.
- **Permissions:** Domain routes require authenticated, verified users. Policies and workspace-scoped queries authorize each record action.
- **Success:** The owner can operate on their own records while other users' records remain inaccessible.
- **Failure:** A foreign, missing, or unauthorized ID returns a safe denial or not-found response and makes no state change.
- **Empty state:** A user with an empty workspace never sees another user's example or fallback data.
- **Mobile:** Mobile routes and navigation enforce the same protections as desktop routes.
- **Business rules:** Never trust a model ID received from the browser without authorization and workspace ownership checks.

## 23. Responsive design

- **Purpose:** Make the core product reliable at the track on small screens and usable on larger screens.
- **Actions:** Navigate, read statuses, create records, edit records, confirm destructive actions, and register a session from a 320px viewport upward.
- **Fields:** Inputs remain visible, labeled, reachable, and correctly typed on mobile devices.
- **Validation:** Responsive layouts are checked for overflow, clipped validation messages, inaccessible controls, and tap targets that are too small.
- **Permissions:** Responsive presentation never changes authorization or workspace scope.
- **Success:** The same essential workflow is available at 320px without horizontal page scrolling.
- **Failure:** Unsupported viewport or rendering errors show a readable fallback rather than hiding critical actions.
- **Empty state:** Empty-state calls to action remain visible and usable on narrow screens.
- **Mobile:** Mobile-first layout, large selectable areas, strong active-navigation states, and **Register Session** as the primary mobile action are required.
- **Business rules:** Preserve `wire:navigate`, persisted toast behaviour, and dark-mode support. Do not introduce a separate native mobile application for the MVP.

## 24. Accessibility

- **Purpose:** Ensure the product can be understood and operated without relying on colour, pointer precision, or decorative styling.
- **Actions:** Read content, navigate by keyboard, enter forms, receive validation feedback, use confirmations, and understand maintenance state.
- **Fields:** Every form control has a visible label; required fields and units are named in text.
- **Validation:** Errors are associated with their fields and announced or otherwise exposed in an accessible manner; focus moves predictably after failed submission where appropriate.
- **Permissions:** Authorization and error messaging remain comprehensible without exposing protected information.
- **Success:** Essential tasks work with keyboard navigation, semantic controls, sufficient contrast, and text alternatives for icon-only controls.
- **Failure:** An inaccessible state is treated as a defect, not as an optional visual enhancement.
- **Empty state:** Empty states use readable text, actionable controls, and not only illustration or colour.
- **Mobile:** Touch targets are comfortably sized and retain visible focus and state cues for keyboard-capable mobile devices.
- **Business rules:** The design is a dark, professional motorsport operations interface with primary accent `#FF5A36`, subtle Nintendo DS-era racing-menu influence, and restrained checkered detailing. It must not copy Nintendo or Mario Kart assets, branding, characters, fonts, sounds, or exact layouts.

## 25. Data integrity and audit history

- **Purpose:** Keep maintenance, usage, and cost decisions reproducible from historical records.
- **Actions:** Create or edit a track session, change component installations, complete maintenance, archive records, and calculate totals from history.
- **Fields:** Monetary amounts use integer cents; durations and usage use integer minutes; lap times use integer milliseconds; timestamps and foreign keys identify historical context.
- **Validation:** All links are same-workspace, dates are coherent, integer units are used, and historical records cannot be overwritten by derived counters.
- **Permissions:** Only authorized workspace owners can mutate their records; read access follows the same scope.
- **Success:** Historical session, component installation, and maintenance record information remains auditable while current calculations remain correct.
- **Failure:** Any failure in session create/edit/delete, component installation change, or maintenance completion rolls back the complete transaction.
- **Empty state:** No historical record is represented as zero usage, completed maintenance, or zero cost unless the underlying data explicitly means that.
- **Mobile:** Audit history is readable as chronological, accessible entries rather than a desktop-only dense table.
- **Business rules:** Use database transactions for session creation, session editing, session deletion, component installation changes, and maintenance completion. Session edits replace attribution correctly with no double count; session deletion removes attribution from active calculations transactionally; maintenance completion changes only the schedule baseline, never total usage.

## 26. Functional definition of done

- **Purpose:** Define the minimum observable evidence that the reduced MVP works end to end.
- **Actions:** A verified owner-driver completes onboarding, creates a kart and component, records an installation and maintenance schedule, registers and corrects a track session, completes maintenance, adds an optional expense, and reviews the dashboard.
- **Fields:** The workflow uses all required fields defined above and canonical cents, minutes, and milliseconds.
- **Validation:** Feature tests cover authorization, verification, required fields, invalid linked entities, and transactional rollback. Unit tests cover usage, maintenance-status, and cost-per-hour calculations.
- **Permissions:** Tests prove that another user cannot access or mutate workspace records.
- **Success:** The dashboard reflects committed data, maintenance history remains auditable, and cost per hour is safe with zero duration.
- **Failure:** Failed validation, authorization, or persistence leaves no partial state and provides a clear user-facing response.
- **Empty state:** A new verified workspace gives a guided route to the first kart, component, schedule, and track session.
- **Mobile:** Critical workflow tests and manual review confirm usability at 320px, including the primary **Register Session** action.
- **Business rules:** The MVP is complete only when the stated core flow works with secure workspace isolation, dark professional motorsport presentation, no horizontal scrolling, visible labels, confirmations for destructive actions, and the required loading, validation, success, error, and empty states.
