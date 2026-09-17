<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\CircuitLayout;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Expense;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CoreCrudController extends Controller
{
    public function updateComponent(Request $request, Component $component, WorkspaceContext $workspaceContext): RedirectResponse
    {
        Gate::authorize('update', $component);
        $workspace = $workspaceContext->personal($request->user());
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type_name' => ['required', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $workspace, $component): void {
            $type = ComponentType::query()->firstOrCreate([
                'workspace_id' => $workspace->getKey(),
                'name' => trim($validated['type_name']),
            ]);
            $costCents = isset($validated['purchase_cost']) && (float) $validated['purchase_cost'] > 0
                ? (int) round(((float) $validated['purchase_cost']) * 100)
                : null;

            $component->update([
                'component_type_id' => $type->getKey(),
                'name' => $validated['name'],
                'manufacturer' => $validated['manufacturer'] ?? null,
                'model' => $validated['model'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'purchase_cost_cents' => $costCents,
                'currency' => $costCents !== null ? 'EUR' : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $expense = Expense::withTrashed()
                ->where('related_type', 'component')
                ->where('related_id', $component->getKey())
                ->first();

            if ($costCents === null) {
                if ($expense instanceof Expense && ! $expense->trashed()) {
                    $expense->delete();
                }

                return;
            }

            if (! $expense instanceof Expense) {
                $expense = new Expense;
                $expense->related_type = 'component';
                $expense->related_id = $component->getKey();
                $expense->created_by = auth()->id();
            }

            if ($expense->trashed()) {
                $expense->restore();
            }

            $expense->amount_cents = $costCents;
            $expense->currency = 'EUR';
            $expense->category = 'parts';
            $expense->description = __('Purchase').': '.$component->name;
            $expense->occurred_at = isset($validated['purchase_date'])
                ? Carbon::parse($validated['purchase_date'])->startOfDay()
                : ($expense->occurred_at ?? now());
            $expense->save();
        });

        return back()->with('status', __('Component updated.'));
    }

    public function updateConfiguration(Request $request, Configuration $configuration): RedirectResponse
    {
        Gate::authorize('update', $configuration);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $configuration->update($validated);

        return back()->with('status', __('Configuration updated.'));
    }

    public function updateCircuit(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $circuit->update($validated);

        return back()->with('status', __('Circuit updated.'));
    }

    public function archiveCircuit(Circuit $circuit): RedirectResponse
    {
        Gate::authorize('delete', $circuit);
        DB::transaction(function () use ($circuit): void {
            $circuit->update(['is_active' => false]);
            $circuit->layouts()->update(['is_active' => false]);
        });

        return back()->with('status', __('Circuit archived.'));
    }

    public function storeLayout(Request $request, Circuit $circuit): RedirectResponse
    {
        Gate::authorize('update', $circuit);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $circuit->layouts()->create([
            ...$validated,
            'is_active' => true,
        ]);
        $circuit->update(['is_active' => true]);

        return back()->with('status', __('Circuit layout created.'));
    }

    public function updateLayout(Request $request, CircuitLayout $circuitLayout): RedirectResponse
    {
        $circuitLayout->loadMissing('circuit');
        Gate::authorize('update', $circuitLayout->circuit);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'length_meters' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $circuitLayout->update($validated);

        return back()->with('status', __('Circuit layout updated.'));
    }

    public function archiveLayout(CircuitLayout $circuitLayout): RedirectResponse
    {
        $circuitLayout->loadMissing('circuit');
        Gate::authorize('update', $circuitLayout->circuit);
        $circuitLayout->update(['is_active' => false]);

        return back()->with('status', __('Circuit layout archived.'));
    }

    public function updateExpense(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        if ($expense->related_type !== null) {
            throw ValidationException::withMessages([
                'expense' => __('Linked expenses must be changed from their source operation.'),
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'occurred_at' => ['required', 'date'],
        ]);
        $expense->update([
            'amount_cents' => (int) round(((float) $validated['amount']) * 100),
            'category' => $validated['category'],
            'description' => $validated['description'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
        ]);

        return back()->with('status', __('Expense updated.'));
    }
}
