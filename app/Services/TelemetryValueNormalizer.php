<?php

namespace App\Services;

class TelemetryValueNormalizer
{
    public function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace(['%', '°'], ['', 'deg'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    public function canonicalKey(string $header): string
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

    public function parseElapsedMs(mixed $value, ?string $header): ?int
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

    public function parseDurationMs(mixed $value): ?int
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

    public function numericValue(mixed $value): ?float
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

    public function positiveInt(mixed $value): ?int
    {
        $numeric = $this->numericValue($value);

        return $numeric === null || $numeric < 0 ? null : (int) round($numeric);
    }

    public function distanceMeters(mixed $value, ?string $header): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $normalizedHeader = $header !== null ? $this->normalizeHeader($header) : '';

        return str_contains($normalizedHeader, '_km') ? $numeric * 1000 : $numeric;
    }

    public function speedKmh(mixed $value, ?string $header): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $normalizedHeader = $header !== null ? $this->normalizeHeader($header) : '';

        return str_contains($normalizedHeader, 'mph') ? $numeric * 1.609344 : $numeric;
    }

    public function parseVboTimeSeconds(mixed $value): ?float
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

    public function vboCoordinate(mixed $value, bool $longitude): ?float
    {
        $numeric = $this->numericValue($value);
        if ($numeric === null) {
            return null;
        }

        $decimalDegrees = $numeric / 60;

        return $longitude ? -$decimalDegrees : $decimalDegrees;
    }
}
