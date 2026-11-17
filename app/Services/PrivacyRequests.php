<?php

namespace App\Services;

use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PrivacyRequests
{
    public function submit(User $user, string $type, string $details): PrivacyRequest
    {
        return PrivacyRequest::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'details' => $details,
            'due_at' => now()->addMonthNoOverflow(),
            'history' => [[
                'at' => now()->toIso8601String(),
                'actor_id' => $user->id,
                'status' => 'received',
                'message' => 'Richiesta ricevuta.',
            ]],
        ]);
    }

    public function respond(PrivacyRequest $privacyRequest, User $editor, string $status, string $message): void
    {
        DB::transaction(function () use ($privacyRequest, $editor, $status, $message): void {
            $locked = PrivacyRequest::query()->lockForUpdate()->findOrFail($privacyRequest->id);
            if ($locked->closed_at !== null) {
                throw ValidationException::withMessages(['status' => 'La richiesta è già chiusa.']);
            }

            $history = $locked->history;
            $history[] = [
                'at' => now()->toIso8601String(),
                'actor_id' => $editor->id,
                'status' => $status,
                'message' => $message,
            ];
            $locked->update([
                'status' => $status,
                'history' => $history,
                'closed_at' => in_array($status, ['completed', 'rejected'], true) ? now() : null,
            ]);
        });
    }
}
