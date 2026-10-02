<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            // The actual DB table name where records are stored (e.g. sch_students)
            $table->string('schema_table')->nullable()->after('name');
        });

        // Add schema_row_id to app_records so we can link to the real row
        Schema::table('app_records', function (Blueprint $table) {
            $table->unsignedBigInteger('schema_row_id')->nullable()->after('app_table_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropColumn('schema_table');
        });
        Schema::table('app_records', function (Blueprint $table) {
            $table->dropColumn('schema_row_id');
        });
    }
};
