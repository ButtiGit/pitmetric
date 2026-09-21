# Step 3 — Progressive monster refactor

This pass keeps routes and external service APIs stable while splitting large files by responsibility.

## Backend boundaries

- `SessionController` delegates page data to `SessionPageService` and recording to `RecordSessionService`.
- `MaintenanceController` delegates page data, value conversion and work-order transitions to focused services.
- `RaceEventOperationsController` delegates entry, schedule, task and note workflows to dedicated services.
- `DataHubService` remains the public facade while export and import live in `WorkspaceBackupExporter` and `WorkspaceBackupImporter`.
- `TelemetryImportService` remains the file/transaction coordinator while CSV/VBO parsing, value normalization and timing aggregation are separated.

## Next view split in this branch

- `resources/views/events/show.blade.php`
- `resources/views/maintenance/index.blade.php`

The goal is composition through Blade partials without changing routes, forms, permissions or workflow semantics.
