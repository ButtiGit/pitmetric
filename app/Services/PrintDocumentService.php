<?php

namespace App\Services;

use App\Models\Session;
use App\Models\TechnicalSetup;
use Illuminate\Support\Carbon;

class PrintDocumentService
{
    /** @param array<string, mixed> $values
     * @return list<array{label: string, value: string}>
     */
    public function setupRows(array $values, bool $kart): array
    {
        $rows = [];
        $geometryKeys = $kart ? array_keys(TechnicalSetup::kartGeometryRules()) : [];
        if ($kart) {
            foreach (TechnicalSetup::kartGeometrySummary($values) as $key => $value) {
                $rows[] = ['label' => __('prints.'.$key), 'value' => $value];
            }
            $rows[] = ['label' => __('prints.front_width'), 'value' => isset($values['track_width_front_mm']) ? $values['track_width_front_mm'].' mm' : '—'];
        }
        foreach (TechnicalSetup::FIELD_DEFINITIONS as $key => $definition) {
            if (in_array($key, $geometryKeys, true) || str_ends_with($key, '_mode') || ! isset($values[$key]) || $values[$key] === '' || ! is_scalar($values[$key])) {
                continue;
            }
            $unit = $definition['unit'] === 'deg' ? '°' : $definition['unit'];
            $rows[] = ['label' => __('prints.fields.'.$key), 'value' => (string) $values[$key].($unit !== '' ? ' '.$unit : '')];
        }

        return $rows;
    }

    public function lapTime(?int $milliseconds): string
    {
        return $milliseconds === null ? '—' : sprintf('%d:%02d.%03d', intdiv($milliseconds, 60000), intdiv($milliseconds % 60000, 1000), $milliseconds % 1000);
    }

    /** @return list<array<string, mixed>> */
    public function weather(Session $session): array
    {
        $cards = [];
        $observation = $session->weatherObservations->first();
        if ($observation !== null && ! $observation->is_reset) {
            $values = $observation->getAttribute('values');
            $measuredAt = $observation->getAttribute('measured_at');
            $cards[] = $this->weatherCard('manual', is_array($values) ? $values : [], $measuredAt === null ? null : Carbon::parse($measuredAt)->toIso8601String(), null, $observation->notes);
        }
        $history = $session->getAttribute('historical_weather_snapshot');
        if (is_array($history)) {
            $card = $this->weatherCard('historical', $history, $history['valid_at'] ?? null, $history['captured_at'] ?? null);
            $card['mismatch'] = ! isset($history['session_started_at']) || $session->started_at === null
                || ! Carbon::parse($history['session_started_at'])->equalTo($session->started_at);
            $cards[] = $card;
        }
        $forecast = $session->getAttribute('weather_snapshot');
        if (is_array($forecast)) {
            $cards[] = $this->weatherCard('forecast', $forecast, $forecast['forecast_at'] ?? null, $forecast['captured_at'] ?? null);
        }

        return $cards;
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function weatherCard(string $kind, array $values, ?string $validAt, ?string $capturedAt, ?string $notes = null): array
    {
        $units = ['air_temperature_c' => '°C', 'humidity_pct' => '%', 'wind_speed_kmh' => 'km/h', 'pressure_hpa' => 'hPa', 'cloud_cover_pct' => '%', 'precipitation_next_hour_mm' => 'mm', 'precipitation_mm' => 'mm'];
        if ($kind === 'manual') {
            $units['track_temperature_c'] = '°C';
        }
        $rows = [];
        foreach ($units as $key => $unit) {
            if (isset($values[$key]) && is_numeric($values[$key])) {
                $rows[] = ['label' => __('prints.weather_fields.'.$key), 'value' => $values[$key].' '.$unit];
            }
        }
        if ($kind === 'manual' && isset($values['condition'])) {
            $rows[] = ['label' => __('prints.condition'), 'value' => __('prints.conditions.'.$values['condition'])];
        }

        return ['kind' => $kind, 'valid_at' => $validAt, 'captured_at' => $capturedAt, 'rows' => $rows, 'notes' => $notes, 'mismatch' => false];
    }
}
