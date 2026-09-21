<?php

namespace App\Http\Controllers;

use App\Models\ComponentInstallation;
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

        $vehicles = Vehicle::query()
            ->where('status', 'active')
            ->with([
                'componentInstallations' => fn ($query) => $query
                    ->whereNull('removed_at')
                    ->with('component.type')
                    ->orderBy('position_or_role')
                    ->orderBy('installed_at'),
            ])
            ->withCount([
                'componentInstallations as active_components_count' => fn ($query) => $query->whereNull('removed_at'),
            ])
            ->orderBy('name')
            ->get();

        return view('configurations.index', [
            'vehicles' => $vehicles,
            'configurations' => Configuration::query()
                ->with([
                    'vehicle.componentInstallations' => fn ($query) => $query
                        ->whereNull('removed_at')
                        ->with('component.type')
                        ->orderBy('position_or_role')
                        ->orderBy('installed_at'),
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
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $vehicle = Vehicle::query()
            ->where('workspace_id', $workspace->getKey())
            ->where('status', 'active')
            ->findOrFail((int) $validated['vehicle_id']);
        $componentIds = $this->activeComponentIds($vehicle);

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

        return to_route('configurations.index')->with('status', __('Configuration created from the vehicle current physical state.'));
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $vehicle = $configuration->vehicle()
            ->whereNull('vehicles.deleted_at')
            ->where('status', 'active')
            ->firstOrFail();
        $componentIds = $this->activeComponentIds($vehicle);

        DB::transaction(function () use ($configuration, $user, $componentIds, $validated, $versionService, $costService): void {
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
        });

        return to_route('configurations.index')->with('status', __('New configuration version captured from the vehicle current physical state.'));
    }

    public function update(Request $request, Configuration $configuration): RedirectResponse
    {
        Gate::authorize('update', $configuration);
        $configuration->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        return to_route('configurations.index')->with('status', __('Configuration updated.'));
    }

    public function destroy(Configuration $configuration): RedirectResponse
    {
        Gate::authorize('delete', $configuration);
        $configuration->delete();

        return to_route('configurations.index')->with('status', __('Configuration archived.'));
    }

    /** @return list<int> */
    private function activeComponentIds(Vehicle $vehicle): array
    {
        return ComponentInstallation::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->whereNull('removed_at')
            ->pluck('component_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
