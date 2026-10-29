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

        $sha256 = hash_file('sha256', $file->getRealPath());

        if ($sha256 === false) {
            throw new RuntimeException('Unable to fingerprint the telemetry source file.');
        }

        $storagePath = null;
        DB::beginTransaction();

        try {
            // Serialize imports for this session so concurrent uploads of the same file cannot duplicate laps.
            Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            $existing = TelemetryImport::query()
                ->where('session_id', $session->getKey())
                ->where('driver_id', $driver?->getKey())
                ->where('source_format', $format)
                ->where('sha256', $sha256)
                ->oldest('id')
                ->first();

            if ($existing instanceof TelemetryImport) {
                DB::commit();

                return $existing->load(['session', 'driver', 'vehicle', 'circuitLayout.circuit', 'laps']);
            }

            $storagePath = $file->storeAs(
                'telemetry/'.$session->workspace_id,
                $file->hashName(),
                'local',
            );

            if ($storagePath === false) {
                throw new RuntimeException('Unable to store the telemetry source file.');
            }

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

            return $telemetryImport->load(['session', 'driver', 'vehicle', 'circuitLayout.circuit', 'laps']);
        } catch (Throwable $exception) {
            DB::rollBack();

            if (is_string($storagePath)) {
                Storage::disk('local')->delete($storagePath);
            }

            throw $exception;
        }
    }
}
