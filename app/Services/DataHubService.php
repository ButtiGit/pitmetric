<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;

class DataHubService
{
    public const FORMAT = 'pitmetric-workspace-backup';

    public const VERSION = 1;

    public function __construct(
        private readonly WorkspaceBackupExporter $exporter,
        private readonly WorkspaceBackupImporter $importer,
    ) {}

    /** @return array<string, mixed> */
    public function export(Workspace $workspace): array
    {
        return $this->exporter->export($workspace);
    }

    /**
     * @param  array<string, mixed>  $backup
     * @return array<string, int>
     */
    public function import(Workspace $workspace, User $user, array $backup): array
    {
        return $this->importer->import($workspace, $user, $backup);
    }
}
