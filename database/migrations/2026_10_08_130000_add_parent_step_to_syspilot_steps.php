<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syspilot_steps', function (Blueprint $table): void {
            $table->foreignId('parent_step_id')
                ->nullable()
                ->constrained('syspilot_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('syspilot_steps', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_step_id');
        });
    }
};
