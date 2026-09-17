<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\OperationCostService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'configurations']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspaceContext->personal($user);

        return view('configurations.index', [
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'components' => Component::query()->with('type')->where('status', 'active')->orderBy('name')->get(),
            'configurations' => Configuration::query()
                ->with([
                    'vehicle',
                    'versions' => fn ($query) => $query->latest('version_number')->with('components.type'),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        CreateConfigurationVersionService $versionService,
        OperationCostService $costService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $vehicleId = (int) $validated['vehicle_id'];
        $componentIds = array_values(array_map('intval', $validated['component_ids'] ?? []));

        $vehicle = Vehicle::query()
            ->where('workspace_id', $workspace->getKey())
            ->findOrFail($vehicleId);

        DB::transaction(function () use ($validated, $vehicle, $user, $versionService, $componentIds, $costService): void {
            $configuration = Configuration::create([
                'vehicle_id' => $vehicle->getKey(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => 'active',
            ]);

            $version = $versionService->create(
                $configuration,
                $user,
                $componentIds,
                __('Initial configuration'),
            );

            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'setup',
                __('Configuration setup').': '.$configuration->name.' · v'.$version->version_number,
                'configuration_version',
                (int) $version->getKey(),
                now(),
            );
        });

        return to_route('configurations.index')->with('status', __('Configuration created.'));
    }

    public function update(Request $request, Configuration $configuration): RedirectResponse
    {
        Gate::authorize('update', $configuration);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $configuration->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return to_route('configurations.index', ['configuration' => $configuration->getKey()])
            ->with('status', __('Configuration updated.'));
    }

    public function storeVersion(
        Request $request,
        Configuration $configuration,
        CreateConfigurationVersionService $versionService,
        OperationCostService $costService,
    ): RedirectResponse {
        Gate::authorize('update', $configuration);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validate([
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $componentIds = array_values(array_map('intval', $validated['component_ids'] ?? []));

        $version = $versionService->create(
            $configuration,
            $user,
            $componentIds,
            $validated['notes'] ?? null,
        );

        $costService->record(
            $user,
            isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
            'setup',
            __('Configuration update').': '.$configuration->name.' · v'.$version->version_number,
            'configuration_version',
            (int) $version->getKey(),
            now(),
        );

        return to_route('configurations.index')->with('status', __('New configuration version created.'));
    }

    public function destroy(Configuration $configuration): RedirectResponse
    {
        Gate::authorize('delete', $configuration);
        $configuration->delete();

        return to_route('configurations.index')->with('status', __('Configuration archived.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
