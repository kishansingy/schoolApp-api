<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_templates', function (Blueprint $table) {
            $table->text('sql_query')->nullable()->after('html_template');
            $table->json('params')->nullable()->after('sql_query');
            // query_mode: 'record' (existing) | 'sql' (raw SQL)
            $table->string('query_mode')->default('record')->after('params');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_templates', function (Blueprint $table) {
            $table->dropColumn(['sql_query', 'params', 'query_mode']);
        });
    }
};
