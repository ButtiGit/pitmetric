<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class LapTimeParser
{
    public function parse(string $value): int
    {
        $value = str_replace(',', '.', trim($value));

        if (str_contains($value, ':')) {
            [$minutes, $seconds] = array_pad(explode(':', $value, 2), 2, null);

            if (! ctype_digit($minutes) || ! is_numeric($seconds) || (float) $seconds >= 60) {
                throw ValidationException::withMessages(['lap_time' => __('Use a lap time like 1:02.345.')]);
            }

            $milliseconds = ((int) $minutes * 60000) + (int) round((float) $seconds * 1000);
        } elseif (is_numeric($value)) {
            $milliseconds = (int) round((float) $value * 1000);
        } else {
            throw ValidationException::withMessages(['lap_time' => __('Use a lap time like 1:02.345 or 62.345.')]);
        }

        if ($milliseconds < 1000 || $milliseconds > 3600000) {
            throw ValidationException::withMessages(['lap_time' => __('Lap time must be between 1 second and 60 minutes.')]);
        }

        return $milliseconds;
    }
}
