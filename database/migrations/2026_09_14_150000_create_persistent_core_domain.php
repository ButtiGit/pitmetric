<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'name']);
        });

        Schema::create('components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('purchase_cost_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('usage_metric_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 80);
            $table->string('storage_unit', 40);
            $table->string('display_unit', 40);
            $table->string('kind', 30);
            $table->unsignedTinyInteger('precision')->default(0);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        DB::table('usage_metric_types')->insert([
            ['key' => 'distance', 'name' => 'Distance', 'storage_unit' => 'meter', 'display_unit' => 'km', 'kind' => 'quantity', 'precision' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'runtime', 'name' => 'Runtime', 'storage_unit' => 'second', 'display_unit' => 'h', 'kind' => 'quantity', 'precision' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cycles', 'name' => 'Cycles', 'storage_unit' => 'count', 'display_unit' => 'cycles', 'kind' => 'counter', 'precision' => 0, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sessions', 'name' => 'Sessions', 'storage_unit' => 'count', 'display_unit' => 'sessions', 'kind' => 'counter', 'precision' => 0, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('component_trackers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usage_metric_type_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('warning_threshold')->nullable();
            $table->unsignedBigInteger('service_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['component_id', 'usage_metric_type_id']);
        });

        Schema::create('component_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('component_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('position_or_role', 100)->nullable();
            $table->timestamp('installed_at');
            $table->timestamp('removed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'vehicle_id', 'removed_at']);
        });

        Schema::create('configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'vehicle_id', 'status']);
        });

        Schema::create('configuration_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['configuration_id', 'version_number']);
        });

        Schema::create('configuration_version_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuration_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->restrictOnDelete();
            $table->string('position_or_role', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['configuration_version_id', 'component_id']);
        });

        Schema::create('circuits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('country', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'name']);
        });

        Schema::create('circuit_layouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('circuit_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('length_meters');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['circuit_id', 'name']);
        });

        Schema::create('track_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('configuration_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_type', 40)->default('practice');
            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('completed_laps')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('distance_override_meters')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'started_at']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('session_usage_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('track_sessions')->cascadeOnDelete();
            $table->foreignId('usage_metric_type_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('value');
            $table->string('source', 30)->default('calculated');
            $table->timestamps();
            $table->unique(['session_id', 'usage_metric_type_id', 'source'], 'session_metric_source_unique');
        });

        Schema::create('usage_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('usage_metric_type_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('value');
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'usage_metric_type_id'], 'usage_batch_source_metric_unique');
            $table->index(['workspace_id', 'occurred_at']);
        });

        Schema::create('component_usage_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_tracker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usage_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('value');
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['component_tracker_id', 'usage_batch_id'], 'tracker_batch_unique');
        });

        Schema::create('maintenance_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_tracker_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('interval_value');
            $table->unsignedBigInteger('warning_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'is_active']);
        });

        Schema::create('maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->restrictOnDelete();
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('performed_at');
            $table->string('description', 180);
            $table->unsignedBigInteger('cost_cents')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'performed_at']);
        });

        Schema::create('tracker_reset_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('component_tracker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_record_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reset_at');
            $table->string('reason', 180)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['component_tracker_id', 'reset_at']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('EUR');
            $table->string('category', 80);
            $table->string('description', 180);
            $table->timestamp('occurred_at');
            $table->string('related_type', 80)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('tracker_reset_events');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('component_usage_entries');
        Schema::dropIfExists('usage_batches');
        Schema::dropIfExists('session_usage_values');
        Schema::dropIfExists('track_sessions');
        Schema::dropIfExists('circuit_layouts');
        Schema::dropIfExists('circuits');
        Schema::dropIfExists('configuration_version_components');
        Schema::dropIfExists('configuration_versions');
        Schema::dropIfExists('configurations');
        Schema::dropIfExists('component_installations');
        Schema::dropIfExists('component_trackers');
        Schema::dropIfExists('usage_metric_types');
        Schema::dropIfExists('components');
        Schema::dropIfExists('component_types');
    }
};
