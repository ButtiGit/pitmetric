<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Expense;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ComponentUsageCalculator;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComponentController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext, ComponentUsageCalculator $usageCalculator): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'components']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspaceContext->personal($user);

        $components = Component::query()
            ->with(['type', 'trackers.metric', 'trackers.resetEvents', 'activeInstallation.vehicle'])
            ->orderBy('name')
            ->get();

        $usage = [];

        foreach ($components as $component) {
            foreach ($component->trackers as $tracker) {
                $usage[$tracker->id] = [
                    'lifetime' => $usageCalculator->lifetime($tracker),
                    'since_service' => $usageCalculator->sinceLastService($tracker),
                    'status' => $usageCalculator->status($tracker),
                ];
            }
        }

        return view('components.index', [
            'components' => $components,
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'metrics' => UsageMetricType::query()->orderBy('name')->get(),
            'usage' => $usage,
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
            'name' => ['required', 'string', 'max:120'],
            'type_name' => ['required', 'string', 'max:100'],
            'metric_key' => ['required', 'string', 'exists:usage_metric_types,key'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $workspace, $user): void {
            $type = ComponentType::query()->firstOrCreate([
                'workspace_id' => $workspace->getKey(),
                'name' => trim($validated['type_name']),
            ]);

            $purchaseCostCents = isset($validated['purchase_cost']) && (float) $validated['purchase_cost'] > 0
                ? (int) round(((float) $validated['purchase_cost']) * 100)
                : null;

            $component = Component::create([
                'component_type_id' => $type->getKey(),
                'name' => $validated['name'],
                'manufacturer' => $validated['manufacturer'] ?? null,
                'model' => $validated['model'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'purchase_cost_cents' => $purchaseCostCents,
                'currency' => $purchaseCostCents !== null ? 'EUR' : null,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            $metric = UsageMetricType::query()->where('key', $validated['metric_key'])->firstOrFail();

            ComponentTracker::create([
                'component_id' => $component->getKey(),
                'usage_metric_type_id' => $metric->getKey(),
                'is_active' => true,
            ]);

            if ($purchaseCostCents !== null) {
                Expense::create([
                    'amount_cents' => $purchaseCostCents,
                    'currency' => 'EUR',
                    'category' => 'parts',
                    'description' => __('Purchase').': '.$component->name,
                    'occurred_at' => isset($validated['purchase_date'])
                        ? Carbon::parse($validated['purchase_date'])->startOfDay()
                        : now(),
                    'related_type' => 'component',
                    'related_id' => $component->getKey(),
                    'created_by' => $user->getKey(),
                ]);
            }
        });

        return to_route('components.index')->with('status', __('Component created.'));
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        Gate::authorize('update', $component);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $component->update($validated);

        return to_route('components.index')->with('status', __('Component updated.'));
    }

    public function destroy(Component $component): RedirectResponse
    {
        Gate::authorize('delete', $component);

        if ($component->installations()->whereNull('removed_at')->exists()) {
            throw ValidationException::withMessages([
                'component' => __('Remove this component from its vehicle before archiving it.'),
            ]);
        }

        $component->delete();

        return to_route('components.index')->with('status', __('Component archived.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
