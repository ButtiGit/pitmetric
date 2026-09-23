<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

#[Fillable(['circuit_id', 'name', 'length_meters', 'is_active', 'notes'])]
class CircuitLayout extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (CircuitLayout $layout): void {
            if (! Schema::hasTable('track_captures') || ! Schema::hasTable('follow_up_tasks')) {
                return;
            }

            $circuit = $layout->circuit()->withTrashed()->first();

            if (! $circuit instanceof Circuit) {
                return;
            }

            $normalizedName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $circuit->name) ?? $circuit->name));
            $captures = TrackCapture::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $circuit->workspace_id)
                ->whereNull('circuit_id')
                ->whereRaw('LOWER(circuit_name) = ?', [mb_strtolower($circuit->name)])
                ->get();
            $hasReferenceTable = Schema::hasTable('track_capture_references');

            foreach ($captures as $capture) {
                $hasPendingContext = $hasReferenceTable && TrackCaptureReference::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', $circuit->workspace_id)
                    ->where('track_capture_id', $capture->getKey())
                    ->where('status', 'pending')
                    ->exists();

                $capture->update([
                    'circuit_id' => $circuit->getKey(),
                    'circuit_layout_id' => $layout->getKey(),
                    'status' => $hasPendingContext ? 'needs_attention' : 'ready',
                    'resolved_at' => $hasPendingContext ? null : now(),
                ]);
            }

            FollowUpTask::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $circuit->workspace_id)
                ->where('kind', 'missing_circuit')
                ->where('subject_key', $normalizedName)
                ->where('status', 'open')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }

    /** @return BelongsTo<Circuit, $this> */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'length_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
