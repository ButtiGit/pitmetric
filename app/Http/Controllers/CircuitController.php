<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\CircuitLayout;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'circuits' => Circuit::query()->withTrashed()->with(['layouts' => fn ($query) => $query->withTrashed()])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('circuits', 'name')->where('workspace_id', $workspace->getKey())],
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

        return to_route('circuits.index')->with('status', __('Circuit created.'));
    }

    public function update(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $circuit->update($request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('circuits', 'name')->where('workspace_id', $circuit->workspace_id)->ignore($circuit)],
            'country' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return to_route('circuits.index')->with('status', __('Circuit updated.'));
    }

    public function storeLayout(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $circuit->layouts()->create([
            ...$request->validate([
                'name' => ['required', 'string', 'max:120', Rule::unique('circuit_layouts', 'name')->where('circuit_id', $circuit->getKey())],
                'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            ]),
            'is_active' => true,
        ]);

        return to_route('circuits.index')->with('status', __('Layout created.'));
    }

    public function updateLayout(Request $request, Circuit $circuit, CircuitLayout $circuitLayout): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        abort_unless((int) $circuitLayout->circuit_id === (int) $circuit->getKey(), 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('circuit_layouts', 'name')->where('circuit_id', $circuit->getKey())->ignore($circuitLayout)],
            'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ((int) $validated['length_meters'] !== $circuitLayout->length_meters
            && (Session::query()->where('circuit_layout_id', $circuitLayout->getKey())->exists()
                || RaceEvent::query()->withTrashed()->where('circuit_layout_id', $circuitLayout->getKey())->exists())) {
            throw ValidationException::withMessages([
                'length_meters' => __('This layout is used in history. Add a new layout to change its length.'),
            ]);
        }

        $circuitLayout->update($validated);

        return to_route('circuits.index')->with('status', __('Layout updated.'));
    }

    public function destroy(Circuit $circuit): RedirectResponse
    {
        Gate::authorize('delete', $circuit);
        $circuit->delete();

        return to_route('circuits.index')->with('status', __('Circuit removed. Existing sessions and weekends are preserved.'));
    }

    public function restore(Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $circuit->restore();

        return to_route('circuits.index')->with('status', __('Circuit restored.'));
    }

    public function destroyLayout(Circuit $circuit, CircuitLayout $circuitLayout): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        abort_unless((int) $circuitLayout->circuit_id === (int) $circuit->getKey(), 404);
        $circuitLayout->delete();

        return to_route('circuits.index')->with('status', __('Layout removed. Historical distances are preserved.'));
    }

    public function restoreLayout(Circuit $circuit, CircuitLayout $circuitLayout): RedirectResponse
    {
        abort_if($circuit->trashed(), 404);
        Gate::authorize('update', $circuit);
        abort_unless((int) $circuitLayout->circuit_id === (int) $circuit->getKey(), 404);
        $circuitLayout->restore();

        return to_route('circuits.index')->with('status', __('Layout restored.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
