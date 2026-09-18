<?php

namespace App\Http\Controllers;

use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OperationCostService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TechnicalSetupController extends Controller
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

        if (! $workspaceContext->isTechnicalSetupReady()) {
            return view('garage.unavailable');
        }

        $workspaceContext->personal($user);

        return view('setups.index', [
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'setups' => TechnicalSetup::query()
                ->with('vehicle')
                ->withCount('snapshots')
                ->orderBy('vehicle_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        OperationCostService $costService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate($this->rules(includeVehicle: true));
        $vehicle = Vehicle::query()
            ->where('workspace_id', $workspace->getKey())
            ->findOrFail((int) $validated['vehicle_id']);

        DB::transaction(function () use ($vehicle, $validated, $user, $costService): void {
            $setup = TechnicalSetup::create([
                'vehicle_id' => $vehicle->getKey(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'values' => $this->valuesFrom($validated),
                'status' => 'active',
                'created_by' => $user->getKey(),
            ]);

            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'setup',
                __('Technical setup preparation').': '.$vehicle->name.' · '.$setup->name,
                'technical_setup',
                (int) $setup->getKey(),
                now(),
            );
        });

        return to_route('setups.index')->with('status', __('Technical setup created.'));
    }

    public function update(
        Request $request,
        TechnicalSetup $technicalSetup,
        OperationCostService $costService,
    ): RedirectResponse {
        Gate::authorize('update', $technicalSetup);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validate($this->rules(includeVehicle: false));
        DB::transaction(function () use ($technicalSetup, $validated, $user, $costService): void {
            $technicalSetup->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'values' => $this->valuesFrom($validated),
            ]);

            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'setup',
                __('Technical setup adjustment').': '.$technicalSetup->vehicle->name.' · '.$technicalSetup->name,
                'technical_setup',
                (int) $technicalSetup->getKey(),
                now(),
                append: true,
            );
        });

        return to_route('setups.index')->with('status', __('Technical setup updated. Existing session snapshots were not changed.'));
    }

    public function destroy(TechnicalSetup $technicalSetup): RedirectResponse
    {
        Gate::authorize('delete', $technicalSetup);
        $technicalSetup->delete();

        return to_route('setups.index')->with('status', __('Technical setup archived. Existing session snapshots remain available.'));
    }

    /** @return array<string, array<int, string>> */
    private function rules(bool $includeVehicle): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tyre_pressure_fl' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'tyre_pressure_fr' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'tyre_pressure_rl' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'tyre_pressure_rr' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'ride_height_front_mm' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'ride_height_rear_mm' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'camber_front_deg' => ['nullable', 'numeric', 'min:-15', 'max:15'],
            'camber_rear_deg' => ['nullable', 'numeric', 'min:-15', 'max:15'],
            'toe_front_mm' => ['nullable', 'numeric', 'min:-20', 'max:20'],
            'toe_rear_mm' => ['nullable', 'numeric', 'min:-20', 'max:20'],
            'anti_roll_front' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'anti_roll_rear' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'brake_bias_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'differential_entry_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'differential_exit_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aero_front' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aero_rear' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'final_drive' => ['nullable', 'string', 'max:30'],
            'fuel_target_l' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];

        if ($includeVehicle) {
            $rules = ['vehicle_id' => ['required', 'integer'], ...$rules];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, int|float|string>
     */
    private function valuesFrom(array $validated): array
    {
        $values = [];

        foreach (array_keys(TechnicalSetup::FIELD_DEFINITIONS) as $key) {
            if (! array_key_exists($key, $validated) || $validated[$key] === null || $validated[$key] === '') {
                continue;
            }

            $values[$key] = $validated[$key];
        }

        return $values;
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
