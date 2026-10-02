<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration runs on the CENTRAL (default) database.
 * It stores company registry only — no app data here.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();        // subdomain: greenwood.app.com
            $table->string('domain')->nullable();    // custom domain if any
            $table->string('db_name')->unique();     // tenant DB name: tenant_greenwood
            $table->string('db_host')->default('127.0.0.1');
            $table->string('db_port')->default('3306');
            $table->string('db_username');
            $table->string('db_password');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('plan')->default('basic');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
