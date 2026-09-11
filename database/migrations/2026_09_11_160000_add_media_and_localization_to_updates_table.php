<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('updates', function (Blueprint $table): void {
            $table->string('title_it')->nullable()->after('title');
            $table->text('excerpt_it')->nullable()->after('excerpt');
            $table->longText('content_it')->nullable()->after('content');
            $table->string('media_type', 20)->nullable()->after('content_it');
            $table->string('media_path')->nullable()->after('media_type');
            $table->text('media_url')->nullable()->after('media_path');
            $table->string('media_alt')->nullable()->after('media_url');
            $table->string('media_alt_it')->nullable()->after('media_alt');
        });
    }

    public function down(): void
    {
        Schema::table('updates', function (Blueprint $table): void {
            $table->dropColumn([
                'title_it',
                'excerpt_it',
                'content_it',
                'media_type',
                'media_path',
                'media_url',
                'media_alt',
                'media_alt_it',
            ]);
        });
    }
};
