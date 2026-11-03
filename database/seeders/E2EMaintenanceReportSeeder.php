<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentUsageEntry;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Services\CompleteMaintenanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class E2EMaintenanceReportSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Browser fixtures require local or testing environment.');
        }
        $user = User::where('email', E2EBrowserSeeder::EMAIL)->firstOrFail();
        Auth::login($user);
        try {
            $component = Component::where('name', 'E2E Print Engine')->firstOrFail();
            $component->installations()->update(['installed_at' => '2026-01-01 00:00:00']);
            $metric = UsageMetricType::where('key', 'distance')->firstOrFail();
            $tracker = ComponentTracker::firstOrCreate(['component_id' => $component->id, 'usage_metric_type_id' => $metric->id], ['is_active' => true]);
            $schedule = MaintenanceSchedule::create(['component_tracker_id' => $tracker->id, 'name' => 'E2E Completed history service', 'interval_value' => 10000, 'is_active' => true]);
            $event = RaceEvent::where('name', 'E2E Print Weekend')->firstOrFail();
            $order = MaintenanceWorkOrder::create(['maintenance_schedule_id' => $schedule->id, 'title' => 'E2E Linked completed order', 'status' => 'todo', 'created_by' => $user->id]);
            for ($i = 1; $i <= 18; $i++) {
                $date = Carbon::parse('2026-11-01 10:00:00', 'UTC')->addMinutes($i);
                $batch = UsageBatch::create(['source_type' => 'maintenance_report_fixture', 'source_id' => $i, 'usage_metric_type_id' => $metric->id, 'value' => 1000, 'occurred_at' => $date, 'created_by' => $user->id]);
                ComponentUsageEntry::create(['component_tracker_id' => $tracker->id, 'usage_batch_id' => $batch->id, 'value' => 1000, 'occurred_at' => $date, 'created_by' => $user->id]);
                $notes = $i === 18 ? str_repeat('Full maintenance notes preserved over page boundaries. ', 30).str_repeat('X', 180).' END-MAINTENANCE-NOTES' : 'Inspection completed and checked.';
                $record = app(CompleteMaintenanceService::class)->complete($schedule, $user, $date, sprintf('E2E Maintenance item %02d', $i), 1250, $notes, $i === 1 ? $order : null);
                $record->update(['event_id' => $event->id]);
            }
        } finally {
            Auth::logout();
        }
    }
}
