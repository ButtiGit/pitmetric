<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TelemetryImportService
{
    public function __construct(
        private readonly CsvTelemetryImporter $csvImporter,
        private readonly VboTelemetryImporter $vboImporter,
    ) {}

    public function import(UploadedFile $file, Session $session, ?Driver $driver, User $user, string $vendor): TelemetryImport
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $format = $extension === 'vbo' ? 'vbo' : 'csv';

        if (! in_array($extension, ['csv', 'txt', 'vbo'], true)) {
            throw new RuntimeException('Unsupported telemetry format. Use CSV or VBO.');
        }

        $storagePath = $file->storeAs(
            'telemetry/'.$session->workspace_id,
            $file->hashName(),
            'local',
        );

        if ($storagePath === false) {
            throw new RuntimeException('Unable to store the telemetry source file.');
        }

        $sha256 = hash_file('sha256', $file->getRealPath());

        if ($sha256 === false) {
            Storage::disk('local')->delete($storagePath);
            throw new RuntimeException('Unable to fingerprint the telemetry source file.');
        }

        DB::beginTransaction();

        try {
            $telemetryImport = TelemetryImport::query()->create([
                'session_id' => $session->getKey(),
                'driver_id' => $driver?->getKey(),
                'vehicle_id' => $session->vehicle_id,
                'circuit_layout_id' => $session->circuit_layout_id,
                'source_vendor' => $vendor,
                'source_format' => $format,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $storagePath,
                'sha256' => $sha256,
                'sample_count' => 0,
                'lap_count' => 0,
                'imported_by' => $user->getKey(),
                'imported_at' => now(),
            ]);

            $summary = $format === 'vbo'
                ? $this->vboImporter->import($file->getRealPath(), $telemetryImport, $session, $driver)
                : $this->csvImporter->import($file->getRealPath(), $telemetryImport, $session, $driver);

            $telemetryImport->update([
                'sample_count' => $summary['sample_count'],
                'lap_count' => $summary['lap_count'],
                'duration_ms' => $summary['duration_ms'],
                'channel_keys' => $summary['channel_keys'],
                'metadata' => $summary['metadata'],
            ]);

            DB::commit();

            return $telemetryImport->fresh(['session', 'driver', 'vehicle', 'circuitLayout.circuit', 'laps']) ?? $telemetryImport;
        } catch (Throwable $exception) {
            DB::rollBack();
            Storage::disk('local')->delete($storagePath);

            throw $exception;
        }
    }
}
