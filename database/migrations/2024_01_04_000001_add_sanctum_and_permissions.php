<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Sanctum personal access tokens
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Per-role permissions on app_tables (overrides table-level can_read etc.)
        Schema::create('table_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->string('role');          // role name from spatie
            $table->boolean('can_read')->default(true);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            $table->unique(['app_table_id', 'role']);
        });

        // Per-role field visibility/editability
        Schema::create('field_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_field_id')->constrained('app_fields')->cascadeOnDelete();
            $table->string('role');
            $table->boolean('visible')->default(true);
            $table->boolean('editable')->default(true);
            $table->boolean('mandatory')->default(false); // override mandatory per role
            $table->timestamps();
            $table->unique(['app_field_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
        Schema::dropIfExists('table_permissions');
        Schema::dropIfExists('personal_access_tokens');
    }
};
