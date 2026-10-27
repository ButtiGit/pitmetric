<?php

namespace App\Services;

use App\Models\Championship;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChampionshipImportService
{
    public const FIELDS = [
        'participants' => ['name', 'number', 'licence', 'team_name'],
        'rounds' => ['number', 'name', 'starts_on', 'ends_on', 'venue'],
        'results' => ['round_number', 'name', 'number', 'position', 'status', 'pole', 'fastest_lap', 'adjustment'],
        'scoring' => ['position', 'points'],
    ];

    public function __construct(private readonly ChampionshipRecords $records) {}

    /**
     * @param  list<array{name: string, rows: list<list<string>>}>  $sheets
     */
    public function create(Request $request, Championship $championship, string $kind, array $sheets): string
    {
        $previous = $request->session()->get('championship_import');
        if (is_string($previous)) {
            Cache::forget('championship-import:'.$previous);
        }
        if (strlen(json_encode($sheets, JSON_THROW_ON_ERROR)) > 4 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => __('championship.invalid_file')]);
        }
        $token = (string) Str::uuid();
        Cache::put('championship-import:'.$token, [
            'user_id' => (int) $request->user()?->getAuthIdentifier(),
            'workspace_id' => (int) $championship->workspace_id,
            'championship_id' => (int) $championship->id,
            'kind' => $kind, 'sheets' => $sheets,
        ], now()->addMinutes(20));
        $request->session()->put('championship_import', $token);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function draft(Request $request, Championship $championship, string $token): array
    {
        $draft = Cache::get('championship-import:'.$token);
        if (! is_array($draft) || $draft['user_id'] !== (int) $request->user()?->getAuthIdentifier()
            || $draft['workspace_id'] !== (int) $championship->workspace_id || $draft['championship_id'] !== (int) $championship->id) {
            throw ValidationException::withMessages(['import' => __('championship.expired_import')]);
        }

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function preview(Request $request, Championship $championship, string $token, array $input): array
    {
        $draft = $this->draft($request, $championship, $token);
        $settings = Validator::make($input, [
            'sheet' => ['required', 'integer', 'min:0', 'max:'.(count($draft['sheets']) - 1)],
            'header_row' => ['required', 'integer', 'min:1', 'max:20'],
            'mapping' => ['required', 'array', 'min:1'],
            'mapping.*' => ['nullable', 'integer', 'min:0', 'max:49'],
        ])->validate();
        $mapped = array_filter($settings['mapping'], static fn (mixed $value): bool => $value !== null && $value !== '');
        if (array_diff(array_keys($mapped), self::FIELDS[$draft['kind']]) !== [] || count(array_unique($mapped)) !== count($mapped)) {
            throw ValidationException::withMessages(['mapping' => __('championship.invalid_mapping')]);
        }
        $draft['settings'] = $settings;
        $plan = $this->plan($championship, $draft);
        Cache::put('championship-import:'.$token, $draft, now()->addMinutes(20));

        return $plan;
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    public function confirm(Request $request, Championship $championship, string $token): array
    {
        return Cache::lock('championship-import-lock:'.$token, 30)->block(3, function () use ($request, $championship, $token): array {
            $counts = DB::transaction(function () use ($request, $championship, $token): array {
                $this->records->lock($championship);
                $draft = $this->draft($request, $championship, $token);
                if (! isset($draft['settings'])) {
                    throw ValidationException::withMessages(['import' => __('championship.preview_required')]);
                }
                $plan = $this->plan($championship, $draft);
                if ($plan['errors'] > 0) {
                    throw ValidationException::withMessages(['import' => __('championship.fix_rows')]);
                }
                $points = [];
                foreach ($plan['rows'] as $row) {
                    if ($row['state'] !== 'new') {
                        continue;
                    }
                    $data = $row['data'];
                    match ($draft['kind']) {
                        'participants' => $this->records->participant($championship, $data),
                        'rounds' => $this->records->round($championship, $data),
                        'results' => $this->records->result($championship, $championship->rounds()->whereKey($data['round_id'])->firstOrFail(), $data),
                        'scoring' => $points[(int) $data['position']] = $data['points'],
                        default => throw ValidationException::withMessages(['import' => __('championship.invalid_mapping')]),
                    };
                }
                if ($draft['kind'] === 'scoring') {
                    $this->records->scoring($championship, ['points' => $points, 'pole_bonus' => ChampionshipRecords::display($championship->pole_bonus), 'fastest_lap_bonus' => ChampionshipRecords::display($championship->fastest_lap_bonus)]);
                }

                return ['imported' => $plan['new'], 'skipped' => $plan['skipped']];
            });
            Cache::forget('championship-import:'.$token);

            return $counts;
        });
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function plan(Championship $championship, array $draft): array
    {
        $settings = $draft['settings'];
        $source = $draft['sheets'][(int) $settings['sheet']]['rows'];
        $rows = [];
        $seen = [];
        $positions = [];
        $numbers = [];
        foreach ($source as $index => $cells) {
            if ($index < (int) $settings['header_row'] || array_filter($cells, static fn (string $value): bool => $value !== '') === []) {
                continue;
            }
            $data = [];
            foreach ($settings['mapping'] as $field => $column) {
                if ($column !== null && $column !== '') {
                    $data[$field] = trim((string) ($cells[(int) $column] ?? ''));
                }
            }
            $row = ['line' => $index + 1, 'label' => $data['name'] ?? ($data['position'] ?? ($data['number'] ?? '')), 'state' => 'new', 'errors' => [], 'data' => $data];
            try {
                [$data, $key, $skip] = $this->normalize($championship, $draft['kind'], $data);
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['row' => __('championship.duplicate_row')]);
                }
                $seen[$key] = true;
                if ($draft['kind'] === 'participants' && ! $skip && filled($data['number'] ?? null)) {
                    if (isset($numbers[$data['number']])) {
                        throw ValidationException::withMessages(['number' => __('championship.duplicate_number')]);
                    }
                    $numbers[$data['number']] = true;
                }
                if ($draft['kind'] === 'results' && ! $skip && $data['status'] === 'classified') {
                    $positionKey = $data['round_id'].':'.$data['position'];
                    if (isset($positions[$positionKey])) {
                        throw ValidationException::withMessages(['position' => __('championship.duplicate_position')]);
                    }
                    $positions[$positionKey] = true;
                }
                $row['data'] = $data;
                $row['state'] = $skip ? 'skip' : 'new';
            } catch (ValidationException $exception) {
                $row['state'] = 'error';
                $row['errors'] = array_merge(...array_values($exception->errors()));
            }
            $rows[] = $row;
            if (count($rows) > 1000) {
                throw ValidationException::withMessages(['import' => __('championship.invalid_file')]);
            }
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['mapping' => __('championship.no_rows')]);
        }

        return ['rows' => $rows, 'new' => count(array_filter($rows, static fn (array $row): bool => $row['state'] === 'new')),
            'skipped' => count(array_filter($rows, static fn (array $row): bool => $row['state'] === 'skip')),
            'errors' => count(array_filter($rows, static fn (array $row): bool => $row['state'] === 'error'))];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{array<string, mixed>, string, bool}
     */
    private function normalize(Championship $championship, string $kind, array $data): array
    {
        if ($kind === 'participants') {
            $data['status'] = 'active';
            $data = $this->records->participantData($championship, $data);
            $driver = $this->records->matchingDriver($data);
            $existing = $driver === null ? null : $championship->participants()->where('driver_id', $driver->id)->first();
            $number = $data['number'] ?? $driver?->racing_number;
            if ($existing === null && filled($number) && $championship->participants()->where('entry_number', $number)->exists()) {
                throw ValidationException::withMessages(['number' => __('championship.duplicate_number')]);
            }

            $data['number'] = $number;

            return [$data, 'driver:'.($driver !== null ? (string) $driver->id : (filled($data['licence'] ?? null) ? mb_strtolower($data['licence']) : mb_strtolower($data['name'].'|'.($data['number'] ?? '')))), $existing !== null];
        }
        if ($kind === 'rounds') {
            foreach (['starts_on', 'ends_on'] as $field) {
                $data[$field] = $this->date($data[$field] ?? '');
            }
            $data['status'] = 'planned';
            $existing = $championship->rounds()->where('number', $data['number'] ?? null)->first();
            $data = $this->records->roundData($championship, $data, $existing);

            return [$data, 'round:'.$data['number'], $existing !== null];
        }
        if ($kind === 'results') {
            $round = $championship->rounds()->where('number', $data['round_number'] ?? null)->first();
            if ($round === null || (blank($data['name'] ?? null) && blank($data['number'] ?? null))) {
                throw ValidationException::withMessages(['round_number' => __('championship.unmatched_result')]);
            }
            $participants = $championship->participants()
                ->when(filled($data['number'] ?? null), fn ($query) => $query->where('entry_number', $data['number']))
                ->when(filled($data['name'] ?? null), fn ($query) => $query->whereHas('driver', fn ($driver) => $driver->whereRaw('LOWER(display_name) = ?', [mb_strtolower($data['name'])])))
                ->limit(2)->get();
            if ($participants->count() !== 1) {
                throw ValidationException::withMessages(['name' => __('championship.unmatched_result')]);
            }
            $data['participant_id'] = $participants->first()->id;
            $data['round_id'] = $round->id;
            $data['status'] = mb_strtolower($data['status'] ?? '') ?: 'classified';
            foreach (['pole', 'fastest_lap'] as $field) {
                $value = mb_strtolower($data[$field] ?? '');
                if (! in_array($value, ['', '0', '1', 'no', 'yes', 'si', 'sì', 'true', 'false'], true)) {
                    throw ValidationException::withMessages([$field => __('championship.invalid_boolean')]);
                }
                $data[$field] = in_array($value, ['1', 'yes', 'si', 'sì', 'true'], true);
            }
            $data['adjustment'] = $data['adjustment'] ?? '0';
            $this->records->resultData($championship, $round, $data);

            return [$data, 'result:'.$round->id.':'.$data['participant_id'], $round->results()->where('participant_id', $data['participant_id'])->exists()];
        }
        $data = Validator::make($data, ['position' => ['required', 'integer', 'min:1', 'max:100'], 'points' => ['required', 'regex:/^\d{1,6}([.,]\d{1,2})?$/']])->validate();

        return [$data, 'points:'.$data['position'], false];
    }

    private function date(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
            } catch (InvalidFormatException) {
                continue;
            }
            if ($date !== null && $date->format(substr($format, 1)) === $value) {
                return $date->format('Y-m-d');
            }
        }
        throw ValidationException::withMessages(['date' => __('championship.invalid_date')]);
    }
}
