<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('maintenance_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('priority', 24)->default('normal');
            $table->string('status', 32)->default('todo');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'assigned_to', 'status']);
            $table->index(['maintenance_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_orders');
    }
};
