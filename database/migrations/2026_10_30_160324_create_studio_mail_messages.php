<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_mail_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_id')->unique();
            $table->string('direction', 12);
            $table->char('thread_key', 64)->index();
            $table->text('message_id')->nullable();
            $table->char('message_id_hash', 64)->nullable()->index();
            $table->text('sender');
            $table->json('recipients');
            $table->json('cc');
            $table->text('subject');
            $table->longText('body')->nullable();
            $table->json('headers')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('sent_at')->index();
            $table->timestamps();
        });

        Schema::create('studio_mail_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('studio_mail_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['studio_mail_message_id', 'user_id'], 'studio_mail_reader_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_mail_reads');
        Schema::dropIfExists('studio_mail_messages');
    }
};
