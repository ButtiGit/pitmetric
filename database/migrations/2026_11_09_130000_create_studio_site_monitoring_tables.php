<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_site_metrics', function (Blueprint $table): void {
            $table->id();
            $table->date('day');
            $table->string('kind', 24);
            $table->string('subject', 255);
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['day', 'kind', 'subject'], 'studio_site_metric_unique');
            $table->index(['kind', 'day']);
        });

        Schema::create('studio_site_check_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16);
            $table->unsignedSmallInteger('passed');
            $table->unsignedSmallInteger('warnings');
            $table->unsignedSmallInteger('failed');
            $table->json('results');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_site_check_runs');
        Schema::dropIfExists('studio_site_metrics');
    }
};
