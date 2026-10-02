<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->boolean('query_value')->default(false)->after('calculated');
            $table->text('query_value_sql')->nullable()->after('query_value');
            $table->string('query_display_field')->nullable()->after('query_value_sql');
            $table->string('query_value_field')->nullable()->after('query_display_field');
        });
    }

    public function down(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->dropColumn(['query_value', 'query_value_sql', 'query_display_field', 'query_value_field']);
        });
    }
};
