<?php

use App\Services\DatabaseSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(DatabaseSchema::class)->ensureWorkspaceDomain();
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
