<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('studio_mail_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_entries');
    }
};
