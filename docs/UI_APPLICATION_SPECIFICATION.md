# PitMetric UI Application Specification

## Document authority

- `AGENTS.md` contains binding development rules.
- `PRODUCT_STRATEGY.md` defines product direction.
- `PROJECT_SCOPE.md` defines MVP boundaries.
- `FUNCTIONAL_SPECIFICATION.md` defines expected functional behaviour.
- `CORE_USER_FLOW.md` defines the critical end-to-end workflow.
- `UI_APPLICATION_SPECIFICATION.md` defines interface structure and interaction behaviour.

## Purpose, scope, and implementation status

This document defines the complete interface architecture for the reduced PitMetric MVP. It describes the intended user-facing structure, visual system, interaction rules, proposed future page map, and responsive behaviour. It does not authorize application-code, database, route, configuration, or dependency changes by itself.

Terms in this document have the same meaning as the product documentation: **workspace**, **kart**, **component**, **component installation**, **maintenance schedule**, **track session**, **maintenance record**, and **expense**.

Use the following labels consistently:

- **Current** means a route, layout, component, or behaviour already present in the starter kit.
- **Proposed** means a future PitMetric implementation target. A proposed route or alias is not evidence that it exists today.
- **Preview** means clearly labelled presentation-only content used before real domain data is connected. It is never inserted into a user's workspace and never used for maintenance or cost calculations.

## Cross-document alignment notes

This specification does not introduce a competing source of authority. The following items are made explicit so they are resolved before implementation rather than hidden in the interface:

| Topic | Existing documentation | Interface direction | Implementation rule |
| --- | --- | --- | --- |
| Track-session circuit and time | The functional and core-flow documents currently require kart, date, and duration, and describe track name as optional. | This UI brief requires **occurred date and time** and **circuit name** in quick and full session creation. | Present them as required UI fields. Before server validation is implemented, update the higher functional documents in an approved change so persistence and validation agree. |
| Workspace preferences | The functional document does not require a user-editable workspace name. | The settings UI reserves workspace name, currency, timezone, duration display, date format, and theme preference. | Use safe defaults and do not invent persistence until the workspace-preferences domain design is approved. |
| Onboarding | The core flow places onboarding before the first kart and component. | The wizard guides the user through preferences, first kart, and first component, with explicit defer actions. | It never silently creates domain records. A deferred user reaches truthful empty states and can resume setup later. |
| Maintenance thresholds | The exact warning policy is unresolved. | The UI exposes a warning-threshold control and displays Regular, Upcoming, Due, and Overdue states. | All values and boundaries come from the domain calculation; the interface must not create a second calculation rule. |
| Preview dashboard | Product documents prohibit fabricated workspace data. | A temporary preview may demonstrate intended layout before domain data exists. | Label every preview value and suppress it after real data is available. Do not mix it into user totals, history, or calculations. |
| Maintenance cost | A maintenance cost must not create duplicate cost accounting. | The maintenance completion form can offer an optional linked expense. | The amount creates or links one expense transactionally; it is not also persisted as a separate maintenance-record amount. |

## Existing application contract

The implementation must preserve the installed Laravel Livewire starter-kit architecture:

- Laravel 13 with PHP 8.3-compatible code, Livewire 4, Flux 2 Free, Tailwind CSS 4, and Pest.
- The authenticated layout convention is `layouts::app`. It currently renders `<x-layouts::app.sidebar>` and `<flux:main>`; the active shell is `resources/views/layouts/app/sidebar.blade.php`.
- Full-page Livewire single-file components use `pages::` aliases and literal Unicode `⚡` filenames. A proposed Garage index, for example, is `resources/views/pages/garage/⚡index.blade.php` with alias `pages::garage.index`.
- Existing authentication pages are Fortify Blade views under `resources/views/pages/auth/`; they are not replaced with Livewire SFCs and their POST flows remain Fortify-owned.
- The current dashboard is a Blade `Route::view` placeholder. This specification distinguishes that temporary state from a future domain-backed dashboard SFC.
- Preserve Flux overrides, `wire:navigate` for internal navigation, the persisted Flux toast group, and the normal CSRF POST logout form.
- Preserve existing light, dark, and system appearance support. PitMetric is dark-first; light and system remain compatibility choices rather than separate visual directions.
- Tailwind configuration remains CSS-first in `resources/css/app.css`. Planned design tokens use `@theme` and semantic CSS variables; no Tailwind CSS 3 directives or `tailwind.config.*` file are proposed.
- Use Flux Free components where available. Do not assume Pro-only widgets, React, Vue, or Alpine-heavy custom widgets.

## Design direction

PitMetric should feel like professional motorsport operations software: compact, reliable, data-led, and credible in a Laravel portfolio. The visual language may borrow only a subtle Nintendo DS-era racing-menu feeling through hierarchy, dense-but-readable panels, decisive selection states, and restrained checkered detail.

It must not copy or imitate Nintendo or Mario Kart branding, characters, logos, fonts, sounds, icons, menu layouts, protected artwork, exact colour combinations, or game interface assets. Avoid racing cliches such as fake carbon fibre, excessive glow, dramatic gradients, decorative gauges, or visual clutter.

The interface is dark-first with primary accent `#FF5A36`. Data clarity, clear actions, and explicit state text always take priority over decoration.

## Global design system

### Semantic colour tokens

These are proposed semantic tokens. Their final Tailwind CSS 4 implementation belongs in the CSS-first theme layer when visual work is approved.

| Token | Dark-first value | Light/system compatibility value | Rule |
| --- | --- | --- | --- |
| `--pm-page` | `#0B0D10` | `#F5F6F8` | Page background; never use pure black or white for large canvases. |
| `--pm-canvas` | `#111419` | `#FFFFFF` | Main content canvas and topbar base. |
| `--pm-sidebar` | `#101216` | `#FAFAFB` | Sidebar and mobile navigation surface. |
| `--pm-elevated` | `#181C21` | `#FFFFFF` | Cards, panels, dropdowns, and modals. |
| `--pm-raised` | `#20252B` | `#F0F2F4` | Hovered surfaces and contained controls. |
| `--pm-border` | `#303640` | `#D7DCE2` | Default 1px border and separators. |
| `--pm-border-strong` | `#4A525E` | `#AAB2BD` | Active, selected, and focus-adjacent boundaries. |
| `--pm-text-primary` | `#F5F7FA` | `#15191F` | Headings, primary values, and essential labels. |
| `--pm-text-secondary` | `#A9B1BD` | `#59626E` | Supporting copy, metadata, and inactive labels. |
| `--pm-text-muted` | `#727C8A` | `#727C8A` | Placeholder, tertiary metadata, and disabled copy when contrast remains sufficient. |
| `--pm-accent` | `#FF5A36` | `#FF5A36` | Primary action, selected emphasis, focus association, and never the only state signal. |
| `--pm-accent-hover` | `#FF704F` | `#E94B29` | Hover and pressed-adjacent primary treatment. |
| `--pm-accent-active` | `#D94829` | `#C93C20` | Pressed, current-route edge, and selected control treatment. |
| `--pm-success` | `#38B980` | `#18764D` | Completed, healthy, or saved states; pair with text and icon. |
| `--pm-warning` | `#F2B84B` | `#8A5A08` | Upcoming or attention-needed states; pair with text and icon. |
| `--pm-danger` | `#EF6A6A` | `#9D2E2E` | Overdue, destructive, and blocking-error states; pair with text and icon. |
| `--pm-info` | `#6CA9FF` | `#185DB5` | Informational, pending, and neutral guidance; pair with text and icon. |
| `--pm-focus` | `#FF9B84` | `#B83A21` | Visible 2px focus ring with a 2px offset from the control. |
| `--pm-disabled-surface` | `#242930` | `#E4E7EB` | Disabled controls, never used to conceal a required action. |
| `--pm-disabled-text` | `#78818D` | `#7B8490` | Disabled text with a non-colour explanation where needed. |
| `--pm-hover` | `#242931` | `#ECEFF2` | Pointer or keyboard hover on neutral interactive surfaces. |
| `--pm-selected` | `#45251E` | `#FFE4DC` | Selected list item, tab, or navigation state; combine with accent edge and text. |

Primary buttons use `--pm-accent` with a near-black foreground such as `#1A100D`, not white text, unless contrast testing proves an alternative passes. All normal-size text must meet WCAG 2.2 AA contrast of at least 4.5:1; large text must meet 3:1; meaningful component boundaries, focus indicators, and icons must meet at least 3:1.

### Spacing, layout, and motion

| System item | Rule |
| --- | --- |
| Spacing scale | Use a 4px base: 0, 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, and 80px. Prefer `gap` between siblings over arbitrary margins. |
| Content widths | Full application canvas: 1440px maximum. Standard operational page: 1280px. Dense data page: 1360px. Form: 640px. Narrow confirmation/readable copy: 480px. |
| Page padding | 16px at 320px, 20px on larger phones, 24px on tablets, 32px on desktop, and 40px on large desktop. |
| Borders | 1px default; 2px only for focus, selected navigation, and critical status emphasis. Do not use thick ornamental outlines. |
| Radius | 8px for controls and small badges, 12px for cards, 16px for modals and large panels. Avoid pill shapes except compact filters, statuses, and segmented controls. |
| Shadows | One restrained elevation shadow for modals/dropdowns and one subtle card shadow only where separation needs it. No coloured glow. |
| Motion | 100ms for micro feedback, 160ms for hover/focus, 220ms for modal or drawer movement. Respect `prefers-reduced-motion` by removing non-essential movement. |
| Hover | Use surface, border, or icon changes in addition to colour where the item is actionable. Touch devices do not rely on hover to reveal primary actions. |
| Selected | Use accent edge or indicator, changed surface, stronger text, and `aria-current` where applicable. |

### Typography, numbers, icons, and patterns

- Use the existing application font stack. Do not introduce a game-like display font.
- Use a compact hierarchy: page title 28-32px, section heading 20-24px, card heading 16-18px, body 14-16px, labels and metadata 12-14px. Line height remains generous enough for dense data.
- Use `tabular-nums` for metrics, dates, lap times, usage, and currency. Align numerical columns by their values, not by decorative icons.
- Display duration as `1 h 24 m` in summaries and accept hours/minutes in inputs while persisting integer minutes. Display money in the chosen currency while persisting integer cents. Display lap time as `m:ss.mmm`.
- Use Flux and the installed Heroicon/Lucide asset strategy. Confirm exact icon names before implementation; every icon-only control has a programmatic name and tooltip.
- A checkered pattern may appear at very low contrast in a page-header rule, empty-state corner, or navigation selection strip. It may not sit behind body text, encode state, fill cards, or reduce contrast.

## Responsive architecture

### Breakpoint behaviour

| Viewport | Shell and navigation | Content and grids | Data presentation |
| --- | --- | --- | --- |
| 320-479px | Compact app header plus fixed mobile bottom navigation. No desktop sidebar. | One column; 16px page padding; one primary action per visual group. | Cards and timeline rows replace wide tables. |
| 480-767px | Same mobile navigation; mobile header may show breadcrumbs only when short. | One or two metric cards per row when labels remain readable. | Filters collapse into a sheet or disclosure; list rows may show secondary metadata inline. |
| 768-1023px | Tablet keeps mobile bottom navigation and app header so the primary action stays reachable. | Two-column forms where each field remains at least 160px; 2-3 metric cards per row. | Compact tables may appear only when every essential column fits; otherwise use responsive data lists. |
| 1024-1439px | Sticky desktop sidebar and desktop topbar replace mobile bottom navigation. | 24-32px page padding; 2-4 metric cards depending on value density. | Tables can use labelled columns, sort/filter controls, and per-row actions. |
| 1440px and above | Desktop shell remains fixed; sidebar may use its expanded width. | 40px page padding; content never exceeds its specified maximum width. | Dense operational tables may show full metadata, but still provide a readable card/timeline alternative where useful. |

### Shell rules

- At `lg` (1024px) and above, use the existing `layouts::app` sidebar shell. The sidebar is sticky, 256px expanded and approximately 72px collapsed if a future compact mode is added. It remains visible while main content scrolls.
- At desktop widths, the topbar is 64px high and contains contextual breadcrumbs, page-level actions, and the existing user menu. It does not duplicate the primary sidebar navigation.
- Below `lg`, replace the current sidebar-toggle-only experience with a compact header plus mobile bottom navigation. The header retains the logo/home link, short page context, and profile access; it is not a second full navigation list.
- The bottom navigation is fixed above `env(safe-area-inset-bottom)`. Main content receives bottom padding of at least `calc(80px + env(safe-area-inset-bottom) + 16px)` so content and forms are never obscured.
- **Register Session** is the center mobile action. It is visually strongest through accent fill, elevated position, clear text, and a 48px minimum touch target. It never floats over an active form submit button or hides page content.
- No page-level horizontal scrolling is permitted at 320px. Long identifiers wrap or truncate with a full accessible label; tables become cards rather than forcing the viewport wider.

### Responsive table and list rules

- Desktop tables use a semantic `table` with clear headers, visible row focus, and actions that remain reachable by keyboard.
- At widths below 768px, replace operational tables with a responsive data list: a primary label, 1-3 essential values, status text, and an explicit action affordance.
- Do not hide a critical value solely because the viewport is narrow. Move it into a labelled detail disclosure or detail page.
- Date, duration, money, status, and action labels remain visible in each mobile row. Avoid unlabeled icon-only row actions.

## Reusable interface components

Use Flux Free primitives where they cover the interaction, then apply PitMetric semantic tokens and content rules. Each component below defines the required interface contract.

### 1. Application shell

- **Purpose:** Provide one consistent verified application frame around every PitMetric operational page.
- **Required content:** Brand link, desktop sidebar or mobile header/bottom navigation, `<flux:main>`, persisted toast group, and authenticated user controls.
- **Optional content:** Contextual topbar actions, page-level search, and a short workspace label.
- **Variants and visual states:** Desktop sidebar shell and mobile-navigation shell; default, loading-navigation, and route-transition states.
- **Loading behaviour:** Preserve the existing page shell during `wire:navigate`; show local page skeletons rather than replacing the complete frame.
- **Disabled behaviour:** Navigation items are not disabled for ordinary loading; a blocked item explains why it is unavailable.
- **Mobile behaviour:** Uses compact header and bottom navigation with safe-area spacing.
- **Accessibility:** Provides one main landmark, a skip-to-content link, logical landmark order, and a labelled navigation region.

### 2. Desktop sidebar

- **Purpose:** Provide persistent primary navigation and account access on desktop.
- **Required content:** PitMetric brand, Dashboard, Garage, Components, Sessions, Maintenance, Expenses, Settings, active-state indicator, and desktop user menu.
- **Optional content:** Collapsed presentation, compact workspace label, and non-primary external project links.
- **Variants and visual states:** Expanded, future-collapsed, hover, keyboard-focus, selected, and unavailable states.
- **Loading behaviour:** Preserve current active item during navigation; show a subtle route-transition indicator without shifting layout.
- **Disabled behaviour:** Only unavailable future areas may be disabled, with visible explanatory text rather than a dead icon.
- **Mobile behaviour:** Hidden below `lg`; it must not coexist with the fixed bottom navigation.
- **Accessibility:** Uses a `nav` label, `aria-current="page"` on the active route, visible focus, and text labels beside icons.

### 3. Topbar

- **Purpose:** Supply desktop page context, breadcrumbs, global actions, and user controls.
- **Required content:** Breadcrumb area or page-context label, optional page actions slot, and existing user profile/dropdown.
- **Optional content:** Date context, compact filters, and an informational status indicator.
- **Variants and visual states:** Standard, contextual-action, loading, and dense-data variants.
- **Loading behaviour:** Page actions show their own loading state; the topbar remains stable.
- **Disabled behaviour:** Disabled actions retain their label and explain prerequisites through helper text or tooltip.
- **Mobile behaviour:** Becomes the compact mobile header, showing only essential context and profile access.
- **Accessibility:** Breadcrumbs use an ordered navigation landmark; dropdown trigger exposes its expanded state and preserves focus.

### 4. Mobile bottom navigation

- **Purpose:** Give one-handed access to the five highest-value mobile destinations.
- **Required content:** Home, Garage, Register Session, Maintenance, and More.
- **Optional content:** Badge count for overdue maintenance only when it is meaningful and announced in text.
- **Variants and visual states:** Default, active, raised-primary-action, and More-open states.
- **Loading behaviour:** Route transition does not remove the navigation; the selected destination shows loading feedback.
- **Disabled behaviour:** Register Session is disabled only when no active kart exists, with an accessible explanation and a direct Create Kart path.
- **Mobile behaviour:** Fixed below `lg`, minimum 64px visible height plus safe-area inset, with 44px minimum item targets and 48px primary target.
- **Accessibility:** Each destination has a text label, active state, and descriptive accessible name; More opens a focus-managed dialog or sheet.

### 5. Page header

- **Purpose:** Establish page title, supporting context, and the primary page action.
- **Required content:** Title and one primary action when the page has a creation or completion workflow.
- **Optional content:** Supporting text, breadcrumb, status summary, secondary actions, and filter summary.
- **Variants and visual states:** Standard, compact, action-heavy, and empty-workspace variants.
- **Loading behaviour:** Keep title visible and show action-level loading rather than a blank header.
- **Disabled behaviour:** Primary action remains visible when unavailable and states the prerequisite.
- **Mobile behaviour:** Stacks title, copy, and actions; primary action becomes full-width only when necessary.
- **Accessibility:** Uses one visible `h1`; action labels describe their outcome.

### 6. Breadcrumbs

- **Purpose:** Show navigational context for nested detail, edit, and completion pages.
- **Required content:** Current page and all meaningful parent destinations.
- **Optional content:** Kart or component short identifier when it improves disambiguation.
- **Variants and visual states:** Full desktop trail and truncated mobile trail with Back action.
- **Loading behaviour:** Keep previous stable context during local page updates.
- **Disabled behaviour:** Current page is not a link; unavailable parent links are omitted rather than disabled.
- **Mobile behaviour:** Show at most parent plus current page; never create a horizontally scrolling strip.
- **Accessibility:** Use `nav aria-label="Breadcrumb"`, ordered semantics, and `aria-current="page"`.

### 7. Primary button

- **Purpose:** Trigger the single most important action in a local context.
- **Required content:** Clear imperative text and, when useful, a leading Flux icon.
- **Optional content:** Short helper text, badge count, or keyboard shortcut hint.
- **Variants and visual states:** Accent solid, hover, pressed, focus, loading, disabled, and destructive-adjacent confirmation-launch states.
- **Loading behaviour:** Replaces or supplements the label with progress, prevents duplicate submission, and retains width.
- **Disabled behaviour:** Shows a non-colour explanation near the control when a prerequisite is unmet.
- **Mobile behaviour:** Minimum 48px height; full-width in narrow form footers; no icon-only primary action.
- **Accessibility:** Has a descriptive label, visible focus ring, preserved accessible name while loading, and no colour-only distinction.

### 8. Secondary button

- **Purpose:** Offer a safe alternative action without competing with the primary action.
- **Required content:** Text label; icon only when it reinforces the label.
- **Optional content:** Shortcut hint or low-priority count.
- **Variants and visual states:** Neutral outlined or raised surface, hover, focus, loading, and disabled.
- **Loading behaviour:** Uses inline progress if it submits an asynchronous action.
- **Disabled behaviour:** Maintains readable label and does not masquerade as inactive text.
- **Mobile behaviour:** Sits below or beside the primary action with adequate spacing; wraps before forcing overflow.
- **Accessibility:** Meets contrast on both themes and communicates unavailable state in text where needed.

### 9. Destructive button

- **Purpose:** Begin an archive, delete, removal, or irreversible-change confirmation.
- **Required content:** Explicit destructive verb, such as Archive Kart, Remove Installation, or Delete Session.
- **Optional content:** A short scope label or affected-record count.
- **Variants and visual states:** Danger outline before confirmation; danger solid only inside a confirmation modal; hover, focus, loading, and disabled states.
- **Loading behaviour:** Locks the confirm action after one activation and reports progress.
- **Disabled behaviour:** Disabled when the user lacks permission or a prerequisite; explain without exposing protected data.
- **Mobile behaviour:** Separated from ordinary saves and placed after a clear visual divider.
- **Accessibility:** Uses direct language, never icon-only, and requires a focus-managed confirmation modal.

### 10. Icon button

- **Purpose:** Provide compact secondary actions such as filter, close, edit, or overflow.
- **Required content:** Confirmed Flux/Heroicon or installed Lucide icon plus an accessible label.
- **Optional content:** Visible short text on wider screens and tooltip.
- **Variants and visual states:** Neutral, subtle, danger, selected, hover, focus, loading, and disabled.
- **Loading behaviour:** Replace icon with an accessible spinner while retaining the label.
- **Disabled behaviour:** Keep icon and accessible reason; do not rely on opacity alone.
- **Mobile behaviour:** Minimum 44px square target with spacing from adjacent controls.
- **Accessibility:** `aria-label` matches the action, tooltip supplements rather than replaces the name, and focus is visible.

### 11. Metric card

- **Purpose:** Present one high-priority dashboard value with enough context to avoid misleading interpretation.
- **Required content:** Label, formatted value, unit or currency, context period, and status or comparison text when relevant.
- **Optional content:** Small icon, trend, sparkline only when it comes from real data, and link to the detail view.
- **Variants and visual states:** Standard, accent-action, success, warning, danger, unavailable, preview, loading, and empty.
- **Loading behaviour:** Uses a stable skeleton with the same approximate dimensions.
- **Disabled behaviour:** Not applicable; unavailable metrics state why they cannot be calculated.
- **Mobile behaviour:** Stacks values in one or two columns without truncating units.
- **Accessibility:** Value and context are read as one labelled group; trends never rely on colour or arrows alone.

### 12. Kart card

- **Purpose:** Summarize one kart in the Garage or dashboard.
- **Required content:** Kart name, active or archived state, key metadata, maintenance attention summary, and explicit open action.
- **Optional content:** Manufacturer/model, race number, current component count, total driving time, and thumbnail-free visual marker.
- **Variants and visual states:** Active, archived, selected, warning, overdue, hover, loading, and empty-list placeholder.
- **Loading behaviour:** Uses a content-shaped skeleton, not fake kart data.
- **Disabled behaviour:** Archived kart card remains viewable but explains why new session/install actions are unavailable.
- **Mobile behaviour:** Entire card opens details only when nested actions remain separately reachable; no hover-only actions.
- **Accessibility:** Card title is a semantic link, status has text/icon, and nested actions do not create invalid interactive nesting.

### 13. Component card

- **Purpose:** Summarize a component and its operational readiness.
- **Required content:** Name, category, total usage, current installation state, and maintenance status.
- **Optional content:** Brand/model, serial suffix, usage since service, upcoming schedule, and link to history.
- **Variants and visual states:** Installed, uninstalled, retired, regular, upcoming, due, overdue, loading, and empty.
- **Loading behaviour:** Shows label/value skeletons with stable card height.
- **Disabled behaviour:** Retired component shows history but disables new-install and new-attribution actions with explanation.
- **Mobile behaviour:** Keeps name, usage, installation, and status visible without table compression.
- **Accessibility:** Status combines written label, icon, and colour; usage includes a clear unit.

### 14. Maintenance status panel

- **Purpose:** Explain one schedule's current state and next safe action.
- **Required content:** Component, schedule name, total usage, service baseline, usage since service, interval, status label, and action.
- **Optional content:** Warning threshold, estimated remaining minutes, last maintenance record, and contextual kart link.
- **Variants and visual states:** Regular, Upcoming, Due, Overdue, no schedule, unavailable calculation, and archived schedule.
- **Loading behaviour:** Uses labelled value skeletons and does not display stale status as current.
- **Disabled behaviour:** Completion action explains why it is unavailable for archived or foreign records.
- **Mobile behaviour:** Values stack in a short labelled grid with a full-width completion action where appropriate.
- **Accessibility:** The panel describes every number in text and does not encode urgency by colour alone.

### 15. Status badge

- **Purpose:** Provide compact, repeatable state recognition in lists and cards.
- **Required content:** Short text label and status icon.
- **Optional content:** Numeric count or tooltip with fuller explanation.
- **Variants and visual states:** Regular, Upcoming, Due, Overdue, Active, Archived, Installed, Uninstalled, Saved, Error, and Preview.
- **Loading behaviour:** Use a neutral placeholder badge only when the label is unknown.
- **Disabled behaviour:** Not applicable; badges communicate state, not control availability.
- **Mobile behaviour:** Remains readable at 12-14px and does not become an unlabeled dot.
- **Accessibility:** Text remains in the DOM; icon is decorative when redundant or labelled when it adds information.

### 16. Progress indicator

- **Purpose:** Show progress toward a maintenance interval or onboarding step without implying precision that does not exist.
- **Required content:** Label, current and target values, unit, and written status.
- **Optional content:** Percentage, remaining-minute text, and threshold marker.
- **Variants and visual states:** Neutral, Upcoming, Due, Overdue, indeterminate, and onboarding-step variants.
- **Loading behaviour:** Indeterminate state is labelled Loading; no fake percentage is shown.
- **Disabled behaviour:** Not applicable; if calculation is unavailable, replace with explanatory state.
- **Mobile behaviour:** Uses a full-width bar with values above or below it, never compressed into unreadable inline text.
- **Accessibility:** Exposes `role="progressbar"` and value semantics when determinate; written status is always present.

### 17. Empty state

- **Purpose:** Turn the absence of records into a clear next step.
- **Required content:** Specific title, one-sentence explanation, and one relevant primary action.
- **Optional content:** Secondary learning link, small restrained illustration or checkered corner detail, and context-specific prerequisite explanation.
- **Variants and visual states:** First-use, no-filter-results, no-permission-safe fallback, and module-not-yet-connected placeholder.
- **Loading behaviour:** Empty state never appears while the list is still loading.
- **Disabled behaviour:** Primary action explains when a prerequisite such as an active kart is missing.
- **Mobile behaviour:** Fits above the bottom navigation and keeps the primary action visible.
- **Accessibility:** Uses textual meaning, descriptive action labels, and decorative visuals marked hidden.

### 18. Loading state

- **Purpose:** Communicate an in-progress operation when content shape is not yet known.
- **Required content:** Plain-language status message or accessible live-region update.
- **Optional content:** Spinner, progress detail, retry delay guidance, or estimated context when accurate.
- **Variants and visual states:** Route navigation, form submission, recalculation, retrying, and blocking-setup variants.
- **Loading behaviour:** Prevent duplicate submits only for the affected action; unrelated navigation remains available where safe.
- **Disabled behaviour:** A control disabled by loading keeps its label and exposes busy state.
- **Mobile behaviour:** Does not cover the bottom navigation or create a layout jump.
- **Accessibility:** Uses `aria-busy` and a concise status announcement without repeating excessively.

### 19. Skeleton state

- **Purpose:** Reserve layout while a known content shape loads.
- **Required content:** Shapes matching final content hierarchy, not generic decorative bars.
- **Optional content:** Labelled skeleton group for cards, table rows, or detail panels.
- **Variants and visual states:** Metric, card, timeline, table-row, form, and dashboard-section skeletons.
- **Loading behaviour:** Replaces only the loading region and transitions to content without flashing.
- **Disabled behaviour:** Not applicable.
- **Mobile behaviour:** Mirrors card/list layout instead of desktop table geometry.
- **Accessibility:** Hidden from assistive technology when a separate loading message is available.

### 20. Inline validation error

- **Purpose:** Explain why a single field cannot be saved and how to correct it.
- **Required content:** Field-associated error text.
- **Optional content:** Character count, format example, or affected-unit reminder.
- **Variants and visual states:** Error, warning-before-submit, and resolved-after-correction.
- **Loading behaviour:** Existing errors remain readable while a subsequent submit is processing.
- **Disabled behaviour:** A disabled field explains why it cannot be changed rather than showing validation error.
- **Mobile behaviour:** Appears directly below the field without causing horizontal overflow.
- **Accessibility:** Connect with `aria-describedby`, set `aria-invalid` when appropriate, and move focus to the first invalid field after failed submit.

### 21. General error banner

- **Purpose:** Report a page, section, transaction, or network failure that is not tied to one field.
- **Required content:** Clear summary, safe next action, and optional retry control.
- **Optional content:** Technical reference ID for support, affected section name, and dismiss action for non-blocking errors.
- **Variants and visual states:** Blocking error, inline section error, warning, offline/retry, and authorization-safe denial.
- **Loading behaviour:** Retry enters a local loading state and does not erase the original error until it succeeds.
- **Disabled behaviour:** Retry is disabled only during retry, with status text.
- **Mobile behaviour:** Stacks message and action; avoids a full-screen alert for recoverable errors.
- **Accessibility:** Uses an appropriate alert or live region, has a descriptive heading, and does not expose sensitive record details.

### 22. Success toast

- **Purpose:** Confirm a committed action without interrupting the next task.
- **Required content:** Outcome text naming the record or action.
- **Optional content:** Undo only when a reversible, implemented workflow exists; link to detail; short recalculation note.
- **Variants and visual states:** Success, informational, warning-follow-up, and preview-only variants.
- **Loading behaviour:** A toast appears only after successful commit, never when a request is merely sent.
- **Disabled behaviour:** Not applicable.
- **Mobile behaviour:** Appears above the bottom navigation and respects safe-area spacing.
- **Accessibility:** Uses the existing persisted Flux toast group with concise live announcement and an accessible dismiss control.

### 23. Confirmation modal

- **Purpose:** Require deliberate confirmation before archive, delete, remove, or maintenance-completion actions.
- **Required content:** Explicit title, affected-record name, consequence, cancel action, and confirmed action.
- **Optional content:** Required acknowledgement phrase only for high-risk future actions, related-history summary, and link to policy detail.
- **Variants and visual states:** Destructive, maintenance completion, ordinary confirmation, loading, and failure-to-confirm variants.
- **Loading behaviour:** Locks the confirm action after activation; allows cancel only when cancellation is still safe.
- **Disabled behaviour:** Confirm button is disabled until required acknowledgement is complete, with visible reason.
- **Mobile behaviour:** Uses a bottom sheet or centered modal with scrollable body and fixed action footer above safe area.
- **Accessibility:** Traps focus, focuses the dialog title or first safe control, restores focus on close, supports Escape when safe, and uses `role="dialog"`.

### 24. Form field

- **Purpose:** Collect one labelled piece of input using Flux field, label, input, error, and help primitives.
- **Required content:** Visible label, input, required marker when applicable, and inline error area.
- **Optional content:** Help text, unit suffix, placeholder example, description, and character count.
- **Variants and visual states:** Default, focused, filled, required, invalid, read-only, disabled, and loading.
- **Loading behaviour:** Field remains stable; a saving form disables only the controls necessary to prevent duplicate writes.
- **Disabled behaviour:** Shows reason in helper text where it is not obvious.
- **Mobile behaviour:** Full-width, minimum 44px control height, appropriate input mode, and visible unit labels.
- **Accessibility:** Label is programmatically associated; required state and error description are exposed without relying on colour.

### 25. Select field

- **Purpose:** Choose a bounded record or predefined value such as kart, component category, status, currency, or timezone.
- **Required content:** Visible label, current selection or placeholder, and option list.
- **Optional content:** Search, grouped options, description, clear action, and record metadata.
- **Variants and visual states:** Default, selected, invalid, loading-options, empty-options, read-only, and disabled.
- **Loading behaviour:** Shows Loading options and keeps a selected value visible when possible.
- **Disabled behaviour:** Names the missing prerequisite, such as Create a kart first.
- **Mobile behaviour:** Uses an accessible Flux select or modal list large enough for touch; no tiny native-looking option row.
- **Accessibility:** Keyboard operable, labelled, exposes expanded/selected state, and does not trap focus unexpectedly.

### 26. Date and time field

- **Purpose:** Collect a date or timestamp with a clear local timezone context.
- **Required content:** Visible label, date input, time input when the workflow requires it, and format helper.
- **Optional content:** Current-time default, timezone label, quick Today/Now action, and date-only variant.
- **Variants and visual states:** Date-only, date-time, invalid, defaulted, read-only, loading, and disabled.
- **Loading behaviour:** Keep entered local values stable during validation and submit.
- **Disabled behaviour:** Explain why time cannot be changed, such as inherited historical record.
- **Mobile behaviour:** Use native-appropriate date/time keyboards with a textual format fallback and no side-by-side overflow at 320px.
- **Accessibility:** Labels name date and time separately, helper text includes the format/timezone, and errors identify the affected part.

### 27. Duration input

- **Purpose:** Collect a human-friendly duration while preserving integer-minute domain storage.
- **Required content:** Hours and minutes controls, label, and computed total-minute helper.
- **Optional content:** Quick durations, session-duration default, and validation example.
- **Variants and visual states:** Compact inline, stacked mobile, invalid, read-only, and disabled.
- **Loading behaviour:** Retains computed display while the form saves.
- **Disabled behaviour:** Gives a reason if duration is derived or locked.
- **Mobile behaviour:** Uses two clearly labelled number inputs or a compact segmented control; never asks for decimal hours.
- **Accessibility:** Each control has its own label, total is announced as supplemental information, and validation names hours or minutes precisely.

### 28. Money input

- **Purpose:** Collect an expense amount in a familiar currency format while persisting integer cents.
- **Required content:** Visible amount label, currency indicator, input, and stored-value/help explanation.
- **Optional content:** Suggested amount, category context, and linked-record cost context.
- **Variants and visual states:** Default, invalid, currency-changed, read-only, loading, and disabled.
- **Loading behaviour:** Preserve typed formatting and do not submit a partial parse.
- **Disabled behaviour:** Explain why a linked expense amount cannot be changed.
- **Mobile behaviour:** Uses decimal keypad where supported, shows currency code/symbol in text, and does not use tiny suffixes.
- **Accessibility:** Announces currency and amount together; errors state the accepted format. Persistence descriptions and code never use floating-point currency values.

### 29. Filter controls

- **Purpose:** Narrow operational lists by useful, workspace-scoped criteria.
- **Required content:** Clear active-filter summary, at least one filter control, and Clear filters action when filters are active.
- **Optional content:** Search text, date range, kart, component, category, status, archive state, and saved current-query display.
- **Variants and visual states:** Inline desktop toolbar, mobile filter sheet, active, loading-results, no-results, and invalid-range states.
- **Loading behaviour:** Results can update locally while controls remain usable; show result updating state.
- **Disabled behaviour:** Filters without eligible records explain why rather than rendering empty controls.
- **Mobile behaviour:** Collapse into an accessible sheet/disclosure with Apply and Clear actions; preserve selected filters in readable summary.
- **Accessibility:** Each filter is labelled, filter count is announced, and applying/clearing does not unexpectedly lose keyboard focus.

### 30. Tabs

- **Purpose:** Switch related views without changing the user's object context.
- **Required content:** Tab labels and one active panel.
- **Optional content:** Status count, short badge, and contextual action within a panel.
- **Variants and visual states:** Standard underline, contained, active, inactive, disabled, loading, and count-bearing.
- **Loading behaviour:** Keep active tab visible while its panel loads; use panel skeletons.
- **Disabled behaviour:** Disabled tabs identify their prerequisite; do not hide a required history category.
- **Mobile behaviour:** Use horizontally wrapping or vertically stacked tabs; avoid a clipped horizontally scrolling tab strip at 320px.
- **Accessibility:** Follow tablist, tab, and tabpanel semantics with arrow-key navigation where appropriate.

### 31. Timeline item

- **Purpose:** Present auditable chronological activity for installations, track sessions, maintenance records, and expenses.
- **Required content:** Date/time, event type, title, concise detail, source-record link, and status where relevant.
- **Optional content:** Actor, provider, cost, duration, related kart/component, and archival marker.
- **Variants and visual states:** Installation, removal, session, maintenance completion, expense, archived/voided, loading, and no-history.
- **Loading behaviour:** Uses chronological skeleton items with stable spacing.
- **Disabled behaviour:** Historical links to archived items remain viewable but unavailable actions are explained.
- **Mobile behaviour:** One vertical line, clear event icon, and stacked metadata; no dense side-by-side table.
- **Accessibility:** Chronological order is conveyed in text; decorative line/icons do not carry unspoken meaning.

### 32. Responsive data list

- **Purpose:** Replace a wide operational table on narrow viewports without removing essential detail.
- **Required content:** Primary record label, key values, status, and explicit row/detail action.
- **Optional content:** Secondary metadata, inline quick action, selection control, and expandable detail.
- **Variants and visual states:** Table on desktop, card/list on mobile, selected, archived, loading, and empty.
- **Loading behaviour:** Uses list-shaped skeletons and retains filter context.
- **Disabled behaviour:** Disabled row actions remain labelled and explain why.
- **Mobile behaviour:** Each item uses 16px internal padding, visible labels for numerical values, and at least 44px action targets.
- **Accessibility:** Desktop table uses headers; mobile list uses semantic headings/labels so relationships remain understandable.

### 33. Duration display

- **Purpose:** Render stored integer minutes consistently and scanably.
- **Required content:** Formatted duration and explicit unit context.
- **Optional content:** Raw minutes in tooltip, comparison period, or compact abbreviated variant.
- **Variants and visual states:** Standard `1 h 24 m`, compact `1h 24m`, total-minutes, zero, unavailable, and preview.
- **Loading behaviour:** Uses a width-stable numeric skeleton.
- **Disabled behaviour:** Not applicable.
- **Mobile behaviour:** Uses compact form only when the full form would wrap; never drops the unit.
- **Accessibility:** Accessible text expands abbreviations where necessary and uses tabular numerals.

### 34. Money display

- **Purpose:** Render a stored integer-cent amount in the selected workspace currency.
- **Required content:** Formatted amount and currency context.
- **Optional content:** Period label, tax/linked-record context when relevant, raw-cent developer-only diagnostic outside production UI, and unavailable label.
- **Variants and visual states:** Positive expense, zero, unavailable, preview, and selected-summary variants.
- **Loading behaviour:** Uses a tabular-numeric skeleton with stable width.
- **Disabled behaviour:** Not applicable.
- **Mobile behaviour:** Avoids wrapping the symbol/code away from the value and uses readable tabular numerals.
- **Accessibility:** Announces currency and amount together. A zero or unavailable value is never represented by colour alone.

### 35. Lap-time display

- **Purpose:** Render stored integer milliseconds as a readable lap time.
- **Required content:** Formatted `m:ss.mmm` or `ss.mmm` value and best-lap label where needed.
- **Optional content:** Delta only when a valid comparison exists, session context, and unavailable label.
- **Variants and visual states:** Standard, best lap, unavailable, preview, and compact list variants.
- **Loading behaviour:** Uses a fixed-width tabular-numeric skeleton.
- **Disabled behaviour:** Not applicable.
- **Mobile behaviour:** Retains milliseconds and label; do not compress to an ambiguous decimal.
- **Accessibility:** Reads a descriptive time phrase in accessible text and never uses colour alone to identify the best lap.

## Navigation

### Authenticated desktop navigation

The desktop sidebar uses the following primary destinations. Existing routes are retained where present; all other names are proposed expectations for the future PitMetric route layer.

| Label | Route expectation | URL expectation | Active state |
| --- | --- | --- | --- |
| Dashboard | `dashboard` (current) | `/dashboard` | Exact route. |
| Garage | `garage.index` (proposed) | `/garage` | Any `garage.*` route. |
| Components | `components.index` (proposed) | `/components` | Any `components.*` route. |
| Sessions | `sessions.index` (proposed) | `/sessions` | Any `sessions.*` route. |
| Maintenance | `maintenance.index` (proposed) | `/maintenance` | Any `maintenance.*` route. |
| Expenses | `expenses.index` (proposed) | `/expenses` | Any `expenses.*` route. |
| Settings | `profile.edit` (current entry point) | `/settings/profile` | `profile.edit`, `appearance.edit`, `security.edit`, and future `settings.*` routes. |

Use named routes and `wire:navigate` for internal links. Route state drives the selected treatment through `request()->routeIs(...)`; selected state includes accent edge, surface change, stronger text, icon treatment, and `aria-current`, not colour alone.

### Mobile bottom navigation and More

| Mobile label | Destination | Behaviour |
| --- | --- | --- |
| Home | `dashboard` | Active on Dashboard only. |
| Garage | `garage.index` | Active across `garage.*`. |
| Register Session | `sessions.create` | Raised primary action; if no active kart exists, show an explained prerequisite path. |
| Maintenance | `maintenance.index` | Active across `maintenance.*`. |
| More | Focus-managed sheet or modal navigation | Active when any More destination is current. |

More provides Components, Expenses, Settings, Profile, and Logout. Components and Expenses use `wire:navigate`; Settings and Profile use their named routes; Logout remains the existing CSRF POST form to `logout`. More never becomes an unlabelled icon-only overflow.

## Public and authentication pages

Authentication preserves Fortify's current registration, login, password reset, verification, throttling, and password confirmation behaviour. Do not add social login, two-factor authentication, or passkeys.

### Public landing page

- **Route, layout, and title:** Current `home` at `/`; public guest layout based on the existing welcome/auth shell; title: `PitMetric | Kart maintenance and cost clarity`.
- **Purpose and supporting copy:** Explain the value proposition in one concise statement: know what to service, when to service it, and what every hour on track costs.
- **Fields:** None.
- **Primary action:** Create account.
- **Secondary actions:** Log in and, when authenticated, Go to Dashboard.
- **Validation and loading:** No form validation; navigation uses normal route loading feedback.
- **Success and error behaviour:** Successful action leads to Fortify registration/login; failed navigation shows the shared error treatment.
- **Mobile layout:** One-column hero, clear action pair, restrained checkered accent only, no dashboard-like fake data.
- **Accessibility:** One `h1`, descriptive CTA labels, sufficient contrast, and no autoplay media or decorative assets conveying information.

### Login

- **Route, layout, and title:** Current Fortify `login` at `/login`; `layouts::auth`; title: `Log in`.
- **Purpose and supporting copy:** Return the owner-driver to their personal workspace: `Enter your email and password to continue.`
- **Fields:** Email, password, and Remember me.
- **Primary action:** Log in.
- **Secondary actions:** Forgot password and Create account.
- **Validation and loading:** Preserve Fortify validation and throttling; disable only the submit action during POST and preserve safe email input.
- **Success and error behaviour:** Redirect to intended verified path or verification notice; invalid credentials use safe generic error language.
- **Mobile layout:** Existing narrow auth card, 16px outer padding, full-width primary button.
- **Accessibility:** Visible labels, password reveal control with accessible name, inline error association, and logical tab order.

### Registration

- **Route, layout, and title:** Current Fortify `register` at `/register`; `layouts::auth`; title: `Create your PitMetric account`.
- **Purpose and supporting copy:** Set expectations: email verification is required before PitMetric workspace data is available.
- **Fields:** Name, email, password, password confirmation.
- **Primary action:** Create account.
- **Secondary actions:** Log in.
- **Validation and loading:** Preserve Fortify password and unique-email rules; field errors appear inline; prevent duplicate POST.
- **Success and error behaviour:** Successful registration directs to the verification notice; failed submission retains non-sensitive input and reports errors.
- **Mobile layout:** Single column, explicit password guidance, no side-by-side fields at 320px.
- **Accessibility:** Required indicators are textual/programmatic, errors are associated with inputs, and the heading explains the form purpose.

### Forgot password

- **Route, layout, and title:** Current Fortify `password.request` at `/forgot-password`; `layouts::auth`; title: `Reset your password`.
- **Purpose and supporting copy:** Request a password reset email without revealing whether an address has an account.
- **Fields:** Email.
- **Primary action:** Email reset link.
- **Secondary actions:** Back to login and Create account.
- **Validation and loading:** Preserve Fortify throttling and email validation; submit enters loading once.
- **Success and error behaviour:** Show generic session-status confirmation after a successful request; errors remain safe and inline.
- **Mobile layout:** Narrow one-field card with a full-width primary action.
- **Accessibility:** Announce status message, preserve label/description, and keep Back to login keyboard reachable.

### Reset password

- **Route, layout, and title:** Current Fortify `password.reset` at `/reset-password/{token}`; `layouts::auth`; title: `Choose a new password`.
- **Purpose and supporting copy:** Complete a valid password reset securely.
- **Fields:** Email, reset token, password, password confirmation.
- **Primary action:** Reset password.
- **Secondary actions:** Back to login.
- **Validation and loading:** Preserve token and password validation; show field-level errors without exposing token details.
- **Success and error behaviour:** On success, redirect to login with the existing status; invalid or expired links offer a safe new-reset path.
- **Mobile layout:** Single-column form and readable password requirements.
- **Accessibility:** Labels, errors, password reveal controls, and focus recovery follow the shared auth rules.

### Email verification

- **Route, layout, and title:** Current Fortify `verification.notice` at `/email/verify`; `layouts::auth` or authenticated minimal shell; title: `Verify your email`.
- **Purpose and supporting copy:** Explain that verification unlocks the personal workspace and verified application routes.
- **Fields:** None for signed-link verification.
- **Primary action:** Resend verification email.
- **Secondary actions:** Update profile email, Log out, and return after opening the link.
- **Validation and loading:** Preserve signed URL and resend throttle behaviour; resend button reports loading and cooldown state.
- **Success and error behaviour:** Successful signed link verifies the account and continues to workspace/onboarding flow; expired or invalid links offer a safe resend path.
- **Mobile layout:** One-column notice, clear resend action, no hidden next step.
- **Accessibility:** Status is announced, resend cooldown is written in text, and no colour alone distinguishes verification state.

### Password confirmation

- **Route, layout, and title:** Current Fortify `password.confirm` at `/user/confirm-password`; `layouts::auth`; title: `Confirm your password`.
- **Purpose and supporting copy:** Reconfirm identity before sensitive security actions.
- **Fields:** Current password.
- **Primary action:** Confirm password.
- **Secondary actions:** Cancel/back when the original destination provides a safe path.
- **Validation and loading:** Preserve Fortify validation and throttling; prevent duplicate confirmation POST.
- **Success and error behaviour:** Return to the intended sensitive page on success; invalid password shows a safe field error.
- **Mobile layout:** Narrow full-width form with no decorative distraction.
- **Accessibility:** Clear reason for confirmation, labelled password input, and focus on error when confirmation fails.

## Onboarding

### Route and redirect rules

- **Proposed route:** `onboarding` at `/onboarding`, rendered by `pages::onboarding` in `resources/views/pages/⚡onboarding.blade.php`.
- **Gate:** Authenticated and verified users only. It is not a team, invitation, or role-configuration flow.
- **Redirect in:** A verified user with incomplete onboarding who requests a future domain page is sent to onboarding. Profile remains available so an unverified user can correct email and resend verification.
- **Redirect out:** A completed user requesting onboarding goes to Dashboard. A logged-out user goes to login; an unverified user goes to verification notice.
- **Returning behaviour:** Resume the earliest incomplete step. A user who explicitly deferred kart/component setup sees the Dashboard guided empty state, not a forced loop.

### Wizard structure

1. **Welcome and preferences.** Explain the product loop in concise text. Offer workspace name, currency, timezone, duration display, date format, and existing theme preference. Defaults are present; preferences can be skipped and revisited in Settings.
2. **First kart.** Use the Kart form with name required and optional operational metadata. It may be deferred through an explicit `Do this later` action with a short consequence message.
3. **First component.** Use the Component form after a kart exists; guide the user to create a component and optionally continue to installation. It may be deferred explicitly.
4. **Finish.** Summarize created records and route to Dashboard or the next most useful task, normally Create Maintenance Schedule or Register Session.

### Progress, skip, completion, and mobile rules

- The progress indicator names the current step and total steps in text, e.g. `Step 2 of 4: First kart`; it is not only a coloured bar.
- Preferences are always skippable. Kart and component creation are deferrable, never silently created, and never blocked by enterprise-style workspace setup.
- Completing the wizard records onboarding completion only after the user has either completed or explicitly deferred the optional operational steps.
- A deferred user is not shown fake karts, components, schedules, sessions, maintenance records, or expenses. Dashboard empty states provide the next action.
- At 320px, the wizard is one column with a stable bottom action area above the mobile navigation. The primary Next or Create action is prominent; Back remains available; save states prevent duplicate creation.

## Dashboard

### Final domain-backed dashboard

The final dashboard is the verified workspace's operational home. Data priority is:

1. overdue maintenance and the safest next action;
2. **Register Session** and other primary actions;
3. active-kart context and high-value metrics;
4. upcoming maintenance;
5. recent sessions, maintenance records, expenses, and combined activity.

| Section | Required content and behaviour |
| --- | --- |
| Greeting and date | User name, current local date, and concise operational context. Do not invent weather or telemetry. |
| Primary actions | Register Session, Create Kart when none exists, Create Component when appropriate, and Complete Maintenance when an authorized schedule needs action. |
| Overdue maintenance | Highest-priority status panels with component, schedule, total usage, usage since service, interval, and Complete Maintenance action. |
| Upcoming maintenance | Compact panels ordered by domain-calculated urgency, including text state and remaining/used minutes. |
| Active karts | Kart cards with maintenance attention and a route to each kart detail. |
| Total recorded driving time | Workspace-scoped active track-session duration displayed through Duration Display. |
| Monthly sessions | Current local-month count with a clear period label. |
| Monthly expenses | Workspace-scoped active expense total for the current local month. |
| Total expenses | Workspace-scoped active expense total with a period/context label. |
| Cost per driving hour | Explicit calculation context. With zero included duration, show `Not available - record a track session to calculate cost per hour.` |
| Kart summary | Each active kart's concise operational state; no fabricated performance ranking. |
| Recent sessions | Date/time, kart, circuit, duration, and component-usage summary; link to detail. |
| Recent maintenance | Date, component, maintenance schedule, performer when present, and linked expense state. |
| Recent expenses | Date, category, formatted amount, linked context, and link to detail. |
| Combined recent activity | Chronological Timeline Items across session, maintenance, installation, and expense changes. |

### Empty, loading, and mobile behaviour

- A workspace with no kart receives the Create Kart empty state first. A workspace with a kart but no component receives Create Component guidance. A workspace with setup complete but no session receives **Register Session** guidance.
- Dashboard cards use section skeletons while loading. A failed section renders a local retryable error banner; it does not make another section disappear.
- At 320px: greeting and primary action first; overdue maintenance second; active-kart and core metrics third; upcoming and activity last. Do not place a large chart before actionable maintenance.
- The dashboard never calculates a rate from zero duration, displays preview data as real, or presents an unverified user with domain data.

### Temporary preview dashboard

Before domain models and queries exist, the current Blade dashboard may be restyled as a layout preview. Every non-real value must carry a visible `Preview data` label and a short note such as `This layout preview is not connected to your workspace.`

Preview cards demonstrate hierarchy, responsiveness, loading skeletons, and empty-state placement only. They do not contain user-like names, session history, cost totals, maintenance urgency, or any value that could be mistaken for calculated data. The preview is removed or replaced section-by-section as real data becomes available.

## Garage

### Garage index

- **Route expectation:** `garage.index` at `/garage`.
- **Header and filters:** Title Garage, Create Kart primary action, active/archived filter, search by kart name, and optional manufacturer/model filter only when useful.
- **Presentation:** Kart cards below 768px; accessible table or denser card grid on larger screens. Active appears first; archived is available only through explicit filter/tab.
- **Empty state:** `No karts yet` with Create Kart. Archive filter has a distinct `No archived karts` state.
- **Validation and permissions:** All filters are workspace-scoped. Foreign/unknown karts are never hinted in results.

### Create and edit kart

- **Route expectations:** `garage.create` at `/garage/create` and `garage.edit` at `/garage/{kart}/edit`.
- **Form:** Name required; manufacturer, model, year, chassis number, race number, purchase date, purchase price, and notes optional. Money uses Money Input and persists cents.
- **Behaviour:** Show inline validation, save loading state, persisted success toast, and return to kart detail. Edit preserves historical data.
- **Mobile:** Single-column sections: identity, optional purchase details, notes, save. Archive is never grouped with Save.

### Kart detail and archive

- **Route expectation:** `garage.show` at `/garage/{kart}`.
- **Required tabs:** Overview, Components, Sessions, Maintenance, and Expenses.
- **Overview:** Identity, active/archived state, current maintenance attention, summary metrics, and clear actions.
- **Components:** Current component installations and history. Before the module exists, show a truthful `Component installations will appear here` placeholder with an appropriate next action rather than an empty fake table.
- **Sessions, Maintenance, Expenses:** Show real responsive lists when available. Before implementation, use labelled placeholders that neither claim zero data nor imply unavailable authorization.
- **Archive:** The UI label may say Archive Kart. It opens a destructive confirmation explaining that historical records remain visible and that the kart cannot receive new sessions or installations. It does not promise physical deletion.

## Components

### Components index and cards

- **Route expectation:** `components.index` at `/components`.
- **Filters:** Search, category, installation state, maintenance status, and archive/retired state when records exist.
- **Presentation:** Component cards on mobile; responsive data list or table on desktop. Each item shows name, category, total usage, current installation, and maintenance status.
- **Empty state:** `No components yet` with Create Component and explanation that components can be installed on a kart and attributed to track sessions.

### Categories and forms

- **Route expectations:** `components.create`, `components.show`, and `components.edit`.
- **Category rule:** Present only categories supported by the current domain vocabulary: Engine, Tyres, Chain, Brakes, and Custom/Other. Do not add inventory, stock level, supplier, purchase-order, or marketplace concepts.
- **Create/edit fields:** Name and category required. Brand, model, serial number, purchase date, purchase price, initial usage, and notes optional. Initial usage uses whole minutes; purchase price uses Money Input.
- **Validation and mobile:** Keep units beside inputs, preserve safe values on error, use a compact category selection, and keep Save independent from retire/archive action.

### Component detail

- **Route expectation:** `components.show` at `/components/{component}`.
- **Required content:** Current installation, total usage, maintenance status, installation history, maintenance schedules, track-session history, maintenance-record history, and associated expense context.
- **Current installation:** Show kart, installed date, and Remove action when active; show `Not currently installed` with Install action when eligible.
- **Installation history:** Use Timeline Items ordered by date. The Install action selects only current-workspace active karts; Remove action requires confirmation and asks for removal date.
- **Retire action:** Use a destructive confirmation to archive/retire a component while preserving its history. It does not become an inventory workflow.
- **Placeholder behaviour:** Each unimplemented submodule gets a labelled, non-fabricated placeholder and a contextual action only if that action is available.

## Maintenance schedules

### Schedule creation, editing, activation, and deactivation

- **Route expectations:** `maintenance.schedules.create` and `maintenance.schedules.edit`, normally accessed from Component detail.
- **Required fields:** Component, maintenance schedule name/type, interval, and an active state.
- **Optional fields:** Warning threshold, notes, and future interval adjustment.
- **Interval input:** Use Duration Input, labelled in hours/minutes while the domain persists integer minutes. Do not accept decimal hours.
- **Warning threshold:** Label the chosen policy clearly, for example `Warn when this many minutes remain`. The UI passes the value to the approved domain calculation and never computes its own alternative threshold.
- **Activation/deactivation:** Deactivate/archive a schedule through a confirmation dialog. History remains visible. Do not use a destructive physical-delete promise.

### State and progress presentation

| State | Required interface treatment | Boundary behaviour |
| --- | --- | --- |
| Regular | Text badge, neutral/success treatment, current and remaining usage, no urgent action. | Usage remains before the domain-calculated warning boundary. |
| Upcoming | Text badge, warning icon/treatment, remaining usage, and View/Complete action. | Reached the configured warning boundary but remains before due usage. |
| Due | Text badge, high-attention treatment, total and baseline context, Complete Maintenance action. | Exactly at the domain-calculated due boundary. |
| Overdue | Text badge, danger icon/treatment, explicit overdue explanation, and Complete Maintenance action. | Beyond the due boundary. |
| No schedule / unavailable | Plain explanation and Create Schedule or Retry action. | Never display as Regular merely because no calculation exists. |

The UI displays total component usage, service baseline, usage since previous service, interval, and threshold context separately. It never resets, hides, or relabels total usage after maintenance completion.

## Track sessions

### Sessions index and filtering

- **Route expectation:** `sessions.index` at `/sessions`.
- **Filters:** Date range, kart, circuit text, and archive/deleted-from-calculation state only when implemented. Filters always remain workspace-scoped.
- **Presentation:** Responsive data list on mobile; table on desktop when columns fit. Each item presents occurred date/time, kart, circuit, duration, component-count/usage summary, and explicit detail action.
- **Empty state:** `No track sessions yet` with **Register Session**. If no kart exists, route to Create Kart instead.

### Quick session creation

- **Route expectation:** `sessions.create` at `/sessions/create`.
- **Goal:** A prepared owner-driver can record the essential session in less than one minute on a smartphone.
- **Required fields:** Kart, occurred date and time, duration, and circuit name.
- **Component selection:** Components installed on the chosen kart at the selected occurrence time are preselected. Each selected component displays exact `usage_minutes`, defaulting to session duration; the user may deselect a component or adjust its whole-minute use.
- **Optional details:** Lap count, best lap, weather, track condition, temperature, and notes live in a collapsed `Add session details` section that remains accessible without leaving the page.
- **Duration entry:** Use hours/minutes controls with an immediate total-minute summary. The UI never requests decimal hours.
- **Confirmation summary:** Before or immediately after save, show kart, occurrence, circuit, duration, selected components/usage, and a reminder that maintenance status will update after commit.
- **Transactional feedback:** Submit once. During save, prevent double submit. Success shows persisted toast plus component-usage summary and links to session detail/dashboard. Failure says no session or attribution was saved and preserves safe input.

### Session detail, edit, and delete

- **Route expectations:** `sessions.show` at `/sessions/{session}`, `sessions.edit` at `/sessions/{session}/edit`, and a confirmed `sessions.destroy` action.
- **Detail:** Present all session fields, exact component usage attribution, resulting maintenance status links, linked expenses when available, and an audit-friendly timeline.
- **Edit:** Reuses the creation form with a clear `Editing replaces component usage attribution` notice. On save, display updated attribution and recalculated status only after the transaction commits.
- **Delete:** Use a destructive confirmation that names the session, its component usage effect, and audit-retention rule. The UI calls it Delete only if implementation is an auditable archive/void that removes active attribution transactionally; otherwise label it Archive Session.
- **Mobile:** Full-page form, sticky save area above bottom navigation, no hidden required fields, and 48px minimum submit target.

## Maintenance records

### Maintenance workspace

- **Route expectation:** `maintenance.index` at `/maintenance`.
- **Tabs:** Overdue, Upcoming, and History. Tabs retain current workspace context and never use colour as the only differentiator.
- **Overdue and Upcoming:** Maintenance Status Panels ordered by domain-calculated urgency, with Component detail and Complete Maintenance actions.
- **History:** Timeline or responsive data list of maintenance records with date, component, schedule, usage snapshot, performer, notes indicator, and linked expense state.
- **Empty states:** Distinguish no maintenance schedules, no upcoming work, and no completed maintenance records.

### Completion form

- **Route expectation:** `maintenance.records.create` at `/maintenance/schedules/{schedule}/complete`.
- **Required context:** Selected component, selected maintenance schedule, performed date, current total usage, current usage since service, service baseline, and description/type.
- **Optional fields:** Performed by, notes, adjusted next interval, and a cost toggle that creates or links an optional expense.
- **Cost rule:** A displayed cost uses Money Input but persists through the optional linked expense only. The completion summary names the linked expense and prevents duplicate cost entry.
- **Success summary:** State that the maintenance record was created, the service baseline moved to the captured usage snapshot, total component usage did not reset, and the next status was recalculated.
- **Failure behaviour:** Field errors remain inline. Transaction failure clearly says that neither maintenance record nor baseline nor linked expense was saved.
- **Mobile:** Present usage values as labelled cards before the form. Keep the confirmation action separate from Cancel and above safe bottom spacing.

## Expenses

### Expenses index and totals

- **Route expectation:** `expenses.index` at `/expenses`.
- **Header:** Add Expense primary action, active total, current-period total, and cost-per-hour contextual link.
- **Filters:** Date range, category, kart, component, track session, maintenance record, and archive state when relevant.
- **Presentation:** Responsive list/card on mobile and table on desktop. Each row includes date, category, formatted amount, linked context, and edit action.
- **Empty state:** Explain that expenses are optional but needed for meaningful cost analysis. Do not invent a zero cost-per-hour value.

### Create, edit, and delete expense

- **Route expectations:** `expenses.create`, `expenses.edit`, and a confirmed `expenses.destroy` action.
- **Required fields:** Amount, date, category, and description.
- **Optional links:** Kart, component, track session, maintenance record, and notes. Each selector offers only same-workspace records.
- **Category-dependent fields:** Maintenance suggests a maintenance-record link; Parts suggests component/kart; Track Day or Session suggests track-session link; Other exposes no unnecessary link. These are suggestions, not inventory or supplier workflows.
- **Money input and display:** Parse a user-facing amount into integer cents at the boundary, display selected currency consistently, and never describe persistence as floating point.
- **Delete rule:** Use a destructive confirmation. When history exists, the action archives/voids the expense so audit history is retained and current totals recalculate safely.

## Settings

### Information architecture

Settings retains the current starter-kit routes and Livewire SFCs:

| Area | Route | Purpose |
| --- | --- | --- |
| Profile | `profile.edit` at `/settings/profile` | Current name/email update and verification resend; authentication required, verification not required. |
| Security | `security.edit` at `/settings/security` | Current password update; authentication, verification, and password confirmation required. |
| Appearance | `appearance.edit` at `/settings/appearance` | Current Flux light/dark/system preference; authentication and verification required. |
| PitMetric preferences | Proposed `settings.pitmetric.edit` at `/settings/pitmetric` | Personal workspace presentation preferences; authentication and verification required. |

### Profile and security

- Preserve existing profile form, email-change re-verification behaviour, resend notice, and existing security/password-confirmation flow.
- Do not add social identity, team member, role, invitation, two-factor, passkey, payment, or subscription settings.
- Mobile settings navigation stacks rather than becoming a horizontally clipped tab strip.

### Appearance and PitMetric preferences

- Theme preference uses the synchronized existing appearance model: Light, Dark, and System. Dark is the default/recommended PitMetric presentation; all supported choices remain usable and accessible.
- Proposed PitMetric preferences include workspace name, currency, timezone, duration display preference, date format, and theme preference summary/link.
- Duration display changes presentation only; persisted duration remains integer minutes. Currency changes formatting only unless a future approved financial-domain decision says otherwise; persisted money remains integer cents. Date format and timezone alter display/context, not historical source data.
- Preferences form uses visible labels, supported-value selects, reset-to-default action, inline validation, save toast, and loading prevention. It never becomes a multi-workspace switcher.

## Shared states and accessibility

### State rules

- **Empty:** Explain what is absent, why it matters, and the next safe action. Differentiate no records, no filter results, unavailable calculation, and loading failure.
- **Loading:** Use action-specific busy states and content-shaped skeletons. Prevent duplicate writes without disabling unrelated safe navigation.
- **Success:** Use the persisted Flux toast group after committed operations. Name the result and next relevant action.
- **Validation:** Server-side validation is authoritative. Fields keep values where safe, show inline errors, and move focus to the first invalid field after failed submission.
- **General/network/Livewire failure:** Use General Error Banner with retry. Do not expose exceptions, SQL, routes, foreign identifiers, or another workspace's data. A failed transactional workflow must say no partial change was saved.
- **Destructive actions:** Always use Confirmation Modal. Clarify archive/void versus physical deletion, history impact, and recalculation impact.
- **Disabled controls:** Never rely on opacity alone. Keep label, state the reason, and provide a prerequisite action when useful.

### Accessibility rules

- Use semantic landmarks, one visible `h1` per page, logical heading order, labelled form controls, and descriptive links/buttons.
- Minimum interactive target is 44px square; primary mobile actions are at least 48px high.
- All keyboard paths work without pointer hover. Focus order is predictable; focus rings use the shared 2px token; modal focus is managed and restored.
- Screen-reader labels cover icon-only controls, status changes, toast messages, loading states, and filter-result changes. Decorative checkered details and redundant icons are hidden from assistive technology.
- Colour is supplemental only. Every status has text and/or icon, validation errors have text, and charts or trends have textual values.
- Respect `prefers-reduced-motion`; reduce non-essential transitions and eliminate parallax, flashing, or auto-advancing content.
- Test standard text, controls, focus indicators, badges, alerts, and dark/light/system themes against the stated contrast requirements.

## Page-by-page implementation map

All proposed route names below are expectations for future implementation. Existing current routes are explicitly marked. Proposed Livewire pages use SFC aliases and the existing `⚡` filename convention.

| Route name | URL | Authentication | Verification | Onboarding | Proposed Livewire SFC alias / file example | Expected layout | Primary reusable components | Underlying domain dependencies | Phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `home` (current) | `/` | Guest/public | No | No | Blade `welcome`; no SFC | Public guest shell | Page header, primary/secondary buttons | None | 5 |
| `login` (current) | `/login` | Guest | No | No | Fortify Blade `pages::auth.login`; no SFC | `layouts::auth` | Form field, primary button, inline error | Fortify session | 7 |
| `register` (current) | `/register` | Guest | No | No | Fortify Blade `pages::auth.register`; no SFC | `layouts::auth` | Form field, primary button, validation | Fortify registration | 7 |
| `password.request` (current) | `/forgot-password` | Guest | No | No | Fortify Blade `pages::auth.forgot-password`; no SFC | `layouts::auth` | Form field, success/error state | Fortify password reset | 7 |
| `password.reset` (current) | `/reset-password/{token}` | Guest | No | No | Fortify Blade `pages::auth.reset-password`; no SFC | `layouts::auth` | Form field, validation | Fortify password reset | 7 |
| `verification.notice` (current) | `/email/verify` | Yes | No | No | Fortify Blade `pages::auth.verify-email`; no SFC | `layouts::auth` | Info panel, primary button, toast | Fortify email verification | 7 |
| `password.confirm` (current) | `/user/confirm-password` | Yes | No | No | Fortify Blade `pages::auth.confirm-password`; no SFC | `layouts::auth` | Form field, primary button, error | Fortify password confirmation | 7 |
| `onboarding` (proposed) | `/onboarding` | Yes | Yes | Is the workflow | `pages::onboarding` / `resources/views/pages/⚡onboarding.blade.php` | `layouts::app` | Progress indicator, form field, buttons | Workspace, preferences, kart/component readiness | 8 |
| `dashboard` (current placeholder) | `/dashboard` | Yes | Yes | Yes | Current Blade `dashboard`; future `pages::dashboard` / `resources/views/pages/⚡dashboard.blade.php` | `layouts::app` | Metric card, maintenance panel, timeline, empty state | All domain aggregates | 6 then 15 |
| `garage.index` (proposed) | `/garage` | Yes | Yes | Yes | `pages::garage.index` / `resources/views/pages/garage/⚡index.blade.php` | `layouts::app` | Page header, filters, kart card/list, empty state | Workspace, karts | 9 |
| `garage.create` (proposed) | `/garage/create` | Yes | Yes | Yes | `pages::garage.create` / `resources/views/pages/garage/⚡create.blade.php` | `layouts::app` | Form field, money input, buttons | Workspace, karts | 9 |
| `garage.show` (proposed) | `/garage/{kart}` | Yes | Yes | Yes | `pages::garage.show` / `resources/views/pages/garage/⚡show.blade.php` | `layouts::app` | Tabs, kart card, timeline, placeholders | Kart plus related records | 9 |
| `garage.edit` (proposed) | `/garage/{kart}/edit` | Yes | Yes | Yes | `pages::garage.edit` / `resources/views/pages/garage/⚡edit.blade.php` | `layouts::app` | Form field, money input, confirmation modal | Kart | 9 |
| `components.index` (proposed) | `/components` | Yes | Yes | Yes | `pages::components.index` / `resources/views/pages/components/⚡index.blade.php` | `layouts::app` | Filters, component card/list, empty state | Workspace, components | 10 |
| `components.create` (proposed) | `/components/create` | Yes | Yes | Yes | `pages::components.create` / `resources/views/pages/components/⚡create.blade.php` | `layouts::app` | Form field, duration/money input | Components | 10 |
| `components.show` (proposed) | `/components/{component}` | Yes | Yes | Yes | `pages::components.show` / `resources/views/pages/components/⚡show.blade.php` | `layouts::app` | Component card, tabs, timeline, status panel | Component, installations, schedules, history | 10 |
| `components.edit` (proposed) | `/components/{component}/edit` | Yes | Yes | Yes | `pages::components.edit` / `resources/views/pages/components/⚡edit.blade.php` | `layouts::app` | Form field, confirmation modal | Component | 10 |
| `maintenance.index` (proposed) | `/maintenance` | Yes | Yes | Yes | `pages::maintenance.index` / `resources/views/pages/maintenance/⚡index.blade.php` | `layouts::app` | Tabs, status panel, timeline, empty state | Schedules, maintenance records | 11 and 13 |
| `maintenance.schedules.create` (proposed) | `/components/{component}/maintenance-schedules/create` | Yes | Yes | Yes | `pages::maintenance.schedules.create` / `resources/views/pages/maintenance/schedules/⚡create.blade.php` | `layouts::app` | Duration input, form field, progress indicator | Component, schedule | 11 |
| `maintenance.schedules.edit` (proposed) | `/maintenance-schedules/{schedule}/edit` | Yes | Yes | Yes | `pages::maintenance.schedules.edit` / `resources/views/pages/maintenance/schedules/⚡edit.blade.php` | `layouts::app` | Duration input, status panel, confirmation modal | Maintenance schedule | 11 |
| `sessions.index` (proposed) | `/sessions` | Yes | Yes | Yes | `pages::sessions.index` / `resources/views/pages/sessions/⚡index.blade.php` | `layouts::app` | Filters, responsive data list, empty state | Track sessions, karts | 12 |
| `sessions.create` (proposed) | `/sessions/create` | Yes | Yes | Yes | `pages::sessions.create` / `resources/views/pages/sessions/⚡create.blade.php` | `layouts::app` | Date/time, duration, select, confirmation summary | Karts, installations, components | 12 |
| `sessions.show` (proposed) | `/sessions/{session}` | Yes | Yes | Yes | `pages::sessions.show` / `resources/views/pages/sessions/⚡show.blade.php` | `layouts::app` | Timeline, status panel, duration/lap display | Track session, attribution, expenses | 12 |
| `sessions.edit` (proposed) | `/sessions/{session}/edit` | Yes | Yes | Yes | `pages::sessions.edit` / `resources/views/pages/sessions/⚡edit.blade.php` | `layouts::app` | Full session form, confirmation summary | Track session, attribution, installations | 12 |
| `maintenance.records.create` (proposed) | `/maintenance-schedules/{schedule}/complete` | Yes | Yes | Yes | `pages::maintenance.records.create` / `resources/views/pages/maintenance/records/⚡create.blade.php` | `layouts::app` | Status panel, money input, confirmation modal | Schedule, component, maintenance record, optional expense | 13 |
| `expenses.index` (proposed) | `/expenses` | Yes | Yes | Yes | `pages::expenses.index` / `resources/views/pages/expenses/⚡index.blade.php` | `layouts::app` | Filters, money display, responsive data list | Expenses and links | 14 |
| `expenses.create` (proposed) | `/expenses/create` | Yes | Yes | Yes | `pages::expenses.create` / `resources/views/pages/expenses/⚡create.blade.php` | `layouts::app` | Money input, select fields, form field | Expenses, linked records | 14 |
| `expenses.edit` (proposed) | `/expenses/{expense}/edit` | Yes | Yes | Yes | `pages::expenses.edit` / `resources/views/pages/expenses/⚡edit.blade.php` | `layouts::app` | Money input, confirmation modal | Expense | 14 |
| `profile.edit` (current) | `/settings/profile` | Yes | No | No | Current `pages::settings.profile` / `resources/views/pages/settings/⚡profile.blade.php` | `layouts::app` | Form field, validation, toast | User/Fortify verification | 7 |
| `appearance.edit` (current) | `/settings/appearance` | Yes | Yes | No | Current `pages::settings.appearance` / `resources/views/pages/settings/⚡appearance.blade.php` | `layouts::app` | Segmented select, toast | Existing Flux appearance | 7 |
| `security.edit` (current) | `/settings/security` | Yes | Yes + password confirm | No | Current `pages::settings.security` / `resources/views/pages/settings/⚡security.blade.php` | `layouts::app` | Form field, validation, toast | User security | 7 |
| `settings.pitmetric.edit` (proposed) | `/settings/pitmetric` | Yes | Yes | No | `pages::settings.pitmetric` / `resources/views/pages/settings/⚡pitmetric.blade.php` | `layouts::app` | Select fields, duration display, toast | Workspace preferences | 8 |

## Implementation order

1. Define semantic visual tokens in the existing Tailwind CSS 4 CSS-first theme layer, including dark/light/system mappings and contrast checks.
2. Extend the existing `layouts::app` shell into the PitMetric desktop sidebar and topbar structure without replacing its layout convention, persisted toast behaviour, or user menu.
3. Add the mobile bottom navigation and accessible More sheet while preserving the desktop sidebar breakpoint behaviour.
4. Build and document-test the reusable Flux-based components, including states, focus, labels, loading, disabled behaviour, and responsive variants.
5. Add placeholder routes and pages only after their domain dependencies are planned; ensure every placeholder is truthful and workspace-safe.
6. Restyle the current dashboard as a clearly labelled preview layout with no fabricated workspace data.
7. Restyle existing Fortify authentication pages and current Profile, Security, and Appearance settings without changing their routes or backend behaviour.
8. Implement onboarding UI, redirect rules, preference defaults, defer actions, and returning-user behaviour.
9. Implement Garage index, kart forms, kart detail tabs, archive confirmation, and truthful module placeholders.
10. Implement Components index/detail/forms, component installation/removal interfaces, and retirement confirmation.
11. Implement maintenance-schedule forms, state panels, threshold presentation, and regular/upcoming/due/overdue interface states.
12. Implement mobile-first track-session creation, exact component attribution, confirmation summary, session detail, edit, and deletion confirmation.
13. Implement maintenance workspace tabs, maintenance-completion form, baseline/usage explanation, optional linked expense, and history.
14. Implement expenses list/forms, contextual links, filtering, archive/void confirmation, and integer-cent presentation.
15. Replace the preview dashboard section-by-section with real workspace-scoped aggregates, safe zero-duration cost handling, and combined recent activity.
16. Perform final accessibility and responsive review at 320px, larger phones, tablet, desktop, and large desktop; add Pest feature coverage and browser/manual checks for critical Livewire interactions.
