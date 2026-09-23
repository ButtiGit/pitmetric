<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('device_name', 120)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('mobile_sync_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_uuid');
            $table->string('operation_type', 40);
            $table->string('resource_type', 80)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'client_uuid']);
        });

        Schema::create('gallery_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('client_uuid');
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('mime_type', 100);
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('taken_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'client_uuid']);
            $table->index(['workspace_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('mobile_sync_receipts');
        Schema::dropIfExists('mobile_access_tokens');
    }
};
