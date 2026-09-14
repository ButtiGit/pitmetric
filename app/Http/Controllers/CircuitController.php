<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CircuitController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'circuits']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspaceContext->personal($user);

        return view('circuits.index', [
            'circuits' => Circuit::query()->with('layouts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'layout_name' => ['required', 'string', 'max:120'],
            'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated): void {
            $circuit = Circuit::create([
                'name' => $validated['name'],
                'country' => $validated['country'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $circuit->layouts()->create([
                'name' => $validated['layout_name'],
                'length_meters' => $validated['length_meters'],
                'is_active' => true,
            ]);
        });

        return to_route('demo.circuits')->with('status', __('Circuit created.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
