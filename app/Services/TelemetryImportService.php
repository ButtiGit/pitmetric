<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\TimingLap;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SplFileObject;
use Throwable;

class TelemetryImportService
{
    private const MAX_SAMPLES = 500000;

    private const INSERT_CHUNK_SIZE = 500;

    /** @var list<string> */
    private const DYNAMIC_CHANNELS = [
        'rpm',
        'throttle',
        'brake',
        'steering',
        'gear',
        'lateral_g',
        'longitudinal_g',
        'vertical_g',
        'heading',
        'altitude',
    ];

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
                ? $this->importVbo($file->getRealPath(), $telemetryImport, $session, $driver)
                : $this->importCsv($file->getRealPath(), $telemetryImport, $session, $driver);

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

    /**
     * @return array{sample_count:int,lap_count:int,duration_ms:?int,channel_keys:list<string>,metadata:array<string,mixed>}
     */
    private function importCsv(string $path, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): array
    {
        [$headerRow, $delimiter, $rawHeaders] = $this->detectCsvHeader($path);
        $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $rawHeaders);
        $columnKeys = [];

        foreach ($headers as $index => $header) {
            $columnKeys[$index] = $this->canonicalKey($header);
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $sequence = 0;
        $firstElapsedMs = null;
        $lastElapsedMs = null;
        $buffer = [];
        $channelKeys = [];
        $lapStats = [];
        $now = now();

        foreach ($file as $rowIndex => $row) {
            if ($rowIndex <= $headerRow || ! is_array($row) || $row === [null]) {
                continue;
            }

            if ($sequence >= self::MAX_SAMPLES) {
                throw new RuntimeException('Telemetry file exceeds the 500,000 sample safety limit. Split the recording before importing it.');
            }

            $values = [];

            foreach ($columnKeys as $index => $key) {
                $values[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $elapsedMs = $this->parseElapsedMs(
                $values['elapsed_ms'] ?? null,
                $rawHeaders[array_search('elapsed_ms', $columnKeys, true)] ?? null,
            );

            if ($elapsedMs === null) {
                continue;
            }

            $firstElapsedMs ??= $elapsedMs;
            $elapsedMs -= $firstElapsedMs;
            $lastElapsedMs = $elapsedMs;
            $lapNumber = $this->positiveInt($values['lap_number'] ?? null);
            $channels = [];

            foreach ($values as $key => $value) {
                if (! in_array($key, self::DYNAMIC_CHANNELS, true)) {
                    continue;
                }

                $numeric = $this->numericValue($value);

                if ($numeric === null) {
                    continue;
                }

                $channels[$key] = $numeric;
                $channelKeys[$key] = true;
            }

            foreach ($columnKeys as $index => $key) {
                if ($key !== 'extra_'.$headers[$index]) {
                    continue;
                }

                $numeric = $this->numericValue($row[$index] ?? null);

                if ($numeric === null) {
                    continue;
                }

                $extraKey = substr($key, 6);
                $channels[$extraKey] = $numeric;
                $channelKeys[$extraKey] = true;
            }

            $buffer[] = [
                'workspace_id' => $telemetryImport->workspace_id,
                'telemetry_import_id' => $telemetryImport->getKey(),
                'sequence' => $sequence,
                'lap_number' => $lapNumber,
                'elapsed_ms' => $elapsedMs,
                'distance_meters' => $this->distanceMeters($values['distance_meters'] ?? null, $rawHeaders[array_search('distance_meters', $columnKeys, true)] ?? null),
                'latitude' => $this->numericValue($values['latitude'] ?? null),
                'longitude' => $this->numericValue($values['longitude'] ?? null),
                'speed_kmh' => $this->speedKmh($values['speed_kmh'] ?? null, $rawHeaders[array_search('speed_kmh', $columnKeys, true)] ?? null),
                'channels' => $channels === [] ? null : json_encode($channels, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($lapNumber !== null) {
                $this->accumulateLap($lapStats, $lapNumber, $elapsedMs, $values);
            }

            $sequence++;

            if (count($buffer) >= self::INSERT_CHUNK_SIZE) {
                DB::table('telemetry_samples')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('telemetry_samples')->insert($buffer);
        }

        $lapCount = $this->storeTimingLaps($lapStats, $telemetryImport, $session, $driver);

        return [
            'sample_count' => $sequence,
            'lap_count' => $lapCount,
            'duration_ms' => $lastElapsedMs,
            'channel_keys' => array_keys($channelKeys),
            'metadata' => [
                'delimiter' => $delimiter === "\t" ? 'tab' : $delimiter,
                'headers' => $rawHeaders,
                'header_row' => $headerRow + 1,
            ],
        ];
    }

    /**
     * @return array{sample_count:int,lap_count:int,duration_ms:?int,channel_keys:list<string>,metadata:array<string,mixed>}
     */
    private function importVbo(string $path, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): array
    {
        $file = new SplFileObject($path, 'r');
        $section = null;
        $headers = [];
        $columnKeys = [];
        $sequence = 0;
        $firstSeconds = null;
        $lastElapsedMs = null;
        $buffer = [];
        $channelKeys = [];
        $lapStats = [];
        $comments = [];
        $now = now();

        while (! $file->eof()) {
            $line = trim((string) $file->fgets());

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[(.+)]$/', $line, $matches) === 1) {
                $section = strtolower(trim($matches[1]));

                continue;
            }

            if ($section === 'comments' && count($comments) < 20) {
                $comments[] = $line;

                continue;
            }

            if ($section === 'column names' && $headers === []) {
                $headers = preg_split('/\s+/', $line) ?: [];
                foreach ($headers as $index => $header) {
                    $columnKeys[$index] = $this->canonicalKey($this->normalizeHeader($header));
                }

                continue;
            }

            if ($section !== 'data' || $headers === []) {
                continue;
            }

            if ($sequence >= self::MAX_SAMPLES) {
                throw new RuntimeException('Telemetry file exceeds the 500,000 sample safety limit. Split the recording before importing it.');
            }

            $row = preg_split('/\s+/', $line) ?: [];
            if (count($row) < 2) {
                continue;
            }

            $values = [];
            foreach ($columnKeys as $index => $key) {
                $values[$key] = $row[$index] ?? null;
            }

            $absoluteSeconds = $this->parseVboTimeSeconds($values['elapsed_ms'] ?? null);
            if ($absoluteSeconds === null) {
                continue;
            }

            $firstSeconds ??= $absoluteSeconds;
            $elapsedMs = (int) round(($absoluteSeconds - $firstSeconds) * 1000);
            if ($elapsedMs < 0) {
                $elapsedMs += 86400000;
            }
            $lastElapsedMs = $elapsedMs;
            $lapNumber = $this->positiveInt($values['lap_number'] ?? null);
            $channels = [];

            foreach ($values as $key => $value) {
                if (in_array($key, ['elapsed_ms', 'lap_number', 'latitude', 'longitude', 'speed_kmh', 'distance_meters'], true)) {
                    continue;
                }

                $numeric = $this->numericValue($value);
                if ($numeric === null) {
                    continue;
                }

                $cleanKey = str_starts_with($key, 'extra_') ? substr($key, 6) : $key;
                $channels[$cleanKey] = $numeric;
                $channelKeys[$cleanKey] = true;
            }

            $buffer[] = [
                'workspace_id' => $telemetryImport->workspace_id,
                'telemetry_import_id' => $telemetryImport->getKey(),
                'sequence' => $sequence,
                'lap_number' => $lapNumber,
                'elapsed_ms' => $elapsedMs,
                'distance_meters' => $this->numericValue($values['distance_meters'] ?? null),
                'latitude' => $this->vboCoordinate($values['latitude'] ?? null, false),
                'longitude' => $this->vboCoordinate($values['longitude'] ?? null, true),
                'speed_kmh' => $this->numericValue($values['speed_kmh'] ?? null),
                'channels' => $channels === [] ? null : json_encode($channels, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($lapNumber !== null) {
                $this->accumulateLap($lapStats, $lapNumber, $elapsedMs, $values);
            }

            $sequence++;

            if (count($buffer) >= self::INSERT_CHUNK_SIZE) {
                DB::table('telemetry_samples')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('telemetry_samples')->insert($buffer);
        }

        $lapCount = $this->storeTimingLaps($lapStats, $telemetryImport, $session, $driver);

        return [
            'sample_count' => $sequence,
            'lap_count' => $lapCount,
            'duration_ms' => $lastElapsedMs,
            'channel_keys' => array_keys($channelKeys),
            'metadata' => [
                'headers' => $headers,
                'comments' => $comments,
            ],
        ];
    }

    /** @return array{0:int,1:string,2:list<string>} */
    private function detectCsvHeader(string $path): array
    {
        $file = new SplFileObject($path, 'r');
        $best = null;

        for ($row = 0; $row < 60 && ! $file->eof(); $row++) {
            $line = trim((string) $file->fgets());
            if ($line === '') {
                continue;
            }

            foreach ([',', ';', "\t"] as $delimiter) {
                $cells = str_getcsv($line, $delimiter);
                if (count($cells) < 2) {
                    continue;
                }

                $score = 0;
                foreach ($cells as $cell) {
                    $canonical = $this->canonicalKey($this->normalizeHeader((string) $cell));
                    if (! str_starts_with($canonical, 'extra_')) {
                        $score++;
                    }
                }

                if ($score >= 2 && ($best === null || $score > $best['score'])) {
                    $best = [
                        'row' => $row,
                        'delimiter' => $delimiter,
                        'headers' => array_map(static fn (?string $cell): string => trim((string) $cell), $cells),
                        'score' => $score,
                    ];
                }
            }
        }

        if ($best === null) {
            throw new RuntimeException('No telemetry header could be detected. Include at least a time column and one telemetry channel.');
        }

        return [$best['row'], $best['delimiter'], $best['headers']];
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace(['%', '°'], ['', 'deg'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    private function canonicalKey(string $header): string
    {
        if (in_array($header, ['time', 'timestamp', 'elapsed', 'elapsed_time', 'session_time', 'time_s', 'time_sec', 'time_seconds', 'time_ms'], true)) {
            return 'elapsed_ms';
        }

        if (in_array($header, ['lap', 'lap_number', 'lap_no', 'lapnum'], true)) {
            return 'lap_number';
        }

        if (str_contains($header, 'lap_time') || $header === 'laptime') {
            return 'lap_time_ms';
        }

        if (preg_match('/^sector_?([1-6])(?:_time)?/', $header, $matches) === 1) {
            return 'sector_'.$matches[1].'_ms';
        }

        if (in_array($header, ['lat', 'latitude', 'gps_lat', 'gps_latitude'], true)) {
            return 'latitude';
        }

        if (in_array($header, ['lon', 'long', 'lng', 'longitude', 'gps_lon', 'gps_longitude'], true)) {
            return 'longitude';
        }

        if (str_contains($header, 'velocity') || preg_match('/(^|_)speed($|_)/', $header) === 1 || $header === 'gps_speed') {
            return 'speed_kmh';
        }

        if (str_contains($header, 'distance') && ! str_contains($header, 'time')) {
            return 'distance_meters';
        }

        if (str_contains($header, 'rpm') || str_contains($header, 'engine_speed')) {
            return 'rpm';
        }

        if (str_contains($header, 'throttle') || str_contains($header, 'accelerator')) {
            return 'throttle';
        }

        if (str_contains($header, 'brake')) {
            return 'brake';
        }

        if (str_contains($header, 'steer')) {
            return 'steering';
        }

        if ($header === 'gear' || str_contains($header, 'gear_position')) {
            return 'gear';
        }

        if (str_contains($header, 'lateral_g') || in_array($header, ['lat_accel', 'g_lat', 'accel_y'], true)) {
            return 'lateral_g';
        }

        if (str_contains($header, 'longitudinal_g') || in_array($header, ['long_accel', 'g_long', 'accel_x'], true)) {
            return 'longitudinal_g';
        }

        if (str_contains($header, 'vertical_g') || in_array($header, ['g_vert', 'accel_z'], true)) {
            return 'vertical_g';
        }

        if (str_contains($header, 'heading')) {
            return 'heading';
        }

        if (str_contains($header, 'height') || str_contains($header, 'altitude')) {
            return 'altitude';
        }

        return 'extra_'.$header;
    }

    private function parseElapsedMs(mixed $value, ?string $header): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $text = trim((string) $value);
        if (str_contains($text, ':')) {
            return $this->parseDurationMs($text);
        }

        $numeric = $this->numericValue($text);
        if ($numeric === null) {
            return null;
        }

        $normalizedHeader = $header !== null ? $this->normalizeHeader($header) : '';

        return str_contains($normalizedHeader, 'ms')
            ? (int) round($numeric)
            : (int) round($numeric * 1000);
    }

    private function parseDurationMs(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $text = trim((string) $value);
        if (! str_contains($text, ':')) {
            $numeric = $this->numericValue($text);

            return $numeric === null ? null : (int) round($numeric * 1000);
        }

        $parts = array_map('floatval', explode(':', $text));
        if (count($parts) === 2) {
            return (int) round((($parts[0] * 60) + $parts[1]) * 1000);
        }

        if (count($parts) === 3) {
            return (int) round((($parts[0] * 3600) + ($parts[1] * 60) + $parts[2]) * 1000);
        }

        return null;
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' || in_array(strtolower($text), ['nan', 'null', 'n/a', '-'], true)) {
            return null;
        }

        $text = str_replace([' ', '%'], '', $text);
        if (str_contains($text, ',') && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null || $numeric < 0) {
            return null;
        }

        return (int) round($numeric);
    }

    private function distanceMeters(mixed $value, ?string $header): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $normalizedHeader = $header !== null ? $this->normalizeHeader($header) : '';

        return str_contains($normalizedHeader, '_km') ? $numeric * 1000 : $numeric;
    }

    private function speedKmh(mixed $value, ?string $header): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $normalizedHeader = $header !== null ? $this->normalizeHeader($header) : '';

        return str_contains($normalizedHeader, 'mph') ? $numeric * 1.609344 : $numeric;
    }

    private function parseVboTimeSeconds(mixed $value): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $hours = (int) floor($numeric / 10000);
        $minutes = (int) floor(($numeric - ($hours * 10000)) / 100);
        $seconds = $numeric - ($hours * 10000) - ($minutes * 100);

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    private function vboCoordinate(mixed $value, bool $longitude): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $decimalDegrees = $numeric / 60;

        return $longitude ? -$decimalDegrees : $decimalDegrees;
    }

    /**
     * @param  array<int, array{first:int,last:int,explicit:?int,sectors:array<int,int>}>  $lapStats
     * @param  array<string, mixed>  $values
     */
    private function accumulateLap(array &$lapStats, int $lapNumber, int $elapsedMs, array $values): void
    {
        $lapStats[$lapNumber] ??= [
            'first' => $elapsedMs,
            'last' => $elapsedMs,
            'explicit' => null,
            'sectors' => [],
        ];

        $lapStats[$lapNumber]['last'] = $elapsedMs;
        $explicit = $this->parseDurationMs($values['lap_time_ms'] ?? null);
        if ($explicit !== null && $explicit > 0) {
            $lapStats[$lapNumber]['explicit'] = $explicit;
        }

        for ($sector = 1; $sector <= 6; $sector++) {
            $duration = $this->parseDurationMs($values['sector_'.$sector.'_ms'] ?? null);
            if ($duration !== null && $duration > 0) {
                $lapStats[$lapNumber]['sectors'][$sector] = $duration;
            }
        }
    }

    /** @param array<int, array{first:int,last:int,explicit:?int,sectors:array<int,int>}> $lapStats */
    private function storeTimingLaps(array $lapStats, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): int
    {
        $count = 0;
        ksort($lapStats);

        foreach ($lapStats as $lapNumber => $stats) {
            $lapTimeMs = $stats['explicit'] ?? ($stats['last'] - $stats['first']);
            if ($lapTimeMs <= 0) {
                continue;
            }

            TimingLap::query()->create([
                'session_id' => $session->getKey(),
                'driver_id' => $driver?->getKey(),
                'vehicle_id' => $session->vehicle_id,
                'circuit_layout_id' => $session->circuit_layout_id,
                'telemetry_import_id' => $telemetryImport->getKey(),
                'lap_number' => $lapNumber,
                'lap_time_ms' => $lapTimeMs,
                'sector_times_ms' => $stats['sectors'] === [] ? null : $stats['sectors'],
                'started_offset_ms' => $stats['first'],
                'ended_offset_ms' => $stats['last'],
                'is_valid' => true,
                'source' => 'import',
            ]);

            $count++;
        }

        return $count;
    }
}
