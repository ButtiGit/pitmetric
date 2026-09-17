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

        return to_route('circuits.index')->with('status', __('Circuit created.'));
    }

    public function update(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $circuit->update($validated);

        return to_route('circuits.index', ['circuit' => $circuit->getKey()])->with('status', __('Circuit updated.'));
    }

    public function storeLayout(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $validated = $request->validate($this->layoutRules());

        $circuit->layouts()->create([
            'name' => $validated['name'],
            'length_meters' => $validated['length_meters'],
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('circuits.index', ['circuit' => $circuit->getKey()])->with('status', __('Circuit layout created.'));
    }

    public function updateLayout(Request $request, CircuitLayout $circuitLayout): RedirectResponse
    {
        $circuit = $circuitLayout->circuit()->firstOrFail();
        Gate::authorize('update', $circuit);
        $validated = $request->validate([
            ...$this->layoutRules(),
            'is_active' => ['nullable', 'boolean'],
        ]);

        $circuitLayout->update([
            'name' => $validated['name'],
            'length_meters' => $validated['length_meters'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return to_route('circuits.index', ['circuit' => $circuit->getKey()])->with('status', __('Circuit layout updated.'));
    }

    public function destroyLayout(CircuitLayout $circuitLayout): RedirectResponse
    {
        $circuit = $circuitLayout->circuit()->firstOrFail();
        Gate::authorize('update', $circuit);

        if ($this->layoutHasHistory($circuitLayout)) {
            $circuitLayout->update(['is_active' => false]);

            return to_route('circuits.index', ['circuit' => $circuit->getKey()])
                ->with('status', __('Circuit layout archived because it is already referenced by historical records.'));
        }

        $circuitLayout->delete();

        return to_route('circuits.index', ['circuit' => $circuit->getKey()])->with('status', __('Circuit layout deleted.'));
    }

    public function destroy(Circuit $circuit): RedirectResponse
    {
        Gate::authorize('delete', $circuit);
        $circuit->load('layouts');

        if ($circuit->layouts->contains(fn (CircuitLayout $layout): bool => $this->layoutHasHistory($layout))) {
            $circuit->layouts()->update(['is_active' => false]);

            return to_route('circuits.index')->with('status', __('Circuit archived. Historical sessions and events remain intact.'));
        }

        DB::transaction(function () use ($circuit): void {
            $circuit->layouts()->delete();
            $circuit->delete();
        });

        return to_route('circuits.index')->with('status', __('Circuit deleted.'));
    }

    /** @return array<string, array<int, string>> */
    private function layoutRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function layoutHasHistory(CircuitLayout $layout): bool
    {
        return Session::query()->where('circuit_layout_id', $layout->getKey())->exists()
            || RaceEvent::query()->where('circuit_layout_id', $layout->getKey())->exists();
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
