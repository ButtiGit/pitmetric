<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('newsletter_subscribed_at')->nullable()->after('email_verified_at');
            $table->string('newsletter_locale', 5)->default('en')->after('newsletter_subscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['newsletter_subscribed_at', 'newsletter_locale']);
        });
    }
};
