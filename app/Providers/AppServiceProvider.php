<?php

namespace App\Providers;

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\Session;
use App\Models\UsageBatch;
use App\Models\User;
use App\Policies\WorkspaceOwnedPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();

        foreach ([
            Circuit::class,
            Component::class,
            ComponentInstallation::class,
            ComponentType::class,
            Configuration::class,
            Expense::class,
            MaintenanceRecord::class,
            MaintenanceSchedule::class,
            Session::class,
            UsageBatch::class,
        ] as $model) {
            Gate::policy($model, WorkspaceOwnedPolicy::class);
        }

        Gate::define('manage-updates', function (User $user): bool {
            $editors = config('pitmetric.update_editor_emails', []);

            return is_array($editors)
                && in_array(strtolower($user->email), $editors, true);
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
