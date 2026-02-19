<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop Bouncer tables to replace with Spatie Laravel Permission.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('assigned_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('abilities');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Bouncer tables are not restored - use Spatie going forward
    }
};
