<?php

use App\Services\CsvTelemetryImporter;
use App\Services\DataHubService;
use App\Services\MaintenancePageService;
use App\Services\MaintenanceWorkOrderService;
use App\Services\RecordSessionService;
use App\Services\SessionPageService;
use App\Services\TelemetryImportService;
use App\Services\VboTelemetryImporter;
use App\Services\WorkspaceBackupExporter;
use App\Services\WorkspaceBackupImporter;

it('keeps the large workflows behind smaller focused services', function () {
    expect(app(DataHubService::class))->toBeInstanceOf(DataHubService::class)
        ->and(app(WorkspaceBackupExporter::class))->toBeInstanceOf(WorkspaceBackupExporter::class)
        ->and(app(WorkspaceBackupImporter::class))->toBeInstanceOf(WorkspaceBackupImporter::class)
        ->and(app(TelemetryImportService::class))->toBeInstanceOf(TelemetryImportService::class)
        ->and(app(CsvTelemetryImporter::class))->toBeInstanceOf(CsvTelemetryImporter::class)
        ->and(app(VboTelemetryImporter::class))->toBeInstanceOf(VboTelemetryImporter::class)
        ->and(app(SessionPageService::class))->toBeInstanceOf(SessionPageService::class)
        ->and(app(RecordSessionService::class))->toBeInstanceOf(RecordSessionService::class)
        ->and(app(MaintenancePageService::class))->toBeInstanceOf(MaintenancePageService::class)
        ->and(app(MaintenanceWorkOrderService::class))->toBeInstanceOf(MaintenanceWorkOrderService::class);
});
