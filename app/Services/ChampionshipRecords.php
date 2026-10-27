<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipParticipant;
use App\Models\ChampionshipResult;
use App\Models\ChampionshipRound;
use App\Models\Driver;
use App\Models\RaceEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChampionshipRecords
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function metadata(array $input): array
    {
        return Validator::make($input, [
            'name' => ['required', 'string', 'max:140'],
            'season' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['planned', 'active', 'completed'])],
            'notes' => ['nullable', 'string', 'max:4000'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function roundData(Championship $championship, array $input, ?ChampionshipRound $round = null): array
    {
        return Validator::make($input, [
            'number' => ['required', 'integer', 'min:1', 'max:1000', Rule::unique('championship_rounds')->where('championship_id', $championship->id)->ignore($round?->id)],
            'name' => ['required', 'string', 'max:140'],
            'starts_on' => ['nullable', 'date_format:Y-m-d', 'required_with:ends_on'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'venue' => ['nullable', 'string', 'max:140'],
            'status' => ['required', Rule::in(['planned', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:4000'],
            'event_id' => ['nullable', 'integer', Rule::exists(RaceEvent::class, 'id')->where('workspace_id', $championship->workspace_id)->whereNull('deleted_at')],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function participantData(Championship $championship, array $input): array
    {
        return Validator::make($input, [
            'driver_id' => ['nullable', 'integer', Rule::exists(Driver::class, 'id')->where('workspace_id', $championship->workspace_id)->whereNull('deleted_at')],
            'name' => ['required_without:driver_id', 'nullable', 'string', 'max:120'],
            'number' => ['nullable', 'string', 'max:20'],
            'licence' => ['nullable', 'string', 'max:80'],
            'team_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'withdrawn'])],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function matchingDriver(array $data): ?Driver
    {
        if (! empty($data['driver_id'])) {
            return Driver::query()->findOrFail((int) $data['driver_id']);
        }
        $query = Driver::withTrashed();
        if (filled($data['licence'] ?? null)) {
            $query->whereRaw('LOWER(licence_reference) = ?', [mb_strtolower($data['licence'])]);
        } else {
            $query->whereRaw('LOWER(display_name) = ?', [mb_strtolower($data['name'])])
                ->where(fn ($q) => $q->where('racing_number', $data['number'] ?? '')->when(blank($data['number'] ?? null), fn ($q) => $q->orWhereNull('racing_number')));
        }
        $matches = $query->limit(2)->get();
        if ($matches->count() > 1) {
            throw ValidationException::withMessages(['name' => __('championship.ambiguous_driver')]);
        }

        $driver = $matches->first();
        if ($driver !== null && filled($data['licence'] ?? null) && mb_strtolower(trim($driver->display_name)) !== mb_strtolower(trim($data['name']))) {
            throw ValidationException::withMessages(['licence' => __('championship.licence_mismatch')]);
        }

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function participant(Championship $championship, array $input): ChampionshipParticipant
    {
        return DB::transaction(function () use ($championship, $input): ChampionshipParticipant {
            $this->lock($championship);
            $data = $this->participantData($championship, $input);
            $driver = $this->matchingDriver($data);
            if ($driver === null) {
                $driver = Driver::create(['display_name' => $data['name'], 'racing_number' => $data['number'] ?? null, 'licence_reference' => $data['licence'] ?? null]);
            }
            if ($championship->participants()->where('driver_id', $driver->id)->exists()) {
                throw ValidationException::withMessages(['driver_id' => __('championship.already_enrolled')]);
            }
            if (filled($data['number'] ?? $driver->racing_number) && $championship->participants()->where('entry_number', $data['number'] ?? $driver->racing_number)->exists()) {
                throw ValidationException::withMessages(['number' => __('championship.duplicate_number')]);
            }

            return $championship->participants()->create([
                'driver_id' => $driver->id, 'entry_number' => $data['number'] ?? $driver->racing_number,
                'team_name' => $data['team_name'] ?? null, 'status' => $data['status'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function round(Championship $championship, array $input, ?ChampionshipRound $round = null): ChampionshipRound
    {
        return DB::transaction(function () use ($championship, $input, $round): ChampionshipRound {
            $this->lock($championship);
            if ($round !== null) {
                $round = $championship->rounds()->whereKey($round->id)->firstOrFail();
            }
            $data = $this->roundData($championship, $input, $round);
            if ($round !== null) {
                $round->update($data);

                return $round;
            }

            return $championship->rounds()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function resultData(Championship $championship, ChampionshipRound $round, array $input): array
    {
        $data = Validator::make($input, [
            'participant_id' => ['required', 'integer', Rule::exists(ChampionshipParticipant::class, 'id')->where('workspace_id', $championship->workspace_id)->where('championship_id', $championship->id)],
            'position' => ['nullable', 'required_if:status,classified', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(['classified', 'dnf', 'dns', 'dsq'])],
            'pole' => ['sometimes', 'boolean'],
            'fastest_lap' => ['sometimes', 'boolean'],
            'adjustment' => ['nullable', 'regex:/^-?\d{1,6}([.,]\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $data['position'] = $data['status'] === 'classified' ? (int) $data['position'] : null;
        $data['pole'] = (bool) ($data['pole'] ?? false);
        $data['fastest_lap'] = (bool) ($data['fastest_lap'] ?? false);
        $data['adjustment'] = self::hundredths((string) ($data['adjustment'] ?? '0'));
        if ($data['position'] !== null && $round->results()->where('participant_id', '<>', $data['participant_id'])->where('position', $data['position'])->exists()) {
            throw ValidationException::withMessages(['position' => __('championship.duplicate_position')]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function result(Championship $championship, ChampionshipRound $round, array $input): ChampionshipResult
    {
        return DB::transaction(function () use ($championship, $round, $input): ChampionshipResult {
            $this->lock($championship);
            $data = $this->resultData($championship, $round, $input);
            $result = $round->results()->withTrashed()->firstOrNew(['participant_id' => $data['participant_id']]);
            $result->fill($data + ['championship_id' => $championship->id]);
            $result->deleted_at = null;
            $result->save();

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function scoring(Championship $championship, array $input): void
    {
        $data = Validator::make($input, [
            'points' => ['required', 'array', 'min:1', 'max:100'],
            'points.*' => ['nullable', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'],
            'pole_bonus' => ['required', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'],
            'fastest_lap_bonus' => ['required', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'],
        ])->validate();
        $points = [];
        foreach ($data['points'] as $position => $amount) {
            if (! ctype_digit((string) $position) || (int) $position < 1 || (int) $position > 100) {
                throw ValidationException::withMessages(['points' => __('championship.invalid_points')]);
            }
            if ($amount !== null && $amount !== '') {
                $points[(int) $position] = self::hundredths((string) $amount);
            }
        }
        if ($points === []) {
            throw ValidationException::withMessages(['points' => __('championship.invalid_points')]);
        }
        ksort($points);
        $championship->update(['points' => $points, 'pole_bonus' => self::hundredths((string) $data['pole_bonus']), 'fastest_lap_bonus' => self::hundredths((string) $data['fastest_lap_bonus'])]);
    }

    public function lock(Championship $championship): void
    {
        Championship::query()->whereKey($championship->id)->lockForUpdate()->firstOrFail();
        $championship->refresh();
    }

    public static function hundredths(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $parts = explode('.', str_replace(',', '.', ltrim($amount, '-')));
        $value = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');

        return $negative ? -$value : $value;
    }

    public static function display(int $amount): string
    {
        return rtrim(rtrim(number_format($amount / 100, 2, '.', ''), '0'), '.');
    }
}
