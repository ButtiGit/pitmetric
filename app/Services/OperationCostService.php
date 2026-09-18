<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonInterface;

class OperationCostService
{
    public function record(
        User $user,
        ?float $amount,
        string $category,
        string $description,
        string $relatedType,
        int $relatedId,
        CarbonInterface $occurredAt,
        ?int $eventId = null,
        bool $append = false,
    ): ?Expense {
        if ($amount === null || $amount <= 0) {
            return null;
        }

        $source = ['related_type' => $relatedType, 'related_id' => $relatedId];
        $values = [
            'event_id' => $eventId,
            'amount_cents' => (int) round($amount * 100),
            'currency' => 'EUR',
            'category' => $category,
            'description' => $description,
            'occurred_at' => $occurredAt,
            'created_by' => $user->getKey(),
        ];

        return $append
            ? Expense::query()->create([...$source, ...$values])
            : Expense::query()->updateOrCreate($source, $values);
    }
}
