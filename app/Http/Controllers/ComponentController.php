<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Services\ComponentUsageCalculator;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            ->with(['type', 'trackers.metric', 'trackers.resetEvents'])
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $workspace): void {
            $type = ComponentType::query()->firstOrCreate([
                'workspace_id' => $workspace->getKey(),
                'name' => trim($validated['type_name']),
            ]);

            $component = Component::create([
                'component_type_id' => $type->getKey(),
                'name' => $validated['name'],
                'manufacturer' => $validated['manufacturer'] ?? null,
                'model' => $validated['model'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            $metric = UsageMetricType::query()->where('key', $validated['metric_key'])->firstOrFail();

            ComponentTracker::create([
                'component_id' => $component->getKey(),
                'usage_metric_type_id' => $metric->getKey(),
                'is_active' => true,
            ]);
        });

        return to_route('demo.components')->with('status', __('Component created.'));
    }

    public function destroy(Component $component): RedirectResponse
    {
        Gate::authorize('delete', $component);
        $component->delete();

        return to_route('demo.components')->with('status', __('Component archived.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
