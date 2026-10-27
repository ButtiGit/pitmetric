<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Services\ChampionshipImportService;
use App\Services\ChampionshipSheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChampionshipImportController extends Controller
{
    public function upload(Request $request, Championship $championship, ChampionshipSheetReader $reader, ChampionshipImportService $imports): JsonResponse
    {
        Gate::authorize('update', $championship);
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ChampionshipImportService::FIELDS))],
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,tsv,xlsx', 'mimetypes:text/plain,text/csv,text/tab-separated-values,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream'],
        ]);
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);
        $sheets = $reader->read($file);
        $token = $imports->create($request, $championship, $data['kind'], $sheets);

        return response()->json(['token' => $token, 'sheets' => $sheets]);
    }

    public function preview(Request $request, Championship $championship, ChampionshipImportService $imports): JsonResponse
    {
        Gate::authorize('update', $championship);
        $data = $request->validate(['token' => ['required', 'uuid']]);

        return response()->json($imports->preview($request, $championship, $data['token'], $request->all()));
    }

    public function confirm(Request $request, Championship $championship, ChampionshipImportService $imports): JsonResponse
    {
        Gate::authorize('update', $championship);
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $counts = $imports->confirm($request, $championship, $data['token']);
        $request->session()->flash('status', __('championship.import_done', ['count' => $counts['imported'], 'skipped' => $counts['skipped']]));

        return response()->json($counts);
    }
}
