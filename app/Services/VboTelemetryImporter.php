<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class VboTelemetryImporter
{
    private const MAX_SAMPLES = 500000;

    private const INSERT_CHUNK_SIZE = 500;

    public function __construct(
        private readonly TelemetryValueNormalizer $values,
        private readonly TelemetryTimingService $timing,
    ) {}

    /**
     * @return array{sample_count:int,lap_count:int,duration_ms:?int,channel_keys:list<string>,metadata:array<string,mixed>}
     */
    public function import(string $path, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): array
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
                    $columnKeys[$index] = $this->values->canonicalKey($this->values->normalizeHeader($header));
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

            $rowValues = [];
            foreach ($columnKeys as $index => $key) {
                $rowValues[$key] = $row[$index] ?? null;
            }

            $absoluteSeconds = $this->values->parseVboTimeSeconds($rowValues['elapsed_ms'] ?? null);
            if ($absoluteSeconds === null) {
                continue;
            }

            $firstSeconds ??= $absoluteSeconds;
            $elapsedMs = (int) round(($absoluteSeconds - $firstSeconds) * 1000);
            if ($elapsedMs < 0) {
                $elapsedMs += 86400000;
            }
            $lastElapsedMs = $elapsedMs;
            $lapNumber = $this->values->positiveInt($rowValues['lap_number'] ?? null);
            $channels = [];

            foreach ($rowValues as $key => $value) {
                if (in_array($key, ['elapsed_ms', 'lap_number', 'latitude', 'longitude', 'speed_kmh', 'distance_meters'], true)) {
                    continue;
                }

                $numeric = $this->values->numericValue($value);
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
                'distance_meters' => $this->values->numericValue($rowValues['distance_meters'] ?? null),
                'latitude' => $this->values->vboCoordinate($rowValues['latitude'] ?? null, false),
                'longitude' => $this->values->vboCoordinate($rowValues['longitude'] ?? null, true),
                'speed_kmh' => $this->values->numericValue($rowValues['speed_kmh'] ?? null),
                'channels' => $channels === [] ? null : json_encode($channels, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($lapNumber !== null) {
                $this->timing->accumulate($lapStats, $lapNumber, $elapsedMs, $rowValues);
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

        $lapCount = $this->timing->store($lapStats, $telemetryImport, $session, $driver);

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
}
