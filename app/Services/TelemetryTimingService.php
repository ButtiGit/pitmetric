<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\TimingLap;

class TelemetryTimingService
{
    public function __construct(private readonly TelemetryValueNormalizer $values) {}

    /**
     * @param  array<int, array{first:int,last:int,explicit:?int,sectors:array<int,int>}>  $lapStats
     * @param  array<string, mixed>  $rowValues
     */
    public function accumulate(array &$lapStats, int $lapNumber, int $elapsedMs, array $rowValues): void
    {
        $lapStats[$lapNumber] ??= [
            'first' => $elapsedMs,
            'last' => $elapsedMs,
            'explicit' => null,
            'sectors' => [],
        ];

        $lapStats[$lapNumber]['last'] = $elapsedMs;
        $explicit = $this->values->parseDurationMs($rowValues['lap_time_ms'] ?? null);
        if ($explicit !== null && $explicit > 0) {
            $lapStats[$lapNumber]['explicit'] = $explicit;
        }

        for ($sector = 1; $sector <= 6; $sector++) {
            $duration = $this->values->parseDurationMs($rowValues['sector_'.$sector.'_ms'] ?? null);
            if ($duration !== null && $duration > 0) {
                $lapStats[$lapNumber]['sectors'][$sector] = $duration;
            }
        }
    }

    /** @param array<int, array{first:int,last:int,explicit:?int,sectors:array<int,int>}> $lapStats */
    public function store(array $lapStats, TelemetryImport $telemetryImport, Session $session, ?Driver $driver): int
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
