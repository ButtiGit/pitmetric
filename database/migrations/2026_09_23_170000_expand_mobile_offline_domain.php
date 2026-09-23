<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_assets', function (Blueprint $table): void {
            $table->string('media_type', 16)->default('image')->after('description');
            $table->string('mime_type', 120)->nullable()->after('media_type');
            $table->string('original_name', 255)->nullable()->after('mime_type');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('original_name');
        });

        Schema::create('mobile_sync_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('client_id');
            $table->string('operation', 80);
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_id']);
            $table->index(['workspace_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_operations');

        Schema::table('gallery_assets', function (Blueprint $table): void {
            $table->dropColumn(['media_type', 'mime_type', 'original_name', 'size_bytes']);
        });
    }
};
