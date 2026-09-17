<?php

namespace App\Services;

use App\Models\EventScheduleItem;
use App\Models\Expense;
use App\Models\Session;
use App\Models\SessionUsageValue;
use App\Models\UsageBatch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SessionCorrectionService
{
    public function __construct(private readonly FinalizeSessionService $finalizeSessionService) {}

    /**
     * @param  array{
     *     circuit_layout_id: int|null,
     *     session_type: string,
     *     started_at: string,
     *     completed_laps: int|null,
     *     duration_seconds: int|null,
     *     distance_override_meters: int|null,
     *     notes: string|null,
     *     session_cost_cents: int|null,
     *     cost_description: string|null
     * }  $data
     */
    public function correct(Session $session, User $user, array $data): Session
    {
        return DB::transaction(function () use ($session, $user, $data): Session {
            $locked = Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            $this->removeGeneratedUsage($locked);

            $locked->update([
                'circuit_layout_id' => $data['circuit_layout_id'],
                'session_type' => $data['session_type'],
                'started_at' => Carbon::parse($data['started_at']),
                'completed_laps' => $data['completed_laps'],
                'duration_seconds' => $data['duration_seconds'],
                'distance_override_meters' => $data['distance_override_meters'],
                'status' => 'draft',
                'finalized_at' => null,
                'notes' => $data['notes'],
            ]);

            $finalized = $this->finalizeSessionService->finalize($locked);
            $this->syncExpense($finalized, $user, $data['session_cost_cents'], $data['cost_description']);

            EventScheduleItem::query()
                ->where('session_id', $finalized->getKey())
                ->update(['status' => 'completed']);

            return $finalized;
        });
    }

    public function archive(Session $session): void
    {
        DB::transaction(function () use ($session): void {
            $locked = Session::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            $this->removeGeneratedUsage($locked);

            Expense::query()
                ->where('related_type', 'session')
                ->where('related_id', $locked->getKey())
                ->delete();

            EventScheduleItem::query()
                ->where('session_id', $locked->getKey())
                ->update([
                    'session_id' => null,
                    'status' => 'planned',
                ]);

            $locked->update([
                'status' => 'voided',
                'finalized_at' => null,
            ]);
        });
    }

    private function removeGeneratedUsage(Session $session): void
    {
        UsageBatch::query()
            ->where('source_type', 'session')
            ->where('source_id', $session->getKey())
            ->delete();

        SessionUsageValue::query()
            ->where('session_id', $session->getKey())
            ->delete();
    }

    private function syncExpense(Session $session, User $user, ?int $amountCents, ?string $description): void
    {
        $expense = Expense::withTrashed()
            ->where('related_type', 'session')
            ->where('related_id', $session->getKey())
            ->first();

        if ($amountCents === null || $amountCents <= 0) {
            if ($expense instanceof Expense && ! $expense->trashed()) {
                $expense->delete();
            }

            return;
        }

        if (! $expense instanceof Expense) {
            $expense = new Expense;
            $expense->related_type = 'session';
            $expense->related_id = $session->getKey();
            $expense->created_by = $user->getKey();
        }

        if ($expense->trashed()) {
            $expense->restore();
        }

        $expense->event_id = $session->event_id;
        $expense->amount_cents = $amountCents;
        $expense->currency = 'EUR';
        $expense->category = 'track';
        $expense->description = $description ?: __('Track session correction');
        $expense->occurred_at = $session->started_at ?? now();
        $expense->save();
    }
}
