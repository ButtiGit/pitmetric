<?php

namespace App\Providers;

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\UsageBatch;
use App\Models\User;
use App\Policies\WorkspaceOwnedPolicy;
use App\Services\WorkspaceContext;
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
        $this->app->scoped(WorkspaceContext::class, fn (): WorkspaceContext => new WorkspaceContext);
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
            Driver::class,
            EventEntry::class,
            EventNote::class,
            EventTask::class,
            Expense::class,
            MaintenanceRecord::class,
            MaintenanceSchedule::class,
            MaintenanceWorkOrder::class,
            RaceEvent::class,
            Session::class,
            TechnicalSetup::class,
            UsageBatch::class,
        ] as $model) {
            Gate::policy($model, WorkspaceOwnedPolicy::class);
        }

        Gate::define('manage-updates', fn (User $user): bool => $this->isUpdateEditor($user));

        Gate::define('team-view', function (User $user): bool {
            return $this->isUpdateEditor($user)
                || app(WorkspaceContext::class)->role($user) !== null;
        });

        Gate::define('team-write', function (User $user): bool {
            return $this->isUpdateEditor($user)
                || app(WorkspaceContext::class)->canWrite($user);
        });

        Gate::define('team-manage', function (User $user): bool {
            return $this->isUpdateEditor($user)
                || app(WorkspaceContext::class)->canManage($user);
        });

        Gate::define('team-own', function (User $user): bool {
            return $this->isUpdateEditor($user)
                || app(WorkspaceContext::class)->isOwner($user);
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

    private function isUpdateEditor(User $user): bool
    {
        $editors = config('pitmetric.update_editor_emails', []);

        return is_array($editors)
            && in_array(strtolower($user->email), $editors, true);
    }
}
