<?php

namespace Database\Seeders;

use App\Models\ComponentTracker;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\UsageMetricType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class E2EWorklistSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Browser fixtures require local or testing environment.');
        }
        $user = User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);
        try {
            $entry = EventEntry::whereHas('vehicle', fn ($q) => $q->where('name', 'E2E Print Kart'))->firstOrFail();
            for ($i = 1; $i <= 32; $i++) {
                EventTask::create(['event_id' => $entry->event_id, 'event_entry_id' => $entry->id, 'title' => sprintf('E2E Work item %02d', $i), 'description' => 'Full work description preserved across printed pages.', 'status' => 'todo', 'priority' => $i === 1 ? 'critical' : 'normal', 'assigned_to' => $user->id, 'created_by' => $user->id]);
            }
            $component = $entry->vehicle->componentInstallations()->firstOrFail()->component;
            $tracker = ComponentTracker::create(['component_id' => $component->id, 'usage_metric_type_id' => UsageMetricType::where('key', 'distance')->firstOrFail()->id, 'is_active' => true]);
            $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'E2E Worklist service', 'interval_value' => 10000, 'is_active' => true]);
            MaintenanceWorkOrder::create(['maintenance_schedule_id' => $schedule->id, 'title' => 'E2E Worklist blocked service', 'status' => 'blocked', 'priority' => 'high', 'assigned_to' => $user->id, 'created_by' => $user->id]);
        } finally {
            Auth::logout();
        }
    }
}
