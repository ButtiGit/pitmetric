# Step 3 — Progressive monster refactor

This pass keeps routes and external service APIs stable while splitting large files by responsibility.

## Backend boundaries

- `SessionController` delegates page data to `SessionPageService` and recording to `RecordSessionService`.
- `MaintenanceController` delegates page data, value conversion and work-order transitions to focused services.
- `RaceEventOperationsController` delegates entry, schedule, task and note workflows to dedicated services.
- `DataHubService` remains the public facade while export and import live in `WorkspaceBackupExporter` and `WorkspaceBackupImporter`; the shared table set lives in `WorkspaceBackupTableRegistry`.
- `TelemetryImportService` remains the file/transaction coordinator while CSV/VBO parsing, value normalization and timing aggregation are separated into dedicated services.

## Blade boundaries

- `resources/views/events/show.blade.php` is now a composition shell over:
  - `events/partials/overview.blade.php`
  - `events/partials/trackside.blade.php`
  - `events/partials/operations-sidebar.blade.php`
  - `events/partials/mobile-actions.blade.php`
- `resources/views/maintenance/index.blade.php` is now a composition shell over:
  - `maintenance/partials/overview.blade.php`
  - `maintenance/partials/workboard.blade.php`
  - `maintenance/partials/health-history.blade.php`

## Guardrails

The refactor intentionally does not change routes, form contracts, authorization gates, public service APIs or core workflow semantics. Existing feature coverage plus `RefactorBoundaryTest` remain the regression guard while later passes can split the newly isolated modules further if they grow again.
