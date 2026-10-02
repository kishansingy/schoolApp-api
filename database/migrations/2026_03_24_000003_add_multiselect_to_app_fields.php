<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->boolean('multiple')->default(false)->after('reference_qualifier');
        });
    }

    public function down(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->dropColumn('multiple');
        });
    }
};
