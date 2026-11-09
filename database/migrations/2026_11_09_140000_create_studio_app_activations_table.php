<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_app_activations', function (Blueprint $table): void {
            $table->id();
            $table->string('release_key', 48);
            $table->string('platform', 16);
            $table->char('installation_hash', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['release_key', 'installation_hash'], 'studio_app_activation_unique');
            $table->index(['release_key', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_app_activations');
    }
};
