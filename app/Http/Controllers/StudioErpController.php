<?php

namespace App\Http\Controllers;

use App\Models\StudioErpCheckRun;
use App\Services\StudioErpTechnical;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class StudioErpController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('studio.site.index').'#gestionale');
    }

    public function run(Request $request, StudioErpTechnical $erp): RedirectResponse
    {
        if (! Schema::hasTable('studio_erp_check_runs')) {
            return to_route('studio.site.index')->with('status', 'Applica le migrazioni Laravel per attivare la diagnostica ERP.');
        }

        $results = $erp->run();
        $passed = count(array_filter($results, fn (array $result): bool => $result['status'] === 'ok'));
        $warnings = count(array_filter($results, fn (array $result): bool => $result['status'] === 'warning'));
        $failed = count(array_filter($results, fn (array $result): bool => $result['status'] === 'error'));

        StudioErpCheckRun::query()->create([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'status' => $failed > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'ok'),
            'passed' => $passed,
            'warnings' => $warnings,
            'failed' => $failed,
            'results' => $results,
        ]);

        return redirect()->to(route('studio.site.index').'#controlli')->with('status', 'Diagnostica ERP completata: report salvato nello storico.');
    }
}
