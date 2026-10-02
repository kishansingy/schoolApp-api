<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_templates', function (Blueprint $table) {
            // JSON array of related table configs:
            // [{ "name": "marks", "label": "marks_rows", "link_field": "student_id", "link_value": "_id" }]
            $table->json('related_tables')->nullable()->after('app_table_id');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_templates', function (Blueprint $table) {
            $table->dropColumn('related_tables');
        });
    }
};
