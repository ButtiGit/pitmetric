<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class CsvTelemetryImporter
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

    public function __construct(
        private readonly TelemetryValueNormalizer $values,
        private readonly TelemetryTimingService $timing,
    ) {}

    /**
     * @return array{sample_count:int,lap_count:int,duration_ms:?int,channel_keys:list<string>,metadata:array<string,mixed>}
     */
    public function import(string $path, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): array
    {
        [$headerRow, $delimiter, $rawHeaders] = $this->detectHeader($path);
        $headers = array_map(fn (string $header): string => $this->values->normalizeHeader($header), $rawHeaders);
        $columnKeys = [];

        foreach ($headers as $index => $header) {
            $columnKeys[$index] = $this->values->canonicalKey($header);
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

            $rowValues = [];
            foreach ($columnKeys as $index => $key) {
                $rowValues[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $elapsedIndex = array_search('elapsed_ms', $columnKeys, true);
            $elapsedMs = $this->values->parseElapsedMs(
                $rowValues['elapsed_ms'] ?? null,
                $elapsedIndex === false ? null : ($rawHeaders[$elapsedIndex] ?? null),
            );

            if ($elapsedMs === null) {
                continue;
            }

            $firstElapsedMs ??= $elapsedMs;
            $elapsedMs -= $firstElapsedMs;
            $lastElapsedMs = $elapsedMs;
            $lapNumber = $this->values->positiveInt($rowValues['lap_number'] ?? null);
            $channels = [];

            foreach ($rowValues as $key => $value) {
                if (! in_array($key, self::DYNAMIC_CHANNELS, true)) {
                    continue;
                }

                $numeric = $this->values->numericValue($value);
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

                $numeric = $this->values->numericValue($row[$index] ?? null);
                if ($numeric === null) {
                    continue;
                }

                $extraKey = substr($key, 6);
                $channels[$extraKey] = $numeric;
                $channelKeys[$extraKey] = true;
            }

            $distanceIndex = array_search('distance_meters', $columnKeys, true);
            $speedIndex = array_search('speed_kmh', $columnKeys, true);
            $buffer[] = [
                'workspace_id' => $telemetryImport->workspace_id,
                'telemetry_import_id' => $telemetryImport->getKey(),
                'sequence' => $sequence,
                'lap_number' => $lapNumber,
                'elapsed_ms' => $elapsedMs,
                'distance_meters' => $this->values->distanceMeters(
                    $rowValues['distance_meters'] ?? null,
                    $distanceIndex === false ? null : ($rawHeaders[$distanceIndex] ?? null),
                ),
                'latitude' => $this->values->numericValue($rowValues['latitude'] ?? null),
                'longitude' => $this->values->numericValue($rowValues['longitude'] ?? null),
                'speed_kmh' => $this->values->speedKmh(
                    $rowValues['speed_kmh'] ?? null,
                    $speedIndex === false ? null : ($rawHeaders[$speedIndex] ?? null),
                ),
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
                'delimiter' => $delimiter === "\t" ? 'tab' : $delimiter,
                'headers' => $rawHeaders,
                'header_row' => $headerRow + 1,
            ],
        ];
    }

    /** @return array{0:int,1:string,2:list<string>} */
    private function detectHeader(string $path): array
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
                    $canonical = $this->values->canonicalKey($this->values->normalizeHeader((string) $cell));
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
}
