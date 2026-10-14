<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desk_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12)->default('client');
            $table->string('name', 180);
            $table->string('company', 180)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('vat_number', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'type', 'name']);
        });

        Schema::create('desk_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('desk_contacts')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('desk_contacts')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('category', 60)->default('support');
            $table->string('status', 20)->default('open');
            $table->string('priority', 12)->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status', 'priority']);
            $table->index(['workspace_id', 'due_at']);
        });

        Schema::create('desk_worklogs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('desk_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('summary', 220);
            $table->text('details')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['workspace_id', 'performed_at']);
        });

        Schema::create('desk_appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('desk_tickets')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('desk_contacts')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('location', 200)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
            $table->index(['workspace_id', 'starts_at']);
        });

        Schema::create('desk_calendar_feeds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('desk_mail_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('desk_tickets')->nullOnDelete();
            $table->foreignId('sent_by')->constrained('users')->restrictOnDelete();
            $table->string('recipient', 254);
            $table->string('subject', 180);
            $table->text('message');
            $table->string('in_reply_to', 255)->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desk_mail_logs');
        Schema::dropIfExists('desk_calendar_feeds');
        Schema::dropIfExists('desk_appointments');
        Schema::dropIfExists('desk_worklogs');
        Schema::dropIfExists('desk_tickets');
        Schema::dropIfExists('desk_contacts');
    }
};
