<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_capture_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_capture_id')->constrained('track_captures')->cascadeOnDelete();
            $table->string('kind', 40);
            $table->string('raw_name', 120);
            $table->string('normalized_name', 160);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['track_capture_id', 'kind', 'normalized_name'], 'track_capture_reference_unique');
            $table->index(['workspace_id', 'kind', 'normalized_name', 'status'], 'track_capture_reference_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_capture_references');
    }
};
